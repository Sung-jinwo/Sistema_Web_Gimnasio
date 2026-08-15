@extends('layouts.app')
@section('title', 'Ventas - SIGG')
@section('page-title', isset($soloReservadas) ? 'Ventas Reservadas' : 'Ventas')
@section('page-subtitle', isset($soloReservadas) ? 'Revisa y completa las reservas pendientes' : 'Venta ágil de productos y membresías')
@section('content')
<div x-data="ventasApp()" class="w-full space-y-5">
    <div class="flex flex-wrap justify-end gap-2">
        @if(isset($soloReservadas))
            <a href="{{ route('ventas.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-list mr-2"></i> Todas las ventas
            </a>
        @else
            <a href="{{ route('ventas.reservados') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-bookmark mr-2"></i> Ver reservadas
            </a>
            <button @click="abrirProductos(true)" class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition">
                <i class="fas fa-bolt mr-2"></i> Venta rápida
            </button>
            <button @click="abrirProductos(false)" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-cart-plus mr-2"></i> Venta de productos
            </button>
            <button @click="abrirMembresia" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                <i class="fas fa-id-card mr-2"></i> Venta de membresía
            </button>
        @endif
    </div>

    <form method="GET" action="{{ route('ventas.index') }}" class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid sm:grid-cols-4 gap-3">
            <input name="search" value="{{ request('search') }}" placeholder="Buscar por alumno o DNI..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="tipo_venta" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los tipos</option>
                <option value="producto" @selected(request('tipo_venta')=='producto')>Producto</option>
                <option value="membresia" @selected(request('tipo_venta')=='membresia')>Membresía</option>
                <option value="rapida" @selected(request('tipo_venta')=='rapida')>Rápida</option>
            </select>
            <select name="estado_pago" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los pagos</option>
                <option value="pagado" @selected(request('estado_pago')=='pagado')>Pagado</option>
                <option value="parcial" @selected(request('estado_pago')=='parcial')>Parcial</option>
                <option value="pendiente" @selected(request('estado_pago')=='pendiente')>Pendiente</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Venta</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pago</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ventas as $venta)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $venta->alumno->nombreCompleto ?? 'Venta rápida' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst($venta->tipo_venta) }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500 hidden md:table-cell">{{ $venta->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold text-gray-900">S/ {{ number_format($venta->venta_total, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($venta->estado_venta === 'completado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completada</span>
                            @elseif($venta->estado_venta === 'reservado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Reservada</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Incompleta</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($venta->estado_pago === 'pagado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>
                            @elseif($venta->estado_pago === 'parcial')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex justify-center gap-2">
                                @if($venta->estado_venta === 'reservado')
                                    <button @click="editarReserva(@json($venta))" class="text-blue-600 hover:text-blue-900" title="Editar reserva">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif
                                @if($venta->estado_venta !== 'anulado' && auth()->user()->hasRole('Administrador'))
                                    <button @click="anular({{ $venta->id_venta }})" class="text-red-600 hover:text-red-900" title="Anular">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay ventas registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ventas->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $ventas->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="modalProductos" title="Venta de productos" subtitle="Toca productos para agregarlos al carrito" size="4xl">
        <form method="POST" action="{{ route('ventas.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="tipo_venta" :value="sinAlumno ? 'rapida' : 'producto'">
            <input type="hidden" name="fkalum" :value="alumno?.id_alumno">
            
            <div class="flex items-center gap-2">
                <input type="checkbox" x-model="sinAlumno" class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                <span class="text-sm font-medium text-gray-700">Venta rápida sin alumno</span>
            </div>

            <div x-show="!sinAlumno" class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar alumno por DNI, código o nombre</label>
                <input x-model="busquedaAlumno" @input.debounce.250ms="buscarAlumno" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Escriba al menos 2 caracteres">
                <div x-show="resultados.length" class="absolute z-20 w-full bg-white border shadow rounded-lg mt-1 max-h-60 overflow-y-auto">
                    <template x-for="a in resultados">
                        <button type="button" @click="alumno = a; resultados = []; busquedaAlumno = `${a.alum_nombre} ${a.alum_apellido} - ${a.alum_numDoc}`" class="block w-full text-left px-4 py-2 hover:bg-pink-50 text-sm" x-text="`${a.alum_nombre} ${a.alum_apellido} · DNI ${a.alum_numDoc}`"></button>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar producto</label>
                <input x-model="buscarProducto" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Buscar por nombre...">
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-64 overflow-y-auto">
                <template x-for="p in productosFiltrados">
                    <button type="button" @click="agregar(p)" class="p-3 border rounded-lg text-left hover:border-pink-500 transition">
                        <b class="block text-sm" x-text="p.prod_nombre"></b>
                        <span class="text-xs text-gray-500" x-text="`S/ ${Number(p.prod_precio).toFixed(2)} · Stock ${p.prod_cantidad}`"></span>
                    </button>
                </template>
            </div>

            <div class="border rounded-lg divide-y">
                <template x-for="(item, index) in carrito">
                    <div class="p-3 flex items-center gap-3">
                        <span class="flex-1 text-sm" x-text="item.prod_nombre"></span>
                        <input type="hidden" :name="`detalles[${index}][fkproducto]`" :value="item.id_productos">
                        <input type="number" min="1" :max="item.prod_cantidad" x-model="item.cantidad" :name="`detalles[${index}][cantidad]`" class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-sm">
                        <input type="hidden" :name="`detalles[${index}][precio_unitario]`" :value="item.prod_precio">
                        <span class="font-semibold text-sm" x-text="`S/ ${(item.prod_precio * item.cantidad).toFixed(2)}`"></span>
                        <button type="button" @click="carrito.splice(index, 1)" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </template>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <select name="fkmetodo" required class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Método de pago</option>
                    <template x-for="m in metodos">
                        <option :value="m.id_metod" x-text="m.metod_nombre"></option>
                    </template>
                </select>
                <input name="monto_pagado" type="number" min="0" step=".01" :max="total" x-bind:value="total" required class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="font-semibold">Total:</span>
                <span class="text-2xl font-bold text-pink-600" x-text="`S/ ${total.toFixed(2)}`"></span>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="modalProductos = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">Registrar venta</button>
            </div>
        </form>
    </x-modal-form>

    <x-modal-form show="modalMembresia" title="Venta de membresía" subtitle="La vigencia se calcula desde el plan" size="lg">
        <form method="POST" action="{{ route('ventas.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="tipo_venta" value="membresia">
            <input type="hidden" name="fkalum" :value="alumno?.id_alumno">

            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar alumno por DNI, código o nombre</label>
                <input x-model="busquedaAlumno" @input.debounce.250ms="buscarAlumno" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Buscar alumno...">
                <div x-show="resultados.length" class="absolute z-20 w-full bg-white border shadow rounded-lg mt-1 max-h-60 overflow-y-auto">
                    <template x-for="a in resultados">
                        <button type="button" @click="alumno = a; resultados = []; busquedaAlumno = `${a.alum_nombre} ${a.alum_apellido} - ${a.alum_numDoc}`" class="block w-full text-left px-4 py-2 hover:bg-pink-50 text-sm" x-text="`${a.alum_nombre} ${a.alum_apellido} · DNI ${a.alum_numDoc}`"></button>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plan de membresía</label>
                <select x-model="membresiaId" name="fkmem" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccione el plan</option>
                    <template x-for="m in membresias">
                        <option :value="m.id_mem" x-text="`${m.mem_nombre} · S/ ${Number(m.mem_precio).toFixed(2)}`"></option>
                    </template>
                </select>
            </div>

            <template x-if="membresiaSeleccionada?.modalidad !== 'por_fechas'">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </template>

            <template x-if="membresiaSeleccionada?.modalidad === 'por_fechas'">
                <div class="p-3 bg-purple-50 rounded-lg text-sm text-purple-900" x-text="`Vigencia fija: ${membresiaSeleccionada.fecha_inicio_fija?.substring(0,10)} al ${membresiaSeleccionada.fecha_fin_fija?.substring(0,10)}`"></div>
            </template>

            <div class="grid sm:grid-cols-2 gap-3">
                <select name="fkmetodo" required class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Método de pago</option>
                    <template x-for="m in metodos">
                        <option :value="m.id_metod" x-text="m.metod_nombre"></option>
                    </template>
                </select>
                <input name="monto_pagado" type="number" min="0" step=".01" :max="membresiaSeleccionada?.mem_precio || 0" required class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div class="flex gap-3">
                <button type="button" @click="modalMembresia = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">Registrar venta</button>
            </div>
        </form>
    </x-modal-form>

    <x-modal-form show="modalReserva" title="Editar reserva" subtitle="Actualiza el cobro o completa la venta">
        <form :action="`{{ url('/ventas') }}/${reserva.id_venta}`" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <select name="estado_venta" x-model="reserva.estado_venta" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="reservado">Reservada</option>
                <option value="completado">Completada</option>
            </select>
            <input name="monto_pagado" x-model="reserva.monto_pagado" type="number" min="0" :max="reserva.venta_total" step=".01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <input x-show="Number(reserva.monto_pagado) < Number(reserva.venta_total)" name="fecha_acordada" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <textarea name="observacion" x-model="reserva.observacion" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Observaciones..."></textarea>
            <div class="flex gap-3">
                <button type="button" @click="modalReserva = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Guardar reserva</button>
            </div>
        </form>
    </x-modal-form>

    <x-modal-form show="modalAnular" title="Anular venta" subtitle="La venta seguirá visible y se restaurará el stock">
        <form :action="`{{ url('/ventas') }}/${anularId}/anular`" method="POST" class="space-y-4">
            @csrf
            <textarea name="motivo_anulacion" required maxlength="500" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Motivo obligatorio..."></textarea>
            <div class="flex gap-3">
                <button type="button" @click="modalAnular = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Confirmar anulación</button>
            </div>
        </form>
    </x-modal-form>
</div>
@endsection

@push('scripts')
<script>
function ventasApp() {
    return {
        modalProductos: false,
        modalMembresia: false,
        modalReserva: false,
        modalAnular: false,
        anularId: null,
        reserva: {},
        productos: [],
        metodos: [],
        membresias: [],
        carrito: [],
        buscarProducto: '',
        sinAlumno: false,
        alumno: null,
        busquedaAlumno: '',
        resultados: [],
        membresiaId: '',
        get productosFiltrados() {
            let q = this.buscarProducto.toLowerCase();
            return this.productos.filter(p => p.prod_nombre.toLowerCase().includes(q));
        },
        get total() {
            return this.carrito.reduce((s, i) => s + Number(i.prod_precio) * Number(i.cantidad), 0);
        },
        get membresiaSeleccionada() {
            return this.membresias.find(m => String(m.id_mem) === String(this.membresiaId));
        },
        async abrirProductos(rapida) {
            this.sinAlumno = rapida;
            let r = await fetch('{{ route('ventas.datos.producto') }}');
            let d = await r.json();
            this.productos = d.productos;
            this.metodos = d.metodos;
            this.carrito = [];
            this.modalProductos = true;
        },
        async abrirMembresia() {
            let r = await fetch('{{ route('ventas.datos.membresia') }}');
            let d = await r.json();
            this.membresias = d.membresias;
            this.metodos = d.metodos;
            this.modalMembresia = true;
        },
        agregar(p) {
            let i = this.carrito.find(x => x.id_productos === p.id_productos);
            if (i) {
                if (i.cantidad < p.prod_cantidad) i.cantidad++;
            } else {
                this.carrito.push({ ...p, cantidad: 1 });
            }
        },
        async buscarAlumno() {
            if (this.busquedaAlumno.length < 2) {
                this.resultados = [];
                return;
            }
            let r = await fetch(`{{ route('ventas.alumnos.buscar') }}?q=${encodeURIComponent(this.busquedaAlumno)}`);
            this.resultados = await r.json();
        },
        editarReserva(v) {
            this.reserva = { ...v };
            this.modalReserva = true;
        },
        anular(id) {
            this.anularId = id;
            this.modalAnular = true;
        }
    }
}
</script>
@endpush