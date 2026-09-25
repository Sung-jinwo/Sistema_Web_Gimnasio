<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReservationService
{
    public function liberarReservaVencida(Venta $venta): bool
    {
        return DB::transaction(function () use ($venta) {
            $venta = Venta::lockForUpdate()->findOrFail($venta->id_venta);
            if ($venta->tipo_venta !== 'producto' || $venta->estado_venta === 'anulado' || $venta->saldo <= 0 || $venta->stock_liberado_at) {
                return false;
            }

            foreach ($venta->detalles()->orderBy('fkproducto')->get() as $detalle) {
                Producto::whereKey($detalle->fkproducto)->increment('prod_cantidad', $detalle->cantidad);
            }

            $venta->update([
                'stock_liberado_at' => now(),
                'estado_venta' => 'reserva_vencida',
                'estado_pago' => 'vencido',
            ]);

            return true;
        });
    }

    public function reapartarParaCobroFinal(Venta $venta): void
    {
        if (! $venta->stock_liberado_at || $venta->tipo_venta !== 'producto') {
            return;
        }

        $detalles = $venta->detalles()->orderBy('fkproducto')->get();
        $productos = Producto::whereIn('id_productos', $detalles->pluck('fkproducto'))
            ->orderBy('id_productos')
            ->lockForUpdate()
            ->get()
            ->keyBy('id_productos');

        foreach ($detalles as $detalle) {
            $producto = $productos->get($detalle->fkproducto);
            if (! $producto || ! $producto->prod_estado || $producto->prod_cantidad < $detalle->cantidad) {
                throw ValidationException::withMessages([
                    'monto' => 'No hay stock suficiente para completar esta reserva.',
                ]);
            }
        }

        foreach ($detalles as $detalle) {
            $productos->get($detalle->fkproducto)->decrement('prod_cantidad', $detalle->cantidad);
        }

        $venta->update(['stock_liberado_at' => null, 'estado_venta' => 'reservado']);
    }

    public function devolverStockAlAnular(Venta $venta): void
    {
        if (! in_array($venta->tipo_venta, ['producto', 'rapida'], true) || $venta->stock_liberado_at) {
            return;
        }

        foreach ($venta->detalles()->orderBy('fkproducto')->get() as $detalle) {
            Producto::whereKey($detalle->fkproducto)->increment('prod_cantidad', $detalle->cantidad);
        }
    }
}
