<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    use Auditable;

    public const ESTADOS = [
        'emitido' => ['Por pagar', 'warn'],
        'pagado' => ['Pagado', 'ok'],
        'anulado' => ['Anulado', 'mute'],
    ];

    public const MEDIOS = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'consignacion' => 'Consignación',
        'pse' => 'PSE',
        'datafono' => 'Datáfono',
    ];

    protected string $moduloAuditoria = 'Recibos';
    protected $guarded = [];
    protected $casts = ['fecha_pago' => 'date', 'anulado_at' => 'datetime'];

    public function sede() { return $this->belongsTo(Sede::class); }
    public function cuota() { return $this->belongsTo(Cuota::class); }
    public function venta() { return $this->belongsTo(Venta::class); }
    public function generador() { return $this->belongsTo(User::class, 'generado_por'); }

    public function descripcionAuditoria(): string { return 'Recibo '.$this->numero; }
}
