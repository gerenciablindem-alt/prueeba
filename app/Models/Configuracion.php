<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function valor(string $clave, mixed $defecto = null): mixed
    {
        return static::find($clave)?->valor ?? $defecto;
    }

    public static function salarioMinimo(): int
    {
        return (int) static::valor('salario_minimo', config('aportes.salario_minimo'));
    }
}
