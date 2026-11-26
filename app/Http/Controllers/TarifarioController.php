<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tarifario;
use App\Services\Tarifas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TarifarioController extends Controller
{
    public function index(Request $request)
    {
        $tarifarios = Tarifario::orderByDesc('vigente_desde')->get();
        $actual = $request->query('t') ? $tarifarios->firstWhere('id', (int) $request->query('t')) : (Tarifario::vigente() ?? $tarifarios->first());
        return view('tarifarios.index', [
            'tarifarios' => $tarifarios,
            'actual' => $actual,
            'planes' => Plan::orderBy('orden')->get(),
            'celdas' => $actual ? $actual->celdas->keyBy(fn ($c) => $c->plan_id.'-'.$c->nivel_riesgo) : collect(),
        ]);
    }

    /** Crea un tarifario en borrador calculando todos los planes y niveles a partir del SMLV. */
    public function store(Request $request)
    {
        $d = $request->validate([
            'anio' => 'required|integer|between:2020,2100',
            'smlv' => 'required|integer|min:100000',
            'vigente_desde' => 'required|date',
        ]);
        $t = DB::transaction(function () use ($d) {
            $t = Tarifario::create($d + ['estado' => 'borrador', 'creado_por' => auth()->id()]);
            foreach (Plan::all() as $plan) {
                foreach (array_keys(config('aportes.tarifas_arl')) as $nivel) {
                    $t->celdas()->create(['plan_id' => $plan->id, 'nivel_riesgo' => $nivel] + Tarifas::calcular($plan, $nivel, $d['smlv']));
                }
            }
            return $t;
        });
        return redirect()->route('tarifarios.index', ['t' => $t->id])->with('ok', 'Tarifario '.$t->anio.' creado en borrador. Revísalo y publícalo.');
    }

    public function publicar(Tarifario $tarifario)
    {
        DB::transaction(function () use ($tarifario) {
            Tarifario::where('estado', 'publicado')->whereKeyNot($tarifario->id)->update(['estado' => 'archivado']);
            $tarifario->update(['estado' => 'publicado', 'publicado_at' => now()]);
        });
        return redirect()->route('tarifarios.index', ['t' => $tarifario->id])->with('ok', 'Tarifario '.$tarifario->anio.' publicado. Las ventas nuevas usan estos valores.');
    }

    public function guardarPlan(Request $request)
    {
        $d = $request->validate([
            'codigo' => 'required|string|max:20|unique:planes,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:250',
        ]);
        foreach (['incluye_eps', 'incluye_afp', 'incluye_arl', 'incluye_ccf'] as $c) $d[$c] = $request->boolean($c);
        if (! ($d['incluye_eps'] || $d['incluye_afp'] || $d['incluye_arl'] || $d['incluye_ccf'])) {
            return back()->withInput()->withErrors(['codigo' => 'El plan debe incluir al menos un subsistema.']);
        }
        $d['orden'] = (int) Plan::max('orden') + 1;
        Plan::create($d);
        return back()->with('ok', 'Plan creado. Crea un tarifario nuevo para que tenga precios.');
    }
}
