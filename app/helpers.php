<?php

if (! function_exists('pesos')) {
    function pesos($valor): string
    {
        return '$ '.number_format((float) $valor, 0, ',', '.');
    }
}

if (! function_exists('pill')) {
    /** Etiqueta de estado con color, a partir de un mapa ESTADOS [clave => [texto, color]]. */
    function pill(string $estado, array $mapa): \Illuminate\Support\HtmlString
    {
        [$texto, $color] = $mapa[$estado] ?? [$estado, 'mute'];
        return new \Illuminate\Support\HtmlString('<span class="pill p-'.e($color).'">'.e($texto).'</span>');
    }
}
