@extends('layouts.app')

@section('content')
<div x-data="{ showUsuarioModal: false }" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
        @can('create', App\Models\User::class)
        <button type="button" @click="showUsuarioModal = true; $nextTick(() => { document.getElementById('usuarioForm').reset(); document.getElementById('usuarioForm').action = '{{ route('usuarios.store') }}'; document.getElementById('usuario_method').value = 'POST'; document.querySelector('#usuarioModalTitle').textContent = 'Nuevo Usuario'; document.getElementById('password').required = true; document.getElementById('password_required').style.display = 'inline'; document.getElementById('password_hint').textContent = 'Mínimo 6 caracteres'; })" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Nuevo Usuario
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('usuarios.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o email..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>{{ $role }}</option>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($usuarios as $usuario)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $usuario->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $usuario->email }}</td>
                        <td class="px-4 py-3 text-sm">
                            <x-badge variant="info">{{ $usuario->roles->first()->name ?? 'Sin rol' }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $usuario->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($usuario->estado)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                @can('update', $usuario)
                                <button type="button" onclick="editUsuario({{ $usuario->id }})" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                                @can('toggleEstado', $usuario)
                                <form action="{{ route('usuarios.toggle', $usuario->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="{{ $usuario->estado ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' }}" title="{{ $usuario->estado ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas {{ $usuario->estado ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                </form>
                                @endcan
                                @can('delete', $usuario)
                                <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar este usuario?')">
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
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No se encontraron usuarios.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($usuarios->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $usuarios->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="showUsuarioModal" title="Nuevo Usuario" subtitle="Complete los datos del usuario" icon='<i class="fas fa-user text-white"></i>' size="md" headerColor="blue">
        <form id="usuarioForm" method="POST">
            @csrf
            <input type="hidden" id="usuario_method" name="_method" value="POST">

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <span class="text-red-500" id="password_required">*</span></label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1" id="password_hint">Mínimo 6 caracteres</p>
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">Seleccionar rol...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fksede" class="block text-sm font-medium text-gray-700 mb-1">Sede <span class="text-red-500">*</span></label>
                    <select id="fksede" name="fksede" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">Seleccionar sede...</option>
                        @foreach(App\Models\Sede::where('sede_estado', true)->get() as $sede)
                            <option value="{{ $sede->id_sede }}">{{ $sede->sede_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showUsuarioModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
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
function editUsuario(id) {
    fetch(`/usuarios/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const usuario = data.data;
        document.getElementById('usuarioForm').action = `/usuarios/${id}`;
        document.getElementById('usuario_method').value = 'PUT';
        document.querySelector('#usuarioModalTitle').textContent = 'Editar Usuario';

        document.getElementById('name').value = usuario.name || '';
        document.getElementById('email').value = usuario.email || '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('password_required').style.display = 'none';
        document.getElementById('password_hint').textContent = 'Dejar vacío para mantener la contraseña actual';
        document.getElementById('telefono').value = usuario.telefono || '';

        const roleSelect = document.getElementById('role');
        const currentRole = usuario.roles && usuario.roles.length > 0 ? usuario.roles[0].name : '';
        roleSelect.value = currentRole;

        const sedeSelect = document.getElementById('fksede');
        sedeSelect.value = usuario.fksede || '';

        Alpine.$data(document.querySelector('[x-data]')).showUsuarioModal = true;
    });
}
</script>
@endpush
@endsection
