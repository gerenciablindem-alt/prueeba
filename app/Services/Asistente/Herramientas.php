<?php

namespace App\Services\Asistente;

use App\Models\Afiliado;
use App\Models\Configuracion;
use App\Models\Cuota;
use App\Models\Empresa;
use App\Models\Incapacidad;
use App\Models\Preafiliacion;
use App\Models\User;
use App\Models\Venta;
use App\Services\Alertas;
use App\Services\CalculadoraIncapacidad;

/**
 * Herramientas de SOLO LECTURA que el asistente puede usar.
 * Cada consulta respeta lo que el usuario puede ver (una empresa cliente solo ve lo suyo).
 */
class Herramientas
{
    public function __construct(private User $u) {}

    /** Definiciones en el formato de la API de Claude. */
    public function definiciones(): array
    {
        $tools = [
            $this->tool('resumen_general', 'Cifras actuales de la operación: afiliaciones por estado, incapacidades por estado y valores por recobrar, alertas pendientes y, para el equipo interno, ventas activas y cuotas del mes. Úsala para preguntas generales como "¿cómo vamos?".', []),
            $this->tool('buscar_afiliados', 'Busca empleados afiliados por nombre, apellido o número de documento. Devuelve sus datos, empresa y el estado de sus afiliaciones e incapacidades.', [
                'texto' => ['type' => 'string', 'description' => 'Nombre, apellido o documento (o parte de él).'],
            ], ['texto']),
            $this->tool('listar_afiliaciones', 'Lista radicados de afiliación (pre-afiliaciones) con filtros. Útil para "¿qué está pendiente?", "¿qué requiere corrección?".', [
                'estado' => ['type' => 'string', 'enum' => array_keys(Preafiliacion::ESTADOS), 'description' => 'Estado del radicado.'],
                'tipo' => ['type' => 'string', 'enum' => ['EPS', 'ARL', 'CCF', 'AFP']],
                'empresa' => ['type' => 'string', 'description' => 'Nombre o NIT de la empresa (o parte).'],
                'limite' => ['type' => 'integer', 'description' => 'Máximo de resultados, 1 a 50. Por defecto 20.'],
            ]),
            $this->tool('listar_incapacidades', 'Lista incapacidades con su valor a recobrar, entidad y estado del cobro.', [
                'estado' => ['type' => 'string', 'enum' => array_keys(Incapacidad::ESTADOS)],
                'limite' => ['type' => 'integer', 'description' => 'Máximo de resultados, 1 a 50. Por defecto 20.'],
            ]),
            $this->tool('calcular_incapacidad', 'Calcula cuánto paga el empleador y cuánto se recobra a la EPS, ARL o fondo por una incapacidad. Reglas simplificadas.', [
                'tipo' => ['type' => 'string', 'enum' => array_keys(Incapacidad::TIPOS)],
                'dias' => ['type' => 'integer', 'description' => 'Días de esta incapacidad.'],
                'ibc' => ['type' => 'integer', 'description' => 'Ingreso base de cotización mensual en pesos.'],
                'dias_previos' => ['type' => 'integer', 'description' => 'Días acumulados de incapacidades anteriores si es prórroga. Por defecto 0.'],
            ], ['tipo', 'dias', 'ibc']),
            $this->tool('alertas', 'Lista lo que requiere atención ahora (radicados por corregir, incapacidades sin radicar, cuotas vencidas, empresas con datos faltantes).', []),
        ];
        if ($this->u->esInterno()) {
            $tools[] = $this->tool('cartera_ventas', 'Estado de cobro de las ventas de planes a independientes para un mes: total, recaudado, cuotas pendientes y vencidas con el cliente.', [
                'periodo' => ['type' => 'string', 'description' => 'Mes en formato AAAA-MM. Por defecto el mes actual.'],
            ]);
            $tools[] = $this->tool('buscar_ventas', 'Busca ventas de planes por nombre o documento del cliente y muestra sus cuotas y recibos recientes.', [
                'texto' => ['type' => 'string'],
            ], ['texto']);
        }
        return $tools;
    }

    public function ejecutar(string $nombre, array $in): array
    {
        $metodo = 'h_'.$nombre;
        $permitidas = array_column($this->definiciones(), 'name');
        if (! in_array($nombre, $permitidas, true) || ! method_exists($this, $metodo)) {
            return ['error' => 'Herramienta no disponible para este usuario.'];
        }
        return $this->$metodo($in);
    }

    private function tool(string $name, string $description, array $props, array $required = []): array
    {
        return ['name' => $name, 'description' => $description, 'inputSchema' => ['type' => 'object', 'properties' => (object) $props, 'required' => $required]];
    }

    private function limite(array $in): int
    {
        return max(1, min(50, (int) ($in['limite'] ?? 20)));
    }

