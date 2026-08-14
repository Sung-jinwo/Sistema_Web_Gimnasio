@extends('layouts.app')

@section('content')
<div x-data="{ showAperturaModal: false, showCierreModal: false, showAnularModal: false }" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Caja</h1>
        @if(!$cajaAbierta)
            @can('abrir', App\Models\Caja::class)
            <button type="button" @click="showAperturaModal = true" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-cash-register mr-2"></i> Abrir Caja
            </button>
            @endcan
        @endif
    </div>

    @if($cajaAbierta)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-pink-600 to-pink-700 px-6 py-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h2 class="text-xl font-bold text-white">Caja Abierta</h2>
                        <p class="text-white/90 text-sm">Abierta por {{ $cajaAbierta->usuario->name }} el {{ $cajaAbierta->fecha_apertura->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="flex gap-2">
                        @can('cerrar', $cajaAbierta)
                        <button type="button" @click="showCierreModal = true" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                            <i class="fas fa-lock mr-1"></i> Cerrar Caja
                        </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="p-6">
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
                        <p class="text-sm text-gray-500">Total Pagos ({{ $pagos['cantidad'] }})</p>
                        <p class="text-2xl font-bold text-green-600">S/ {{ number_format($pagos['total'], 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Gastos ({{ $gastos['cantidad'] }})</p>
                        <p class="text-2xl font-bold text-red-600">S/ {{ number_format($gastos['total'], 2) }}</p>
                    </div>
                </div>

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
                            <p class="text-sm text-gray-500 text-center py-2">No hay pagos registrados</p>
                            @endforelse
                        </div>
                    </div>

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
                </div>

                <div class="mt-6 bg-pink-50 rounded-lg p-6">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900">Total Esperado en Caja:</span>
                        <span class="text-3xl font-bold text-pink-600">S/ {{ number_format($montoEsperado, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Movimientos del Turno</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
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
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Apertura</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cierre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inicial</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entregado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diferencia</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($cajas as $caja)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $caja->usuario->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $caja->sede->sede_nombre ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $caja->fecha_apertura_formato }}</td>
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
                                @if($caja->estado === 'abierta')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Abierta</span>
                                @elseif($caja->estado === 'cerrada')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Cerrada</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Anulada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    @if($caja->estado === 'cerrada')
                                        @can('verPdf', $caja)
                                        <a href="{{ route('caja.pdf', $caja->id_caja) }}" class="text-blue-600 hover:text-blue-900" title="Descargar PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        @endcan
                                        @can('anular', $caja)
                                        <button type="button" @click="showAnularModal = true" onclick="document.getElementById('anularForm').action = '{{ route('caja.anular', $caja->id_caja) }}'" class="text-red-600 hover:text-red-900" title="Anular">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">No hay cajas registradas</td>
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
            <div>
                <label for="monto_inicial" class="block text-sm font-medium text-gray-700 mb-1">Monto Inicial (S/) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="monto_inicial" name="monto_inicial" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="0.00">
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
    <x-modal-form show="showCierreModal" title="Cerrar Caja" subtitle="Ingrese el monto entregado" icon='<i class="fas fa-lock text-white"></i>' size="sm" headerColor="red">
        <form method="POST" action="{{ route('caja.cierre', $cajaAbierta->id_caja) }}" class="space-y-4">
            @csrf
            <div class="bg-pink-50 rounded-lg p-4 mb-4">
                <p class="text-sm text-gray-700">Total Esperado en Caja:</p>
                <p class="text-2xl font-bold text-pink-600">S/ {{ number_format($montoEsperado, 2) }}</p>
            </div>

            <div>
                <label for="monto_entregado" class="block text-sm font-medium text-gray-700 mb-1">Monto Entregado (S/) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="monto_entregado" name="monto_entregado" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="0.00">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showCierreModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Cerrar Caja
                </button>
            </div>
        </form>
    </x-modal-form>
    @endcan
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
