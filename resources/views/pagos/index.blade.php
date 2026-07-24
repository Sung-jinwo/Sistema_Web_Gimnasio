@extends('layouts.app')

@section('title', 'Pagos')
@section('page-title', 'Pagos')
@section('page-subtitle', 'Registro de pagos de alumnos')

@section('content')
<div class="space-y-6">

    <div class="flex justify-end">
        <a href="{{ route('pagos.create') }}" class="inline-flex items-center gap-2 bg-pink-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-pink-700 transition-colors text-sm">
            <i class="fas fa-plus"></i> Registrar Pago
        </a>
    </div>

    <x-table :headers="['Alumno', 'Membresia', 'Inicio', 'Fin', 'Monto', 'Estado', 'Metodo Pago']">
        @forelse($pagos ?? [] as $pago)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $pago->alumno->alum_nombre ?? '' }} {{ $pago->alumno->alum_apellido ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $pago->membresia->memb_nombre ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $pago->pago_fecha_inicio ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $pago->pago_fecha_fin ?? '' }}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-900">S/ {{ number_format($pago->pago_monto ?? 0, 2) }}</td>
            <td class="px-6 py-4 text-sm">
                @if(($pago->pago_estado ?? '') === 'Completo')
                    <x-badge variant="success">Completo</x-badge>
                @elseif(($pago->pago_estado ?? '') === 'Incompleto')
                    <x-badge variant="warning">Incompleto</x-badge>
                @else
                    <x-badge variant="default">{{ $pago->pago_estado ?? 'N/A' }}</x-badge>
                @endif
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $pago->metodoPago->metpago_nombre ?? '' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-credit-card text-4xl text-gray-300 mb-3"></i>
                <p>No hay pagos registrados</p>
            </td>
        </tr>
        @endforelse
    </x-table>

    @if(isset($pagos) && $pagos->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $pagos->firstItem() ?? 0 }} a {{ $pagos->lastItem() ?? 0 }} de {{ $pagos->total() }} registros
        </div>
        <div>{{ $pagos->links() }}</div>
    </div>
    @endif
</div>
@endsection
