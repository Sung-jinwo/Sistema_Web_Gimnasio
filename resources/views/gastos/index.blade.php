@extends('layouts.app')

@section('title', 'Gastos')
@section('page-title', 'Gastos')
@section('page-subtitle', 'Registro de gastos del gimnasio')

@section('content')
<div x-data="{ showRegistrarModal: false }" class="space-y-6">

    <div class="flex justify-end">
        <x-button @click="showRegistrarModal = true" class="bg-pink-600 hover:bg-pink-700 focus:ring-pink-500">
            <i class="fas fa-plus"></i> Registrar Gasto
        </x-button>
    </div>

    <x-table :headers="['Fecha', 'Concepto', 'Categoria', 'Monto', 'Usuario']">
        @forelse($gastos ?? [] as $gasto)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-700">{{ $gasto->gast_fecha ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $gasto->gast_concepto ?? '' }}</td>
            <td class="px-6 py-4 text-sm">
                <x-badge variant="default">{{ $gasto->gast_categoria ?? '' }}</x-badge>
            </td>
            <td class="px-6 py-4 text-sm font-semibold text-red-600">S/ {{ number_format($gasto->gast_monto ?? 0, 2) }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $gasto->user->name ?? '' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-receipt text-4xl text-gray-300 mb-3"></i>
                <p>No hay gastos registrados</p>
            </td>
        </tr>
        @endforelse
    </x-table>

    @if(isset($gastos) && $gastos->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $gastos->firstItem() ?? 0 }} a {{ $gastos->lastItem() ?? 0 }} de {{ $gastos->total() }} registros
        </div>
        <div>{{ $gastos->links() }}</div>
    </div>
    @endif

    <x-modal-form show="showRegistrarModal" title="Registrar Gasto" size="md" headerColor="red">
        <form action="{{ route('gastos.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Concepto</label>
                <input type="text" name="gast_concepto" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent" placeholder="Descripcion del gasto">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select name="gast_categoria" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent">
                    <option value="">Seleccione...</option>
                    <option value="Servicios">Servicios</option>
                    <option value="Mantenimiento">Mantenimiento</option>
                    <option value="Insumos">Insumos</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monto (S/)</label>
                <input type="number" step="0.01" name="gast_monto" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent" placeholder="0.00">
            </div>
            <div class="flex gap-3 pt-4">
                <x-button variant="outline" @click="showRegistrarModal = false" class="flex-1">
                    Cancelar
                </x-button>
                <x-button type="submit" class="flex-1 bg-pink-600 hover:bg-pink-700 focus:ring-pink-500">
                    Guardar
                </x-button>
            </div>
        </form>
    </x-modal-form>
</div>
@endsection
