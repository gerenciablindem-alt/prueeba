<?php

namespace Tests\Unit;

use App\Services\CalculadoraIncapacidad;
use PHPUnit\Framework\TestCase;

class CalculadoraIncapacidadTest extends TestCase
{
    private CalculadoraIncapacidad $c;

    protected function setUp(): void
    {
        $this->c = new CalculadoraIncapacidad(1500000);
    }

    public function test_enfermedad_general_reparte_empleador_y_eps(): void
    {
        $r = $this->c->calcular('enfermedad_general', 10, 3000000);
        $this->assertSame(['Empleador', 'EPS'], array_column($r['tramos'], 'paga'));
        $this->assertSame([2, 8], array_column($r['tramos'], 'dias'));
        // 3.000.000 / 30 = 100.000 diarios * 2/3
        $this->assertEquals(round(100000 * 2 / 3 * 2), $r['empleador']);
        $this->assertEquals(round(100000 * 2 / 3 * 8), $r['recobro']);
    }

    public function test_nunca_paga_menos_del_minimo_diario(): void
    {
        $r = $this->c->calcular('enfermedad_general', 5, 1500000);
        $this->assertEquals(50000 * 5, $r['total']);
    }

    public function test_prorroga_continua_tramos_acumulados(): void
    {
        $r = $this->c->calcular('enfermedad_general', 30, 3000000, 80);
        $this->assertSame([[81, 90], [91, 110]], array_map(fn ($t) => [$t['desde'], $t['hasta']], $r['tramos']));
        $this->assertSame(0.5, $r['tramos'][1]['porcentaje']);
        $this->assertEquals(0, $r['empleador']);
    }

    public function test_despues_del_dia_180_paga_el_fondo(): void
    {
        $r = $this->c->calcular('enfermedad_general', 10, 3000000, 175);
        $this->assertSame(['EPS', 'Fondo de pensiones'], array_column($r['tramos'], 'paga'));
    }

    public function test_accidente_laboral_al_100_por_ciento(): void
    {
        $r = $this->c->calcular('accidente_laboral', 10, 3000000);
        $this->assertEquals(100000, $r['empleador']);
        $this->assertEquals(900000, $r['recobro']);
    }

    public function test_licencia_maternidad_la_paga_la_eps(): void
    {
        $r = $this->c->calcular('licencia_maternidad', 126, 3000000);
        $this->assertEquals(12600000, $r['recobro']);
        $this->assertEquals(0, $r['empleador']);
    }
}
