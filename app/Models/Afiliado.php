<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Afiliado extends Model
{
    use Auditable;

    protected string $moduloAuditoria = 'Afiliados';

    protected $guarded = [];

    protected $casts = ['fecha_nacimiento' => 'date', 'fecha_expedicion' => 'date', 'fecha_ingreso' => 'date'];

    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function cargo() { return $this->belongsTo(Cargo::class); }
    public function preafiliaciones() { return $this->hasMany(Preafiliacion::class); }
    public function traslados() { return $this->hasMany(Traslado::class); }
    public function incapacidades() { return $this->hasMany(Incapacidad::class); }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function descripcionAuditoria(): string
    {
        return $this->nombre_completo.' ('.$this->documento.')';
    }

    public function scopeVisiblesPara(Builder $q, User $u): Builder
    {
        return $u->esEmpresa() ? $q->where('empresa_id', $u->empresa_id) : $q;
    }
}
