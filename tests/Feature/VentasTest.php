<?php

namespace Tests\Feature;

use App\Models\Cuota;
use App\Models\Plan;
use App\Models\Recibo;
use App\Models\Sede;
use App\Models\Tarifario;
use App\Models\User;
use App\Models\Venta;
use App\Services\Cobros;
use App\Services\Tarifas;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function admin(): User { return User::where('email', 'demo@example.com')->first(); }

    public function test_tarifa_redondea_cada_aporte_a_la_centena(): void
    {
        $plan = Plan::where('codigo', 'SAPC')->first();
        $t = Tarifas::calcular($plan, 1, 1423500);
        $this->assertSame(['eps' => 178000, 'afp' => 227800, 'ccf' => 28500, 'arl' => 7500, 'total' => 441800], $t);
        $this->assertSame(99100, Tarifas::calcular(Plan::where('codigo', 'ARL')->first(), 5, 1423500)['total']);
    }

    public function test_pantallas_de_ventas_cargan(): void
    {
        $this->actingAs($this->admin());
        $v = Venta::first();
        $r = Recibo::first();
        foreach (['/ventas', '/ventas/crear', route('ventas.show', $v), '/cuotas', '/cuotas?estado=vencida&periodo='.now()->subMonth()->format('Y-m'), '/recibos', route('recibos.show', $r), '/tarifarios', '/alertas'] as $u) {
            $this->get($u)->assertOk();
        }
        $this->get('/ventas?q='.$v->documento)->assertSee($v->nombre_cliente);
    }

    public function test_venta_nueva_toma_el_precio_y_genera_la_primera_cuota(): void
    {
        $this->actingAs($this->admin());
        $plan = Plan::where('codigo', 'SA')->first();
        $this->post('/ventas', [
            'sede_id' => Sede::first()->id, 'plan_id' => $plan->id, 'nivel_riesgo' => 3, 'tipo_documento' => 'CC', 'documento' => '987654321',
            'nombre_cliente' => 'Cliente De Prueba', 'administracion' => 30000, 'periodo_inicial' => now()->format('Y-m'),
        ])->assertRedirect();
        $v = Venta::where('documento', '987654321')->firstOrFail();
        $celda = Tarifario::vigente()->celdas()->where('plan_id', $plan->id)->where('nivel_riesgo', 3)->first();
        $this->assertSame($celda->total + 30000, $v->valor_total);
        $this->assertSame(1, $v->cuotas()->count());
        $this->assertSame($v->valor_total, $v->cuotas()->first()->valor);

        // No se puede vender el mismo plan dos veces a la misma persona.
        $this->post('/ventas', [
            'sede_id' => Sede::first()->id, 'plan_id' => $plan->id, 'nivel_riesgo' => 1, 'tipo_documento' => 'CC', 'documento' => '987654321',
            'nombre_cliente' => 'Cliente De Prueba', 'administracion' => 30000, 'periodo_inicial' => now()->format('Y-m'),
        ])->assertSessionHasErrors('documento');
    }

    public function test_generar_cuotas_no_duplica(): void
    {
        $this->actingAs($this->admin());
        $siguiente = now()->addMonth()->format('Y-m');
        $activas = Venta::where('estado', 'activa')->count();
        $this->post('/cuotas/generar', ['periodo' => $siguiente])->assertRedirect();
        $this->post('/cuotas/generar', ['periodo' => $siguiente])->assertRedirect();
        $this->assertSame($activas, Cuota::whereDate('periodo', now()->addMonth()->startOfMonth()->toDateString())->count());
    }

    public function test_recibo_pago_y_anulacion(): void
    {
        $this->actingAs($this->admin());
        $c = (new Cobros)->cuotaPara(Venta::where('estado', 'activa')->first(), now()->addMonths(2));
        $this->post(route('cuotas.recibo', $c))->assertRedirect();
        $this->post(route('cuotas.recibo', $c))->assertRedirect();
        $this->assertSame(1, $c->recibos()->count(), 'Emitir dos veces devuelve el mismo recibo');
        $r = $c->recibos()->first();
        $this->assertStringStartsWith('RC'.$r->sede_id.'-', $r->numero);

        $this->post(route('recibos.pagar', $r), ['fecha_pago' => now()->toDateString(), 'medio_pago' => 'efectivo', 'valor_pagado' => $r->valor])->assertSessionHasNoErrors();
        $this->assertSame('pagada', $c->fresh()->estado);
        $this->post(route('recibos.pagar', $r), ['fecha_pago' => now()->toDateString(), 'medio_pago' => 'efectivo', 'valor_pagado' => $r->valor])->assertSessionHasErrors('recibo');

        $this->post(route('recibos.anular', $r), ['motivo_anulacion' => 'Error de digitación'])->assertRedirect();
        $this->assertSame('anulado', $r->fresh()->estado);
        $this->assertSame('pendiente', $c->fresh()->estado);
        $this->post(route('cuotas.recibo', $c));
        $this->assertSame(2, $c->recibos()->count(), 'Se puede emitir un recibo nuevo después de anular');
    }

    public function test_retiro_anula_cuotas_futuras(): void
    {
        $this->actingAs($this->admin());
        $v = Venta::where('estado', 'activa')->first();
        $futura = (new Cobros)->cuotaPara($v, now()->addMonths(3));
        $this->post(route('ventas.retirar', $v), ['fecha_retiro' => now()->toDateString(), 'motivo_retiro' => 'Prueba'])->assertRedirect();
        $this->assertSame('retirada', $v->fresh()->estado);
        $this->assertSame('anulada', $futura->fresh()->estado);
        $this->assertNull((new Cobros)->cuotaPara($v->fresh(), now()->addMonths(4)));
    }

    public function test_tarifario_nuevo_y_publicacion(): void
    {
        $this->actingAs($this->admin());
        $this->post('/tarifarios', ['anio' => now()->year + 1, 'smlv' => 1500000, 'vigente_desde' => (now()->year + 1).'-01-01'])->assertRedirect();
        $t = Tarifario::where('anio', now()->year + 1)->firstOrFail();
        $this->assertSame('borrador', $t->estado);
        $this->assertSame(Plan::count() * 5, $t->celdas()->count());
        $this->post(route('tarifarios.publicar', $t))->assertRedirect();
        $this->assertSame($t->id, Tarifario::vigente()->id);
        $this->assertSame(1, Tarifario::where('estado', 'publicado')->count());
    }

    public function test_cliente_empresa_y_operador_tienen_permisos_limitados(): void
    {
        $this->actingAs(User::where('email', 'empresa@example.com')->first());
        $this->get('/ventas')->assertForbidden();
        $this->get('/recibos')->assertForbidden();

        $op = User::create(['name' => 'Op', 'email' => 'op@example.com', 'password' => 'x12345678', 'rol' => 'operador', 'activo' => true]);
        $this->actingAs($op);
        $this->get('/ventas')->assertOk();
        $this->post(route('recibos.anular', Recibo::first()), ['motivo_anulacion' => 'x'])->assertForbidden();
        $this->post('/tarifarios', ['anio' => 2030, 'smlv' => 1, 'vigente_desde' => '2030-01-01'])->assertForbidden();
    }
}
