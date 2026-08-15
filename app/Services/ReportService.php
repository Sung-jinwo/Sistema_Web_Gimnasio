<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\Gasto;
use App\Models\MembresiaAlumno;
use App\Models\Producto;
use App\Models\Venta;

class ReportService
{
    public function reporteVentas(?string $fechaInicio, ?string $fechaFin, ?int $sedeId, ?int $empleadoId, ?string $tipoVenta)
    {
        $query = Venta::with(['alumno', 'user', 'sede', 'producto', 'metodo']);

        if ($fechaInicio) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }
        if ($sedeId) {
            $query->where('fksede', $sedeId);
        }
        if ($empleadoId) {
            $query->where('fkusers', $empleadoId);
        }
        if ($tipoVenta) {
            $query->where('tipo_venta', $tipoVenta);
        }

        $ventas = $query->orderByDesc('created_at')->get();

        return [
            'ventas' => $ventas,
            'total' => $ventas->sum('venta_total'),
            'cantidad' => $ventas->count(),
            'por_tipo' => $ventas->groupBy('tipo_venta')->map->sum('venta_total'),
            'por_metodo' => $ventas->groupBy(fn ($v) => $v->metodo->metod_nombre ?? 'Sin método')->map->sum('venta_total'),
        ];
    }

    public function reporteMembresias(?string $estado, ?int $sedeId)
    {
        $query = MembresiaAlumno::with(['alumno.sede', 'membresia']);

        $hoy = now()->format('Y-m-d');

        if ($estado === 'activa') {
            $query->where('estado', 'activa')->where('fecha_fin', '>=', $hoy);
        } elseif ($estado === 'por_vencer') {
            $fechaLimite = now()->addDays(5)->format('Y-m-d');
            $query->where('estado', 'activa')->whereBetween('fecha_fin', [$hoy, $fechaLimite]);
        } elseif ($estado === 'vencida') {
            $query->where(function ($q) use ($hoy) {
                $q->where('estado', 'vencida')
                    ->orWhere(function ($q2) use ($hoy) {
                        $q2->where('estado', 'activa')->where('fecha_fin', '<', $hoy);
                    });
            });
        }

        if ($sedeId) {
            $query->whereHas('alumno', fn ($q) => $q->where('fksede', $sedeId));
        }

        $membresias = $query->orderBy('fecha_fin')->get();

        return [
            'membresias' => $membresias,
            'cantidad' => $membresias->count(),
        ];
    }

    public function reporteProductos(?int $sedeId, ?int $categoriaId)
    {
        $query = Producto::with(['categoria', 'sede']);

        if ($sedeId) {
            $query->where('fksede', $sedeId);
        }
        if ($categoriaId) {
            $query->where('fkcategoria', $categoriaId);
        }

        $productos = $query->orderBy('prod_nombre')->get();

        return [
            'productos' => $productos,
            'total_stock' => $productos->sum('prod_cantidad'),
            'valor_total' => $productos->sum(fn ($p) => $p->prod_cantidad * $p->prod_precio),
            'stock_critico' => $productos->filter(fn ($p) => $p->prod_cantidad <= $p->prod_stock_minimo)->count(),
        ];
    }

    public function reporteComisiones(?string $fechaInicio, ?string $fechaFin, ?int $empleadoId)
    {
        $query = Comision::with(['usuario', 'venta.alumno']);

        if ($fechaInicio) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }
        if ($empleadoId) {
            $query->where('fkuser', $empleadoId);
        }

        $comisiones = $query->orderByDesc('created_at')->get();

        return [
            'comisiones' => $comisiones,
            'total_base' => $comisiones->sum('comision_base'),
            'total_penalizaciones' => $comisiones->sum('penalizacion'),
            'total_final' => $comisiones->sum('comision_final'),
            'cantidad' => $comisiones->count(),
            'por_empleado' => $comisiones->groupBy(fn ($c) => $c->usuario->name ?? 'N/A')->map(fn ($group) => [
                'base' => $group->sum('comision_base'),
                'penalizacion' => $group->sum('penalizacion'),
                'final' => $group->sum('comision_final'),
            ]),
        ];
    }

    public function reporteGastos(?string $fechaInicio, ?string $fechaFin, ?int $sedeId, ?int $categoriaId, ?string $estado)
    {
        $query = Gasto::with(['categoria', 'user', 'sede']);

        if ($fechaInicio) {
            $query->whereDate('gas_fecha', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('gas_fecha', '<=', $fechaFin);
        }
        if ($sedeId) {
            $query->where('fksede', $sedeId);
        }
        if ($categoriaId) {
            $query->where('fkcategoria', $categoriaId);
        }
        if ($estado) {
            $query->where('estado', $estado);
        }

        $gastos = $query->orderByDesc('gas_fecha')->get();

        return [
            'gastos' => $gastos,
            'total' => $gastos->sum('gas_monto'),
            'cantidad' => $gastos->count(),
            'por_categoria' => $gastos->groupBy(fn ($g) => $g->categoria->cat_nombre ?? 'Sin categoría')->map->sum('gas_monto'),
        ];
    }

    public function reporteCaja(?string $fechaInicio, ?string $fechaFin, ?int $sedeId, ?int $empleadoId)
    {
        $query = \App\Models\Caja::with(['usuario', 'sede']);

        if ($fechaInicio) {
            $query->whereDate('fecha_apertura', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('fecha_apertura', '<=', $fechaFin);
        }
        if ($sedeId) {
            $query->where('fksede', $sedeId);
        }
        if ($empleadoId) {
            $query->where('fkuser', $empleadoId);
        }

        $cajas = $query->orderByDesc('fecha_apertura')->get();

        return [
            'cajas' => $cajas,
            'cantidad' => $cajas->count(),
            'total_diferencia' => $cajas->sum('diferencia'),
            'con_diferencia' => $cajas->filter(fn ($c) => $c->diferencia && $c->diferencia != 0)->count(),
        ];
    }

    public function reporteVencimientos(?int $mes, ?int $anio, ?int $sedeId, ?string $estado)
    {
        $query = MembresiaAlumno::with(['alumno.sede', 'membresia']);

        if ($mes) {
            $query->whereMonth('fecha_fin', $mes);
        }
        if ($anio) {
            $query->whereYear('fecha_fin', $anio);
        }
        if ($sedeId) {
            $query->whereHas('alumno', fn ($q) => $q->where('fksede', $sedeId));
        }

        $hoy = now()->format('Y-m-d');

        if ($estado === 'por_vencer') {
            $fechaLimite = now()->addDays(5)->format('Y-m-d');
            $query->where('estado', 'activa')->whereBetween('fecha_fin', [$hoy, $fechaLimite]);
        } elseif ($estado === 'vencido') {
            $query->where(function ($q) use ($hoy) {
                $q->where('estado', 'vencida')
                    ->orWhere(function ($q2) use ($hoy) {
                        $q2->where('estado', 'activa')->where('fecha_fin', '<', $hoy);
                    });
            });
        }

        $vencimientos = $query->orderBy('fecha_fin')->get();

        return [
            'vencimientos' => $vencimientos,
            'cantidad' => $vencimientos->count(),
        ];
    }
}
