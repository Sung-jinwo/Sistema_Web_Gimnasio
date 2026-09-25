@extends('layouts.app')

@section('page-title','Caja por sede')
@section('page-subtitle','Apertura, movimientos, cierre y consolidado diario')

@section('content')
<div x-data="{ showAperturaModal: false, showCierreModal: false, showAnularModal: false, showObservarModal: false }" class="w-full space-y-5">
    @if(auth()->user()->hasRole('Administrador'))
    <form method="GET" action="{{ route('caja.index') }}" class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                <select name="sede" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todas las sedes</option>
                    @foreach($sedes as $s)
                        <option value="{{ $s->id_sede }}" @selected(request('sede') == $s->id_sede)>{{ $s->sede_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Empleado</label>
                <select name="empleado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos los empleados</option>
                    @foreach($empleados as $e)
                        <option value="{{ $e->id }}" @selected(request('empleado') == $e->id)>{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha operativa</label>
                <input type="date" name="fecha" value="{{ request('fecha') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Pendientes de revisión</option>
                    @foreach(['abierta' => 'Abierta', 'pendiente_cierre' => 'Pendiente de cierre', 'pendiente_revision' => 'Pendiente de revisión', 'observada' => 'Observada', 'cerrada' => 'Cerrada', 'anulada' => 'Anulada'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-eye mr-2"></i> Ver cajas
            </button>
        </div>
        <div class="flex gap-2 mt-3">
            <a href="{{ route('caja.index', ['fecha' => today()->format('Y-m-d')]) }}" class="px-3 py-1.5 text-sm rounded-lg border {{ request('fecha') === today()->format('Y-m-d') ? 'bg-pink-600 text-white border-pink-600' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Hoy</a>
            <a href="{{ route('caja.index', ['fecha' => today()->subDay()->format('Y-m-d')]) }}" class="px-3 py-1.5 text-sm rounded-lg border {{ request('fecha') === today()->subDay()->format('Y-m-d') ? 'bg-pink-600 text-white border-pink-600' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Ayer</a>
            @if(request()->hasAny(['sede', 'empleado', 'fecha', 'estado', 'caja']))
            <a href="{{ route('caja.index') }}" class="px-3 py-1.5 text-sm rounded-lg border bg-white text-gray-700 hover:bg-gray-50">Ver pendientes</a>
            @endif
        </div>
    </form>
    @endif

    @if(auth()->user()->hasRole('Administrador'))
    <div class="grid md:grid-cols-3 gap-4">
        @foreach($consolidado as $item)
        <article class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="font-bold text-gray-900 mb-2">{{ $item['sede'] }}</h3>
            <p class="text-sm text-gray-500">Esperado: S/ {{ number_format($item['esperado'], 2) }}</p>
            <p class="text-sm">Entregado: <b>S/ {{ number_format($item['entregado'], 2) }}</b></p>
            <p class="text-sm">Diferencia: S/ {{ number_format($item['diferencia'], 2) }}</p>
        </article>
        @endforeach
    </div>
    @endif
    @if(!$cajaPropiaAbierta && !$bloqueoApertura)
        @can('abrir', App\Models\Caja::class)
        <div class="flex justify-end mb-6">
            <button type="button" @click="showAperturaModal = true" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-cash-register mr-2"></i> Abrir Caja
            </button>
        </div>
        @endcan
    @endif
    @if(!$cajaPropiaAbierta && $bloqueoApertura)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900" role="alert">
            <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-2"></i>{{ $bloqueoApertura }}</p>
        </div>
    @endif

    @if($cajaAbierta)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-pink-600 to-pink-700 px-6 py-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h2 class="text-xl font-bold text-white">Caja {{ $cajaAbierta->estado_formato }}</h2>
                        <p class="text-white/90 text-sm">{{ $cajaAbierta->usuario->name }} · día operativo {{ $cajaAbierta->fecha_operativa?->format('d/m/Y') }} · apertura {{ $cajaAbierta->fecha_apertura->format('H:i') }}</p>
                    </div>
                    <div class="flex gap-2">
                        @can('cerrar', $cajaAbierta)
                        <button type="button" @click="showCierreModal = true" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                            <i class="fas fa-paper-plane mr-1"></i> {{ $cajaAbierta->estado === 'observada' ? 'Corregir y reenviar' : 'Enviar cierre' }}
                        </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if($cajaAbierta->estado === 'observada')
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                        <p class="font-semibold">Cierre observado por Administración</p>
                        <p class="mt-1 text-sm">{{ $cajaAbierta->observacion_revision }}</p>
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Monto Inicial</p>
                        <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($cajaAbierta->monto_inicial, 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Ventas ({{ $ventas['cantidad'] }})</p>
                        <p class="text-2xl font-bold text-blue-600">S/ {{ number_format($ventas['total'], 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Abonos recibidos ({{ $pagos['cantidad'] }})</p>
                        <p class="text-2xl font-bold text-green-600">S/ {{ number_format($pagos['total'], 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Gastos ({{ $gastos['cantidad'] }})</p>
                        <p class="text-2xl font-bold text-red-600">S/ {{ number_format($gastos['total'], 2) }}</p>
                    </div>
                </div>

                @if($esAdmin || !in_array($cajaAbierta->estado, ['abierta', 'pendiente_cierre']))
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-3">Pagos por Método</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            @forelse($pagos['por_metodo'] as $metodo => $monto)
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 last:border-0">
                                <span class="text-sm text-gray-700">{{ $metodo }}</span>
                                <span class="text-sm font-semibold text-gray-900">S/ {{ number_format($monto, 2) }}</span>
                            </div>
                            @empty
                            <p class="text-sm text-gray-500 text-center py-2">No hay abonos registrados</p>
                            @endforelse
                        </div>
                    </div>

                    @if($esAdmin)
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-3">Comisiones</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="text-sm text-gray-700">Comisión Base</span>
                                <span class="text-sm font-semibold text-gray-900">S/ {{ number_format($comisiones['total_base'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="text-sm text-gray-700">Penalizaciones</span>
                                <span class="text-sm font-semibold text-red-600">- S/ {{ number_format($comisiones['total_penalizaciones'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm font-bold text-gray-900">Comisión Final</span>
                                <span class="text-sm font-bold text-green-600">S/ {{ number_format($comisiones['total_final'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="mt-6 bg-pink-50 rounded-lg p-6">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900">Total Esperado en Caja:</span>
                        <span class="text-3xl font-bold text-pink-600">S/ {{ number_format($montoEsperado, 2) }}</span>
                    </div>
                </div>
                @endif

                @if($cajaAbierta->detallesCierre->isNotEmpty())
                <div class="mt-6 overflow-x-auto">
                    <h3 class="mb-3 text-lg font-bold text-gray-900">Conciliación por método</h3>
                    <table data-responsive="off" class="min-w-full divide-y divide-gray-200 border">
                        <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Método</th><th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Esperado</th><th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Declarado</th><th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Diferencia</th></tr></thead>
                        <tbody class="divide-y">
                            @foreach($cajaAbierta->detallesCierre as $detalle)
                            <tr><td class="px-3 py-2 text-sm">{{ $detalle->metodo->metod_nombre }}</td><td class="px-3 py-2 text-sm text-right">S/ {{ number_format($detalle->monto_esperado, 2) }}</td><td class="px-3 py-2 text-sm text-right">S/ {{ number_format($detalle->monto_declarado, 2) }}</td><td class="px-3 py-2 text-sm text-right font-semibold {{ $detalle->diferencia > 0 ? 'text-red-600' : ($detalle->diferencia < 0 ? 'text-amber-600' : 'text-green-600') }}">S/ {{ number_format($detalle->diferencia, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if($esAdmin && $cajaAbierta->estado === 'pendiente_revision')
                @if($tieneGastosPendientes)
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Debes aprobar o rechazar los gastos pendientes antes de aprobar este cierre.</div>
                    <div class="mt-3 overflow-x-auto">
                        <h3 class="mb-3 text-lg font-bold text-gray-900">Gastos pendientes</h3>
                        <table data-responsive="off" class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Concepto</th><th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Método</th><th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Monto</th><th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Registrado por</th><th class="px-3 py-2 text-center text-xs uppercase text-gray-500">Acciones</th></tr></thead>
                            <tbody class="divide-y">
                                @foreach($gastosPendientes as $gasto)
                                <tr>
                                    <td class="px-3 py-2 text-sm">{{ $gasto->gas_concepto }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $gasto->metodo->metod_nombre ?? 'Efectivo (histórico)' }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-semibold">S/ {{ number_format($gasto->gas_monto, 2) }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $gasto->user->name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex justify-center items-center gap-1">
                                            <form method="POST" action="{{ route('gastos.aprobar', $gasto->id_gasto) }}" class="inline">
                                                @csrf
                                                <button class="btn-accion text-green-600 hover:text-green-900" title="Aprobar gasto">
                                                    <i class="fas fa-circle-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('gastos.rechazar', $gasto->id_gasto) }}" class="flex items-center gap-1">
                                                @csrf
                                                <input name="motivo_rechazo" required maxlength="500" placeholder="Motivo*" class="w-36 px-2 py-1 text-xs border rounded-lg">
                                                <button class="btn-accion text-red-600 hover:text-red-900" title="Rechazar gasto">
                                                    <i class="fas fa-circle-xmark"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <form method="POST" action="{{ route('comisiones.aprobar-seleccion') }}" id="formAprobarLote">
                    @csrf
                    <div class="mt-6 overflow-x-auto">
                        <div class="mb-3 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                            <h3 class="text-lg font-bold text-gray-900">Comisiones por revisar</h3>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                <i class="fas fa-check-double mr-2"></i> Aprobar seleccionadas
                            </button>
                        </div>
                        @if($comisionesBloqueantes->isNotEmpty())
                            <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Hay {{ $comisionesBloqueantes->count() }} comisión(es) sin resolver. Apruébalas u obsérvalas antes del cierre definitivo.</div>
                        @endif
                        <table data-responsive="off" class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-center text-xs uppercase text-gray-500">Sel.</th><th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Beneficiario</th><th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Venta / alumno</th><th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Total vendido</th><th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Habilitada</th><th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Penalización</th><th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Final</th><th class="px-3 py-2 text-center text-xs uppercase text-gray-500">Estado</th><th class="px-3 py-2 text-center text-xs uppercase text-gray-500">Acciones</th></tr></thead>
                            <tbody class="divide-y">
                                @forelse($comisiones['comisiones'] as $comision)
                                <tr>
                                    <td class="px-3 py-2 text-center">
                                        @if(in_array($comision->estado, ['pendiente_revision', 'observada'], true))
                                            <input type="checkbox" name="comisiones[]" value="{{ $comision->id_comision }}" form="formAprobarLote" class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm">{{ $comision->usuario->name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        @if($comision->venta)
                                            <div class="font-medium">#{{ $comision->venta->id_venta }} · {{ $comision->venta->alumno->nombreCompleto ?? 'Venta rápida' }}</div>
                                            <div class="text-xs text-gray-500">{{ ucfirst($comision->venta->tipo_venta) }}</div>
                                        @else
                                            <span class="text-gray-400">Sin venta</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right">S/ {{ number_format($comision->venta->venta_total ?? 0, 2) }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $comision->fecha_habilitacion?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-semibold text-red-600">- S/ {{ number_format($comision->penalizacion, 2) }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-bold text-green-600">S/ {{ number_format($comision->comision_final, 2) }}</td>
                                    <td class="px-3 py-2 text-center text-sm">{{ $comision->estado_formato }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex justify-center items-center gap-2">
                                            @if(in_array($comision->estado, ['pendiente_revision', 'observada'], true))
                                            <form method="POST" action="{{ route('comisiones.aprobar', $comision->id_comision) }}" class="inline">
                                                @csrf
                                                <button class="btn-accion text-green-600 hover:text-green-900" title="Aprobar (no modifica el importe)">
                                                    <i class="fas fa-circle-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('comisiones.observar', $comision->id_comision) }}" class="flex items-center gap-1">
                                                @csrf
                                                <input name="motivo_observacion" required maxlength="1000" placeholder="Motivo*" class="w-36 px-2 py-1 text-xs border rounded-lg">
                                                <button class="btn-accion text-red-600 hover:text-red-900" title="Observar">
                                                    <i class="fas fa-circle-exclamation"></i>
                                                </button>
                                            </form>
                                            @else
                                            <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </div>
                                        @if($comision->estado === 'observada' && $comision->motivo_observacion)
                                        <p class="mt-1 text-xs text-red-600">{{ $comision->motivo_observacion }}</p>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="px-3 py-4 text-center text-sm text-gray-500">No hay comisiones vinculadas a esta caja.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
                <p class="mt-2 text-xs text-gray-500">Caja de {{ $cajaAbierta->usuario->name }} · las comisiones pertenecen a cada vendedor (beneficiario).</p>
                <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
                    <button type="button" @click="showObservarModal = true" class="px-4 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50">Observar cierre</button>
                    <form method="POST" action="{{ route('caja.aprobar', $cajaAbierta) }}" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <input name="observacion_revision" maxlength="1000" placeholder="Observación opcional" class="px-3 py-2 border rounded-lg">
                        <button @disabled($tieneGastosPendientes || $comisionesBloqueantes->isNotEmpty()) class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">Aprobar cierre</button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Movimientos del Turno</h3>
            </div>
            <div class="overflow-x-auto">
                <table data-card="compacta" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hora</th>
                            <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                            <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                            <th data-card-prioritario class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($operaciones['movimientos'] as $mov)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $mov->created_at->format('H:i') }}</td>
                            <td class="px-4 py-3">
                                @if($mov->tipo === 'ingreso')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ingreso</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Egreso</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $mov->concepto }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $mov->usuario->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold {{ $mov->tipo === 'ingreso' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $mov->tipo === 'ingreso' ? '+' : '-' }}S/ {{ number_format($mov->monto, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay movimientos registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <i class="fas fa-cash-register text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay caja abierta</h3>
            <p class="text-gray-500 mb-4">Presiona "Abrir Caja" para iniciar un nuevo turno</p>
        </div>
    @endif

    <div class="mt-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Historial de Cajas</h2>
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table data-card="compacta" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                            <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                            <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Apertura</th>
                            <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cierre</th>
                            <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inicial</th>
                            <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entregado</th>
                            <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diferencia</th>
                            <th data-card-oculto class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Por resolver</th>
                            <th data-card-prioritario class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($cajas as $caja)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $caja->usuario->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $caja->sede->sede_nombre ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <div class="font-medium text-gray-900">{{ $caja->fecha_operativa?->format('d/m/Y') ?? '-' }}</div>
                                <div class="text-xs">Ap. {{ $caja->fecha_apertura_formato }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $caja->fecha_cierre_formato }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($caja->monto_inicial, 2) }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                @if($caja->monto_entregado !== null)
                                    S/ {{ number_format($caja->monto_entregado, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-bold {{ $caja->diferencia_formato }}">
                                @if($caja->diferencia !== null)
                                    S/ {{ number_format($caja->diferencia, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(($caja->gastos_pendientes_count ?? 0) > 0 || ($caja->comisiones_sin_resolver_count ?? 0) > 0)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800" title="{{ $caja->gastos_pendientes_count }} gasto(s) pendiente(s), {{ $caja->comisiones_sin_resolver_count }} comisión(es) sin resolver">
                                        G: {{ $caja->gastos_pendientes_count }} · C: {{ $caja->comisiones_sin_resolver_count }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php($estadoColor = match($caja->estado) { 'abierta' => 'bg-green-100 text-green-800', 'cerrada' => 'bg-blue-100 text-blue-800', 'pendiente_revision' => 'bg-yellow-100 text-yellow-800', 'pendiente_cierre', 'observada' => 'bg-red-100 text-red-800', default => 'bg-gray-100 text-gray-800' })
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $estadoColor }}">{{ $caja->estado_formato }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    @if(in_array($caja->estado, ['abierta', 'pendiente_cierre', 'pendiente_revision', 'observada']))
                                        <a href="{{ route('caja.index', ['caja' => $caja->id_caja]) }}" class="btn-accion text-pink-600 hover:text-pink-900" title="Revisar caja"><i class="fas fa-eye"></i></a>
                                    @endif
                                    @if($caja->estado === 'cerrada')
                                        @can('verPdf', $caja)
                                        <a href="{{ route('caja.pdf', $caja->id_caja) }}" target="_blank" class="btn-accion text-blue-600 hover:text-blue-900" title="Descargar PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        @endcan
                                        @can('anular', $caja)
                                        <button type="button" @click="showAnularModal = true" onclick="document.getElementById('anularForm').action = '{{ route('caja.anular', $caja->id_caja) }}'" class="btn-accion text-red-600 hover:text-red-900" title="Anular">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">No hay cajas registradas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cajas->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $cajas->links() }}
            </div>
            @endif
        </div>
    </div>

    @can('abrir', App\Models\Caja::class)
    <x-modal-form show="showAperturaModal" title="Abrir Caja" subtitle="Ingrese el monto inicial" icon='<i class="fas fa-cash-register text-white"></i>' size="sm" headerColor="green">
        <form method="POST" action="{{ route('caja.apertura') }}" class="space-y-4">
            @csrf
            @if(auth()->user()->hasRole('Administrador'))
            <div>
                <label for="fksede" class="block text-sm font-medium text-gray-700 mb-1">Sede <span class="text-red-500">*</span></label>
                <select id="fksede" name="fksede" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccione una sede</option>
                    @foreach($sedes as $s)
                        <option value="{{ $s->id_sede }}">{{ $s->sede_nombre }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label for="monto_inicial" class="block text-sm font-medium text-gray-700 mb-1">Monto Inicial (S/) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="monto_inicial" name="monto_inicial" value="0" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="0.00">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showAperturaModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Abrir Caja
                </button>
            </div>
        </form>
    </x-modal-form>
    @endcan

    @if($cajaAbierta)
    @can('cerrar', $cajaAbierta)
    <x-modal-form show="showCierreModal" title="Enviar cierre" subtitle="Cuenta y declara cada método sin consultar el esperado" icon='<i class="fas fa-lock text-white"></i>' size="md" headerColor="red">
        <form method="POST" action="{{ route('caja.cierre', $cajaAbierta->id_caja) }}" class="space-y-4">
            @csrf
            <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Realiza el conteo antes de enviar. La comparación con el sistema se mostrará después.</div>
            @foreach($metodosCierre as $metodo)
                @php($detalleActual = $cajaAbierta->detallesCierre->firstWhere('fkmetodo', $metodo->id_metod))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $metodo->metod_nombre }} (S/) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="declarados[{{ $metodo->id_metod }}]" value="{{ old('declarados.'.$metodo->id_metod, $detalleActual?->monto_declarado ?? 0) }}" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="0.00">
                </div>
            @endforeach
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $esAdmin && (int) auth()->id() !== (int) $cajaAbierta->fkuser ? 'Motivo del cierre en representación' : 'Observación del empleado' }}
                </label>
                <textarea name="observacion" maxlength="1000" rows="2" @required($esAdmin && (int) auth()->id() !== (int) $cajaAbierta->fkuser) class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('observacion', $cajaAbierta->observacion) }}</textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showCierreModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Enviar a revisión
                </button>
            </div>
        </form>
    </x-modal-form>
    @endcan
    @endif

    @if($cajaAbierta && $esAdmin && $cajaAbierta->estado === 'pendiente_revision')
    <x-modal-form show="showObservarModal" title="Observar cierre" subtitle="El empleado podrá corregir los importes declarados" icon='<i class="fas fa-triangle-exclamation text-white"></i>' size="sm" headerColor="red">
        <form method="POST" action="{{ route('caja.observar', $cajaAbierta) }}" class="space-y-4">
            @csrf
            <textarea name="observacion_revision" required maxlength="1000" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Motivo de la observación..."></textarea>
            <div class="flex gap-3"><button type="button" @click="showObservarModal=false" class="flex-1 px-4 py-2 border rounded-lg">Cancelar</button><button class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg">Devolver cierre</button></div>
        </form>
    </x-modal-form>
    @endif

    <x-modal-form show="showAnularModal" title="Anular Caja" subtitle="Ingrese el motivo de anulación" icon='<i class="fas fa-ban text-white"></i>' size="sm" headerColor="red">
        <form id="anularForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="observacion" class="block text-sm font-medium text-gray-700 mb-1">Motivo de Anulación</label>
                <textarea id="observacion" name="observacion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Ingrese el motivo..."></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showAnularModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Anular Caja
                </button>
            </div>
        </form>
    </x-modal-form>
</div>
@endsection
