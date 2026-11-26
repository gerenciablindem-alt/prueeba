<?php

namespace App\Services;

/**
 * Reparte el valor de una incapacidad entre quien la paga, según el tipo y los días acumulados.
 *
 * Reglas usadas (simplificadas, deben validarse con el equipo):
 * - Enfermedad general: días 1-2 los paga el empleador; días 3-90 la EPS; ambos al 66,67% del IBC.
 *   Días 91-180 la EPS al 50%. Días 181-540 el fondo de pensiones al 50%.
 *   El valor diario nunca es menor al salario mínimo diario.
 * - Accidente de trabajo y enfermedad laboral: el día 1 lo paga el empleador y desde el día 2 la ARL, al 100%.
 * - Licencias de maternidad y paternidad: EPS al 100%.
 * Con prórrogas, los días se cuentan sobre el acumulado de la incapacidad inicial.
 */
class CalculadoraIncapacidad
{
    public function __construct(private int $salarioMinimo) {}

    /** @return array{tramos: array<int, array>, total: float, empleador: float, recobro: float} */
    public function calcular(string $tipo, int $dias, int $ibc, int $diasPrevios = 0): array
    {
        $diario = $ibc / 30;
        $minimoDiario = $this->salarioMinimo / 30;
        $inicio = $diasPrevios + 1;
        $fin = $diasPrevios + $dias;
        $tramos = [];

        $tramo = function (string $paga, int $desde, int $hasta, float $pct) use (&$tramos, $inicio, $fin, $diario, $minimoDiario) {
            $d = max($desde, $inicio);
            $h = min($hasta, $fin);
            if ($h < $d) return;
            $valorDia = $diario * $pct;
            if ($pct < 1 && $valorDia < $minimoDiario) $valorDia = $minimoDiario;
            $tramos[] = ['paga' => $paga, 'desde' => $d, 'hasta' => $h, 'dias' => $h - $d + 1, 'porcentaje' => $pct, 'valor' => round($valorDia * ($h - $d + 1))];
        };

        switch ($tipo) {
            case 'enfermedad_general':
                $tramo('Empleador', 1, 2, 2 / 3);
                $tramo('EPS', 3, 90, 2 / 3);
                $tramo('EPS', 91, 180, 0.5);
                $tramo('Fondo de pensiones', 181, 540, 0.5);
                break;
            case 'accidente_laboral':
            case 'enfermedad_laboral':
                $tramo('Empleador', 1, 1, 1);
                $tramo('ARL', 2, PHP_INT_MAX, 1);
                break;
            default: // licencias
                $tramo('EPS', 1, PHP_INT_MAX, 1);
        }

        $total = array_sum(array_column($tramos, 'valor'));
        $empleador = array_sum(array_map(fn ($t) => $t['paga'] === 'Empleador' ? $t['valor'] : 0, $tramos));

        return ['tramos' => $tramos, 'total' => $total, 'empleador' => $empleador, 'recobro' => $total - $empleador];
    }

    /** Tipo de entidad a la que se le cobra. */
    public static function tipoEntidadCobro(string $tipo): string
    {
        return in_array($tipo, ['accidente_laboral', 'enfermedad_laboral'], true) ? 'ARL' : 'EPS';
    }
}
