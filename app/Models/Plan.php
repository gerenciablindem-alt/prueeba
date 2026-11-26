<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use Auditable;

    protected $table = 'planes';
    protected string $moduloAuditoria = 'Planes';
    protected $guarded = [];
    protected $casts = ['incluye_eps' => 'boolean', 'incluye_afp' => 'boolean', 'incluye_arl' => 'boolean', 'incluye_ccf' => 'boolean', 'activo' => 'boolean'];

    /** Subsistemas que cubre el plan, p. ej. ['EPS', 'ARL']. */
    public function subsistemas(): array
    {
        return array_keys(array_filter(['EPS' => $this->incluye_eps, 'AFP' => $this->incluye_afp, 'ARL' => $this->incluye_arl, 'CCF' => $this->incluye_ccf]));
    }
}
