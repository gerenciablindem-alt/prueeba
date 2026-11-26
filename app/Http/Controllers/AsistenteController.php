<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Services\Asistente\Asistente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AsistenteController extends Controller
{
    private const MAX_TURNOS = 12;

    public function index(Request $request)
    {
        return view('asistente.index', ['historial' => $request->session()->get('asistente', []), 'disponible' => Asistente::disponible()]);
    }

    public function mensaje(Request $request, Asistente $asistente)
    {
        $d = $request->validate(['pregunta' => 'required|string|max:2000']);
        if (! Asistente::disponible()) {
            return response()->json(['texto' => 'El asistente no está activado. Un administrador debe poner la clave de API en Configuración.', 'error' => true], 422);
        }
        $limite = (int) config('aportes.ia_limite_diario');
        $clave = 'ia:consultas:'.now()->format('Y-m-d');
        Cache::add($clave, 0, now()->endOfDay());
        if (Cache::increment($clave) > $limite) {
            return response()->json(['texto' => "Se alcanzó el límite de $limite consultas de hoy. Mañana se reinicia; un administrador puede cambiarlo.", 'error' => true], 429);
        }

        @set_time_limit(180);
        $historial = $request->session()->get('asistente', []);
        $r = $asistente->responder($request->user(), $historial, $d['pregunta']);
        if (empty($r['error'])) {
            $historial[] = ['role' => 'user', 'content' => $d['pregunta']];
            $historial[] = ['role' => 'assistant', 'content' => $r['texto']];
            $request->session()->put('asistente', array_slice($historial, -self::MAX_TURNOS * 2));
        }
        return response()->json($r + ['html' => (string) Str::markdown($r['texto'], ['html_input' => 'escape', 'allow_unsafe_links' => false])]);
    }

    public function reiniciar(Request $request)
    {
        $request->session()->forget('asistente');
        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    /** Solo administradores: guarda o quita la clave de API (cifrada con la llave de la app). */
    public function guardarClave(Request $request)
    {
        $d = $request->validate(['anthropic_api_key' => 'nullable|string|max:300', 'quitar' => 'nullable|boolean']);
        if ($request->boolean('quitar')) {
            Configuracion::where('clave', 'anthropic_api_key')->delete();
            return back()->with('ok', 'Clave de IA eliminada. El asistente quedó desactivado.');
        }
        $k = trim((string) ($d['anthropic_api_key'] ?? ''));
        if ($k === '') return back()->withErrors(['anthropic_api_key' => 'Pega la clave de API.']);
        Configuracion::updateOrCreate(['clave' => 'anthropic_api_key'], ['valor' => Crypt::encryptString($k)]);
        return back()->with('ok', 'Clave de IA guardada. El asistente ya está activo.');
    }
}
