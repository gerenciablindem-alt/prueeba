<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Empresa;
use App\Models\Preafiliacion;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    private function consulta(Request $request)
    {
        $u = auth()->user();
        return Afiliado::visiblesPara($u)->with(['empresa', 'preafiliaciones' => fn ($q) => $q->with('entidad')->latest()])
            ->when($request->query('empresa_id'), fn ($q, $v) => $q->where('empresa_id', $v))
            ->when($request->query('estado'), fn ($q, $v) => $q->whereHas('preafiliaciones', fn ($p) => $p->where('estado', $v)))
            ->when($request->query('desde'), fn ($q, $v) => $q->whereHas('preafiliaciones', fn ($p) => $p->whereDate('created_at', '>=', $v)))
            ->when($request->query('hasta'), fn ($q, $v) => $q->whereHas('preafiliaciones', fn ($p) => $p->whereDate('created_at', '<=', $v)))
            ->when(trim((string) $request->query('q')), fn ($q, $s) => $q->where(fn ($w) => $w->where('documento', 'like', "%$s%")->orWhere('nombres', 'like', "%$s%")->orWhere('apellidos', 'like', "%$s%")))
            ->orderBy('apellidos');
    }

    /** Última afiliación de cada tipo para un afiliado. */
    public static function resumen(Afiliado $a): array
    {
        return collect(['EPS', 'ARL', 'CCF', 'AFP'])->mapWithKeys(function ($t) use ($a) {
            $p = $a->preafiliaciones->firstWhere('tipo', $t);
            return [$t => $p ? ['entidad' => $p->entidad->nombre, 'estado' => $p->estado] : null];
        })->all();
    }

    public function index(Request $request)
    {
        $u = auth()->user();
        $pre = Preafiliacion::visiblesPara($u);
        $stats = [
            'Por estado' => (clone $pre)->selectRaw('estado k, count(*) n')->groupBy('estado')->pluck('n', 'k')
                ->mapWithKeys(fn ($n, $k) => [Preafiliacion::ESTADOS[$k][0] ?? $k => $n]),
            'Por entidad' => (clone $pre)->join('entidades', 'entidades.id', '=', 'preafiliaciones.entidad_id')
                ->selectRaw('entidades.nombre k, count(*) n')->groupBy('entidades.nombre')->orderByDesc('n')->limit(10)->pluck('n', 'k'),
            'Por empresa' => (clone $pre)->join('empresas', 'empresas.id', '=', 'preafiliaciones.empresa_id')
                ->selectRaw('empresas.nombre k, count(*) n')->groupBy('empresas.nombre')->orderByDesc('n')->limit(10)->pluck('n', 'k'),
        ];
        return view('reportes.index', [
            'items' => $this->consulta($request)->paginate(25)->withQueryString(),
            'stats' => $stats,
            'empresas' => Empresa::visiblesPara($u)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /** Descarga CSV (abre directo en Excel). */
    public function exportar(Request $request)
    {
        $nombre = 'reporte-afiliaciones-'.now()->format('Y-m-d').'.csv';
        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tipo doc', 'Documento', 'Nombre completo', 'Empresa', 'EPS', 'Estado EPS', 'ARL', 'Estado ARL', 'CCF', 'Estado CCF', 'AFP', 'Estado AFP', 'Última fecha'], ';');
            $this->consulta($request)->chunk(500, function ($afiliados) use ($out) {
                foreach ($afiliados as $a) {
                    $r = self::resumen($a);
                    $fila = [$a->tipo_documento, $a->documento, $a->nombre_completo, $a->empresa->nombre];
                    foreach ($r as $x) {
                        $fila[] = $x['entidad'] ?? '';
                        $fila[] = $x ? Preafiliacion::ESTADOS[$x['estado']][0] : '';
                    }
                    $fila[] = optional($a->preafiliaciones->first())->created_at?->format('d/m/Y');
                    fputcsv($out, $fila, ';');
                }
            });
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
