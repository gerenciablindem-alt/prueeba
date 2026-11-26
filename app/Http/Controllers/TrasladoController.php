<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Entidad;
use App\Models\Traslado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrasladoController extends Controller
{
    public function index(Request $request)
    {
        $u = auth()->user();
        $q = Traslado::visiblesPara($u)->with('afiliado', 'empresa', 'origen', 'destino');
        if ($t = $request->query('tipo')) $q->where('tipo', $t);
        if ($e = $request->query('estado')) $q->where('estado', $e);
        if ($s = trim((string) $request->query('q'))) {
            $q->whereHas('afiliado', fn ($a) => $a->where('documento', 'like', "%$s%")->orWhere('nombres', 'like', "%$s%")->orWhere('apellidos', 'like', "%$s%"));
        }
        return view('traslados.index', [
            'items' => $q->latest()->paginate(25)->withQueryString(),
            'conteoTipo' => Traslado::visiblesPara($u)->selectRaw('tipo, count(*) n')->groupBy('tipo')->pluck('n', 'tipo'),
            'afiliados' => $u->esInterno() ? Afiliado::with('empresa')->orderBy('apellidos')->get() : collect(),
            'entidades' => Entidad::where('activa', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'afiliado_id' => 'required|exists:afiliados,id',
            'tipo' => ['required', Rule::in(array_keys(Entidad::TIPOS))],
            'entidad_origen_id' => 'required|exists:entidades,id',
            'entidad_destino_id' => 'required|exists:entidades,id|different:entidad_origen_id',
            'fecha_solicitud' => 'required|date',
            'seguimiento' => 'nullable|string|max:1000',
        ], ['entidad_destino_id.different' => 'La entidad destino debe ser distinta a la de origen.']);
        foreach (['entidad_origen_id', 'entidad_destino_id'] as $c) {
            if (Entidad::whereKey($datos[$c])->value('tipo') !== $datos['tipo']) {
                return back()->withInput()->withErrors([$c => 'La entidad debe ser del tipo '.$datos['tipo'].'.']);
            }
        }
        $datos['empresa_id'] = Afiliado::whereKey($datos['afiliado_id'])->value('empresa_id');
        $datos['creado_por'] = auth()->id();
        Traslado::create($datos);
        return back()->with('ok', 'Traslado registrado.');
    }

    public function update(Request $request, Traslado $traslado)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Traslado::ESTADOS))],
            'fecha_efectiva' => 'nullable|date',
            'nota' => 'nullable|string|max:500',
        ]);
        if (! empty($datos['nota'])) {
            $datos['seguimiento'] = trim(now()->format('d/m/Y').' · '.auth()->user()->name.': '.$datos['nota']."\n".$traslado->seguimiento);
        }
        unset($datos['nota']);
        $traslado->update($datos);
        return back()->with('ok', 'Seguimiento actualizado.');
    }
}
