<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Auditable, HasFactory, Notifiable;

    public const ROLES = [
        'admin' => 'Administrador',
        'operador' => 'Operador',
        'empresa' => 'Empresa cliente',
    ];

    protected string $moduloAuditoria = 'Usuarios';

    protected $fillable = ['name', 'email', 'password', 'rol', 'sede_id', 'empresa_id', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'activo' => 'boolean'];
    }

    public function sede() { return $this->belongsTo(Sede::class); }
    public function empresa() { return $this->belongsTo(Empresa::class); }

    public function esAdmin(): bool { return $this->rol === 'admin'; }
    public function esEmpresa(): bool { return $this->rol === 'empresa'; }
    public function esInterno(): bool { return in_array($this->rol, ['admin', 'operador'], true); }
}
