@extends('layouts.app')

@section('content')
@section('page-title','Sedes')
@section('page-subtitle','Administración de locales')
<div id="sedesRoot" x-data="{ showSedeModal: false }" class="w-full space-y-5">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Sedes</h1>
        @can('create', App\Models\Sede::class)
        <button type="button" @click="showSedeModal = true; $nextTick(() => { document.getElementById('sedeForm').reset(); document.getElementById('sedeForm').action = '{{ route('sedes.store') }}'; document.getElementById('sede_method').value = 'POST'; document.querySelector('#sedeModalTitle').textContent = 'Nueva Sede'; document.getElementById('sede_estado').checked = true; })" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Nueva Sede
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('sedes.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o dirección..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-search mr-2"></i> Buscar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Dirección</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Teléfono</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Responsable</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sedes as $sede)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sede->sede_nombre }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $sede->sede_direccion ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">{{ $sede->sede_telefono ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">{{ $sede->sede_responsable ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($sede->sede_estado)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactiva</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                @can('update', $sede)
                                <button type="button" onclick="editSede({{ $sede->id_sede }})" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                                @can('toggleEstado', $sede)
                                <form action="{{ route('sedes.toggle', $sede->id_sede) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="{{ $sede->sede_estado ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' }}" title="{{ $sede->sede_estado ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas {{ $sede->sede_estado ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No se encontraron sedes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sedes->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $sedes->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="showSedeModal" title="Nueva Sede" subtitle="Complete los datos de la sede" icon='<i class="fas fa-building text-white"></i>' size="md" headerColor="purple">
        <form id="sedeForm" method="POST">
            @csrf
            <input type="hidden" id="sede_method" name="_method" value="POST">

            <div class="space-y-4">
                <div>
                    <label for="sede_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="sede_nombre" name="sede_nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <div>
                    <label for="sede_direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" id="sede_direccion" name="sede_direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="sede_telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" id="sede_telefono" name="sede_telefono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="sede_responsable" class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                        <input type="text" id="sede_responsable" name="sede_responsable" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label for="sede_horario" class="block text-sm font-medium text-gray-700 mb-1">Horario</label>
                    <input type="text" id="sede_horario" name="sede_horario" placeholder="Ej: Lun-Sab 6:00-22:00" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="sede_estado" name="sede_estado" value="1" checked class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                    <label for="sede_estado" class="ml-2 text-sm font-medium text-gray-700">Sede activa</label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showSedeModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                        Guardar
                    </button>
                </div>
            </div>
        </form>
    </x-modal-form>
</div>

@push('scripts')
<script>
function editSede(id) {
    fetch(`/sedes/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('sedeForm').action = `/sedes/${id}`;
        document.getElementById('sede_method').value = 'PUT';
        document.querySelector('#sedeModalTitle').textContent = 'Editar Sede';

        document.getElementById('sede_nombre').value = data.sede_nombre || '';
        document.getElementById('sede_direccion').value = data.sede_direccion || '';
        document.getElementById('sede_telefono').value = data.sede_telefono || '';
        document.getElementById('sede_responsable').value = data.sede_responsable || '';
        document.getElementById('sede_horario').value = data.sede_horario || '';
        document.getElementById('sede_estado').checked = data.sede_estado;

        Alpine.$data(document.getElementById('sedesRoot')).showSedeModal = true;
    });
}
</script>
@endpush
@endsection
