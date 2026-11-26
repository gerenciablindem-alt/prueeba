<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaClave;

class AuthController extends Controller
{
    public function login() { return view('auth.login'); }

    public function autenticar(Request $request)
    {
        $datos = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($datos + ['activo' => true], $request->boolean('recordar'))) {
            return back()->withErrors(['email' => 'El correo o la contraseña no coinciden.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function olvido() { return view('auth.olvido'); }

    public function enviarEnlace(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        // Mismo mensaje exista o no el correo, para no revelar qué cuentas existen.
        return back()->with('ok', 'Si el correo está registrado, te enviamos un enlace para crear una nueva contraseña.');
    }

    public function restablecer(Request $request, string $token)
    {
        return view('auth.restablecer', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function guardarClave(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', ReglaClave::min(8)]]);
        $estado = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        return $estado === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('ok', 'Tu contraseña se actualizó. Ya puedes entrar.')
            : back()->withErrors(['email' => 'El enlace no es válido o ya venció. Pide uno nuevo.']);
    }

    public function perfil() { return view('auth.perfil', ['u' => auth()->user()]); }

    public function actualizarPerfil(Request $request)
    {
        $u = $request->user();
        $datos = $request->validate([
            'name' => 'required|string|max:120',
            'password_actual' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', ReglaClave::min(8)],
        ]);
        $u->name = $datos['name'];
        if (! empty($datos['password'])) $u->password = $datos['password'];
        $u->save();
        return back()->with('ok', 'Perfil actualizado.');
    }
}
