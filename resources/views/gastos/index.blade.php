@extends('layouts.app')

@section('content')
<div x-data="{ showRegistrarModal: false, showRechazarModal: false, gastoIdRechazar: null }" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Gastos</h1>
        @can('create', App\Models\Gasto::class)
        <button type="button" @click="showRegistrarModal = true" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Registrar Gasto
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('gastos.index') }}" class="flex flex-col sm:flex-row gap-3">
            <select name="estado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="aprobado" {{ request('estado') === 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                <option value="rechazado" {{ request('estado') === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
            </select>
            <select name="fkcategoria" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id_categoria }}" {{ request('fkcategoria') == $categoria->id_categoria ? 'selected' : '' }}>{{ $categoria->cat_nombre }}</option>
                @endforeach
            </select>
            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Usuario</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($gastos as $gasto)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($gasto->gas_fecha)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $gasto->gas_concepto }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 hidden md:table-cell">{{ $gasto->categoria->cat_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-red-600">S/ {{ number_format($gasto->gas_monto, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 hidden lg:table-cell">{{ $gasto->user->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($gasto->estado === 'pendiente')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                            @elseif($gasto->estado === 'aprobado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aprobado</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rechazado</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                @can('update', $gasto)
                                <button type="button" onclick="editGasto({{ $gasto->id_gasto }})" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                                @can('aprobar', $gasto)
                                <form action="{{ route('gastos.aprobar', $gasto->id_gasto) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900" title="Aprobar">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                                <button type="button" @click="showRechazarModal = true; gastoIdRechazar = {{ $gasto->id_gasto }}" class="text-red-600 hover:text-red-900" title="Rechazar">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron gastos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($gastos->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $gastos->links() }}
        </div>
        @endif
    </div>

    @can('create', App\Models\Gasto::class)
    <x-modal-form show="showRegistrarModal" title="Registrar Gasto" subtitle="Complete los datos del gasto" icon='<i class="fas fa-receipt text-white"></i>' size="md" headerColor="red">
        <form method="POST" action="{{ route('gastos.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="gas_concepto" class="block text-sm font-medium text-gray-700 mb-1">Concepto <span class="text-red-500">*</span></label>
                <input type="text" id="gas_concepto" name="gas_concepto" required maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Descripción del gasto">
            </div>

            <div>
                <label for="fkcategoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <select id="fkcategoria" name="fkcategoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccionar categoría...</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id_categoria }}">{{ $categoria->cat_nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="gas_monto" class="block text-sm font-medium text-gray-700 mb-1">Monto (S/) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="gas_monto" name="gas_monto" required min="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="0.00">
            </div>

            <div>
                <label for="gas_fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                <input type="date" id="gas_fecha" name="gas_fecha" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div>
                <label for="gas_observacion" class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
                <textarea id="gas_observacion" name="gas_observacion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
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

    @can('aprobar', new App\Models\Gasto)
    <x-modal-form show="showRechazarModal" title="Rechazar Gasto" subtitle="Ingrese el motivo del rechazo" icon='<i class="fas fa-times-circle text-white"></i>' size="md" headerColor="red">
        <form :action="`/gastos/${gastoIdRechazar}/rechazar`" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="motivo_rechazo" class="block text-sm font-medium text-gray-700 mb-1">Motivo del Rechazo <span class="text-red-500">*</span></label>
                <textarea id="motivo_rechazo" name="motivo_rechazo" required maxlength="500" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Ingrese el motivo por el cual se rechaza este gasto..."></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showRechazarModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Rechazar
                </button>
            </div>
        </form>
    </x-modal-form>
    @endcan
</div>

@push('scripts')
<script>
function editGasto(id) {
    window.location.href = `/gastos/${id}/edit`;
}
</script>
@endpush
@endsection
