<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Preafiliacion extends Model
{
    use Auditable;

    public const ESTADOS = [
        'pendiente' => ['Pendiente', 'warn'],
        'revision' => ['En revisión', 'info'],
        'correccion' => ['Requiere corrección', 'bad'],
        'aprobado' => ['Aprobada', 'ok'],
        'rechazado' => ['Rechazada', 'bad'],
    ];

    protected string $moduloAuditoria = 'Afiliaciones';

    protected $table = 'preafiliaciones';

    protected $guarded = [];

    protected $casts = ['fecha_afiliacion' => 'date', 'estado_cambiado_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->radicado ??= static::siguienteRadicado();
            $p->estado_cambiado_at ??= now();
        });
        static::updating(function (self $p) {
            if ($p->isDirty('estado')) {
                $p->estado_cambiado_at = now();
            }
        });
    }

    public static function siguienteRadicado(): string
    {
        $anio = now()->year;
        $ultimo = static::where('radicado', 'like', "PA-$anio-%")->orderByDesc('radicado')->value('radicado');
        $n = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;
        return sprintf('PA-%d-%06d', $anio, $n);
    }

    public function afiliado() { return $this->belongsTo(Afiliado::class); }
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function entidad() { return $this->belongsTo(Entidad::class); }
    public function creador() { return $this->belongsTo(User::class, 'creado_por'); }
    public function documentos() { return $this->morphMany(Documento::class, 'documentable'); }

    public function diasEnEstado(): int
    {
        return (int) ($this->estado_cambiado_at ?? $this->created_at)->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function scopeVisiblesPara(Builder $q, User $u): Builder
    {
        return $u->esEmpresa() ? $q->where('empresa_id', $u->empresa_id) : $q;
    }
}
