<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas de planes de seguridad social (independientes), cuotas mensuales y recibos.
 * Basado en las tablas planes, tarifarios, tarifario_celdas, ventas, cuotas, recibos
 * y recibo_pagos de la app original (ver docs/esquema-original-supabase.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('incluye_eps')->default(true);
            $table->boolean('incluye_afp')->default(false);
            $table->boolean('incluye_arl')->default(true);
            $table->boolean('incluye_ccf')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('tarifarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedBigInteger('smlv');
            $table->date('vigente_desde');
            $table->string('estado', 15)->default('borrador'); // borrador | publicado | archivado
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('publicado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tarifario_celdas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarifario_id')->constrained('tarifarios')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planes')->cascadeOnDelete();
            $table->unsignedTinyInteger('nivel_riesgo'); // 1 a 5
            $table->unsignedInteger('eps')->default(0);
            $table->unsignedInteger('afp')->default(0);
            $table->unsignedInteger('ccf')->default(0);
            $table->unsignedInteger('arl')->default(0);
            $table->unsignedInteger('total');
            $table->timestamps();
            $table->unique(['tarifario_id', 'plan_id', 'nivel_riesgo']);
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->foreignId('plan_id')->constrained('planes');
            $table->foreignId('tarifario_id')->constrained('tarifarios');
            $table->unsignedTinyInteger('nivel_riesgo');
            $table->string('tipo_documento', 5)->default('CC');
            $table->string('documento', 20);
            $table->string('nombre_cliente');
            $table->string('celular', 30)->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('eps')->default(0);
            $table->unsignedInteger('afp')->default(0);
            $table->unsignedInteger('ccf')->default(0);
            $table->unsignedInteger('arl')->default(0);
            $table->unsignedInteger('administracion')->default(0);
            $table->unsignedInteger('valor_total');
            $table->date('periodo_inicial');
            $table->string('estado', 15)->default('activa'); // activa | retirada
            $table->date('fecha_retiro')->nullable();
            $table->string('motivo_retiro')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['documento']);
            $table->index(['estado', 'sede_id']);
        });

        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->date('periodo'); // primer día del mes cotizado
            $table->unsignedInteger('aportes');
            $table->unsignedInteger('administracion');
            $table->unsignedInteger('valor');
            $table->date('fecha_limite');
            $table->string('estado', 15)->default('pendiente'); // pendiente | pagada | anulada
            $table->timestamps();
            $table->unique(['venta_id', 'periodo']);
            $table->index(['periodo', 'estado']);
        });

        Schema::create('recibos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->unsignedInteger('consecutivo');
            $table->string('numero', 30)->unique();
            $table->foreignId('cuota_id')->constrained('cuotas');
            $table->foreignId('venta_id')->constrained('ventas');
            $table->unsignedInteger('valor');
            $table->string('estado', 15)->default('emitido'); // emitido | pagado | anulado
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_pago')->nullable();
            $table->string('medio_pago', 20)->nullable();
            $table->string('referencia')->nullable();
            $table->unsignedInteger('valor_pagado')->nullable();
            $table->foreignId('pago_registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_at')->nullable();
            $table->string('motivo_anulacion')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['sede_id', 'consecutivo']);
        });
    }

    public function down(): void
    {
        foreach (['recibos', 'cuotas', 'ventas', 'tarifario_celdas', 'tarifarios', 'planes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
