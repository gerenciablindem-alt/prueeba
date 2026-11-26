<?php

return [
    // Salario mínimo mensual por defecto. Se puede cambiar en Configuración dentro de la app.
    'salario_minimo' => (int) env('APORTES_SALARIO_MINIMO', 1423500),
    // Días sin movimiento a partir de los cuales un radicado genera alerta.
    'dias_alerta_radicado' => (int) env('APORTES_DIAS_ALERTA', 7),
];
