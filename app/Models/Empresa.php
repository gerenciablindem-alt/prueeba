<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use Auditable;

    protected string $moduloAuditoria = 'Empresas';

    protected $guarded = [];

    protected $casts = ['activa' => 'boolean'];

    public function sede() { return $this->belongsTo(Sede::class); }
    public function cargos() { return $this->hasMany(Cargo::class); }
    public function afiliados() { return $this->hasMany(Afiliado::class); }
    public function entidades() { return $this->belongsToMany(Entidad::class, 'empresa_entidad'); }

    /** Campos que faltan para radicar sin devoluciones. */
    public function datosFaltantes(): array
    {
        $f = [];
        if (! $this->ciiu) $f[] = 'CIIU';
        if (! $this->direccion) $f[] = 'Dirección';
        if (! $this->telefono) $f[] = 'Teléfono';
        if (! $this->email) $f[] = 'Correo';
        if (($this->cargos_count ?? $this->cargos()->count()) === 0) $f[] = 'Cargos';
        return $f;
    }

    public function scopeVisiblesPara(Builder $q, User $u): Builder
    {
        return $u->esEmpresa() ? $q->whereKey($u->empresa_id) : $q;
    }
}
