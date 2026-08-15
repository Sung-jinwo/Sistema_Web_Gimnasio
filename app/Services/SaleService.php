<?php

namespace App\Services;

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
            $items = $datos['detalles'] ?? [['fkproducto' => $datos['fkproducto'], 'cantidad' => $datos['cantidad'] ?? 1]];
            $productos = collect($items)->map(function ($item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['fkproducto']);
                $this->validarStockDisponible($producto, (int) $item['cantidad']);

                return ['producto' => $producto, 'cantidad' => (int) $item['cantidad']];
            });
            $total = $productos->sum(fn ($item) => $item['producto']->prod_precio * $item['cantidad']);
            $pagado = min((float) ($datos['monto_pagado'] ?? $total), $total);
            $saldo = max(0, $total - $pagado);
            if ($saldo > 0 && empty($datos['fecha_acordada'])) {
                throw new \InvalidArgumentException('Debe indicar una fecha acordada cuando el pago es parcial o pendiente.');
            }

            $venta = Venta::create([
                'fkalum' => $datos['fkalum'],
                'fkusers' => $datos['fkusers'],
                'fksede' => $datos['fksede'],
                'fkmetodo' => $datos['fkmetodo'],
                'fkproducto' => $productos->first()['producto']->id_productos,
                'tipo_venta' => $datos['tipo_venta'] ?? 'producto',
                'estado_venta' => $datos['estado_venta'] ?? 'completado',
                'estado_pago' => $saldo <= 0 ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente'),
                'venta_total' => $total,
                'venta_descuento' => $datos['venta_descuento'] ?? 0,
                'monto_pagado' => $pagado,
                'saldo' => $saldo,
                'fecha_acordada' => $saldo > 0 ? ($datos['fecha_acordada'] ?? null) : null,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            foreach ($productos as $item) {
                DetalleVenta::create(['fkventa' => $venta->id_venta, 'fkproducto' => $item['producto']->id_productos, 'cantidad' => $item['cantidad'], 'precio_unitario' => $item['producto']->prod_precio, 'subtotal' => $item['producto']->prod_precio * $item['cantidad']]);
                $this->actualizarStock($item['producto']->id_productos, $item['cantidad']);
            }

            $this->calcularComision($venta->id_venta, $venta->fkusers, $datos['fecha_acordada'] ?? null);

            return $venta->load('detalles');
        });
    }

    public function crearVentaMembresia(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $membresia = Membresia::findOrFail($datos['fkmem']);
            $pagado = min((float) ($datos['monto_pagado'] ?? $membresia->mem_precio), (float) $membresia->mem_precio);
            $saldo = max(0, (float) $membresia->mem_precio - $pagado);
            if ($saldo > 0 && empty($datos['fecha_acordada'])) {
                throw new \InvalidArgumentException('Debe indicar una fecha acordada cuando el pago es parcial o pendiente.');
            }

            $venta = Venta::create([
                'fkalum' => $datos['fkalum'],
                'fkusers' => $datos['fkusers'],
                'fksede' => $datos['fksede'],
                'fkmetodo' => $datos['fkmetodo'],
                'tipo_venta' => 'membresia',
                'estado_venta' => $datos['estado_venta'] ?? 'completado',
                'estado_pago' => $saldo <= 0 ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente'),
                'venta_total' => $membresia->mem_precio,
                'venta_descuento' => $datos['venta_descuento'] ?? 0,
                'monto_pagado' => $pagado,
                'saldo' => $saldo,
                'fecha_acordada' => $saldo > 0 ? ($datos['fecha_acordada'] ?? null) : null,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            $this->membresiaService->asignarMembresia(
                $datos['fkalum'],
                $membresia->id_mem,
                $membresia->modalidad,
                $datos['fecha_inicio'] ?? now()->format('Y-m-d'),
                $datos['fecha_fin'] ?? null
            );

            $this->calcularComision($venta->id_venta, $venta->fkusers, $datos['fecha_acordada'] ?? null);

            return $venta;
        });
    }

    public function crearVentaRapida(array $datos): Venta
    {
        $datos['fkalum'] = null;
        $datos['estado_venta'] = 'completado';

        return $this->crearVentaProducto($datos + ['tipo_venta' => 'rapida']);
    }

    public function actualizarStock(int $productoId, int $cantidad): void
    {
        $producto = Producto::findOrFail($productoId);
        $nuevoStock = $producto->prod_cantidad - $cantidad;

        if ($nuevoStock < 0) {
            throw new \Exception('Stock insuficiente para el producto: '.$producto->prod_nombre);
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
            throw new \Exception('Stock insuficiente para el producto: '.$producto->prod_nombre.'. Stock disponible: '.$producto->prod_cantidad);
        }
    }
}
