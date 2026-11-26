<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Sede;
use App\Services\Cobros;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CuotaController extends Controller
{
    private function periodo(Request $request): Carbon
    {
        $p = (string) $request->input('periodo', now()->format('Y-m'));
        return preg_match('/^\d{4}-\d{2}$/', $p) ? Carbon::createFromFormat('Y-m-d', $p.'-01')->startOfDay() : now()->startOfMonth();
    }

    public function index(Request $request)
    {
        $periodo = $this->periodo($request);
        $base = Cuota::whereDate('periodo', $periodo->toDateString())
            ->when($request->query('sede'), fn ($q, $s) => $q->whereHas('venta', fn ($v) => $v->where('sede_id', $s)));
        $q = (clone $base)->with('venta.plan', 'reciboVigente');
        $estado = $request->query('estado');
        if ($estado === 'vencida') $q->where('estado', 'pendiente')->whereDate('fecha_limite', '<', today());
        elseif ($estado === 'pendiente') $q->where('estado', 'pendiente')->whereDate('fecha_limite', '>=', today());
        elseif ($estado) $q->where('estado', $estado);
        if ($s = trim((string) $request->query('q'))) {
            $q->whereHas('venta', fn ($v) => $v->where('documento', 'like', "%$s%")->orWhere('nombre_cliente', 'like', "%$s%"));
        }
        $todas = (clone $base)->get();
        return view('cuotas.index', [
            'periodo' => $periodo,
            'items' => $q->orderBy('id')->paginate(50)->withQueryString(),
            'sedes' => Sede::orderBy('nombre')->get(),
            'resumen' => [
                'total' => $todas->where('estado', '!=', 'anulada')->sum('valor'),
                'recaudado' => $todas->where('estado', 'pagada')->sum('valor'),
                'pendientes' => $todas->filter(fn ($c) => $c->estado_visible === 'pendiente')->count(),
                'vencidas' => $todas->filter(fn ($c) => $c->estado_visible === 'vencida')->count(),
                'pagadas' => $todas->where('estado', 'pagada')->count(),
            ],
        ]);
    }

    public function generar(Request $request, Cobros $cobros)
    {
        $request->validate(['periodo' => 'required|date_format:Y-m', 'sede_id' => 'nullable|exists:sedes,id']);
        $periodo = $this->periodo($request);
        $r = $cobros->generarPeriodo($periodo, $request->integer('sede_id') ?: null);
        return redirect()->route('cuotas.index', ['periodo' => $periodo->format('Y-m')])
            ->with('ok', "Cuotas de {$periodo->translatedFormat('F Y')}: {$r['generadas']} nuevas, {$r['ya_existian']} ya existían.");
    }

    public function emitirRecibo(Cuota $cuota, Cobros $cobros)
    {
        try {
            $r = $cobros->emitirRecibo($cuota, auth()->id());
        } catch (\DomainException $e) {
            return back()->withErrors(['cuota' => $e->getMessage()]);
        }
        return redirect()->route('recibos.show', $r);
    }
}
