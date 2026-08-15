@extends('layouts.app')

@section('title','Alumnos - SIGG')
@section('page-title','Alumnos')
@section('page-subtitle','Registro y ficha integral de alumnos')
@section('content')
<div id="alumnosRoot" x-data="{ showCreateModal: {{ $errors->any() ? 'true' : 'false' }}, showEditModal: false, selectedAlumno: null, editUrl: '', closeEditModal(){ this.showEditModal=false; this.selectedAlumno=null } }" class="w-full space-y-5">
    <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4">
        @can('create', App\Models\Alumno::class)
        <button type="button" @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Nuevo Alumno
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('alumnos.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, DNI o código..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="sede" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id_sede }}" {{ request('sede') == $sede->id_sede ? 'selected' : '' }}>{{ $sede->sede_nombre }}</option>
                @endforeach
            </select>
            <select name="estado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivo</option>
            </select>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DNI</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Celular</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($alumnos as $alumno)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $alumno->alum_codigo }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $alumno->alum_numDoc }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $alumno->nombreCompleto }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">{{ $alumno->alum_telefo ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">{{ $alumno->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($alumno->alum_estado)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('alumnos.show', $alumno->id_alumno) }}" class="text-blue-600 hover:text-blue-900" title="Ver ficha">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('update', $alumno)
                                <button type="button" onclick="editAlumno({{ $alumno->id_alumno }})" class="text-green-600 hover:text-green-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                                @can('delete', $alumno)
                                <form action="{{ route('alumnos.destroy', $alumno->id_alumno) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar este alumno?')">
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
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron alumnos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($alumnos->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $alumnos->links() }}
        </div>
        @endif
    </div>

    @include('alumnos.create')
    @include('alumnos.edit', ['updateRoute' => route('alumnos.update', ['alumno' => ':id'])])
</div>

@push('scripts')
<script>
function editAlumno(id) {
    fetch(`/alumnos/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const state = Alpine.$data(document.getElementById('alumnosRoot'));
        state.selectedAlumno = data;
        state.editUrl = `{{ url('/alumnos') }}/${id}`;
        state.showEditModal = true;
    });
}
</script>
@endpush
@endsection
