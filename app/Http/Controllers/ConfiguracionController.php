<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Entidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfiguracionController extends Controller
{
    public function edit()
    {
        return view('configuracion', ['salarioMinimo' => Configuracion::salarioMinimo(), 'entidades' => Entidad::orderBy('tipo')->orderBy('nombre')->get()]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'salario_minimo' => 'nullable|integer|min:100000',
            'nueva_entidad_tipo' => ['nullable', Rule::in(array_keys(Entidad::TIPOS))],
            'nueva_entidad_nombre' => 'nullable|required_with:nueva_entidad_tipo|string|max:120',
            'nueva_entidad_codigo' => 'nullable|string|max:20',
        ]);
        if (! empty($datos['salario_minimo'])) {
            Configuracion::updateOrCreate(['clave' => 'salario_minimo'], ['valor' => $datos['salario_minimo']]);
        }
        if (! empty($datos['nueva_entidad_nombre'])) {
            Entidad::create(['tipo' => $datos['nueva_entidad_tipo'], 'nombre' => $datos['nueva_entidad_nombre'], 'codigo' => $datos['nueva_entidad_codigo'] ?? null]);
        }
        return back()->with('ok', 'Configuración guardada.');
    }
}
