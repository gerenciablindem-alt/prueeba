<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    public const ESTADOS = [
        'pendiente' => ['Pendiente', 'warn'],
        'vencida' => ['Vencida', 'bad'],
        'pagada' => ['Pagada', 'ok'],
        'anulada' => ['Anulada', 'mute'],
    ];

    protected $guarded = [];
    protected $casts = ['periodo' => 'date', 'fecha_limite' => 'date'];

    public function venta() { return $this->belongsTo(Venta::class); }
    public function recibos() { return $this->hasMany(Recibo::class); }
    public function reciboVigente() { return $this->hasOne(Recibo::class)->whereIn('estado', ['emitido', 'pagado'])->latestOfMany(); }

    /** Estado que se muestra: una cuota pendiente cuya fecha límite ya pasó aparece como vencida. */
    public function getEstadoVisibleAttribute(): string
    {
        return $this->estado === 'pendiente' && $this->fecha_limite->lt(today()) ? 'vencida' : $this->estado;
    }

    public function getPeriodoNombreAttribute(): string
    {
        return ucfirst($this->periodo->translatedFormat('F Y'));
    }
}
