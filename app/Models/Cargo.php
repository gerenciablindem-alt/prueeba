<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use Auditable;

    protected string $moduloAuditoria = 'Empresas';

    protected $guarded = [];

    public function empresa() { return $this->belongsTo(Empresa::class); }
}
