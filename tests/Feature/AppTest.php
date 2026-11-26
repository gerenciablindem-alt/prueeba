<?php

namespace Tests\Feature;

use App\Models\Afiliado;
use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Entidad;
use App\Models\Incapacidad;
use App\Models\Preafiliacion;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function admin(): User { return User::where('email', 'demo@example.com')->first(); }
    private function cliente(): User { return User::where('email', 'empresa@example.com')->first(); }

    public function test_invitado_va_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_login_correcto_e_incorrecto(): void
    {
        $this->post('/login', ['email' => 'demo@example.com', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'demo@example.com', 'password' => 'demo12345'])->assertRedirect(route('dashboard'));
    }

    public function test_usuario_inactivo_no_entra(): void
    {
        $this->admin()->update(['activo' => false]);
        $this->post('/login', ['email' => 'demo@example.com', 'password' => 'demo12345'])->assertSessionHasErrors('email');
    }

    public function test_todas_las_pantallas_cargan_para_admin(): void
    {
        $this->actingAs($this->admin());
        $p = Preafiliacion::first();
        $i = Incapacidad::first();
        $urls = ['/', '/alertas', '/preafiliaciones', '/preafiliaciones/crear', route('preafiliaciones.show', $p), '/afiliados', route('afiliados.show', $p->afiliado_id),
            '/traslados', '/reportes', '/incapacidades', '/incapacidades/crear', route('incapacidades.show', $i), '/incapacidades/calculadora?tipo=accidente_laboral&dias=20',
            '/empresas', '/empresas/crear', route('empresas.edit', Empresa::first()), '/auditoria', '/usuarios', '/usuarios/crear', '/configuracion', '/perfil',
            '/preafiliaciones?estado=pendiente&tipo=EPS&q=Laura'];
        foreach ($urls as $u) {
            $this->get($u)->assertOk();
        }
        $this->get('/reportes/exportar')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_cliente_solo_ve_su_empresa(): void
    {
        $c = $this->cliente();
        $this->actingAs($c);
        $ajena = Preafiliacion::where('empresa_id', '!=', $c->empresa_id)->first();
        $propia = Preafiliacion::where('empresa_id', $c->empresa_id)->first();
        $this->get(route('preafiliaciones.show', $ajena))->assertForbidden();
        $this->get(route('preafiliaciones.show', $propia))->assertOk();
        $this->get('/preafiliaciones')->assertOk()->assertDontSee($ajena->radicado);
        $this->get('/empresas')->assertForbidden();
        $this->get('/auditoria')->assertForbidden();
        $this->put(route('preafiliaciones.update', $propia), ['estado' => 'aprobado'])->assertForbidden();
    }

    public function test_crear_preafiliacion_genera_un_radicado_por_entidad_y_audita(): void
    {
        $this->actingAs($this->admin());
        $e = Empresa::first();
        $antes = Preafiliacion::count();
        $this->post('/preafiliaciones', [
            'tipos' => ['EPS', 'ARL'], 'empresa_id' => $e->id, 'tipo_documento' => 'CC', 'documento' => '1.234.567.890',
            'nombres' => 'Ana', 'apellidos' => 'Prueba', 'salario' => 1800000,
            'entidades' => ['EPS' => Entidad::where('tipo', 'EPS')->value('id'), 'ARL' => Entidad::where('tipo', 'ARL')->value('id')],
        ])->assertRedirect(route('preafiliaciones.index'));
        $this->assertSame($antes + 2, Preafiliacion::count());
        $a = Afiliado::where('documento', '1234567890')->firstOrFail();
        $this->assertSame(2, $a->preafiliaciones()->count());
        $this->assertTrue(Auditoria::where('modulo', 'Afiliaciones')->where('evento', 'creado')->exists());
        $this->assertMatchesRegularExpression('/^PA-\d{4}-\d{6}$/', Preafiliacion::latest('id')->value('radicado'));
    }

    public function test_entidad_debe_coincidir_con_el_tipo(): void
    {
        $this->actingAs($this->admin());
        $this->post('/preafiliaciones', [
            'tipos' => ['EPS'], 'empresa_id' => Empresa::first()->id, 'tipo_documento' => 'CC', 'documento' => '999888777',
            'nombres' => 'X', 'apellidos' => 'Y', 'salario' => 1, 'entidades' => ['EPS' => Entidad::where('tipo', 'ARL')->value('id')],
        ])->assertSessionHasErrors('entidades.EPS');
    }

    public function test_devolver_radicado_exige_motivo_y_queda_en_historial(): void
    {
        $this->actingAs($this->admin());
        $p = Preafiliacion::where('estado', 'pendiente')->first();
        $this->put(route('preafiliaciones.update', $p), ['estado' => 'correccion'])->assertSessionHasErrors('observaciones');
        $this->put(route('preafiliaciones.update', $p), ['estado' => 'correccion', 'observaciones' => 'Falta firma'])->assertSessionHasNoErrors();
        $h = Auditoria::where('auditable_type', Preafiliacion::class)->where('auditable_id', $p->id)->where('evento', 'actualizado')->first();
        $this->assertSame('pendiente', $h->cambios['estado']['antes']);
        $this->assertSame('correccion', $h->cambios['estado']['despues']);
    }

    public function test_registrar_incapacidad_y_prorroga(): void
    {
        $this->actingAs($this->admin());
        $a = Afiliado::first();
        $this->post('/incapacidades', ['afiliado_id' => $a->id, 'tipo' => 'enfermedad_general', 'fecha_inicio' => now()->toDateString(), 'dias' => 80, 'ibc' => 3000000])->assertRedirect();
        $i = Incapacidad::latest('id')->first();
        $this->assertSame('por_radicar', $i->estado);
        $this->assertEquals(round(100000 * 2 / 3 * 78), $i->valor_recobro);
        $this->post('/incapacidades', ['afiliado_id' => $a->id, 'tipo' => 'enfermedad_general', 'fecha_inicio' => now()->addDays(80)->toDateString(), 'dias' => 20, 'ibc' => 3000000, 'prorroga_de_id' => $i->id])->assertRedirect();
        $p = Incapacidad::latest('id')->first();
        $this->assertSame(100, $p->dias_acumulados);
        $this->assertEquals(round(100000 * 2 / 3 * 10 + 100000 * 0.5 * 10), $p->valor_recobro);
    }

    public function test_prorroga_de_otro_empleado_es_rechazada(): void
    {
        $this->actingAs($this->admin());
        $otra = Incapacidad::first();
        $a = Afiliado::where('id', '!=', $otra->afiliado_id)->first();
        $this->post('/incapacidades', ['afiliado_id' => $a->id, 'tipo' => 'enfermedad_general', 'fecha_inicio' => now()->toDateString(), 'dias' => 5, 'ibc' => 2000000, 'prorroga_de_id' => $otra->id])
            ->assertSessionHasErrors('prorroga_de_id');
    }

    public function test_marcar_pagada_exige_valor_y_fecha(): void
    {
        $this->actingAs($this->admin());
        $i = Incapacidad::where('estado', 'radicada')->first();
        $this->put(route('incapacidades.update', $i), ['estado' => 'pagada'])->assertSessionHasErrors(['valor_pagado', 'fecha_pago']);
    }

    public function test_cliente_no_registra_incapacidad_de_otra_empresa(): void
    {
        $c = $this->cliente();
        $this->actingAs($c);
        $ajeno = Afiliado::where('empresa_id', '!=', $c->empresa_id)->first();
        $this->post('/incapacidades', ['afiliado_id' => $ajeno->id, 'tipo' => 'enfermedad_general', 'fecha_inicio' => now()->toDateString(), 'dias' => 3, 'ibc' => 2000000])->assertForbidden();
    }

    public function test_buscar_documento_existente(): void
    {
        $this->actingAs($this->admin());
        $a = Afiliado::first();
        $this->getJson(route('afiliados.buscar', ['documento' => $a->documento]))->assertOk()->assertJsonPath('nombres', $a->nombres);
    }

    public function test_admin_no_se_quita_su_propio_rol(): void
    {
        $u = $this->admin();
        $this->actingAs($u);
        $this->put(route('usuarios.update', $u), ['name' => $u->name, 'email' => $u->email, 'rol' => 'operador', 'activo' => 1])->assertSessionHasErrors('rol');
    }

    public function test_alertas_incluyen_correcciones_y_por_radicar(): void
    {
        $this->actingAs($this->admin());
        $this->get('/alertas')->assertOk()->assertSee('requiere corrección')->assertSee('sin radicar');
    }
}
