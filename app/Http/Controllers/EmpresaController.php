<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\Empresa;
use App\Models\Entidad;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    public const DEPARTAMENTOS = ['Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá', 'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre', 'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada'];

    public function index(Request $request)
    {
        $q = Empresa::with('sede')->withCount(['cargos', 'afiliados', 'entidades']);
        if ($s = trim((string) $request->query('q'))) $q->where(fn ($w) => $w->where('nombre', 'like', "%$s%")->orWhere('documento', 'like', "%$s%"));
        if ($request->query('estado') === 'activas') $q->where('activa', true);
        if ($request->query('estado') === 'inactivas') $q->where('activa', false);
        if ($sede = $request->query('sede_id')) $q->where('sede_id', $sede);
        $empresas = $q->orderBy($request->query('orden') === 'nombre' ? 'nombre' : 'created_at', $request->query('orden') === 'nombre' ? 'asc' : 'desc')->get();
        if ($request->boolean('faltantes')) $empresas = $empresas->filter(fn ($e) => $e->datosFaltantes());
        return view('empresas.index', [
            'empresas' => $empresas,
            'sedes' => Sede::withCount('empresas')->orderBy('nombre')->get(),
            'departamentos' => self::DEPARTAMENTOS,
        ]);
    }

    private function reglas(?Empresa $e = null): array
    {
        return [
            'nombre' => 'required|string|max:150',
            'sede_id' => 'required|exists:sedes,id',
            'tipo_documento' => 'required|in:NIT,CC',
            'documento' => ['required', 'string', 'max:30', Rule::unique('empresas', 'documento')->ignore($e?->id)],
            'ciiu' => 'nullable|string|max:10',
            'actividad_economica' => 'nullable|string|max:200',
            'direccion' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'activa' => 'boolean',
            'entidades' => 'array',
            'entidades.*' => 'exists:entidades,id',
        ];
    }

    public function create()
    {
        return view('empresas.form', ['e' => new Empresa(['activa' => true]), 'sedes' => Sede::orderBy('nombre')->get(), 'entidades' => Entidad::where('activa', true)->orderBy('tipo')->orderBy('nombre')->get()->groupBy('tipo')]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        $e = Empresa::create(collect($datos)->except('entidades')->all() + ['activa' => $request->boolean('activa', true)]);
        $e->entidades()->sync($datos['entidades'] ?? []);
        return redirect()->route('empresas.edit', $e)->with('ok', 'Empresa creada. Ahora agrega sus cargos.');
    }

    public function edit(Empresa $empresa)
    {
        $empresa->load('cargos', 'entidades');
        return view('empresas.form', ['e' => $empresa, 'sedes' => Sede::orderBy('nombre')->get(), 'entidades' => Entidad::where('activa', true)->orderBy('tipo')->orderBy('nombre')->get()->groupBy('tipo')]);
    }

    public function update(Request $request, Empresa $empresa)
    {
        $datos = $request->validate($this->reglas($empresa));
        $empresa->update(collect($datos)->except('entidades')->all() + ['activa' => $request->boolean('activa')]);
        $empresa->entidades()->sync($datos['entidades'] ?? []);
        return back()->with('ok', 'Empresa actualizada.');
    }

    public function agregarCargo(Request $request, Empresa $empresa)
    {
        $datos = $request->validate(['nombre' => 'required|string|max:100', 'riesgo_arl' => 'required|integer|between:1,5']);
        $empresa->cargos()->create($datos);
        return back()->with('ok', 'Cargo agregado.');
    }

    public function quitarCargo(Cargo $cargo)
    {
        $cargo->delete();
        return back()->with('ok', 'Cargo eliminado.');
    }

    public function guardarSede(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:120',
            'tipo_documento' => 'required|in:NIT,CC',
            'documento' => 'required|string|max:30',
            'departamento' => ['required', Rule::in(self::DEPARTAMENTOS)],
            'ciudad' => 'required|string|max:80',
        ]);
        Sede::create($datos);
        return back()->with('ok', 'Sede creada.');
    }
}
