<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auditorias';

    protected $guarded = [];

    protected $casts = ['cambios' => 'array', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function empresa() { return $this->belongsTo(Empresa::class); }
}
