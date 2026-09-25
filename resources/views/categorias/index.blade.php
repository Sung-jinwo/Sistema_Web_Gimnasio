@extends('layouts.app')

@section('page-title','Categorías de productos')
@section('page-subtitle','Organiza el catálogo de inventario')

@section('content')
<div
    x-data="{ modal: false, editando: false, id: null, nombre: '', nuevo() { this.editando = false; this.nombre = ''; this.modal = true }, editar(id, nombre) { this.editando = true; this.id = id; this.nombre = nombre; this.modal = true } }"
    class="w-full space-y-5"
>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <p class="text-sm text-gray-500">{{ $categorias->total() }} categoría(s) registrada(s)</p>
        <div class="flex gap-2">
            <a href="{{ route('productos.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-arrow-left mr-2"></i> Volver a productos
            </a>
            @if(auth()->user()->hasRole('Administrador'))
                <button @click="nuevo" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    <i class="fas fa-plus mr-2"></i> Nueva categoría
                </button>
            @endif
        </div>
    </div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($categorias as $c)
            <article class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 {{ !$c->cat_estado ? 'opacity-60' : '' }}">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-tags text-pink-600"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $c->cat_nombre }}</h3>
                            <p class="text-sm text-gray-500">{{ $c->productos_count }} producto(s)</p>
                        </div>
                    </div>
                    @if($c->cat_estado)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactiva</span>
                    @endif
                </div>

                @if(auth()->user()->hasRole('Administrador'))
                    <div class="flex gap-2 mt-4">
                        <button @click="editar({{ $c->id_categoria }}, @js($c->cat_nombre))" class="btn-accion text-green-600 hover:text-green-900" title="Editar">
                            <i class="fas fa-pen-to-square"></i>
                        </button>
                        <form method="POST" action="{{ route('categorias.toggle', $c) }}" class="inline" onsubmit="return confirm('¿Confirma el cambio de estado?')">
                            @csrf
                            <button class="btn-accion {{ $c->cat_estado ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' }}" title="{{ $c->cat_estado ? 'Desactivar' : 'Activar' }}">
                                <i class="fas {{ $c->cat_estado ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                <i class="fas fa-tags text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay categorías registradas</h3>
                <p class="text-gray-500">Crea la primera para organizar tu inventario.</p>
            </div>
        @endforelse
    </div>

    @if($categorias->hasPages())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
        {{ $categorias->links() }}
    </div>
    @endif

    @if(auth()->user()->hasRole('Administrador'))
        <x-modal-form show="modal" title="Categoría" subtitle="Nombre de la categoría" icon='<i class="fas fa-tags text-white"></i>' size="sm">
            <form :action="editando ? `{{ url('/categorias') }}/${id}` : '{{ route('categorias.store') }}'" method="POST" class="space-y-4">
                @csrf
                <input x-show="editando" type="hidden" name="_method" value="PUT">
                <div>
                    <label for="cat_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input id="cat_nombre" x-model="nombre" name="cat_nombre" maxlength="50" required placeholder="Ej. Suplementos" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="modal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                    <button class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">Guardar</button>
                </div>
            </form>
        </x-modal-form>
    @endif
</div>
@endsection
