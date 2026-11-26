<?php

namespace Database\Seeders;

use App\Models\Entidad;
use Illuminate\Database\Seeder;

/** Catálogo inicial de entidades. Se puede ampliar desde Configuración. */
class EntidadesSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'EPS' => ['Nueva EPS', 'EPS Sura', 'Sanitas', 'Salud Total', 'Coosalud', 'Compensar EPS', 'Famisanar', 'S.O.S. EPS', 'Emssanar', 'Asmet Salud', 'Mutual Ser', 'Aliansalud'],
            'ARL' => ['ARL Sura', 'Positiva', 'Colmena Seguros', 'AXA Colpatria', 'Seguros Bolívar', 'La Equidad Seguros', 'Seguros de Vida Alfa'],
            'CCF' => ['Comfandi', 'Comfenalco Valle', 'Compensar', 'Colsubsidio', 'Cafam', 'Comfama', 'Comfenalco Antioquia'],
            'AFP' => ['Colpensiones', 'Porvenir', 'Protección', 'Colfondos', 'Skandia'],
        ];
        foreach ($catalogo as $tipo => $nombres) {
            foreach ($nombres as $n) {
                Entidad::firstOrCreate(['tipo' => $tipo, 'nombre' => $n]);
            }
        }
    }
}
