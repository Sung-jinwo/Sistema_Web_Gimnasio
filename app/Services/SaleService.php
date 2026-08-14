<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\DetalleVenta;
use App\Models\Membresia;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class SaleService
{
    protected MembresiaService $membresiaService;
    protected CommissionService $commissionService;

    public function __construct(MembresiaService $membresiaService, CommissionService $commissionService)
    {
        $this->membresiaService = $membresiaService;
        $this->commissionService = $commissionService;
    }

    public function crearVentaProducto(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $producto = Producto::findOrFail($datos['fkproducto']);
            $cantidad = $datos['cantidad'] ?? 1;

            $this->validarStockDisponible($producto, $cantidad);

            $venta = Venta::create([
                'fkalum' => $datos['fkalum'],
                'fkusers' => $datos['fkusers'],
                'fksede' => $datos['fksede'],
                'fkmetodo' => $datos['fkmetodo'],
                'fkproducto' => $producto->id_productos,
                'tipo_venta' => 'producto',
                'estado_venta' => $datos['estado_venta'] ?? 'completado',
                'estado_pago' => $datos['estado_pago'] ?? 'pagado',
                'venta_total' => $producto->prod_precio * $cantidad,
                'venta_descuento' => $datos['venta_descuento'] ?? 0,
                'monto_pagado' => $datos['monto_pagado'] ?? ($producto->prod_precio * $cantidad),
                'saldo' => $datos['saldo'] ?? 0,
                'fecha_acordada' => $datos['fecha_acordada'] ?? null,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            DetalleVenta::create([
                'fkventa' => $venta->id_venta,
                'fkproducto' => $producto->id_productos,
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->prod_precio,
                'subtotal' => $producto->prod_precio * $cantidad,
            ]);

            $this->actualizarStock($producto->id_productos, $cantidad);

            $this->calcularComision($venta->id_venta, $venta->fkusers, $datos['fecha_acordada'] ?? null);

            return $venta->load('detalles');
        });
    }

    public function crearVentaMembresia(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $membresia = Membresia::findOrFail($datos['fkmem']);

            $venta = Venta::create([
                'fkalum' => $datos['fkalum'],
                'fkusers' => $datos['fkusers'],
                'fksede' => $datos['fksede'],
                'fkmetodo' => $datos['fkmetodo'],
                'tipo_venta' => 'membresia',
                'estado_venta' => $datos['estado_venta'] ?? 'completado',
                'estado_pago' => $datos['estado_pago'] ?? 'pagado',
                'venta_total' => $membresia->mem_precio,
                'venta_descuento' => $datos['venta_descuento'] ?? 0,
                'monto_pagado' => $datos['monto_pagado'] ?? $membresia->mem_precio,
                'saldo' => $datos['saldo'] ?? 0,
                'fecha_acordada' => $datos['fecha_acordada'] ?? null,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            $this->membresiaService->asignarMembresia(
                $datos['fkalum'],
                $membresia->id_mem,
                $datos['modalidad'] ?? 'por_meses',
                $datos['fecha_inicio'] ?? now()->format('Y-m-d'),
                $datos['fecha_fin'] ?? null
            );

            $this->calcularComision($venta->id_venta, $venta->fkusers, $datos['fecha_acordada'] ?? null);

            return $venta;
        });
    }

    public function crearVentaRapida(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $producto = Producto::findOrFail($datos['fkproducto']);
            $cantidad = $datos['cantidad'] ?? 1;

            $this->validarStockDisponible($producto, $cantidad);

            $venta = Venta::create([
                'fkalum' => null,
                'fkusers' => $datos['fkusers'],
                'fksede' => $datos['fksede'],
                'fkmetodo' => $datos['fkmetodo'],
                'fkproducto' => $producto->id_productos,
                'tipo_venta' => 'rapida',
                'estado_venta' => 'completado',
                'estado_pago' => 'pagado',
                'venta_total' => $producto->prod_precio * $cantidad,
                'venta_descuento' => 0,
                'monto_pagado' => $producto->prod_precio * $cantidad,
                'saldo' => 0,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            DetalleVenta::create([
                'fkventa' => $venta->id_venta,
                'fkproducto' => $producto->id_productos,
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->prod_precio,
                'subtotal' => $producto->prod_precio * $cantidad,
            ]);

            $this->actualizarStock($producto->id_productos, $cantidad);

            $this->calcularComision($venta->id_venta, $venta->fkusers);

            return $venta->load('detalles');
        });
    }

    public function actualizarStock(int $productoId, int $cantidad): void
    {
        $producto = Producto::findOrFail($productoId);
        $nuevoStock = $producto->prod_cantidad - $cantidad;

        if ($nuevoStock < 0) {
            throw new \Exception('Stock insuficiente para el producto: ' . $producto->prod_nombre);
        }

        $producto->prod_cantidad = $nuevoStock;
        $producto->save();
    }

    public function calcularComision(int $ventaId, int $usuarioId, ?string $fechaAcordada = null): void
    {
        $montoBase = $this->commissionService->calcularComisionBase($ventaId, $usuarioId);

        if ($montoBase > 0) {
            $this->commissionService->guardarComision($ventaId, $usuarioId, $montoBase, $fechaAcordada);
        }
    }

    protected function validarStockDisponible(Producto $producto, int $cantidad): void
    {
        if ($producto->prod_cantidad < $cantidad) {
            throw new \Exception('Stock insuficiente para el producto: ' . $producto->prod_nombre . '. Stock disponible: ' . $producto->prod_cantidad);
        }
    }
}
