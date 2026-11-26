<?php

return [
    // Salario mínimo mensual por defecto. Se puede cambiar en Configuración dentro de la app.
    'salario_minimo' => (int) env('APORTES_SALARIO_MINIMO', 1423500),
    // Días sin movimiento a partir de los cuales un radicado genera alerta.
    'dias_alerta_radicado' => (int) env('APORTES_DIAS_ALERTA', 7),

    // Ventas de planes: porcentajes de cotización de independientes sobre el IBC.
    // Revísalos cada año. Se aplican al crear un tarifario nuevo.
    'porcentajes' => [
        'eps' => 12.5,
        'afp' => 16.0,
        'ccf' => 2.0,
    ],
    // Tarifa ARL por nivel de riesgo (I a V), en porcentaje.
    'tarifas_arl' => [1 => 0.522, 2 => 1.044, 3 => 2.436, 4 => 4.350, 5 => 6.960],
    // Valor de administración mensual sugerido para una venta nueva.
    'administracion' => (int) env('APORTES_ADMINISTRACION', 35000),
    // Día del mes en que vence la cuota del periodo.
    'dia_limite_pago' => (int) env('APORTES_DIA_LIMITE', 10),
    // Prefijo de los recibos de caja.
    'prefijo_recibo' => env('APORTES_PREFIJO_RECIBO', 'RC'),
];
