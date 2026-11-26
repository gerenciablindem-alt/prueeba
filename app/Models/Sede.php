<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use Auditable;

    protected string $moduloAuditoria = 'Sedes';

    protected $guarded = [];

    protected $casts = ['activa' => 'boolean'];

    public function empresas() { return $this->hasMany(Empresa::class); }
}
