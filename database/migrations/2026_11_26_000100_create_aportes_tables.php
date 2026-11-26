<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo_documento', 10)->default('NIT');
            $table->string('documento', 30);
            $table->string('departamento');
            $table->string('ciudad');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->string('nombre');
            $table->string('tipo_documento', 10)->default('NIT');
            $table->string('documento', 30)->unique();
            $table->string('ciiu', 10)->nullable();
            $table->string('actividad_economica')->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('operador')->after('password'); // admin | operador | empresa
            $table->foreignId('sede_id')->nullable()->after('rol')->constrained('sedes')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->after('sede_id')->constrained('empresas')->nullOnDelete();
            $table->boolean('activo')->default(true)->after('empresa_id');
        });

        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedTinyInteger('riesgo_arl')->default(1); // 1 a 5
            $table->timestamps();
        });

        Schema::create('entidades', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 3); // EPS | ARL | CCF | AFP
            $table->string('nombre');
            $table->string('codigo', 20)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->index(['tipo', 'activa']);
        });

        Schema::create('empresa_entidad', function (Blueprint $table) {
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('entidad_id')->constrained('entidades')->cascadeOnDelete();
            $table->primary(['empresa_id', 'entidad_id']);
        });

        Schema::create('afiliados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('cargo_id')->nullable()->constrained('cargos')->nullOnDelete();
            $table->string('tipo_documento', 5)->default('CC');
            $table->string('documento', 20);
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento')->nullable();
            $table->date('fecha_expedicion')->nullable();
            $table->string('sexo', 1)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->unsignedBigInteger('salario')->default(0);
            $table->date('fecha_ingreso')->nullable();
            $table->timestamps();
            $table->unique(['empresa_id', 'tipo_documento', 'documento']);
            $table->index('documento');
        });

        Schema::create('preafiliaciones', function (Blueprint $table) {
            $table->id();
            $table->string('radicado', 20)->unique();
            $table->foreignId('afiliado_id')->constrained('afiliados');
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('entidad_id')->constrained('entidades');
            $table->string('tipo', 3);
            $table->string('estado', 20)->default('pendiente');
            $table->date('fecha_afiliacion')->nullable();
            $table->string('numero_entidad')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('estado_cambiado_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['estado', 'tipo']);
        });

        Schema::create('traslados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('afiliado_id')->constrained('afiliados');
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->string('tipo', 3);
            $table->foreignId('entidad_origen_id')->constrained('entidades');
            $table->foreignId('entidad_destino_id')->constrained('entidades');
            $table->string('estado', 20)->default('solicitado'); // solicitado | en_tramite | aprobado | rechazado
            $table->date('fecha_solicitud');
            $table->date('fecha_efectiva')->nullable();
            $table->text('seguimiento')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('incapacidades', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->foreignId('afiliado_id')->constrained('afiliados');
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('prorroga_de_id')->nullable()->constrained('incapacidades')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('diagnostico', 10)->nullable(); // CIE-10
            $table->date('fecha_inicio');
            $table->unsignedSmallInteger('dias');
            $table->unsignedBigInteger('ibc');
            $table->unsignedSmallInteger('dias_acumulados')->default(0);
            $table->foreignId('entidad_id')->nullable()->constrained('entidades')->nullOnDelete();
            $table->string('estado', 20)->default('por_radicar'); // por_radicar | radicada | aprobada | pagada | rechazada
            $table->string('numero_radicado_entidad')->nullable();
            $table->date('fecha_radicacion')->nullable();
            $table->unsignedBigInteger('valor_calculado')->default(0);
            $table->unsignedBigInteger('valor_recobro')->default(0);
            $table->unsignedBigInteger('valor_pagado')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('estado');
        });

        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('nombre');
            $table->string('ruta');
            $table->unsignedBigInteger('tamano')->default(0);
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('modulo', 40);
            $table->nullableMorphs('auditable');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->string('evento', 20); // creado | actualizado | eliminado | importado
            $table->string('descripcion')->nullable();
            $table->json('cambios')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['modulo', 'created_at']);
        });

        Schema::create('configuraciones', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->string('valor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['configuraciones', 'auditorias', 'documentos', 'incapacidades', 'traslados', 'preafiliaciones', 'afiliados', 'empresa_entidad', 'entidades', 'cargos'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropConstrainedForeignId('sede_id');
            $table->dropColumn(['rol', 'activo']);
        });
        Schema::dropIfExists('empresas');
        Schema::dropIfExists('sedes');
    }
};
