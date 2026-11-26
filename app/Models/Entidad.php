<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entidad extends Model
{
    public const TIPOS = [
        'EPS' => 'Entidad Promotora de Salud',
        'ARL' => 'Aseguradora de Riesgos Laborales',
        'CCF' => 'Caja de Compensación Familiar',
        'AFP' => 'Administradora de Fondos de Pensiones',
    ];

    protected $table = 'entidades';

    protected $guarded = [];

    protected $casts = ['activa' => 'boolean'];
}
