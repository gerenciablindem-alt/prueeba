<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => User::with('empresa', 'sede')->orderBy('name')->get()]);
    }

    private function datos(Request $request, ?User $u = null): array
    {
        $datos = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($u?->id)],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'sede_id' => 'nullable|exists:sedes,id',
            'empresa_id' => 'nullable|required_if:rol,empresa|exists:empresas,id',
            'password' => [$u ? 'nullable' : 'required', Password::min(8)],
        ], ['empresa_id.required_if' => 'Elige la empresa a la que pertenece este usuario.']);
        if ($datos['rol'] !== 'empresa') $datos['empresa_id'] = null;
        if (empty($datos['password'])) unset($datos['password']);
        $datos['activo'] = $request->boolean('activo');
        return $datos;
    }

    private function form(User $u)
    {
        return view('usuarios.form', ['u' => $u, 'empresas' => Empresa::orderBy('nombre')->get(['id', 'nombre']), 'sedes' => Sede::orderBy('nombre')->get()]);
    }

    public function create() { return $this->form(new User(['activo' => true, 'rol' => 'operador'])); }
    public function edit(User $usuario) { return $this->form($usuario); }

    public function store(Request $request)
    {
        User::create($this->datos($request));
        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $this->datos($request, $usuario);
        if ($usuario->id === auth()->id() && (! $datos['activo'] || $datos['rol'] !== 'admin')) {
            return back()->withErrors(['rol' => 'No puedes quitarte el rol de administrador ni desactivarte a ti mismo.']);
        }
        $usuario->update($datos);
        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }
}
