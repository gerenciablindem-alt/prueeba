<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $q = Auditoria::with('user', 'empresa')
            ->when($request->query('modulo'), fn ($q, $v) => $q->where('modulo', $v))
            ->when($request->query('empresa_id'), fn ($q, $v) => $q->where('empresa_id', $v))
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('evento'), fn ($q, $v) => $q->where('evento', $v))
            ->when($request->query('desde'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->query('hasta'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
        return view('auditoria.index', [
            'items' => $q->latest('created_at')->latest('id')->paginate(30)->withQueryString(),
            'modulos' => Auditoria::distinct()->orderBy('modulo')->pluck('modulo'),
            'empresas' => Empresa::orderBy('nombre')->get(['id', 'nombre']),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
