<?php

namespace Database\Seeders;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Datos mínimos para arrancar: catálogo de entidades, una sede y el usuario administrador. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EntidadesSeeder::class);
        $this->call(PlanesSeeder::class);

        $sede = Sede::firstOrCreate(['nombre' => env('SEDE_PRINCIPAL', 'Sede principal')], [
            'tipo_documento' => 'NIT', 'documento' => '000000000', 'departamento' => 'Valle del Cauca', 'ciudad' => 'Cali',
        ]);

        $email = env('ADMIN_EMAIL', 'admin@example.com');
        if (! User::where('email', $email)->exists()) {
            $clave = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);
            User::create(['name' => env('ADMIN_NAME', 'Administrador'), 'email' => $email, 'password' => $clave, 'rol' => 'admin', 'sede_id' => $sede->id, 'activo' => true]);
            $this->command?->info("Administrador creado: $email".(env('ADMIN_PASSWORD') ? '' : " · contraseña: $clave (cámbiala al entrar)"));
        }
    }
}
