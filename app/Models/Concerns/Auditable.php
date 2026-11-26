<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

/**
 * Registra en la tabla de auditoría cada creación, cambio y eliminación,
 * con el valor anterior y el nuevo de cada campo modificado.
 */
trait Auditable
{
    protected static array $camposOcultosAuditoria = ['password', 'remember_token', 'updated_at', 'created_at'];

    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->registrarAuditoria('creado', collect($m->getAttributes())
            ->except(static::$camposOcultosAuditoria)
            ->map(fn ($v) => ['antes' => null, 'despues' => $v])->all()));

        static::updated(function ($m) {
            $cambios = collect($m->getChanges())->except(static::$camposOcultosAuditoria)
                ->map(fn ($v, $k) => ['antes' => $m->getOriginal($k), 'despues' => $v])->all();
            if ($cambios) {
                $m->registrarAuditoria('actualizado', $cambios);
            }
        });

        static::deleted(fn ($m) => $m->registrarAuditoria('eliminado', null));
    }

    public function registrarAuditoria(string $evento, ?array $cambios, ?string $descripcion = null): void
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'modulo' => $this->moduloAuditoria(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'empresa_id' => $this->empresa_id ?? ($this instanceof \App\Models\Empresa ? $this->id : null),
            'evento' => $evento,
            'descripcion' => $descripcion ?? $this->descripcionAuditoria(),
            'cambios' => $cambios,
        ]);
    }

    public function moduloAuditoria(): string
    {
        return property_exists($this, 'moduloAuditoria') ? $this->moduloAuditoria : class_basename($this);
    }

    public function descripcionAuditoria(): string
    {
        return $this->radicado ?? $this->codigo ?? $this->nombre ?? $this->name ?? ('#'.$this->getKey());
    }
}
