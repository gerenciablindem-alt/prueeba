<?php

use App\Http\Controllers\AfiliadoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\IncapacidadController;
use App\Http\Controllers\PreafiliacionController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TarifarioController;
use App\Http\Controllers\TrasladoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'autenticar'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'olvido'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'enviarEnlace'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'restablecer'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'guardarClave'])->name('password.update');
});

Route::middleware(['auth', 'rol'])->group(function () {
    Route::post('/logout', [AuthController::class, 'salir'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/alertas', [DashboardController::class, 'alertas'])->name('alertas');
    Route::get('/perfil', [AuthController::class, 'perfil'])->name('perfil');
    Route::put('/perfil', [AuthController::class, 'actualizarPerfil'])->name('perfil.update');

    // Afiliaciones
    Route::get('/preafiliaciones', [PreafiliacionController::class, 'index'])->name('preafiliaciones.index');
    Route::get('/preafiliaciones/crear', [PreafiliacionController::class, 'create'])->name('preafiliaciones.create');
    Route::post('/preafiliaciones', [PreafiliacionController::class, 'store'])->name('preafiliaciones.store');
    Route::get('/preafiliaciones/{preafiliacion}', [PreafiliacionController::class, 'show'])->name('preafiliaciones.show');
    Route::put('/preafiliaciones/{preafiliacion}', [PreafiliacionController::class, 'update'])->middleware('rol:admin,operador')->name('preafiliaciones.update');
    Route::post('/preafiliaciones/{preafiliacion}/documentos', [PreafiliacionController::class, 'subirDocumento'])->name('preafiliaciones.documentos');
    Route::get('/documentos/{documento}', [PreafiliacionController::class, 'descargarDocumento'])->name('documentos.show');
    Route::get('/api/afiliados/buscar', [AfiliadoController::class, 'buscar'])->name('afiliados.buscar');

    Route::get('/afiliados', [AfiliadoController::class, 'index'])->name('afiliados.index');
    Route::get('/afiliados/{afiliado}', [AfiliadoController::class, 'show'])->name('afiliados.show');

    Route::get('/traslados', [TrasladoController::class, 'index'])->name('traslados.index');
    Route::post('/traslados', [TrasladoController::class, 'store'])->middleware('rol:admin,operador')->name('traslados.store');
    Route::put('/traslados/{traslado}', [TrasladoController::class, 'update'])->middleware('rol:admin,operador')->name('traslados.update');

    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');

    // Incapacidades
    Route::get('/incapacidades/calculadora', [IncapacidadController::class, 'calculadora'])->name('incapacidades.calculadora');
    Route::get('/incapacidades', [IncapacidadController::class, 'index'])->name('incapacidades.index');
    Route::get('/incapacidades/crear', [IncapacidadController::class, 'create'])->name('incapacidades.create');
    Route::post('/incapacidades', [IncapacidadController::class, 'store'])->name('incapacidades.store');
    Route::get('/incapacidades/{incapacidad}', [IncapacidadController::class, 'show'])->name('incapacidades.show');
    Route::put('/incapacidades/{incapacidad}', [IncapacidadController::class, 'update'])->middleware('rol:admin,operador')->name('incapacidades.update');
    Route::post('/incapacidades/{incapacidad}/documentos', [IncapacidadController::class, 'subirDocumento'])->name('incapacidades.documentos');

    // Empresas (equipo interno)
    Route::middleware('rol:admin,operador')->group(function () {
        Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
        Route::get('/empresas/crear', [EmpresaController::class, 'create'])->name('empresas.create');
        Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
        Route::get('/empresas/{empresa}/editar', [EmpresaController::class, 'edit'])->name('empresas.edit');
        Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
        Route::post('/empresas/{empresa}/cargos', [EmpresaController::class, 'agregarCargo'])->name('empresas.cargos.store');
        Route::delete('/cargos/{cargo}', [EmpresaController::class, 'quitarCargo'])->name('cargos.destroy');
        Route::post('/sedes', [EmpresaController::class, 'guardarSede'])->name('sedes.store');

        // Ventas de planes, cuotas y recibos
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/crear', [VentaController::class, 'create'])->name('ventas.create');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
        Route::get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
        Route::post('/ventas/{venta}/retiro', [VentaController::class, 'retirar'])->name('ventas.retirar');
        Route::get('/cuotas', [CuotaController::class, 'index'])->name('cuotas.index');
        Route::post('/cuotas/generar', [CuotaController::class, 'generar'])->name('cuotas.generar');
        Route::post('/cuotas/{cuota}/recibo', [CuotaController::class, 'emitirRecibo'])->name('cuotas.recibo');
        Route::get('/recibos', [ReciboController::class, 'index'])->name('recibos.index');
        Route::get('/recibos/{recibo}', [ReciboController::class, 'show'])->name('recibos.show');
        Route::post('/recibos/{recibo}/pago', [ReciboController::class, 'pagar'])->name('recibos.pagar');
        Route::get('/tarifarios', [TarifarioController::class, 'index'])->name('tarifarios.index');
    });

    // Administración
    Route::middleware('rol:admin')->group(function () {
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::get('/configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
        Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        Route::post('/recibos/{recibo}/anular', [ReciboController::class, 'anular'])->name('recibos.anular');
        Route::post('/tarifarios', [TarifarioController::class, 'store'])->name('tarifarios.store');
        Route::post('/tarifarios/{tarifario}/publicar', [TarifarioController::class, 'publicar'])->name('tarifarios.publicar');
        Route::post('/planes', [TarifarioController::class, 'guardarPlan'])->name('planes.store');
    });
});
