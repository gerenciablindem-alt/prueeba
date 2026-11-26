<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Entidad;
use App\Models\Preafiliacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PreafiliacionController extends Controller
{
    public function index(Request $request)
    {
        $u = auth()->user();
        $q = Preafiliacion::visiblesPara($u)->with('afiliado', 'empresa', 'entidad');
        if ($e = $request->query('estado')) $q->where('estado', $e);
        if ($t = $request->query('tipo')) $q->where('tipo', $t);
        if ($emp = $request->query('empresa_id')) $q->where('empresa_id', $emp);
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('radicado', 'like', "%$s%")
                ->orWhereHas('afiliado', fn ($a) => $a->where('documento', 'like', "%$s%")
                    ->orWhere('nombres', 'like', "%$s%")->orWhere('apellidos', 'like', "%$s%")));
        }
        $base = Preafiliacion::visiblesPara($u);
        return view('preafiliaciones.index', [
            'items' => $q->latest()->paginate((int) $request->query('por_pagina', 25))->withQueryString(),
            'conteoEstado' => (clone $base)->selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado'),
            'conteoTipo' => (clone $base)->selectRaw('tipo, count(*) n')->groupBy('tipo')->pluck('n', 'tipo'),
            'empresas' => Empresa::visiblesPara($u)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function create()
    {
        $u = auth()->user();
        $empresas = Empresa::visiblesPara($u)->where('activa', true)->with('cargos', 'entidades')->orderBy('nombre')->get();
        return view('preafiliaciones.create', [
            'empresas' => $empresas,
            'empresasJs' => $empresas->mapWithKeys(fn ($e) => [$e->id => [
                'nombre' => $e->nombre,
                'cargos' => $e->cargos->map->only('id', 'nombre', 'riesgo_arl')->values(),
                'entidades' => $e->entidades->map->only('id', 'tipo')->values(),
                'faltan' => $e->datosFaltantes(),
            ]]),
            'entidades' => Entidad::where('activa', true)->orderBy('nombre')->get()->groupBy('tipo'),
        ]);
    }

    public function store(Request $request)
    {
        $u = auth()->user();
        $datos = $request->validate([
            'tipos' => 'required|array|min:1',
            'tipos.*' => Rule::in(array_keys(Entidad::TIPOS)),
            'empresa_id' => ['required', Rule::exists('empresas', 'id')->where('activa', true)],
            'tipo_documento' => 'required|in:CC,CE,PPT,TI,PA',
            'documento' => 'required|string|max:20',
            'nombres' => 'required|string|max:80',
            'apellidos' => 'required|string|max:80',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'fecha_expedicion' => 'nullable|date|before_or_equal:today',
            'sexo' => 'nullable|in:F,M',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'direccion' => 'nullable|string|max:150',
            'ciudad' => 'nullable|string|max:80',
            'cargo_id' => ['nullable', Rule::exists('cargos', 'id')->where('empresa_id', $request->input('empresa_id'))],
            'salario' => 'required|integer|min:0',
            'fecha_ingreso' => 'nullable|date',
            'entidades' => 'required|array',
            'entidades.*' => 'nullable|exists:entidades,id',
            'observaciones' => 'nullable|string|max:1000',
        ], [], ['tipos' => 'tipo de afiliación', 'empresa_id' => 'empresa']);

        if ($u->esEmpresa() && (int) $datos['empresa_id'] !== (int) $u->empresa_id) abort(403);

        foreach ($datos['tipos'] as $tipo) {
            $entidadId = $datos['entidades'][$tipo] ?? null;
            if (! $entidadId || Entidad::whereKey($entidadId)->value('tipo') !== $tipo) {
                return back()->withInput()->withErrors(["entidades.$tipo" => "Selecciona la $tipo a la que se afilia."]);
            }
        }

        $creadas = DB::transaction(function () use ($datos, $u) {
            $documento = preg_replace('/\D/', '', $datos['documento']);
            $afiliado = Afiliado::updateOrCreate(
                ['empresa_id' => $datos['empresa_id'], 'tipo_documento' => $datos['tipo_documento'], 'documento' => $documento],
                collect($datos)->only(['nombres', 'apellidos', 'fecha_nacimiento', 'fecha_expedicion', 'sexo', 'telefono', 'email', 'direccion', 'ciudad', 'cargo_id', 'salario', 'fecha_ingreso'])->all()
            );
            return collect($datos['tipos'])->map(fn ($tipo) => Preafiliacion::create([
                'afiliado_id' => $afiliado->id,
                'empresa_id' => $datos['empresa_id'],
                'entidad_id' => $datos['entidades'][$tipo],
                'tipo' => $tipo,
                'estado' => 'pendiente',
                'observaciones' => $datos['observaciones'] ?? null,
                'creado_por' => $u->id,
            ]));
        });

        return redirect()->route('preafiliaciones.index')
            ->with('ok', 'Se crearon '.$creadas->count().' radicado(s): '.$creadas->pluck('radicado')->join(', '));
    }

    public function show(Preafiliacion $preafiliacion)
    {
        $this->autorizarEmpresa($preafiliacion);
        $preafiliacion->load('afiliado.cargo', 'empresa', 'entidad', 'documentos', 'creador');
        $historial = \App\Models\Auditoria::with('user')->where('auditable_type', Preafiliacion::class)
            ->where('auditable_id', $preafiliacion->id)->latest('created_at')->get();
        return view('preafiliaciones.show', ['p' => $preafiliacion, 'historial' => $historial]);
    }

    public function update(Request $request, Preafiliacion $preafiliacion)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Preafiliacion::ESTADOS))],
            'fecha_afiliacion' => 'nullable|date',
            'numero_entidad' => 'nullable|string|max:60',
            'observaciones' => 'nullable|string|max:1000|required_if:estado,correccion,rechazado',
        ], ['observaciones.required_if' => 'Explica el motivo para que la empresa pueda corregirlo.']);
        $preafiliacion->update($datos);
        return back()->with('ok', 'Radicado actualizado.');
    }

    public function subirDocumento(Request $request, Preafiliacion $preafiliacion)
    {
        $this->autorizarEmpresa($preafiliacion);
        return $this->guardarDocumento($request, $preafiliacion);
    }

    public function descargarDocumento(Documento $documento)
    {
        $this->autorizarEmpresa($documento->documentable);
        return Storage::download($documento->ruta, $documento->nombre);
    }

    public static function guardarDocumentoEn(Request $request, $modelo)
    {
        $request->validate(['archivo' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png']);
        $f = $request->file('archivo');
        $modelo->documentos()->create([
            'nombre' => $f->getClientOriginalName(),
            'ruta' => $f->store('documentos/'.now()->format('Y/m')),
            'tamano' => $f->getSize(),
            'subido_por' => auth()->id(),
        ]);
        return back()->with('ok', 'Documento cargado.');
    }

    private function guardarDocumento(Request $request, $modelo)
    {
        return static::guardarDocumentoEn($request, $modelo);
    }
}
