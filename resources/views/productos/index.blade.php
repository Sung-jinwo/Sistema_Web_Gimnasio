@extends('layouts.app')
@section('title', 'Productos - SIGG')
@section('page-title', 'Productos')
@section('page-subtitle', 'Inventario por sede y control de stock')
@section('content')
<div x-data="productoCrud()" class="w-full space-y-5">
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('categorias.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-tags mr-2"></i> Categorías
        </a>
        <button @click="nuevo" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Nuevo producto
        </button>
    </div>

    <form method="GET" action="{{ route('productos.index') }}" class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid sm:grid-cols-3 gap-3">
            <input name="search" value="{{ request('search') }}" placeholder="Buscar producto..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="fkcategoria" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $c)
                    <option value="{{ $c->id_categoria }}" @selected(request('fkcategoria') == $c->id_categoria)>{{ $c->cat_nombre }}</option>
                @endforeach
            </select>
            @if(auth()->user()->hasRole('Administrador'))
                <select name="sede" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todas las sedes</option>
                    @foreach($sedes as $s)
                        <option value="{{ $s->id_sede }}" @selected(request('sede') == $s->id_sede)>{{ $s->sede_nombre }}</option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-filter mr-2"></i> Aplicar filtros
            </button>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Stock</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Mínimo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Categoría</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($productos as $producto)
                    <tr class="hover:bg-gray-50 {{ !$producto->prod_estado ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $producto->prod_codigo ?: 'Sin código' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $producto->prod_nombre }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold text-pink-600">S/ {{ number_format($producto->prod_precio, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm hidden md:table-cell">
                            <span class="{{ $producto->prod_cantidad <= $producto->prod_stock_minimo ? 'text-red-600 font-bold' : 'text-gray-900' }}">
                                {{ $producto->prod_cantidad }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500 hidden md:table-cell">{{ $producto->prod_stock_minimo }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500 hidden lg:table-cell">{{ $producto->categoria->cat_nombre ?? 'Sin categoría' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500 hidden lg:table-cell">{{ $producto->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex justify-center gap-2">
                                <button @click="editar(@json($producto))" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('productos.destroy', $producto->id_productos) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="{{ $producto->prod_estado ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' }}" title="{{ $producto->prod_estado ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas {{ $producto->prod_estado ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No se encontraron productos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($productos->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $productos->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="modal" title="Producto" subtitle="Complete los datos del inventario" size="lg">
        <form :action="url" method="POST" class="space-y-4">
            @csrf
            <input x-show="editando" type="hidden" name="_method" value="PUT">
            
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                    <input x-model="form.prod_codigo" name="prod_codigo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input x-model="form.prod_nombre" name="prod_nombre" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio <span class="text-red-500">*</span></label>
                    <input x-model="form.prod_precio" name="prod_precio" type="number" min=".01" step=".01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock <span class="text-red-500">*</span></label>
                    <input x-model="form.prod_cantidad" name="prod_cantidad" type="number" min="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock mínimo</label>
                    <input x-model="form.prod_stock_minimo" name="prod_stock_minimo" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                    <input x-model="form.prod_marca" name="prod_marca" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select x-model="form.fkcategoria" name="fkcategoria" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">Seleccione</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id_categoria }}">{{ $c->cat_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->hasRole('Administrador'))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sede <span class="text-red-500">*</span></label>
                    <select x-model="form.fksede" name="fksede" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">Seleccione</option>
                        @foreach($sedes as $s)
                            <option value="{{ $s->id_sede }}">{{ $s->sede_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="modal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">Guardar</button>
            </div>
        </form>
    </x-modal-form>
</div>
@endsection

@push('scripts')
<script>
function productoCrud() {
    return {
        modal: false,
        editando: false,
        url: '{{ route('productos.store') }}',
        form: {},
        nuevo() {
            this.form = {
                prod_stock_minimo: 5,
                fksede: '{{ auth()->user()->fksede }}'
            };
            this.editando = false;
            this.url = '{{ route('productos.store') }}';
            this.modal = true;
        },
        editar(p) {
            this.form = p;
            this.editando = true;
            this.url = `{{ url('/productos') }}/${p.id_productos}`;
            this.modal = true;
        }
    }
}
</script>
@endpush