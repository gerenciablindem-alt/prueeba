<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use Auditable;

    public const ESTADOS = [
        'activa' => ['Activa', 'ok'],
        'retirada' => ['Retirada', 'mute'],
    ];

    public const NIVELES = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];

    protected string $moduloAuditoria = 'Ventas';
    protected $guarded = [];
    protected $casts = ['periodo_inicial' => 'date', 'fecha_retiro' => 'date'];

    public function sede() { return $this->belongsTo(Sede::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
    public function tarifario() { return $this->belongsTo(Tarifario::class); }
    public function cuotas() { return $this->hasMany(Cuota::class)->orderByDesc('periodo'); }
    public function recibos() { return $this->hasMany(Recibo::class)->latest('id'); }

    public function getAportesAttribute(): int
    {
        return $this->eps + $this->afp + $this->ccf + $this->arl;
    }

    public function descripcionAuditoria(): string
    {
        return 'Venta #'.$this->id.' · '.$this->nombre_cliente;
    }

    /** ¿Debe cotizar en el periodo dado (primer día del mes)? */
    public function cotizaEn(\Carbon\CarbonInterface $periodo): bool
    {
        if ($periodo->lt($this->periodo_inicial->copy()->startOfMonth())) return false;
        if ($this->estado === 'retirada' && $this->fecha_retiro && $periodo->gt($this->fecha_retiro->copy()->startOfMonth())) return false;
        return true;
    }
}
