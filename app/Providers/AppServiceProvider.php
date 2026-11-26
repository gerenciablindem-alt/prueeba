<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Súbelo cuando el demo necesite tablas o datos nuevos; el servidor se actualiza solo. */
    private const VERSION_DEMO = '2';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        if (config('app.demo') && ! $this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            $this->prepararDemo();
        }
    }

    /** En modo demo, crea las tablas y carga los datos ficticios la primera vez que alguien abre la app. */
    private function prepararDemo(): void
    {
        $listo = storage_path('app/demo/listo');
        $version = fn () => is_file($listo) ? (str_contains((string) file_get_contents($listo), 'v'.self::VERSION_DEMO.' ') ? 'actual' : 'vieja') : 'ninguna';
        if ($version() === 'actual') {
            return;
        }
        $lock = fopen(storage_path('app/demo/instalando.lock'), 'c');
        flock($lock, LOCK_EX);
        try {
            $v = $version();
            if ($v !== 'actual') {
                Artisan::call('migrate', ['--force' => true]);
                // Instalación nueva: todos los datos. Demo existente: solo lo que agregó la versión nueva.
                $seeder = $v === 'ninguna' ? \Database\Seeders\DemoSeeder::class : \Database\Seeders\VentasDemoSeeder::class;
                Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
                file_put_contents($listo, 'v'.self::VERSION_DEMO.' '.now()->toIso8601String());
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
