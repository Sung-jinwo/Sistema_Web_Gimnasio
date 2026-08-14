@extends('layouts.app')

@section('content')
<div x-data="{ showAbonarModal: false }" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Cuotas Vencidas</h1>
        <a href="{{ route('pagos.incompletos') }}" class="inline-flex items-center px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver a Pagos Incompletos
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Acordada</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Días Vencida</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cuotas as $cuota)
                    @php
                        $alumno = $cuota->pago->alumno ?? $cuota->venta->alumno ?? null;
                        $membresia = $cuota->pago->membresia ?? null;
                        $diasVencida = now()->diffInDays(\Carbon\Carbon::parse($cuota->fecha_acordada));
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $cuota->numero_cuota }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $alumno->nombreCompleto ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $membresia->mem_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($cuota->monto, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-red-600 font-semibold">S/ {{ number_format($cuota->saldo, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $cuota->fecha_acordada_formato }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                {{ $diasVencida }} días
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($cuota->estado === 'vencida')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencida</span>
                            @elseif($cuota->estado === 'parcial')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="abonarCuotaVencida({{ $cuota->id_cuota }}, {{ $cuota->saldo }})" class="text-green-600 hover:text-green-900" title="Abonar">
                                <i class="fas fa-money-bill-wave"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No hay cuotas vencidas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cuotas->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $cuotas->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="showAbonarModal" title="Abonar a Cuota Vencida" subtitle="Ingrese el monto a abonar" icon='<i class="fas fa-money-bill-wave text-white"></i>' size="sm" headerColor="green">
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
</div>

@push('scripts')
<script>
function abonarCuotaVencida(cuotaId, saldo) {
    document.getElementById('abonarForm').action = `/cuotas/${cuotaId}/abonar`;
    document.getElementById('saldoDisponible').textContent = 'S/ ' + saldo.toFixed(2);
    document.getElementById('monto_abono').max = saldo;
    document.getElementById('monto_abono').value = '';
    Alpine.$data(document.querySelector('[x-data]')).showAbonarModal = true;
}
</script>
@endpush
@endsection