    private function h_resumen_general(array $in): array
    {
        $u = $this->u;
        $r = [
            'fecha' => now()->format('Y-m-d'),
            'empresas_visibles' => Empresa::visiblesPara($u)->count(),
            'afiliados' => Afiliado::visiblesPara($u)->count(),
            'afiliaciones_por_estado' => Preafiliacion::visiblesPara($u)->selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado'),
            'incapacidades_por_estado' => Incapacidad::visiblesPara($u)->selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado'),
            'valor_por_recobrar' => (int) Incapacidad::visiblesPara($u)->whereIn('estado', ['por_radicar', 'radicada', 'aprobada'])->sum('valor_recobro'),
            'valor_rechazado' => (int) Incapacidad::visiblesPara($u)->where('estado', 'rechazada')->sum('valor_recobro'),
            'alertas_pendientes' => app(Alertas::class)->para($u)->count(),
            'salario_minimo' => Configuracion::salarioMinimo(),
        ];
        if ($u->esInterno()) {
            $r['ventas_activas'] = Venta::where('estado', 'activa')->count();
            $r['facturacion_mensual_ventas'] = (int) Venta::where('estado', 'activa')->sum('valor_total');
            $r['cuotas_vencidas'] = Cuota::where('estado', 'pendiente')->whereDate('fecha_limite', '<', today())->count();
            $r['valor_cuotas_vencidas'] = (int) Cuota::where('estado', 'pendiente')->whereDate('fecha_limite', '<', today())->sum('valor');
        }
        return $r;
    }

    private function h_buscar_afiliados(array $in): array
    {
        $s = trim((string) ($in['texto'] ?? ''));
        if ($s === '') return ['error' => 'Escribe un nombre o documento.'];
        $q = Afiliado::visiblesPara($this->u)->with('empresa', 'cargo', 'preafiliaciones.entidad', 'incapacidades')
            ->limit(10);
        // Cada palabra debe aparecer en el documento, los nombres o los apellidos.
        foreach (preg_split('/\s+/', $s) as $palabra) {
            $q->where(fn ($w) => $w->where('documento', 'like', "%$palabra%")->orWhere('nombres', 'like', "%$palabra%")->orWhere('apellidos', 'like', "%$palabra%"));
        }
        $q = $q->get();
        return ['resultados' => $q->map(fn ($a) => [
            'nombre' => $a->nombre_completo, 'documento' => $a->tipo_documento.' '.$a->documento, 'empresa' => $a->empresa->nombre,
            'cargo' => $a->cargo?->nombre, 'salario' => $a->salario, 'fecha_ingreso' => $a->fecha_ingreso?->format('Y-m-d'),
            'afiliaciones' => $a->preafiliaciones->map(fn ($p) => ['radicado' => $p->radicado, 'tipo' => $p->tipo, 'entidad' => $p->entidad?->nombre, 'estado' => Preafiliacion::ESTADOS[$p->estado][0] ?? $p->estado, 'observaciones' => $p->observaciones])->values(),
            'incapacidades' => $a->incapacidades->map(fn ($i) => ['codigo' => $i->codigo, 'tipo' => Incapacidad::TIPOS[$i->tipo] ?? $i->tipo, 'dias' => $i->dias, 'estado' => Incapacidad::ESTADOS[$i->estado][0] ?? $i->estado, 'valor_recobro' => $i->valor_recobro])->values(),
            'url' => route('afiliados.show', $a),
        ])->values(), 'total' => $q->count()];
    }

    private function h_listar_afiliaciones(array $in): array
    {
        $q = Preafiliacion::visiblesPara($this->u)->with('afiliado', 'empresa', 'entidad');
        if (! empty($in['estado']) && isset(Preafiliacion::ESTADOS[$in['estado']])) $q->where('estado', $in['estado']);
        if (! empty($in['tipo'])) $q->where('tipo', $in['tipo']);
        if (! empty($in['empresa'])) {
            $e = $in['empresa'];
            $q->whereHas('empresa', fn ($w) => $w->where('nombre', 'like', "%$e%")->orWhere('documento', 'like', "%$e%"));
        }
        $total = (clone $q)->count();
        return ['total' => $total, 'mostrando' => $q->latest()->limit($this->limite($in))->get()->map(fn ($p) => [
            'radicado' => $p->radicado, 'empleado' => $p->afiliado->nombre_completo, 'empresa' => $p->empresa->nombre, 'tipo' => $p->tipo,
            'entidad' => $p->entidad?->nombre, 'estado' => Preafiliacion::ESTADOS[$p->estado][0] ?? $p->estado, 'dias_en_estado' => $p->diasEnEstado(),
            'observaciones' => $p->observaciones, 'url' => route('preafiliaciones.show', $p),
        ])->values()];
    }

