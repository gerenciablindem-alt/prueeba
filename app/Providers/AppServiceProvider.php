<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        if (is_file($listo)) {
            return;
        }
        $lock = fopen(storage_path('app/demo/instalando.lock'), 'c');
        flock($lock, LOCK_EX);
        try {
            if (! is_file($listo)) {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed', ['--class' => \Database\Seeders\DemoSeeder::class, '--force' => true]);
                file_put_contents($listo, now()->toIso8601String());
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
