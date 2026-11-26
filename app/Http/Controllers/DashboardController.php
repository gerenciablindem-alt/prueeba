<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Empresa;
use App\Models\Incapacidad;
use App\Models\Venta;
use Illuminate\Http\Request;
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

    /** Búsqueda global para la paleta Ctrl K. */
    public function buscar(Request $request)
    {
        $u = $request->user();
        $s = trim((string) $request->query('q'));
        if (mb_strlen($s) < 2) return response()->json(['resultados' => []]);
        $like = '%'.$s.'%';
        $r = collect();

        Afiliado::visiblesPara($u)->with('empresa')
            ->where(fn ($w) => $w->where('documento', 'like', $like)->orWhere('nombres', 'like', $like)->orWhere('apellidos', 'like', $like))
            ->limit(6)->get()
            ->each(fn ($a) => $r->push(['grupo' => 'Afiliados', 'titulo' => $a->nombre_completo, 'detalle' => $a->documento.' · '.$a->empresa->nombre, 'url' => route('afiliados.show', $a)]));

        Preafiliacion::visiblesPara($u)->with('afiliado')->where('radicado', 'like', $like)->limit(5)->get()
            ->each(fn ($p) => $r->push(['grupo' => 'Radicados', 'titulo' => $p->radicado, 'detalle' => $p->tipo.' · '.$p->afiliado->nombre_completo.' · '.(Preafiliacion::ESTADOS[$p->estado][0] ?? $p->estado), 'url' => route('preafiliaciones.show', $p)]));

        Incapacidad::visiblesPara($u)->with('afiliado')->where('codigo', 'like', $like)->limit(5)->get()
            ->each(fn ($i) => $r->push(['grupo' => 'Incapacidades', 'titulo' => $i->codigo, 'detalle' => $i->afiliado->nombre_completo.' · '.(Incapacidad::ESTADOS[$i->estado][0] ?? $i->estado), 'url' => route('incapacidades.show', $i)]));

        if ($u->esInterno()) {
            Empresa::where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('documento', 'like', $like))->limit(5)->get()
                ->each(fn ($e) => $r->push(['grupo' => 'Empresas', 'titulo' => $e->nombre, 'detalle' => 'NIT '.$e->documento, 'url' => route('empresas.edit', $e)]));
            Venta::with('plan')->where(fn ($w) => $w->where('documento', 'like', $like)->orWhere('nombre_cliente', 'like', $like))->limit(5)->get()
                ->each(fn ($v) => $r->push(['grupo' => 'Ventas', 'titulo' => $v->nombre_cliente, 'detalle' => $v->documento.' · '.$v->plan->nombre, 'url' => route('ventas.show', $v)]));
        }
        return response()->json(['resultados' => $r->values()]);
    }
}
