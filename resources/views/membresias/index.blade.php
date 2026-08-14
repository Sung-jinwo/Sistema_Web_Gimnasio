@extends('layouts.app')

@section('content')
<div x-data="{ showCreateModal: false, showEditModal: false }" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Catálogo de Membresías</h1>
        <button type="button" @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Nueva Membresía
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('membresias.index') }}" class="flex flex-col sm:flex-row gap-3">
            <select name="mem_categoria" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todas las categorías</option>
                <option value="Regular" {{ request('mem_categoria') === 'Regular' ? 'selected' : '' }}>Regular</option>
                <option value="Premium" {{ request('mem_categoria') === 'Premium' ? 'selected' : '' }}>Premium</option>
                <option value="VIP" {{ request('mem_categoria') === 'VIP' ? 'selected' : '' }}>VIP</option>
            </select>
            <select name="estado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="A" {{ request('estado') === 'A' ? 'selected' : '' }}>Activo</option>
                <option value="I" {{ request('estado') === 'I' ? 'selected' : '' }}>Inactivo</option>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Comisión</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Duración</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Categoría</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($membresias as $membresia)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $membresia->mem_nombre }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $membresia->mem_tipo }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $membresia->modalidad === 'por_meses' ? 'Por meses' : 'Por fechas' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">S/ {{ number_format($membresia->mem_precio, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">{{ $membresia->comision }}%</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">{{ $membresia->mem_duracion }} días</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm hidden lg:table-cell">
                            <x-badge variant="info">{{ $membresia->mem_categoria }}</x-badge>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($membresia->estado === 'A')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex justify-center gap-2">
                                <button type="button" onclick="editMembresia({{ $membresia->id_mem }})" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if($membresia->estado === 'A')
                                <form action="{{ route('membresias.destroy', $membresia->id_mem) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de desactivar esta membresía?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Desactivar">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No se encontraron membresías.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($membresias->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $membresias->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="showCreateModal" title="Nueva Membresía" subtitle="Complete los datos del plan" icon='<i class="fas fa-award text-white"></i>' size="lg" headerColor="purple">
        <form id="createForm" method="POST" action="{{ route('membresias.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="mem_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" id="mem_nombre" name="mem_nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="mem_precio" class="block text-sm font-medium text-gray-700 mb-1">Precio (S/) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="mem_precio" name="mem_precio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label for="comision" class="block text-sm font-medium text-gray-700 mb-1">Comisión (%)</label>
                    <input type="number" step="0.01" id="comision" name="comision" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="modalidad" value="por_meses" checked class="form-radio text-pink-600" onchange="toggleModalidad(this.value)">
                        <span class="ml-2 text-sm text-gray-700">Por meses</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="modalidad" value="por_fechas" class="form-radio text-pink-600" onchange="toggleModalidad(this.value)">
                        <span class="ml-2 text-sm text-gray-700">Por fechas</span>
                    </label>
                </div>
            </div>

            <div id="duracionField">
                <label for="mem_duracion" class="block text-sm font-medium text-gray-700 mb-1">Duración (días) <span class="text-red-500">*</span></label>
                <input type="number" id="mem_duracion" name="mem_duracion" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="mem_categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select id="mem_categoria" name="mem_categoria" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="Regular">Regular</option>
                        <option value="Premium">Premium</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
                <div>
                    <label for="mem_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                    <select id="mem_tipo" name="mem_tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="Diaria">Diaria</option>
                        <option value="Semanal">Semanal</option>
                        <option value="Mensual">Mensual</option>
                        <option value="Trimestral">Trimestral</option>
                        <option value="Semestral">Semestral</option>
                        <option value="Anual">Anual</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="mem_beneficios" class="block text-sm font-medium text-gray-700 mb-1">Beneficios</label>
                <textarea id="mem_beneficios" name="mem_beneficios" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    Guardar
                </button>
            </div>
        </form>
    </x-modal-form>

    <x-modal-form show="showEditModal" title="Editar Membresía" subtitle="Modifique los datos del plan" icon='<i class="fas fa-edit text-white"></i>' size="lg" headerColor="blue">
        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="edit_mem_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" id="edit_mem_nombre" name="mem_nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="edit_mem_precio" class="block text-sm font-medium text-gray-700 mb-1">Precio (S/) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="edit_mem_precio" name="mem_precio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label for="edit_comision" class="block text-sm font-medium text-gray-700 mb-1">Comisión (%)</label>
                    <input type="number" step="0.01" id="edit_comision" name="comision" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="modalidad" value="por_meses" class="form-radio text-pink-600" onchange="toggleEditModalidad(this.value)">
                        <span class="ml-2 text-sm text-gray-700">Por meses</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="modalidad" value="por_fechas" class="form-radio text-pink-600" onchange="toggleEditModalidad(this.value)">
                        <span class="ml-2 text-sm text-gray-700">Por fechas</span>
                    </label>
                </div>
            </div>

            <div id="editDuracionField">
                <label for="edit_mem_duracion" class="block text-sm font-medium text-gray-700 mb-1">Duración (días) <span class="text-red-500">*</span></label>
                <input type="number" id="edit_mem_duracion" name="mem_duracion" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="edit_mem_categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select id="edit_mem_categoria" name="mem_categoria" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="Regular">Regular</option>
                        <option value="Premium">Premium</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
                <div>
                    <label for="edit_mem_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                    <select id="edit_mem_tipo" name="mem_tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="Diaria">Diaria</option>
                        <option value="Semanal">Semanal</option>
                        <option value="Mensual">Mensual</option>
                        <option value="Trimestral">Trimestral</option>
                        <option value="Semestral">Semestral</option>
                        <option value="Anual">Anual</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="edit_mem_beneficios" class="block text-sm font-medium text-gray-700 mb-1">Beneficios</label>
                <textarea id="edit_mem_beneficios" name="mem_beneficios" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showEditModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    Actualizar
                </button>
            </div>
        </form>
    </x-modal-form>
</div>

@push('scripts')
<script>
function toggleModalidad(valor) {
    const duracionField = document.getElementById('duracionField');
    const duracionInput = document.getElementById('mem_duracion');
    
    if (valor === 'por_fechas') {
        duracionField.style.display = 'none';
        duracionInput.removeAttribute('required');
    } else {
        duracionField.style.display = 'block';
        duracionInput.setAttribute('required', 'required');
    }
}

function toggleEditModalidad(valor) {
    const duracionField = document.getElementById('editDuracionField');
    const duracionInput = document.getElementById('edit_mem_duracion');
    
    if (valor === 'por_fechas') {
        duracionField.style.display = 'none';
        duracionInput.removeAttribute('required');
    } else {
        duracionField.style.display = 'block';
        duracionInput.setAttribute('required', 'required');
    }
}

function editMembresia(id) {
    fetch(`/membresias/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('editForm').action = `/membresias/${id}`;
        document.getElementById('edit_mem_nombre').value = data.mem_nombre || '';
        document.getElementById('edit_mem_precio').value = data.mem_precio || '';
        document.getElementById('edit_comision').value = data.comision || 0;
        document.getElementById('edit_mem_duracion').value = data.mem_duracion || '';
        document.getElementById('edit_mem_categoria').value = data.mem_categoria || 'Regular';
        document.getElementById('edit_mem_tipo').value = data.mem_tipo || 'Mensual';
        document.getElementById('edit_mem_beneficios').value = data.mem_beneficios || '';
        
        const modalidad = data.modalidad || 'por_meses';
        document.querySelector(`input[name="modalidad"][value="${modalidad}"]`).checked = true;
        toggleEditModalidad(modalidad);
        
        Alpine.$data(document.querySelector('[x-data]')).showEditModal = true;
    });
}
</script>
@endpush
@endsection
