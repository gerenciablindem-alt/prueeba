<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Traslado extends Model
{
    use Auditable;

    public const ESTADOS = [
        'solicitado' => ['Solicitado', 'warn'],
        'en_tramite' => ['En trámite', 'info'],
        'aprobado' => ['Aprobado', 'ok'],
        'rechazado' => ['Rechazado', 'bad'],
    ];

    protected string $moduloAuditoria = 'Traslados';

    protected $guarded = [];

    protected $casts = ['fecha_solicitud' => 'date', 'fecha_efectiva' => 'date'];

    public function afiliado() { return $this->belongsTo(Afiliado::class); }
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function origen() { return $this->belongsTo(Entidad::class, 'entidad_origen_id'); }
    public function destino() { return $this->belongsTo(Entidad::class, 'entidad_destino_id'); }

    public function descripcionAuditoria(): string
    {
        return 'Traslado #'.$this->id;
    }

    public function scopeVisiblesPara(Builder $q, User $u): Builder
    {
        return $u->esEmpresa() ? $q->where('empresa_id', $u->empresa_id) : $q;
    }
}
