<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Sede;
use App\Models\Tarifario;
use App\Models\Venta;
use App\Services\Cobros;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $q = Venta::with('plan', 'sede')->withCount(['cuotas as cuotas_pendientes' => fn ($c) => $c->where('estado', 'pendiente')]);
        if ($e = $request->query('estado')) $q->where('estado', $e);
        if ($p = $request->query('plan')) $q->where('plan_id', $p);
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('documento', 'like', "%$s%")->orWhere('nombre_cliente', 'like', "%$s%"));
        }
        return view('ventas.index', [
            'items' => $q->latest()->paginate(25)->withQueryString(),
            'planes' => Plan::orderBy('orden')->get(),
            'activas' => Venta::where('estado', 'activa')->count(),
            'ingresoMensual' => Venta::where('estado', 'activa')->sum('valor_total'),
            'administracionMensual' => Venta::where('estado', 'activa')->sum('administracion'),
        ]);
    }

    public function create()
    {
        $tarifario = Tarifario::vigente();
        return view('ventas.create', [
            'tarifario' => $tarifario,
            'planes' => Plan::where('activo', true)->orderBy('orden')->get(),
            'sedes' => Sede::where('activa', true)->orderBy('nombre')->get(),
            'celdasJs' => $tarifario ? $tarifario->celdas->mapWithKeys(fn ($c) => [$c->plan_id.'-'.$c->nivel_riesgo => $c->only('eps', 'afp', 'ccf', 'arl', 'total')]) : [],
        ]);
    }

    public function store(Request $request, Cobros $cobros)
    {
        $tarifario = Tarifario::vigente();
        abort_unless($tarifario, 422, 'No hay un tarifario publicado.');
        $d = $request->validate([
            'sede_id' => 'required|exists:sedes,id',
            'plan_id' => ['required', Rule::exists('planes', 'id')->where('activo', true)],
            'nivel_riesgo' => 'required|integer|between:1,5',
            'tipo_documento' => ['required', Rule::in(['CC', 'CE', 'PA', 'PPT', 'TI'])],
            'documento' => 'required|string|max:20',
            'nombre_cliente' => 'required|string|max:150',
            'celular' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'administracion' => 'required|integer|min:0|max:10000000',
            'periodo_inicial' => 'required|date_format:Y-m',
        ]);
        $celda = $tarifario->celdas()->where('plan_id', $d['plan_id'])->where('nivel_riesgo', $d['nivel_riesgo'])->first();
        if (! $celda) {
            return back()->withInput()->withErrors(['plan_id' => 'El tarifario vigente no tiene precio para ese plan y nivel de riesgo.']);
        }
        if (Venta::where('documento', $d['documento'])->where('plan_id', $d['plan_id'])->where('estado', 'activa')->exists()) {
            return back()->withInput()->withErrors(['documento' => 'Esa persona ya tiene una venta activa de este plan.']);
        }
        $d['periodo_inicial'] = Carbon::createFromFormat('Y-m-d', $d['periodo_inicial'].'-01')->toDateString();
        $venta = Venta::create($d + [
            'tarifario_id' => $tarifario->id,
            'eps' => $celda->eps, 'afp' => $celda->afp, 'ccf' => $celda->ccf, 'arl' => $celda->arl,
            'valor_total' => $celda->total + $d['administracion'],
            'estado' => 'activa',
            'creado_por' => auth()->id(),
        ]);
        $cobros->cuotaPara($venta, $venta->periodo_inicial);
        return redirect()->route('ventas.show', $venta)->with('ok', 'Venta registrada y primera cuota generada.');
    }

    public function show(Venta $venta)
    {
        $venta->load('plan', 'sede', 'tarifario', 'cuotas.reciboVigente');
        return view('ventas.show', ['v' => $venta, 'recibos' => $venta->recibos()->get()]);
    }

    public function retirar(Request $request, Venta $venta)
    {
        $d = $request->validate(['fecha_retiro' => 'required|date', 'motivo_retiro' => 'required|string|max:250']);
        abort_if($venta->estado === 'retirada', 422, 'La venta ya está retirada.');
        $venta->update($d + ['estado' => 'retirada']);
        // Las cuotas pendientes posteriores al mes de retiro se anulan.
        $venta->cuotas()->where('estado', 'pendiente')->where('periodo', '>', Carbon::parse($d['fecha_retiro'])->startOfMonth()->toDateString())
            ->whereDoesntHave('recibos', fn ($r) => $r->where('estado', 'emitido'))->update(['estado' => 'anulada']);
        return back()->with('ok', 'Venta retirada.');
    }
}
