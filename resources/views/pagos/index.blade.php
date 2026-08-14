@extends('layouts.app')

@section('content')
<div x-data="{ showRegistrarModal: false }" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Pagos</h1>
        @can('create', App\Models\Pago::class)
        <button type="button" @click="showRegistrarModal = true" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Registrar Pago
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('pagos.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por alumno..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="estado_pago" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="completo" {{ request('estado_pago') === 'completo' ? 'selected' : '' }}>Completo</option>
                <option value="incompleto" {{ request('estado_pago') === 'incompleto' ? 'selected' : '' }}>Incompleto</option>
                <option value="reservado" {{ request('estado_pago') === 'reservado' ? 'selected' : '' }}>Reservado</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </form>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Pagado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Saldo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Método</th>
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
                        <td class="px-4 py-3 text-sm text-green-600 font-semibold hidden md:table-cell">S/ {{ number_format($pago->monto_pagado ?? $pago->pag_monto, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-red-600 font-semibold hidden md:table-cell">S/ {{ number_format($pago->saldo ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">{{ $pago->metodo->metod_nombre ?? '-' }}</td>
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
                                @can('update', $pago)
                                <button type="button" onclick="editPago({{ $pago->id_pag }})" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                                @can('delete', $pago)
                                <form action="{{ route('pagos.destroy', $pago->id_pag) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar este pago?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No se encontraron pagos.</td>
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

    @can('create', App\Models\Pago::class)
    <x-modal-form show="showRegistrarModal" title="Registrar Pago" subtitle="Complete los datos del pago" icon='<i class="fas fa-money-bill text-white"></i>' size="md" headerColor="purple">
        <form method="POST" action="{{ route('pagos.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="fkalum" class="block text-sm font-medium text-gray-700 mb-1">Alumno <span class="text-red-500">*</span></label>
                <select id="fkalum" name="fkalum" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccionar alumno...</option>
                    @foreach(App\Models\Alumno::where('fksede', auth()->user()->fksede)->where('alum_estado', true)->orderBy('alum_nombre')->get() as $alumno)
                        <option value="{{ $alumno->id_alumno }}">{{ $alumno->nombreCompleto }} - DNI: {{ $alumno->alum_numDoc }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fkmem" class="block text-sm font-medium text-gray-700 mb-1">Membresía <span class="text-red-500">*</span></label>
                <select id="fkmem" name="fkmem" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccionar membresía...</option>
                    @foreach(App\Models\Membresia::where('estado', 'A')->get() as $membresia)
                        <option value="{{ $membresia->id_mem }}">{{ $membresia->mem_nombre }} - S/ {{ number_format($membresia->mem_precio, 2) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="pag_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                    <input type="date" id="pag_inicio" name="pag_inicio" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label for="pag_fin" class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin <span class="text-red-500">*</span></label>
                    <input type="date" id="pag_fin" name="pag_fin" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label for="pag_monto" class="block text-sm font-medium text-gray-700 mb-1">Monto (S/) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="pag_monto" name="pag_monto" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div>
                <label for="fkmetodo" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago <span class="text-red-500">*</span></label>
                <select id="fkmetodo" name="fkmetodo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccionar método...</option>
                    @foreach(App\Models\MetodoPago::all() as $metodo)
                        <option value="{{ $metodo->id_metod }}">{{ $metodo->metod_nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="estado_pago" class="block text-sm font-medium text-gray-700 mb-1">Estado de Pago <span class="text-red-500">*</span></label>
                <select id="estado_pago" name="estado_pago" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="completo">Completo</option>
                    <option value="incompleto">Incompleto</option>
                    <option value="reservado">Reservado</option>
                </select>
            </div>

            <div>
                <label for="fecha_limite_pago" class="block text-sm font-medium text-gray-700 mb-1">Fecha Límite de Pago</label>
                <input type="date" id="fecha_limite_pago" name="fecha_limite_pago" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Opcional: para pagos incompletos</p>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showRegistrarModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    Registrar
                </button>
            </div>
        </form>
    </x-modal-form>
    @endcan
</div>

@push('scripts')
<script>
function editPago(id) {
    window.location.href = `/pagos/${id}/edit`;
}
</script>
@endpush
@endsection
