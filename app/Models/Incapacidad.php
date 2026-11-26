<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Incapacidad extends Model
{
    use Auditable;

    public const TIPOS = [
        'enfermedad_general' => 'Enfermedad general',
        'accidente_laboral' => 'Accidente de trabajo',
        'enfermedad_laboral' => 'Enfermedad laboral',
        'licencia_maternidad' => 'Licencia de maternidad',
        'licencia_paternidad' => 'Licencia de paternidad',
    ];

    public const ESTADOS = [
        'por_radicar' => ['Por radicar', 'warn'],
        'radicada' => ['Radicada', 'info'],
        'aprobada' => ['Aprobada', 'ok'],
        'pagada' => ['Pagada', 'ok'],
        'rechazada' => ['Rechazada', 'bad'],
    ];

    protected string $moduloAuditoria = 'Incapacidades';

    protected $table = 'incapacidades';

    protected $guarded = [];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_radicacion' => 'date', 'fecha_pago' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (self $i) {
            if (! $i->codigo) {
                $ultimo = static::orderByDesc('id')->value('codigo');
                $i->codigo = sprintf('INC-%05d', $ultimo ? ((int) substr($ultimo, 4)) + 1 : 1);
            }
        });
    }

    public function afiliado() { return $this->belongsTo(Afiliado::class); }
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function entidad() { return $this->belongsTo(Entidad::class); }
    public function prorrogaDe() { return $this->belongsTo(self::class, 'prorroga_de_id'); }
    public function prorrogas() { return $this->hasMany(self::class, 'prorroga_de_id'); }
    public function documentos() { return $this->morphMany(Documento::class, 'documentable'); }

    public function getFechaFinAttribute()
    {
        return $this->fecha_inicio->copy()->addDays($this->dias - 1);
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function scopeVisiblesPara(Builder $q, User $u): Builder
    {
        return $u->esEmpresa() ? $q->where('empresa_id', $u->empresa_id) : $q;
    }
}
