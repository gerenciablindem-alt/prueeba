<?php

namespace Database\Seeders;

use App\Models\Afiliado;
use App\Models\Empresa;
use App\Models\Entidad;
use App\Models\Incapacidad;
use App\Models\Preafiliacion;
use App\Models\Sede;
use App\Models\Traslado;
use App\Models\User;
use App\Services\CalculadoraIncapacidad;
use Illuminate\Database\Seeder;

/**
 * Datos FICTICIOS para probar la app: php artisan db:seed --class=DemoSeeder
 * No usar en producción.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        $sede = Sede::first();
        $sede2 = Sede::firstOrCreate(['nombre' => 'Sede Norte'], ['tipo_documento' => 'NIT', 'documento' => '900000001', 'departamento' => 'Valle del Cauca', 'ciudad' => 'Tuluá']);

        $demoAdmin = User::firstOrCreate(['email' => 'demo@example.com'], ['name' => 'Usuario Demo', 'password' => 'demo12345', 'rol' => 'admin', 'sede_id' => $sede->id, 'activo' => true]);
        auth()->login($demoAdmin);

        $nombresEmp = ['Construcciones Andina SAS', 'Logística del Pacífico SAS', 'Acabados Horizonte SAS', 'Servicios Integrales Río Claro', 'Textiles La Ceiba SAS', 'Obras y Montajes del Valle SAS'];
        $empresas = collect($nombresEmp)->map(function ($n, $i) use ($sede, $sede2) {
            $e = Empresa::firstOrCreate(['documento' => '9012345'.str_pad($i, 2, '0', STR_PAD_LEFT)], [
                'nombre' => $n, 'sede_id' => $i % 3 === 2 ? $sede2->id : $sede->id, 'tipo_documento' => 'NIT',
                'ciiu' => $i === 1 ? null : '4111', 'actividad_economica' => 'Construcción de edificios residenciales',
                'direccion' => 'Calle '.(10 + $i).' # 5-20', 'telefono' => $i === 3 ? null : '602555000'.$i, 'email' => 'contacto'.$i.'@example.com',
            ]);
            if ($i !== 4) {
                $e->cargos()->firstOrCreate(['nombre' => 'Auxiliar de obra'], ['riesgo_arl' => 5]);
                $e->cargos()->firstOrCreate(['nombre' => 'Administrativo'], ['riesgo_arl' => 1]);
            }
            $e->entidades()->sync(Entidad::whereIn('nombre', ['ARL Sura', 'Comfandi'])->pluck('id'));
            return $e;
        });

        User::firstOrCreate(['email' => 'empresa@example.com'], ['name' => 'Cliente Demo', 'password' => 'demo12345', 'rol' => 'empresa', 'empresa_id' => $empresas[0]->id, 'activo' => true]);

        $nombres = ['Laura', 'Jorge', 'Valentina', 'Andrés', 'Camila', 'Daniel', 'Patricia', 'Sebastián', 'Natalia', 'Julián', 'Paola', 'Felipe', 'Diana', 'Óscar', 'Carolina', 'Mauricio', 'Luisa', 'Esteban'];
        $apellidos = ['Valencia Ríos', 'Castaño Mejía', 'Ocampo Díaz', 'Murillo Caicedo', 'Herrera Gil', 'Rentería Mosquera', 'Gómez Lozano', 'Lasso Arango', 'Quintero Paz', 'Cárdenas Vélez'];
        $estados = ['aprobado', 'aprobado', 'aprobado', 'aprobado', 'pendiente', 'revision', 'correccion', 'rechazado'];
        $ent = Entidad::all()->groupBy('tipo');
        mt_srand(7);

        foreach (range(1, 36) as $k) {
            $e = $empresas[$k % $empresas->count()];
            $a = Afiliado::firstOrCreate(['empresa_id' => $e->id, 'tipo_documento' => 'CC', 'documento' => (string) (1100000000 + $k * 7919)], [
                'nombres' => $nombres[$k % count($nombres)], 'apellidos' => $apellidos[$k % count($apellidos)],
                'fecha_nacimiento' => now()->subYears(20 + $k % 30)->subDays($k * 11), 'sexo' => $k % 2 ? 'M' : 'F',
                'telefono' => '300555'.str_pad($k, 4, '0', STR_PAD_LEFT), 'ciudad' => 'Cali', 'cargo_id' => $e->cargos()->value('id'),
                'salario' => [1423500, 1750000, 2100000, 2600000][$k % 4], 'fecha_ingreso' => now()->subDays($k * 9),
            ]);
            foreach (['EPS', 'ARL', 'CCF', 'AFP'] as $j => $tipo) {
                if ($tipo === 'AFP' && $k % 3) continue;
                if ($tipo === 'CCF' && $k % 2) continue;
                $estado = $estados[($k + $j) % count($estados)];
                $creado = now()->subDays(($k * 3 + $j) % 60)->setTime(9, 0);
                $p = new Preafiliacion([
                    'afiliado_id' => $a->id, 'empresa_id' => $e->id, 'entidad_id' => $ent[$tipo][($k + $j) % $ent[$tipo]->count()]->id, 'tipo' => $tipo,
                    'estado' => $estado, 'creado_por' => $demoAdmin->id,
                    'observaciones' => $estado === 'correccion' ? 'La copia de la cédula no es legible.' : ($estado === 'rechazado' ? 'El afiliado ya tiene una afiliación activa en otra EPS.' : null),
                    'estado_cambiado_at' => $creado,
                ]);
                $p->created_at = $creado;
                $p->save();
            }
        }

        $af = Afiliado::orderBy('id')->get();
        Traslado::create(['afiliado_id' => $af[2]->id, 'empresa_id' => $af[2]->empresa_id, 'tipo' => 'EPS', 'entidad_origen_id' => $ent['EPS'][0]->id, 'entidad_destino_id' => $ent['EPS'][1]->id, 'estado' => 'en_tramite', 'fecha_solicitud' => now()->subDays(12), 'seguimiento' => 'Solicitud radicada en la EPS destino.']);
        Traslado::create(['afiliado_id' => $af[5]->id, 'empresa_id' => $af[5]->empresa_id, 'tipo' => 'ARL', 'entidad_origen_id' => $ent['ARL'][1]->id, 'entidad_destino_id' => $ent['ARL'][0]->id, 'estado' => 'aprobado', 'fecha_solicitud' => now()->subDays(40), 'fecha_efectiva' => now()->subDays(10)]);

        $calc = new CalculadoraIncapacidad(1423500);
        $casos = [
            [1, 'enfermedad_general', 15, 'radicada', 18, 'J06.9'],
            [4, 'accidente_laboral', 20, 'aprobada', 30, 'S62.6'],
            [6, 'licencia_maternidad', 126, 'pagada', 70, null],
            [9, 'enfermedad_general', 30, 'radicada', 45, 'M54.5'],
            [11, 'enfermedad_general', 2, 'por_radicar', 4, 'A09'],
            [13, 'enfermedad_general', 10, 'rechazada', 80, 'K29.7'],
            [15, 'enfermedad_general', 5, 'por_radicar', 2, 'J11.1'],
        ];
        foreach ($casos as [$idx, $tipo, $dias, $estado, $hace, $dx]) {
            $a = $af[$idx];
            $r = $calc->calcular($tipo, $dias, $a->salario);
            Incapacidad::create([
                'afiliado_id' => $a->id, 'empresa_id' => $a->empresa_id, 'tipo' => $tipo, 'diagnostico' => $dx,
                'fecha_inicio' => now()->subDays($hace), 'dias' => $dias, 'ibc' => $a->salario, 'dias_acumulados' => $dias,
                'entidad_id' => $ent[CalculadoraIncapacidad::tipoEntidadCobro($tipo)][0]->id,
                'estado' => $r['recobro'] > 0 ? $estado : 'pagada', 'valor_calculado' => $r['total'], 'valor_recobro' => $r['recobro'],
                'numero_radicado_entidad' => in_array($estado, ['radicada', 'aprobada', 'pagada', 'rechazada']) ? 'RAD-'.(55000 + $idx) : null,
                'fecha_radicacion' => in_array($estado, ['radicada', 'aprobada', 'pagada', 'rechazada']) ? now()->subDays($hace - 3) : null,
                'valor_pagado' => $estado === 'pagada' ? $r['recobro'] : null, 'fecha_pago' => $estado === 'pagada' ? now()->subDays(5) : null,
                'observaciones' => $estado === 'rechazada' ? 'La EPS pide la epicrisis completa.' : null, 'creado_por' => $demoAdmin->id,
            ]);
        }
        auth()->logout();
        $this->command?->info('Datos de demostración cargados. Entra con demo@example.com / demo12345 (admin) o empresa@example.com / demo12345 (empresa).');
    }
}