    private function h_listar_incapacidades(array $in): array
    {
        $q = Incapacidad::visiblesPara($this->u)->with('afiliado', 'empresa', 'entidad');
        if (! empty($in['estado']) && isset(Incapacidad::ESTADOS[$in['estado']])) $q->where('estado', $in['estado']);
        $total = (clone $q)->count();
        return ['total' => $total, 'mostrando' => $q->latest()->limit($this->limite($in))->get()->map(fn ($i) => [
            'codigo' => $i->codigo, 'empleado' => $i->afiliado->nombre_completo, 'empresa' => $i->empresa->nombre, 'tipo' => Incapacidad::TIPOS[$i->tipo] ?? $i->tipo,
            'inicio' => $i->fecha_inicio->format('Y-m-d'), 'dias' => $i->dias, 'dias_acumulados' => $i->dias_acumulados, 'cobrar_a' => $i->entidad?->nombre,
            'valor_recobro' => $i->valor_recobro, 'valor_pagado' => $i->valor_pagado, 'estado' => Incapacidad::ESTADOS[$i->estado][0] ?? $i->estado,
            'observaciones' => $i->observaciones, 'url' => route('incapacidades.show', $i),
        ])->values()];
    }

    private function h_calcular_incapacidad(array $in): array
    {
        $tipo = (string) ($in['tipo'] ?? '');
        $dias = (int) ($in['dias'] ?? 0);
        $ibc = (int) ($in['ibc'] ?? 0);
        if (! isset(Incapacidad::TIPOS[$tipo]) || $dias < 1 || $dias > 540 || $ibc < 1) {
            return ['error' => 'Revisa el tipo, los días (1 a 540) y el IBC.'];
        }
        $smlv = Configuracion::salarioMinimo();
        $r = (new CalculadoraIncapacidad($smlv))->calcular($tipo, $dias, max($ibc, $smlv), max(0, (int) ($in['dias_previos'] ?? 0)));
        return $r + ['nota' => 'Cálculo con reglas simplificadas; valídalo antes de radicar.', 'url_calculadora' => route('incapacidades.calculadora', ['tipo' => $tipo, 'dias' => $dias, 'ibc' => $ibc])];
    }

    private function h_alertas(array $in): array
    {
        $a = app(Alertas::class)->para($this->u);
        return ['total' => $a->count(), 'alertas' => $a->take(25)->values()];
    }

    private function h_cartera_ventas(array $in): array
    {
        $p = (string) ($in['periodo'] ?? '');
        $periodo = preg_match('/^\d{4}-\d{2}$/', $p) ? \Carbon\Carbon::createFromFormat('Y-m-d', $p.'-01') : now()->startOfMonth();
        $cuotas = Cuota::with('venta')->whereDate('periodo', $periodo->toDateString())->get();
        $pend = $cuotas->filter(fn ($c) => in_array($c->estado_visible, ['pendiente', 'vencida'], true));
        return [
            'periodo' => $periodo->format('Y-m'),
            'cuotas' => $cuotas->count(),
            'total' => (int) $cuotas->where('estado', '!=', 'anulada')->sum('valor'),
            'recaudado' => (int) $cuotas->where('estado', 'pagada')->sum('valor'),
            'por_cobrar' => $pend->map(fn ($c) => ['cliente' => $c->venta->nombre_cliente, 'documento' => $c->venta->documento, 'valor' => $c->valor,
                'vence' => $c->fecha_limite->format('Y-m-d'), 'estado' => $c->estado_visible, 'celular' => $c->venta->celular, 'url' => route('ventas.show', $c->venta_id)])->values(),
            'url' => route('cuotas.index', ['periodo' => $periodo->format('Y-m')]),
        ];
    }

    private function h_buscar_ventas(array $in): array
    {
        $s = trim((string) ($in['texto'] ?? ''));
        if ($s === '') return ['error' => 'Escribe un nombre o documento.'];
        $v = Venta::with('plan', 'cuotas')->where(fn ($w) => $w->where('documento', 'like', "%$s%")->orWhere('nombre_cliente', 'like', "%$s%"))->limit(10)->get();
        return ['resultados' => $v->map(fn ($x) => [
            'cliente' => $x->nombre_cliente, 'documento' => $x->documento, 'plan' => $x->plan->nombre, 'riesgo' => $x->nivel_riesgo, 'valor_mensual' => $x->valor_total,
            'estado' => $x->estado, 'desde' => $x->periodo_inicial->format('Y-m'),
            'cuotas_recientes' => $x->cuotas->take(4)->map(fn ($c) => ['periodo' => $c->periodo->format('Y-m'), 'valor' => $c->valor, 'estado' => $c->estado_visible])->values(),
            'url' => route('ventas.show', $x),
        ])->values()];
    }
}
