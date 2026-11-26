<?php

namespace App\Services;

use App\Models\Plan;

/**
 * Calcula lo que cotiza un independiente al mes según su plan y nivel de riesgo.
 * El IBC es el salario mínimo. Cada aporte se redondea al múltiplo de 100 superior, como en la PILA.
 */
class Tarifas
{
    public static function calcular(Plan $plan, int $nivelRiesgo, int $ibc): array
    {
        $pct = config('aportes.porcentajes');
        $arl = config('aportes.tarifas_arl');
        $r = fn (float $p) => (int) (ceil($ibc * $p / 100 / 100) * 100);
        $v = [
            'eps' => $plan->incluye_eps ? $r($pct['eps']) : 0,
            'afp' => $plan->incluye_afp ? $r($pct['afp']) : 0,
            'ccf' => $plan->incluye_ccf ? $r($pct['ccf']) : 0,
            'arl' => $plan->incluye_arl ? $r($arl[$nivelRiesgo] ?? $arl[1]) : 0,
        ];
        $v['total'] = array_sum($v);
        return $v;
    }
}
