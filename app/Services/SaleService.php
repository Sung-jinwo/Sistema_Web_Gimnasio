<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\DetalleVenta;
use App\Models\Membresia;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class SaleService
{
    protected MembresiaService $membresiaService;

    protected CommissionService $commissionService;

    protected PaymentService $paymentService;

    public function __construct(MembresiaService $membresiaService, CommissionService $commissionService, PaymentService $paymentService)
    {
        $this->membresiaService = $membresiaService;
        $this->commissionService = $commissionService;
        $this->paymentService = $paymentService;
    }

    public function crearVentaProducto(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $this->validarCajaOperativa($datos);
            $items = $datos['detalles'] ?? [['fkproducto' => $datos['fkproducto'], 'cantidad' => $datos['cantidad'] ?? 1]];
            $productos = collect($items)->map(function ($item) use ($datos) {
                $producto = Producto::lockForUpdate()->findOrFail($item['fkproducto']);
                if (! $producto->prod_estado || (int) $producto->fksede !== (int) $datos['fksede']) {
                    throw new \InvalidArgumentException('El producto no está disponible para esta sede.');
                }
                $this->validarStockDisponible($producto, (int) $item['cantidad']);

                return ['producto' => $producto, 'cantidad' => (int) $item['cantidad']];
            });
            $total = $productos->sum(fn ($item) => $item['producto']->prod_precio * $item['cantidad']);
            $cobros = $this->validarCobros($datos['cobros'], (float) $total, (bool) ($datos['pago_total_legacy'] ?? false));
            $pagado = $cobros->sum('monto');
            $saldo = max(0, $total - $pagado);
            if (($datos['tipo_venta'] ?? 'producto') === 'rapida' && $saldo > 0) {
                throw new \InvalidArgumentException('La venta rápida debe quedar totalmente pagada.');
            }
            if ($saldo > 0 && empty($datos['fecha_acordada'])) {
                throw new \InvalidArgumentException('Debe indicar una fecha acordada cuando el pago es parcial o pendiente.');
            }

            $venta = Venta::create([
                'fkalum' => $datos['fkalum'],
                'fkusers' => $datos['fkusers'],
                'fksede' => $datos['fksede'],
                'fkcaja' => $datos['fkcaja'] ?? null,
                'fkmetodo' => $cobros->first()['fkmetodo'],
                'fkproducto' => $productos->first()['producto']->id_productos,
                'tipo_venta' => $datos['tipo_venta'] ?? 'producto',
                'estado_venta' => $saldo > 0 ? 'reservado' : 'completado',
                'estado_pago' => $saldo <= 0 ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente'),
                'venta_total' => $total,
                'venta_descuento' => $datos['venta_descuento'] ?? 0,
                'monto_pagado' => $pagado,
                'saldo' => $saldo,
                'fecha_acordada' => $saldo > 0 ? ($datos['fecha_acordada'] ?? null) : null,
                'pagada_at' => $saldo <= 0 ? now() : null,
                'venta_fecha' => today(),
                'observacion' => $datos['observacion'] ?? null,
            ]);

            foreach ($productos as $item) {
                DetalleVenta::create(['fkventa' => $venta->id_venta, 'fkproducto' => $item['producto']->id_productos, 'cantidad' => $item['cantidad'], 'precio_unitario' => $item['producto']->prod_precio, 'subtotal' => $item['producto']->prod_precio * $item['cantidad']]);
                $this->actualizarStock($item['producto']->id_productos, $item['cantidad']);
            }

            $this->paymentService->registrarCobroInicial($venta, $cobros->all(), $datos);

            $this->calcularComision($venta->id_venta, $venta->fkusers, $datos['fecha_acordada'] ?? null);

            return $venta->load('detalles');
        });
    }

    public function crearVentaMembresia(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $this->validarCajaOperativa($datos);
            $membresia = Membresia::where('estado', 'A')->find($datos['fkmem']);
            if (! $membresia) {
                throw new \InvalidArgumentException('La membresía seleccionada no está disponible.');
            }
            $cobros = $this->validarCobros($datos['cobros'], (float) $membresia->mem_precio, (bool) ($datos['pago_total_legacy'] ?? false));
            $pagado = $cobros->sum('monto');
            $saldo = max(0, (float) $membresia->mem_precio - $pagado);
            $esAnonima = empty($datos['fkalum'] ?? null);
            if ($esAnonima && (int) $membresia->mem_duracion !== 1) {
                throw new \InvalidArgumentException('Solo los pases de un día pueden venderse sin alumno.');
            }
            if ($esAnonima && $saldo > 0) {
                throw new \InvalidArgumentException('El pase diario anónimo debe quedar totalmente pagado.');
            }
            if ($saldo > 0 && empty($datos['fecha_acordada'])) {
                throw new \InvalidArgumentException('Debe indicar una fecha acordada cuando el pago es parcial o pendiente.');
            }

            $venta = Venta::create([
                'fkalum' => $datos['fkalum'] ?? null,
                'fkusers' => $datos['fkusers'],
                'fksede' => $datos['fksede'],
                'fkcaja' => $datos['fkcaja'] ?? null,
                'fkmetodo' => $cobros->first()['fkmetodo'],
                'fkmem' => $membresia->id_mem,
                'tipo_venta' => 'membresia',
                'estado_venta' => 'completado',
                'estado_pago' => $saldo <= 0 ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente'),
                'venta_total' => $membresia->mem_precio,
                'venta_descuento' => $datos['venta_descuento'] ?? 0,
                'monto_pagado' => $pagado,
                'saldo' => $saldo,
                'fecha_acordada' => $saldo > 0 ? ($datos['fecha_acordada'] ?? null) : null,
                'pagada_at' => $saldo <= 0 ? now() : null,
                'venta_fecha' => today(),
                'observacion' => $datos['observacion'] ?? null,
            ]);

            if (! $esAnonima) {
                $this->membresiaService->asignarMembresia(
                    $datos['fkalum'],
                    $membresia->id_mem,
                    $membresia->modalidad,
                    $datos['fecha_inicio'] ?? now()->format('Y-m-d'),
                    $datos['fecha_fin'] ?? null,
                    $venta->id_venta
                );
            }

            $this->paymentService->registrarCobroInicial($venta, $cobros->all(), $datos);

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

        $this->commissionService->guardarComision($ventaId, $usuarioId, $montoBase, $fechaAcordada);
    }

    protected function validarStockDisponible(Producto $producto, int $cantidad): void
    {
        if ($producto->prod_cantidad < $cantidad) {
            throw new \Exception('Stock insuficiente para el producto: '.$producto->prod_nombre.'. Stock disponible: '.$producto->prod_cantidad);
        }
    }

    private function validarCajaOperativa(array $datos): void
    {
        $caja = Caja::lockForUpdate()->find($datos['fkcaja'] ?? null);
        if (! $caja || $caja->estado !== 'abierta' || ! $caja->fecha_operativa?->isSameDay(today())) {
            throw new \InvalidArgumentException('La caja ya no está disponible para registrar esta venta.');
        }
    }

    private function validarCobros(array $cobros, float $total, bool $pagoTotalLegacy = false)
    {
        $normalizados = collect($cobros)->map(fn ($cobro) => [
            'fkmetodo' => (int) $cobro['fkmetodo'],
            'monto' => round((float) $cobro['monto'], 2),
        ]);

        if ($pagoTotalLegacy && $normalizados->count() === 1) {
            $normalizados[0] = ['fkmetodo' => $normalizados[0]['fkmetodo'], 'monto' => round($total, 2)];
        }

        if ($normalizados->count() > 2 || $normalizados->pluck('fkmetodo')->unique()->count() !== $normalizados->count()) {
            throw new \InvalidArgumentException('Solo se permiten dos métodos de pago diferentes.');
        }
        if ($normalizados->skip(1)->contains(fn ($cobro) => $cobro['monto'] <= 0)) {
            throw new \InvalidArgumentException('El segundo pago debe ser mayor a cero.');
        }

        $pagado = $normalizados->sum('monto');
        if ($pagado > $total) {
            throw new \InvalidArgumentException('La suma de los pagos no puede superar el total de la venta.');
        }

        return $normalizados;
    }
}
