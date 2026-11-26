<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Sede;
use App\Models\Tarifario;
use App\Models\User;
use App\Models\Venta;
use App\Services\Cobros;
use Illuminate\Database\Seeder;

/** Ventas, cuotas y recibos FICTICIOS para el demo. No hace nada si ya hay ventas. */
class VentasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanesSeeder::class);
        if (Venta::exists()) {
            return;
        }
        $admin = User::where('email', 'demo@example.com')->first() ?? User::where('rol', 'admin')->first();
        auth()->login($admin);
        $t = Tarifario::vigente();
        $planes = Plan::orderBy('orden')->get();
        $sedes = Sede::orderBy('id')->get();
        $cobros = new Cobros;
        $nombres = ['Mariana Ortiz Peña', 'Ricardo Salazar Mina', 'Yuliana Restrepo Cano', 'Héctor Bonilla Ruiz', 'Sandra Viveros Lenis', 'Fabián Muñoz Ospina',
            'Alejandra Cuero Palacios', 'Wilson Arboleda Rico', 'Liliana Prado Soto', 'Gustavo Tenorio Paz', 'Marcela Zúñiga León', 'Iván Bermúdez Rojas'];
        foreach ($nombres as $k => $nombre) {
            $plan = $planes[$k % $planes->count()];
            $nivel = [1, 1, 2, 3, 1, 4][$k % 6];
            $celda = $t->celdas()->where('plan_id', $plan->id)->where('nivel_riesgo', $nivel)->first();
            $adm = [35000, 35000, 40000, 30000][$k % 4];
            $inicio = now()->startOfMonth()->subMonths(2 + $k % 4);
            $v = Venta::create([
                'sede_id' => $sedes[$k % $sedes->count()]->id, 'plan_id' => $plan->id, 'tarifario_id' => $t->id, 'nivel_riesgo' => $nivel,
                'tipo_documento' => 'CC', 'documento' => (string) (1200000000 + $k * 6151), 'nombre_cliente' => $nombre,
                'celular' => '310555'.str_pad((string) $k, 4, '0', STR_PAD_LEFT), 'email' => 'cliente'.$k.'@example.com',
                'eps' => $celda->eps, 'afp' => $celda->afp, 'ccf' => $celda->ccf, 'arl' => $celda->arl, 'administracion' => $adm,
                'valor_total' => $celda->total + $adm, 'periodo_inicial' => $inicio, 'estado' => 'activa', 'creado_por' => $admin->id,
            ]);
            for ($p = $inicio->copy(); $p->lte(now()->startOfMonth()); $p->addMonth()) {
                $c = $cobros->cuotaPara($v, $p);
                $esMesActual = $p->isSameMonth(now());
                if ($esMesActual && $k % 4 === 0) continue; // sin recibo todavía
                if (! $esMesActual && $k === 7 && $p->isSameMonth(now()->subMonth())) continue; // una cuota vencida
                $r = $cobros->emitirRecibo($c, $admin->id);
                if (! $esMesActual || $k % 4 > 1) {
                    $cobros->registrarPago($r, ['fecha_pago' => $c->fecha_limite->copy()->subDays(2)->min(today()), 'medio_pago' => ['transferencia', 'efectivo', 'pse'][$k % 3], 'referencia' => 'TRX'.(80000 + $k * 31 + $p->month)], $admin->id);
                }
            }
        }
        $ultima = Venta::orderByDesc('id')->first();
        $ultima->update(['estado' => 'retirada', 'fecha_retiro' => now()->subMonth()->endOfMonth(), 'motivo_retiro' => 'Consiguió empleo formal']);
        foreach ($ultima->cuotas()->whereDate('periodo', now()->startOfMonth()->toDateString())->get() as $c) {
            $c->recibos()->where('estado', 'emitido')->get()->each(fn ($r) => $cobros->anular($r, 'Cliente retirado', $admin->id));
            $c->update(['estado' => 'anulada']);
        }
        auth()->logout();
    }
}
