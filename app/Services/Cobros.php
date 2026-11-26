<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Recibo;
use App\Models\Venta;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Genera cuotas mensuales y recibos, y registra o anula pagos. */
class Cobros
{
    /** Crea la cuota del periodo para una venta si no existe. Devuelve null si no aplica. */
    public function cuotaPara(Venta $v, CarbonInterface $periodo): ?Cuota
    {
        $periodo = $periodo->copy()->startOfMonth();
        if (! $v->cotizaEn($periodo)) return null;
        $existente = Cuota::where('venta_id', $v->id)->whereDate('periodo', $periodo->toDateString())->first();
        if ($existente) return $existente;
        return Cuota::create(['venta_id' => $v->id, 'periodo' => $periodo->toDateString(),
            'aportes' => $v->aportes,
            'administracion' => $v->administracion,
            'valor' => $v->valor_total,
            'estado' => 'pendiente',
            'fecha_limite' => $periodo->copy()->day(min(config('aportes.dia_limite_pago'), $periodo->daysInMonth))->toDateString(),
        ]);
    }

    /** Genera las cuotas del periodo para todas las ventas activas. */
    public function generarPeriodo(CarbonInterface $periodo, ?int $sedeId = null): array
    {
        $r = ['generadas' => 0, 'ya_existian' => 0, 'omitidas' => 0];
        Venta::where('estado', 'activa')->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))->each(function (Venta $v) use ($periodo, &$r) {
            $c = $this->cuotaPara($v, $periodo);
            $r[$c === null ? 'omitidas' : ($c->wasRecentlyCreated ? 'generadas' : 'ya_existian')]++;
        });
        return $r;
    }

    /** Emite el recibo de una cuota, o devuelve el que ya tiene. */
    public function emitirRecibo(Cuota $c, ?int $userId = null): Recibo
    {
        if ($c->estado === 'anulada') {
            throw new \DomainException('La cuota está anulada.');
        }
        return DB::transaction(function () use ($c, $userId) {
            if ($r = $c->reciboVigente()->lockForUpdate()->first()) return $r;
            $v = $c->venta;
            $n = (int) Recibo::where('sede_id', $v->sede_id)->lockForUpdate()->max('consecutivo') + 1;
            return Recibo::create([
                'sede_id' => $v->sede_id, 'consecutivo' => $n,
                'numero' => config('aportes.prefijo_recibo').$v->sede_id.'-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                'cuota_id' => $c->id, 'venta_id' => $v->id, 'valor' => $c->valor, 'estado' => 'emitido', 'generado_por' => $userId,
            ]);
        });
    }

    public function registrarPago(Recibo $r, array $datos, ?int $userId = null): void
    {
        if ($r->estado !== 'emitido') {
            throw new \DomainException('Solo se puede registrar el pago de un recibo por pagar.');
        }
        DB::transaction(function () use ($r, $datos, $userId) {
            $r->update([
                'estado' => 'pagado', 'fecha_pago' => $datos['fecha_pago'], 'medio_pago' => $datos['medio_pago'],
                'referencia' => $datos['referencia'] ?? null, 'valor_pagado' => $datos['valor_pagado'] ?? $r->valor,
                'pago_registrado_por' => $userId,
            ]);
            $r->cuota->update(['estado' => 'pagada']);
        });
    }

    public function anular(Recibo $r, string $motivo, ?int $userId = null): void
    {
        if ($r->estado === 'anulado') return;
        DB::transaction(function () use ($r, $motivo, $userId) {
            $r->update(['estado' => 'anulado', 'anulado_at' => now(), 'motivo_anulacion' => $motivo, 'anulado_por' => $userId]);
            $r->cuota->update(['estado' => 'pendiente']);
        });
    }
}
