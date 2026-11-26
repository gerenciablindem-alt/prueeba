<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Configuracion;
use App\Models\Entidad;
use App\Models\Incapacidad;
use App\Services\CalculadoraIncapacidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncapacidadController extends Controller
{
    private function calculadora_(): CalculadoraIncapacidad
    {
        return new CalculadoraIncapacidad(Configuracion::salarioMinimo());
    }

    public function index(Request $request)
    {
        $u = auth()->user();
        $q = Incapacidad::visiblesPara($u)->with('afiliado', 'empresa', 'entidad');
        if ($e = $request->query('estado')) $q->where('estado', $e);
        if ($t = $request->query('tipo')) $q->where('tipo', $t);
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('codigo', 'like', "%$s%")->orWhereHas('afiliado', fn ($a) => $a->where('documento', 'like', "%$s%")->orWhere('nombres', 'like', "%$s%")->orWhere('apellidos', 'like', "%$s%")));
        }
        $base = Incapacidad::visiblesPara($u);
        return view('incapacidades.index', [
            'items' => $q->latest('fecha_inicio')->paginate(25)->withQueryString(),
            'conteo' => (clone $base)->selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado'),
            'porRecobrar' => (clone $base)->whereIn('estado', ['radicada', 'aprobada'])->sum('valor_recobro'),
            'rechazado' => (clone $base)->where('estado', 'rechazada')->sum('valor_recobro'),
            'pagado' => (clone $base)->where('estado', 'pagada')->sum('valor_pagado'),
        ]);
    }

    public function create(Request $request)
    {
        $u = auth()->user();
        return view('incapacidades.create', [
            'afiliados' => Afiliado::visiblesPara($u)->with('empresa')->orderBy('apellidos')->get(),
            'entidades' => Entidad::whereIn('tipo', ['EPS', 'ARL'])->where('activa', true)->orderBy('nombre')->get(),
            'previas' => Incapacidad::visiblesPara($u)->with('afiliado')->where('estado', '!=', 'rechazada')->latest('fecha_inicio')->limit(200)->get(),
            'prorrogaDe' => $request->query('prorroga_de'),
            'salarioMinimo' => Configuracion::salarioMinimo(),
        ]);
    }

    public function store(Request $request)
    {
        $u = auth()->user();
        $datos = $request->validate([
            'afiliado_id' => 'required|exists:afiliados,id',
            'tipo' => ['required', Rule::in(array_keys(Incapacidad::TIPOS))],
            'diagnostico' => 'nullable|string|max:10',
            'fecha_inicio' => 'required|date',
            'dias' => 'required|integer|min:1|max:540',
            'ibc' => 'required|integer|min:0',
            'entidad_id' => 'nullable|exists:entidades,id',
            'prorroga_de_id' => 'nullable|exists:incapacidades,id',
            'observaciones' => 'nullable|string|max:1000',
        ], [], ['afiliado_id' => 'empleado', 'ibc' => 'IBC']);

        $afiliado = Afiliado::findOrFail($datos['afiliado_id']);
        $this->autorizarEmpresa($afiliado);

        $previos = 0;
        if (! empty($datos['prorroga_de_id'])) {
            $anterior = Incapacidad::findOrFail($datos['prorroga_de_id']);
            if ($anterior->afiliado_id !== $afiliado->id) {
                return back()->withInput()->withErrors(['prorroga_de_id' => 'La incapacidad inicial debe ser del mismo empleado.']);
            }
            $previos = $anterior->dias_acumulados;
        }

        $calc = $this->calculadora_()->calcular($datos['tipo'], $datos['dias'], $datos['ibc'], $previos);
        $inc = Incapacidad::create($datos + [
            'empresa_id' => $afiliado->empresa_id,
            'dias_acumulados' => $previos + $datos['dias'],
            'valor_calculado' => $calc['total'],
            'valor_recobro' => $calc['recobro'],
            'estado' => $calc['recobro'] > 0 ? 'por_radicar' : 'pagada',
            'creado_por' => $u->id,
        ]);
        return redirect()->route('incapacidades.show', $inc)->with('ok', "Incapacidad {$inc->codigo} registrada.");
    }

    public function show(Incapacidad $incapacidad)
    {
        $this->autorizarEmpresa($incapacidad);
        $incapacidad->load('afiliado', 'empresa', 'entidad', 'documentos', 'prorrogaDe', 'prorrogas');
        $previos = $incapacidad->dias_acumulados - $incapacidad->dias;
        return view('incapacidades.show', [
            'i' => $incapacidad,
            'calc' => $this->calculadora_()->calcular($incapacidad->tipo, $incapacidad->dias, $incapacidad->ibc, $previos),
        ]);
    }

    public function update(Request $request, Incapacidad $incapacidad)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Incapacidad::ESTADOS))],
            'entidad_id' => 'nullable|exists:entidades,id',
            'numero_radicado_entidad' => 'nullable|string|max:60|required_if:estado,radicada',
            'fecha_radicacion' => 'nullable|date',
            'valor_pagado' => 'nullable|integer|min:0|required_if:estado,pagada',
            'fecha_pago' => 'nullable|date|required_if:estado,pagada',
            'observaciones' => 'nullable|string|max:1000|required_if:estado,rechazada',
        ], [
            'numero_radicado_entidad.required_if' => 'Anota el número que te dio la entidad al radicar.',
            'valor_pagado.required_if' => 'Anota cuánto pagó la entidad.',
            'fecha_pago.required_if' => 'Anota la fecha del pago.',
            'observaciones.required_if' => 'Anota el motivo del rechazo.',
        ]);
        if ($datos['estado'] === 'radicada' && empty($datos['fecha_radicacion'])) $datos['fecha_radicacion'] = now()->toDateString();
        $incapacidad->update($datos);
        return back()->with('ok', 'Incapacidad actualizada.');
    }

    public function subirDocumento(Request $request, Incapacidad $incapacidad)
    {
        $this->autorizarEmpresa($incapacidad);
        return PreafiliacionController::guardarDocumentoEn($request, $incapacidad);
    }

    public function calculadora(Request $request)
    {
        $tipo = $request->query('tipo', 'enfermedad_general');
        $dias = max(1, min(540, (int) $request->query('dias', 15)));
        $ibc = max(0, (int) $request->query('ibc', 2000000));
        $previos = max(0, min(539, (int) $request->query('previos', 0)));
        if (! array_key_exists($tipo, Incapacidad::TIPOS)) $tipo = 'enfermedad_general';
        return view('incapacidades.calculadora', [
            'tipo' => $tipo, 'dias' => $dias, 'ibc' => $ibc, 'previos' => $previos,
            'calc' => $this->calculadora_()->calcular($tipo, $dias, $ibc, $previos),
            'salarioMinimo' => Configuracion::salarioMinimo(),
        ]);
    }
}
