<?php

namespace Tests\Feature;

use Anthropic\Client;
use App\Models\Configuracion;
use App\Models\User;
use App\Services\Asistente\Asistente;
use App\Services\Asistente\Herramientas;
use Database\Seeders\DemoSeeder;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenteTest extends TestCase
{
    use RefreshDatabase;

    private array $peticiones = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function admin(): User { return User::where('email', 'demo@example.com')->first(); }
    private function cliente(): User { return User::where('email', 'empresa@example.com')->first(); }

    private function respuesta(array $content, string $stop): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_'.uniqid(), 'type' => 'message', 'role' => 'assistant', 'model' => Asistente::MODELO,
            'content' => $content, 'stop_reason' => $stop, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
        ]));
    }

    /** Conecta el asistente a respuestas simuladas de la API y guarda lo que se envió. */
    private function simular(array $respuestas): void
    {
        $stack = HandlerStack::create(new MockHandler($respuestas));
        $stack->push(Middleware::history($this->peticiones));
        $this->app->instance(Asistente::class, new Asistente(new Client(apiKey: 'sk-test', requestOptions: ['transporter' => new Guzzle(['handler' => $stack]), 'maxRetries' => 0])));
        Configuracion::updateOrCreate(['clave' => 'anthropic_api_key'], ['valor' => encrypt('sk-test', false)]);
    }

    private function cuerpo(int $i): array
    {
        return json_decode((string) $this->peticiones[$i]['request']->getBody(), true);
    }

    public function test_sin_clave_el_asistente_avisa(): void
    {
        $this->actingAs($this->admin());
        $this->get('/asistente')->assertOk()->assertSee('Pon la clave de API');
        $this->postJson('/asistente/mensaje', ['pregunta' => 'hola'])->assertStatus(422);
    }

    public function test_ciclo_de_herramientas_y_respuesta(): void
    {
        $this->simular([
            $this->respuesta([
                ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig1'],
                ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'listar_afiliaciones', 'input' => ['estado' => 'correccion', 'limite' => 5]],
            ], 'tool_use'),
            $this->respuesta([['type' => 'text', 'text' => 'Hay **radicados** por corregir.']], 'end_turn'),
        ]);
        $this->actingAs($this->admin());
        $r = $this->postJson('/asistente/mensaje', ['pregunta' => '¿Qué está por corregir?'])->assertOk()->json();
        $this->assertSame('Hay **radicados** por corregir.', $r['texto']);
        $this->assertStringContainsString('<strong>radicados</strong>', $r['html']);
        $this->assertSame(['listar_afiliaciones'], $r['herramientas']);

        $p1 = $this->cuerpo(0);
        $this->assertSame(Asistente::MODELO, $p1['model']);
        $this->assertSame('default', $p1['fallbacks']);
        $this->assertContains('listar_afiliaciones', array_column($p1['tools'], 'name'));
        $this->assertContains('cartera_ventas', array_column($p1['tools'], 'name'));

        // La segunda petición agrega la respuesta completa (con su razonamiento) y el resultado de la herramienta.
        $p2 = $this->cuerpo(1);
        $this->assertCount(3, $p2['messages']);
        $this->assertSame('thinking', $p2['messages'][1]['content'][0]['type']);
        $this->assertSame('sig1', $p2['messages'][1]['content'][0]['signature']);
        $res = $p2['messages'][2]['content'][0];
        $this->assertSame('tool_result', $res['type']);
        $this->assertSame('toolu_1', $res['tool_use_id']);
        $this->assertStringContainsString('Requiere corrección', $res['content']);

        // La siguiente pregunta reenvía solo el texto de los turnos anteriores.
        $this->simular([$this->respuesta([['type' => 'text', 'text' => 'Listo.']], 'end_turn')]);
        $this->peticiones = [];
        $this->simular([$this->respuesta([['type' => 'text', 'text' => 'Listo.']], 'end_turn')]);
        $this->postJson('/asistente/mensaje', ['pregunta' => 'Gracias'])->assertOk();
        $m = $this->cuerpo(0)['messages'];
        $this->assertCount(3, $m);
        $this->assertSame('Hay **radicados** por corregir.', $m[1]['content']);
    }

    public function test_empresa_cliente_solo_ve_lo_suyo(): void
    {
        $c = $this->cliente();
        $h = new Herramientas($c);
        $this->assertNotContains('cartera_ventas', array_column($h->definiciones(), 'name'));
        $this->assertArrayHasKey('error', $h->ejecutar('cartera_ventas', []));
        $todas = (new Herramientas($this->admin()))->ejecutar('listar_afiliaciones', ['limite' => 50]);
        $suyas = $h->ejecutar('listar_afiliaciones', ['limite' => 50]);
        $this->assertLessThan($todas['total'], $suyas['total']);
        foreach ($suyas['mostrando'] as $p) {
            $this->assertSame($c->empresa->nombre, $p['empresa']);
        }
    }

    public function test_herramientas_responden(): void
    {
        $h = new Herramientas($this->admin());
        foreach (['resumen_general' => [], 'alertas' => [], 'listar_incapacidades' => ['estado' => 'radicada'], 'cartera_ventas' => [], 'buscar_ventas' => ['texto' => 'Mariana'],
            'buscar_afiliados' => ['texto' => 'Laura Quintero'], 'calcular_incapacidad' => ['tipo' => 'enfermedad_general', 'dias' => 20, 'ibc' => 2000000]] as $n => $in) {
            $r = $h->ejecutar($n, $in);
            $this->assertArrayNotHasKey('error', $r, $n);
            json_encode($r, JSON_THROW_ON_ERROR);
        }
        $this->assertNotEmpty($h->ejecutar('buscar_afiliados', ['texto' => 'Laura Quintero'])['resultados']);
        $this->assertArrayHasKey('error', $h->ejecutar('calcular_incapacidad', ['tipo' => 'x', 'dias' => 0, 'ibc' => 0]));
    }

    public function test_limite_diario(): void
    {
        config(['aportes.ia_limite_diario' => 1]);
        $this->simular([$this->respuesta([['type' => 'text', 'text' => 'Uno.']], 'end_turn')]);
        $this->actingAs($this->admin());
        $this->postJson('/asistente/mensaje', ['pregunta' => 'a'])->assertOk();
        $this->postJson('/asistente/mensaje', ['pregunta' => 'b'])->assertStatus(429);
    }

    public function test_clave_se_guarda_cifrada_y_solo_admin(): void
    {
        $this->actingAs($this->admin());
        $this->post('/configuracion/ia', ['anthropic_api_key' => 'sk-ant-prueba-123'])->assertRedirect();
        $guardada = Configuracion::find('anthropic_api_key')->valor;
        $this->assertStringNotContainsString('sk-ant-prueba', $guardada);
        $this->assertSame('sk-ant-prueba-123', Asistente::claveConfigurada());
        $this->get('/configuracion')->assertOk()->assertDontSee('sk-ant-prueba-123');
        $this->post('/configuracion/ia', ['quitar' => 1])->assertRedirect();
        $this->assertFalse(Asistente::disponible());

        $this->actingAs($this->cliente());
        $this->post('/configuracion/ia', ['anthropic_api_key' => 'x'])->assertForbidden();
    }

    public function test_busqueda_global(): void
    {
        $this->actingAs($this->admin());
        $r = $this->getJson('/buscar?q=Laura')->assertOk()->json('resultados');
        $this->assertNotEmpty($r);
        $this->assertSame('Afiliados', $r[0]['grupo']);
        $this->getJson('/buscar?q=Mariana')->assertOk()->assertJsonFragment(['grupo' => 'Ventas']);
        $this->actingAs($this->cliente());
        $this->getJson('/buscar?q=Mariana')->assertOk()->assertJsonMissing(['grupo' => 'Ventas']);
    }
}
