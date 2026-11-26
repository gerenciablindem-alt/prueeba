<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use Illuminate\Http\Request;

class AfiliadoController extends Controller
{
    /** Consulta de afiliaciones: busca por documento o nombre y muestra el estado en cada entidad. */
    public function index(Request $request)
    {
        $u = auth()->user();
        $s = trim((string) $request->query('q'));
        $items = Afiliado::visiblesPara($u)->with('empresa', 'preafiliaciones.entidad')
            ->when($s, fn ($q) => $q->where(fn ($w) => $w->where('documento', 'like', "%$s%")
                ->orWhere('nombres', 'like', "%$s%")->orWhere('apellidos', 'like', "%$s%")))
            ->orderBy('apellidos')->paginate(25)->withQueryString();
        return view('afiliados.index', compact('items', 's'));
    }

    public function show(Afiliado $afiliado)
    {
        $this->autorizarEmpresa($afiliado);
        $afiliado->load('empresa', 'cargo', 'preafiliaciones.entidad', 'traslados.origen', 'traslados.destino', 'incapacidades');
        return view('afiliados.show', ['a' => $afiliado]);
    }

    /** Busca un documento para avisar de afiliaciones existentes y autocompletar datos. */
    public function buscar(Request $request)
    {
        $doc = preg_replace('/\D/', '', (string) $request->query('documento'));
        if (strlen($doc) < 5) return response()->json(null);
        $a = Afiliado::visiblesPara(auth()->user())->with('preafiliaciones.entidad', 'empresa')->where('documento', $doc)->latest()->first();
        if (! $a) return response()->json(null);
        return response()->json([
            'nombres' => $a->nombres, 'apellidos' => $a->apellidos, 'tipo_documento' => $a->tipo_documento,
            'fecha_nacimiento' => $a->fecha_nacimiento?->format('Y-m-d'), 'fecha_expedicion' => $a->fecha_expedicion?->format('Y-m-d'),
            'sexo' => $a->sexo, 'telefono' => $a->telefono, 'email' => $a->email, 'direccion' => $a->direccion, 'ciudad' => $a->ciudad,
            'empresa' => $a->empresa->nombre,
            'afiliaciones' => $a->preafiliaciones->map(fn ($p) => [
                'radicado' => $p->radicado, 'tipo' => $p->tipo, 'entidad' => $p->entidad->nombre,
                'estado' => \App\Models\Preafiliacion::ESTADOS[$p->estado][0], 'fecha' => $p->created_at->format('d/m/Y'),
            ]),
        ]);
    }
}
