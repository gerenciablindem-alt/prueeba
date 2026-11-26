<?php

namespace App\Http\Controllers;

use App\Models\Incapacidad;
use App\Models\Preafiliacion;
use App\Services\Alertas;

class DashboardController extends Controller
{
    public function index(Alertas $alertas)
    {
        $u = auth()->user();
        $pre = Preafiliacion::visiblesPara($u);
        $porEstado = (clone $pre)->selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado');
        $porTipo = (clone $pre)->selectRaw('tipo, count(*) n')->groupBy('tipo')->pluck('n', 'tipo');
        $inc = Incapacidad::visiblesPara($u)->whereIn('estado', ['por_radicar', 'radicada', 'aprobada', 'rechazada']);

        return view('dashboard', [
            'total' => $porEstado->sum(),
            'porEstado' => $porEstado,
            'porTipo' => collect(['EPS', 'ARL', 'CCF', 'AFP'])->mapWithKeys(fn ($t) => [$t => $porTipo[$t] ?? 0]),
            'porCobrar' => (clone $inc)->sum('valor_recobro'),
            'incAbiertas' => (clone $inc)->count(),
            'alertas' => $alertas->para($u),
            'recientes' => Preafiliacion::visiblesPara($u)->with('afiliado', 'empresa', 'entidad')->latest()->limit(8)->get(),
        ]);
    }

    public function alertas(Alertas $alertas)
    {
        return view('alertas', ['alertas' => $alertas->para(auth()->user())]);
    }
}
