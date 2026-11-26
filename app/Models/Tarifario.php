<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Tarifario extends Model
{
    use Auditable;

    public const ESTADOS = [
        'borrador' => ['Borrador', 'warn'],
        'publicado' => ['Publicado', 'ok'],
        'archivado' => ['Archivado', 'mute'],
    ];

    protected string $moduloAuditoria = 'Tarifarios';
    protected $guarded = [];
    protected $casts = ['vigente_desde' => 'date', 'publicado_at' => 'datetime'];

    public function celdas() { return $this->hasMany(TarifarioCelda::class); }

    public function descripcionAuditoria(): string { return 'Tarifario '.$this->anio; }

    /** El tarifario publicado más reciente. */
    public static function vigente(): ?self
    {
        return static::where('estado', 'publicado')->orderByDesc('vigente_desde')->first();
    }
}
