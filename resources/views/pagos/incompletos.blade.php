@extends('layouts.app')

@section('content')
@section('page-title','Saldos pendientes')
@section('page-subtitle','Pagos parciales, fechas acordadas y cuotas')
<div x-data="{ showAbonarModal: false, cuotaSeleccionada: null }" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Pagos Incompletos</h1>
        <a href="{{ route('pagos.cuotas.vencidas') }}" class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i> Ver Cuotas Vencidas
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pagos as $pago)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $pago->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $pago->alumno->nombreCompleto ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $pago->membresia->mem_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($pago->total ?? $pago->pag_monto, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-green-600 font-semibold">S/ {{ number_format($pago->monto_pagado ?? $pago->pag_monto, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-red-600 font-semibold">S/ {{ number_format($pago->saldo ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($pago->estado_pago === 'completo')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completo</span>
                            @elseif($pago->estado_pago === 'incompleto')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Incompleto</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Reservado</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                @if($pago->cuotas->count() > 0)
                                <button type="button" onclick="verCuotas({{ json_encode($pago->cuotas) }})" class="text-blue-600 hover:text-blue-900" title="Ver cuotas">
                                    <i class="fas fa-list"></i>
                                </button>
                                @endif
                                @can('update', $pago)
                                <button type="button" onclick="abonarPago({{ $pago->id_pag }}, {{ $pago->saldo ?? 0 }})" class="text-green-600 hover:text-green-900" title="Abonar">
                                    <i class="fas fa-money-bill-wave mr-1"></i> Registrar abono
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay pagos incompletos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pagos->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $pagos->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="showAbonarModal" title="Abonar a Cuota" subtitle="Ingrese el monto a abonar" icon='<i class="fas fa-money-bill-wave text-white"></i>' size="sm" headerColor="green">
        <form id="abonarForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="monto_abono" class="block text-sm font-medium text-gray-700 mb-1">Monto a Abonar (S/) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="monto_abono" name="monto" required min="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Saldo disponible: <span id="saldoDisponible">S/ 0.00</span></p>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showAbonarModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Abonar
                </button>
            </div>
        </form>
    </x-modal-form>

    <x-modal show="showCuotasModal" size="lg">
        <h3 class="text-lg font-bold mb-4 text-gray-900">Cuotas del Pago</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cuota</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pagado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha Acordada</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody id="cuotasTableBody" class="bg-white divide-y divide-gray-200">
                </tbody>
            </table>
        </div>
        <x-slot:footer>
            <button type="button" @click="showCuotasModal = false" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Cerrar
            </button>
        </x-slot:footer>
    </x-modal>
</div>

@push('scripts')
<script>
function abonarPago(cuotaId, saldo) {
    document.getElementById('abonarForm').action = `/cuotas/${cuotaId}/abonar`;
    document.getElementById('saldoDisponible').textContent = 'S/ ' + saldo.toFixed(2);
    document.getElementById('monto_abono').max = saldo;
    document.getElementById('monto_abono').value = '';
    Alpine.$data(document.querySelector('[x-data]')).showAbonarModal = true;
}

function verCuotas(cuotas) {
    const tbody = document.getElementById('cuotasTableBody');
    tbody.innerHTML = '';
    
    cuotas.forEach(cuota => {
        const estadoClass = cuota.estado === 'pagada' ? 'bg-green-100 text-green-800' :
                           cuota.estado === 'vencida' ? 'bg-red-100 text-red-800' :
                           cuota.estado === 'parcial' ? 'bg-yellow-100 text-yellow-800' :
                           'bg-gray-100 text-gray-800';
        
        const estadoTexto = cuota.estado === 'pagada' ? 'Pagada' :
                           cuota.estado === 'vencida' ? 'Vencida' :
                           cuota.estado === 'parcial' ? 'Parcial' :
                           'Pendiente';
        
        tbody.innerHTML += `
            <tr>
                <td class="px-4 py-2 text-sm">${cuota.numero_cuota}</td>
                <td class="px-4 py-2 text-sm font-semibold">S/ ${parseFloat(cuota.monto).toFixed(2)}</td>
                <td class="px-4 py-2 text-sm text-green-600">S/ ${parseFloat(cuota.monto_pagado).toFixed(2)}</td>
                <td class="px-4 py-2 text-sm text-red-600">S/ ${parseFloat(cuota.saldo).toFixed(2)}</td>
                <td class="px-4 py-2 text-sm">${cuota.fecha_acordada}</td>
                <td class="px-4 py-2 text-center">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">${estadoTexto}</span>
                </td>
            </tr>
        `;
    });
    
    Alpine.$data(document.querySelector('[x-data]')).showCuotasModal = true;
}
</script>
@endpush
@endsection
