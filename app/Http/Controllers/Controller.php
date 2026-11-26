<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Un usuario de empresa cliente solo puede ver registros de su propia empresa. */
    protected function autorizarEmpresa($modelo): void
    {
        $u = auth()->user();
        if ($u->esEmpresa() && (int) $modelo->empresa_id !== (int) $u->empresa_id) {
            abort(403, 'Este registro no pertenece a tu empresa.');
        }
    }
}
