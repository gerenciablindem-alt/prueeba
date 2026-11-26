<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Empresa;
use App\Models\Incapacidad;
use App\Models\Preafiliacion;
use App\Models\Traslado;
use App\Models\User;
use Illuminate\Support\Collection;

/** Construye la lista de cosas que requieren atención, de la más urgente a la menos urgente. */
class Alertas
{
    public function para(User $u): Collection
    {
        $dias = config('aportes.dias_alerta_radicado');
        $a = collect();

        Preafiliacion::visiblesPara($u)->with('afiliado', 'empresa')->where('estado', 'correccion')->get()
            ->each(fn ($p) => $a->push($this->alerta('bad', "{$p->radicado} requiere corrección",
                "{$p->afiliado->nombre_completo} · {$p->empresa->nombre} · hace {$p->diasEnEstado()} días", route('preafiliaciones.show', $p))));

        Incapacidad::visiblesPara($u)->with('afiliado')->where('estado', 'por_radicar')->get()
            ->each(fn ($i) => $a->push($this->alerta('bad', "{$i->codigo} sin radicar ante la entidad",
                "{$i->afiliado->nombre_completo} · inició el {$i->fecha_inicio->format('d/m/Y')}", route('incapacidades.show', $i))));

        Incapacidad::visiblesPara($u)->with('afiliado')->where('estado', 'rechazada')->get()
            ->each(fn ($i) => $a->push($this->alerta('bad', "{$i->codigo} rechazada",
                "{$i->afiliado->nombre_completo} · $ ".number_format($i->valor_recobro, 0, ',', '.').' por recuperar', route('incapacidades.show', $i))));

        Preafiliacion::visiblesPara($u)->with('afiliado')->whereIn('estado', ['pendiente', 'revision'])
            ->where('estado_cambiado_at', '<=', now()->subDays($dias))->get()
            ->each(fn ($p) => $a->push($this->alerta('warn', "{$p->radicado} lleva {$p->diasEnEstado()} días sin respuesta",
                "{$p->tipo} · {$p->afiliado->nombre_completo}", route('preafiliaciones.show', $p))));

        Incapacidad::visiblesPara($u)->with('afiliado')->where('dias_acumulados', '>=', 150)->whereIn('estado', ['por_radicar', 'radicada', 'aprobada'])->get()
            ->each(fn ($i) => $a->push($this->alerta('warn', "{$i->codigo}: {$i->dias_acumulados} días acumulados",
                "{$i->afiliado->nombre_completo} se acerca al día 180. Pide el concepto de rehabilitación a la EPS.", route('incapacidades.show', $i))));

        Traslado::visiblesPara($u)->with('afiliado')->whereIn('estado', ['solicitado', 'en_tramite'])
            ->where('updated_at', '<=', now()->subDays($dias))->get()
            ->each(fn ($t) => $a->push($this->alerta('warn', "Traslado de {$t->afiliado->nombre_completo} sin movimiento",
                "{$t->tipo} · solicitado el {$t->fecha_solicitud->format('d/m/Y')}", route('traslados.index'))));

        if ($u->esInterno()) {
            $vencidas = Cuota::where('estado', 'pendiente')->whereDate('fecha_limite', '<', today())->get();
            if ($vencidas->isNotEmpty()) {
                $a->push($this->alerta('warn', $vencidas->count().' cuotas vencidas sin pagar',
                    pesos($vencidas->sum('valor')).' por cobrar a clientes de ventas', route('cuotas.index', ['periodo' => $vencidas->min('periodo')->format('Y-m'), 'estado' => 'vencida'])));
            }
        }

        Empresa::visiblesPara($u)->withCount('cargos')->where('activa', true)->get()
            ->filter(fn ($e) => $e->datosFaltantes())
            ->each(fn ($e) => $a->push($this->alerta('info', "{$e->nombre}: faltan datos",
                'Completa '.implode(', ', $e->datosFaltantes()).' para radicar sin devoluciones', route('empresas.edit', $e))));

        return $a;
    }

    private function alerta(string $nivel, string $titulo, string $detalle, string $url): array
    {
        return compact('nivel', 'titulo', 'detalle', 'url');
    }
}
