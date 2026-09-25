@extends('layouts.app')

@section('title', 'Detalle de comisión')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ auth()->user()->hasRole('Administrador') ? route('comisiones.index') : route('comisiones.mis-comisiones') }}" class="inline-flex items-center text-pink-600 hover:text-pink-700">
            <i class="fas fa-arrow-left mr-2"></i> Volver al listado
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-pink-600 to-pink-700 px-6 py-4">
            <h1 class="text-2xl font-bold text-white">Detalle de Comisión</h1>
            <p class="text-white/90 text-sm mt-1">Información detallada de la comisión</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <p class="text-sm text-gray-500">Empleado</p>
                    <p class="font-medium text-gray-900">{{ $comision->usuario->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Fecha de Registro</p>
                    <p class="font-medium text-gray-900">{{ $comision->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tipo</p>
                    <p class="font-medium text-gray-900">
                        @if($comision->tipo === 'membresia')
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">Membresía</span>
                        @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Venta</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Estado</p>
                    <p class="font-medium text-gray-900">
                        <x-badge-estado-comision :estado="$comision->estado" />
                    </p>
                    @if($comision->estado === 'esperando_pago')
                        <p class="mt-1 text-xs text-gray-500">Importe base estimado: el total se definirá cuando el cliente complete el pago.</p>
                    @endif
                    @if($comision->estado === 'observada' && $comision->motivo_observacion)
                        <p class="mt-1 text-sm text-red-600"><span class="font-semibold">Motivo:</span> {{ $comision->motivo_observacion }}</p>
                    @endif
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Revisión</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Habilitada el</p>
                        <p class="font-medium text-gray-900">{{ $comision->fecha_habilitacion?->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Aprobada por</p>
                        <p class="font-medium text-gray-900">{{ $comision->aprobadaPor->name ?? '-' }}{{ $comision->fecha_aprobacion ? ' el ' . $comision->fecha_aprobacion->format('d/m/Y H:i') : '' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Caja de revisión</p>
                        <p class="font-medium text-gray-900">{{ $comision->fkcaja ? 'Caja #' . $comision->fkcaja : 'Sin asignar (espera pago)' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Caja de la venta</p>
                        <p class="font-medium text-gray-900">{{ $comision->venta?->fkcaja ? 'Caja #' . $comision->venta->fkcaja : '-' }}</p>
                    </div>
                </div>
            </div>

            @if($comision->venta)
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Venta Asociada</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Alumno</p>
                        <p class="font-medium text-gray-900">{{ $comision->venta->alumno->nombreCompleto ?? 'Venta rápida' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Producto</p>
                        <p class="font-medium text-gray-900">{{ $comision->venta->producto->prod_nombre ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Venta</p>
                        <p class="font-medium text-gray-900">S/ {{ number_format($comision->venta->venta_total, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Fecha Venta</p>
                        <p class="font-medium text-gray-900">{{ $comision->venta->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Cálculo de Comisión</h2>
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Comisión Base:</span>
                            <span class="text-xl font-bold text-gray-900">S/ {{ number_format($comision->comision_base, 2) }}</span>
                        </div>

                        @if($comision->penalizacion > 0)
                        <div class="border-t border-gray-300 pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Fecha Acordada de Pago:</span>
                                <span class="font-medium text-gray-900">{{ $comision->fecha_acordada_formato }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Fecha de Pago Real:</span>
                                <span class="font-medium text-gray-900">{{ $comision->fecha_pago_real_formato }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Días de Retraso:</span>
                                <span class="font-medium text-gray-900">{{ $comision->dias_retraso }} días</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Semanas de Retraso:</span>
                                <span class="font-medium text-gray-900">{{ $calculo['semanas_retraso'] }} semanas</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Penalización (S/5 × semanas):</span>
                                <span class="text-xl font-bold text-red-600">- S/ {{ number_format($comision->penalizacion, 2) }}</span>
                            </div>
                        </div>
                        @else
                        <div class="border-t border-gray-300 pt-4">
                            <p class="text-sm text-gray-500 italic">Sin penalización (pago dentro del período de tolerancia de 7 días)</p>
                        </div>
                        @endif

                        <div class="border-t-2 border-gray-900 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">Comisión Final:</span>
                                <span class="text-2xl font-bold text-green-600">S/ {{ number_format($comision->comision_final, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->hasRole('Administrador') && in_array($comision->estado, ['pendiente_revision', 'observada'], true))
            <div class="border-t border-gray-200 pt-6 mt-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Revisión administrativa</h2>
                <p class="text-sm text-gray-500 mb-3">Aprobar confirma el derecho y el importe calculado (no editable).</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <form action="{{ route('comisiones.aprobar', $comision->id_comision) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                            <i class="fas fa-check-circle mr-2"></i> Aprobar comisión
                        </button>
                    </form>
                    <form action="{{ route('comisiones.observar', $comision->id_comision) }}" method="POST" class="flex-[2] flex gap-2">
                        @csrf
                        <input name="motivo_observacion" required maxlength="1000" placeholder="Motivo de la observación *" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">Observar</button>
                    </form>
                </div>
            </div>
            @endif

            @if($comision->estado === 'aprobada' && auth()->user()->hasRole('Administrador'))
            <div class="border-t border-gray-200 pt-6 mt-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Liquidación (no afecta cajas conciliadas)</h2>
                <form action="{{ route('comisiones.liquidar', $comision->id_comision) }}" method="POST" onsubmit="return confirm('¿Está seguro de liquidar esta comisión?')" class="grid sm:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                        <select name="fkmetodo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Seleccione</option>
                            @foreach($metodosPago as $metodo)
                                <option value="{{ $metodo->id_metod }}">{{ $metodo->metod_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                        <input name="referencia" maxlength="100" placeholder="Opcional" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
                        <input name="observacion" maxlength="1000" placeholder="Opcional" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                            <i class="fas fa-check-circle mr-2"></i> Liquidar Comisión
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
