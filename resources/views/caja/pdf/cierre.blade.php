<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cierre de Caja #{{ $caja->id_caja }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #E84B7A;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #E84B7A;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-box {
            background-color: #f9f9f9;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 3px solid #E84B7A;
        }
        .info-box p {
            margin: 3px 0;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h2 {
            background-color: #E84B7A;
            color: white;
            padding: 8px;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-box {
            background-color: #E84B7A;
            color: white;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        .total-box h3 {
            margin: 0;
            font-size: 18px;
        }
        .total-box p {
            margin: 5px 0 0 0;
            font-size: 24px;
            font-weight: bold;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .summary-cell h4 {
            margin: 0 0 5px 0;
            font-size: 11px;
            color: #666;
        }
        .summary-cell p {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        .positive { color: #10b981; }
        .negative { color: #ef4444; }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CIERRE DE CAJA</h1>
        <p><strong>Sede:</strong> {{ $caja->sede->sede_nombre ?? 'N/A' }}</p>
        <p><strong>Empleado:</strong> {{ $caja->usuario->name ?? 'N/A' }}</p>
    </div>

    <div class="info-box">
        <p><strong>Fecha de Apertura:</strong> {{ $caja->fecha_apertura->format('d/m/Y H:i') }}</p>
        <p><strong>Fecha de Cierre:</strong> {{ $caja->fecha_cierre ? $caja->fecha_cierre->format('d/m/Y H:i') : 'N/A' }}</p>
        <p><strong>Monto Inicial:</strong> S/ {{ number_format($caja->monto_inicial, 2) }}</p>
    </div>

    <div class="section">
        <h2>VENTAS ({{ $ventas['cantidad'] }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Alumno</th>
                    <th>Producto</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventas['ventas'] as $venta)
                <tr>
                    <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $venta->alumno->nombreCompleto ?? 'Venta rápida' }}</td>
                    <td>{{ $venta->producto->prod_nombre ?? '-' }}</td>
                    <td class="text-right">S/ {{ number_format($venta->venta_total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No hay ventas registradas</td>
                </tr>
                @endforelse
                <tr style="background-color: #f5f5f5; font-weight: bold;">
                    <td colspan="3" class="text-right">TOTAL VENTAS:</td>
                    <td class="text-right">S/ {{ number_format($ventas['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>PAGOS DE MEMBRESÍAS ({{ $pagos['cantidad'] }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Alumno</th>
                    <th>Membresía</th>
                    <th>Método</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pagos['pagos'] as $pago)
                <tr>
                    <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $pago->alumno->nombreCompleto ?? '-' }}</td>
                    <td>{{ $pago->membresia->mem_nombre ?? '-' }}</td>
                    <td>{{ $pago->metodo->metod_nombre ?? '-' }}</td>
                    <td class="text-right">S/ {{ number_format($pago->pag_monto, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No hay pagos registrados</td>
                </tr>
                @endforelse
                <tr style="background-color: #f5f5f5; font-weight: bold;">
                    <td colspan="4" class="text-right">TOTAL PAGOS:</td>
                    <td class="text-right">S/ {{ number_format($pagos['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if(count($pagos['por_metodo']) > 0)
        <h3 style="margin-top: 10px; font-size: 12px;">Desglose por Método de Pago:</h3>
        <table>
            <thead>
                <tr>
                    <th>Método</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagos['por_metodo'] as $metodo => $monto)
                <tr>
                    <td>{{ $metodo }}</td>
                    <td class="text-right">S/ {{ number_format($monto, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="section">
        <h2>GASTOS APROBADOS ({{ $gastos['cantidad'] }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Categoría</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gastos['gastos'] as $gasto)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($gasto->gas_fecha)->format('d/m/Y') }}</td>
                    <td>{{ $gasto->gas_concepto }}</td>
                    <td>{{ $gasto->categoria->cat_nombre ?? '-' }}</td>
                    <td class="text-right">S/ {{ number_format($gasto->gas_monto, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No hay gastos aprobados</td>
                </tr>
                @endforelse
                <tr style="background-color: #f5f5f5; font-weight: bold;">
                    <td colspan="3" class="text-right">TOTAL GASTOS:</td>
                    <td class="text-right">S/ {{ number_format($gastos['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>COMISIONES ({{ $comisiones['cantidad'] }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Tipo</th>
                    <th class="text-right">Base</th>
                    <th class="text-right">Penalización</th>
                    <th class="text-right">Final</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comisiones['comisiones'] as $comision)
                <tr>
                    <td>{{ $comision->usuario->name ?? '-' }}</td>
                    <td>{{ $comision->tipo === 'membresia' ? 'Membresía' : 'Venta' }}</td>
                    <td class="text-right">S/ {{ number_format($comision->comision_base, 2) }}</td>
                    <td class="text-right" style="color: #ef4444;">- S/ {{ number_format($comision->penalizacion, 2) }}</td>
                    <td class="text-right" style="color: #10b981; font-weight: bold;">S/ {{ number_format($comision->comision_final, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No hay comisiones registradas</td>
                </tr>
                @endforelse
                <tr style="background-color: #f5f5f5; font-weight: bold;">
                    <td colspan="2" class="text-right">TOTALES:</td>
                    <td class="text-right">S/ {{ number_format($comisiones['total_base'], 2) }}</td>
                    <td class="text-right" style="color: #ef4444;">- S/ {{ number_format($comisiones['total_penalizaciones'], 2) }}</td>
                    <td class="text-right" style="color: #10b981;">S/ {{ number_format($comisiones['total_final'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="total-box">
        <h3>RESUMEN DE CIERRE</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell">
                    <h4>Monto Inicial</h4>
                    <p>S/ {{ number_format($caja->monto_inicial, 2) }}</p>
                </div>
                <div class="summary-cell">
                    <h4>Total Ingresos</h4>
                    <p class="positive">S/ {{ number_format($ventas['total'] + $pagos['total'], 2) }}</p>
                </div>
                <div class="summary-cell">
                    <h4>Total Egresos</h4>
                    <p class="negative">S/ {{ number_format($gastos['total'], 2) }}</p>
                </div>
            </div>
        </div>
        <p style="margin-top: 15px; font-size: 14px;">Monto Esperado en Caja:</p>
        <p style="font-size: 28px;">S/ {{ number_format($caja->total_ingresos_esperado, 2) }}</p>
        <p style="margin-top: 10px; font-size: 14px;">Monto Entregado:</p>
        <p style="font-size: 28px;">S/ {{ number_format($caja->monto_entregado, 2) }}</p>
        <p style="margin-top: 10px; font-size: 14px;">Diferencia:</p>
        <p style="font-size: 28px;" class="{{ $caja->diferencia >= 0 ? 'positive' : 'negative' }}">
            S/ {{ number_format($caja->diferencia, 2) }}
        </p>
    </div>

    <div class="footer">
        <p>Documento generado el {{ date('d/m/Y H:i') }}</p>
        <p>SIGG - Sistema Integral de Gestión para Gimnasios</p>
    </div>
</body>
</html>
