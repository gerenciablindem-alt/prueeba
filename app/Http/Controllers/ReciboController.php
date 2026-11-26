<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use App\Services\Cobros;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReciboController extends Controller
{
    public function index(Request $request)
    {
        $q = Recibo::with('venta', 'cuota');
        if ($e = $request->query('estado')) $q->where('estado', $e);
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('numero', 'like', "%$s%")->orWhereHas('venta', fn ($v) => $v->where('documento', 'like', "%$s%")->orWhere('nombre_cliente', 'like', "%$s%")));
        }
        return view('recibos.index', ['items' => $q->latest('id')->paginate(25)->withQueryString()]);
    }

    public function show(Recibo $recibo)
    {
        $recibo->load('venta.plan', 'cuota', 'sede', 'generador');
        return view('recibos.show', ['r' => $recibo]);
    }

    public function pagar(Request $request, Recibo $recibo, Cobros $cobros)
    {
        $d = $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
            'medio_pago' => ['required', Rule::in(array_keys(Recibo::MEDIOS))],
            'referencia' => 'nullable|string|max:100',
            'valor_pagado' => 'required|integer|min:1',
        ]);
        try {
            $cobros->registrarPago($recibo, $d, auth()->id());
        } catch (\DomainException $e) {
            return back()->withErrors(['recibo' => $e->getMessage()]);
        }
        return back()->with('ok', 'Pago registrado.');
    }

    public function anular(Request $request, Recibo $recibo, Cobros $cobros)
    {
        $d = $request->validate(['motivo_anulacion' => 'required|string|max:250']);
        $cobros->anular($recibo, $d['motivo_anulacion'], auth()->id());
        return back()->with('ok', 'Recibo anulado. La cuota volvió a quedar pendiente.');
    }
}
