<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use App\Models\Plan;
use App\Models\Tarifario;
use App\Services\Tarifas;
use Illuminate\Database\Seeder;

/** Planes base y un tarifario publicado del año en curso, si todavía no hay ninguno. */
class PlanesSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            ['SA', 'Salud + ARL', 'Independiente con salud y riesgos laborales', true, false, true, false],
            ['SAP', 'Salud + ARL + Pensión', 'Cobertura completa sin caja de compensación', true, true, true, false],
            ['SAPC', 'Completo con caja', 'Salud, ARL, pensión y caja de compensación', true, true, true, true],
            ['ARL', 'Solo ARL', 'Para quien ya cotiza salud y pensión por otro lado', false, false, true, false],
        ];
        foreach ($planes as $i => [$codigo, $nombre, $desc, $eps, $afp, $arl, $ccf]) {
            Plan::firstOrCreate(['codigo' => $codigo], ['nombre' => $nombre, 'descripcion' => $desc, 'incluye_eps' => $eps, 'incluye_afp' => $afp, 'incluye_arl' => $arl, 'incluye_ccf' => $ccf, 'orden' => $i + 1]);
        }

        if (Tarifario::exists()) {
            return;
        }
        $smlv = Configuracion::salarioMinimo();
        $t = Tarifario::create(['anio' => now()->year, 'smlv' => $smlv, 'vigente_desde' => now()->startOfYear(), 'estado' => 'publicado', 'publicado_at' => now()]);
        foreach (Plan::all() as $plan) {
            foreach (array_keys(config('aportes.tarifas_arl')) as $nivel) {
                $t->celdas()->create(['plan_id' => $plan->id, 'nivel_riesgo' => $nivel] + Tarifas::calcular($plan, $nivel, $smlv));
            }
        }
    }
}
