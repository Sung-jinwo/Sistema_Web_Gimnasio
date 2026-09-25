@extends('layouts.app')
@section('title', 'Ventas - SIGG')
@section('page-title', 'Ventas')
@section('page-subtitle', 'Venta ágil de productos y membresías')
@section('content')
<div x-data="ventasApp()" x-init="restaurarModal(); aperturaDesdeAlumno()" class="w-full space-y-5">
    @if(!$cajaPropiaAbierta)
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900" role="alert">
            <div>
                <p class="font-semibold"><i class="fas fa-cash-register mr-2"></i>{{ $bloqueoCaja ?? 'Debes abrir tu caja antes de registrar ventas.' }}</p>
                <p class="mt-1 text-sm">Cada empleado registra los cobros únicamente en su propia caja abierta.</p>
            </div>
            <a href="{{ route('caja.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Ir a Caja</a>
        </div>
    @endif

    <div class="flex flex-wrap justify-end gap-2">
        <button type="button" @click="abrirProductos(true)" @disabled(!$cajaPropiaAbierta) class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition disabled:opacity-50 disabled:cursor-not-allowed" title="{{ $cajaPropiaAbierta ? 'Registrar venta rápida' : 'Primero debes abrir tu caja' }}">
            <i class="fas fa-bolt mr-2"></i> Venta rápida
        </button>
        <button type="button" @click="abrirProductos(false)" @disabled(!$cajaPropiaAbierta) class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" title="{{ $cajaPropiaAbierta ? 'Registrar venta de productos' : 'Primero debes abrir tu caja' }}">
            <i class="fas fa-cart-plus mr-2"></i> Venta de productos
        </button>
        <button type="button" @click="abrirMembresia" @disabled(!$cajaPropiaAbierta) class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition disabled:opacity-50 disabled:cursor-not-allowed" title="{{ $cajaPropiaAbierta ? 'Registrar venta de membresía' : 'Primero debes abrir tu caja' }}">
            <i class="fas fa-id-card mr-2"></i> Venta de membresía
        </button>
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
            <table data-card="compacta" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th data-card-oculto class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Sede</th>
                        <th data-card-prioritario class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Venta</th>
                        <th data-card-prioritario class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pago</th>
                        @if(auth()->user()->hasRole('Administrador'))
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        @endif
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
                            @elseif($venta->estado_venta === 'reserva_vencida')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Reserva vencida</span>
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
                        @if(auth()->user()->hasRole('Administrador'))
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex justify-center gap-2">
                                @if($venta->estado_venta !== 'anulado')
                                    <button @click="anular({{ $venta->id_venta }})" class="btn-accion text-red-600 hover:text-red-900" title="Anular">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasRole('Administrador') ? 8 : 7 }}" class="px-4 py-8 text-center text-gray-500">No hay ventas registradas.</td>
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

    <x-modal-form show="modalProductos" title="Venta de productos" subtitle="Busca productos por nombre y agrégalos al carrito" size="4xl">
        <form method="POST" action="{{ route('ventas.store') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="_formulario" value="venta-producto">
            <p x-show="errorCargaProductos" x-cloak class="rounded-lg bg-red-50 p-3 text-sm text-red-700" x-text="errorCargaProductos"></p>
            <input type="hidden" name="tipo_venta" :value="sinAlumno ? 'rapida' : 'producto'">
            <input type="hidden" name="fkalum" :value="alumno?.id_alumno">
            
            <div class="flex items-center gap-2">
                <input type="checkbox" x-model="sinAlumno" class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                <span class="text-sm font-medium text-gray-700">Venta rápida sin alumno</span>
            </div>

            <div x-show="!sinAlumno" class="relative" @click.outside="resultados = []">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar alumno por DNI</label>
                <input x-model="busquedaAlumno" @input.debounce.250ms="buscarAlumno" inputmode="numeric" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Escriba al menos 3 dígitos">
                <p x-show="errorBusquedaAlumno" x-cloak class="mt-1 text-sm text-red-600" x-text="errorBusquedaAlumno"></p>
                <div x-show="buscandoAlumno || resultados.length || (busquedaAlumno.length >= 3 && !alumno)" x-cloak class="absolute z-20 w-full bg-white border shadow rounded-lg mt-1 max-h-60 overflow-y-auto">
                    <p x-show="buscandoAlumno" class="px-4 py-3 text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</p>
                    <template x-for="a in resultados">
                        <button type="button" @click="seleccionarAlumno(a)" class="block w-full text-left px-4 py-2 hover:bg-pink-50 text-sm" x-text="`${a.alum_nombre} ${a.alum_apellido} · DNI ${a.alum_numDoc}`"></button>
                    </template>
                    <p x-show="!buscandoAlumno && busquedaAlumno.length >= 3 && !resultados.length && !alumno" class="px-4 py-3 text-sm text-gray-500">No se encontraron alumnos.</p>
                </div>
                <p x-show="alumno" x-cloak class="mt-2 text-sm text-green-700" x-text="`Alumno seleccionado: ${alumno?.alum_nombre} ${alumno?.alum_apellido} · DNI ${alumno?.alum_numDoc}`"></p>
            </div>

            <div class="relative" @click.outside="resultadosProductos = []">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar producto</label>
                <input x-model="busquedaProducto" @input.debounce.250ms="buscarProductos" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Escriba al menos 2 caracteres">
                <p x-show="errorBusquedaProductos" x-cloak class="mt-1 text-sm text-red-600" x-text="errorBusquedaProductos"></p>
                <div x-show="buscandoProductos || resultadosProductos.length || busquedaProducto.length >= 2" x-cloak class="absolute z-20 w-full bg-white border shadow rounded-lg mt-1 max-h-60 overflow-y-auto">
                    <p x-show="buscandoProductos" class="px-4 py-3 text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</p>
                    <template x-for="p in resultadosProductos">
                        <button type="button" @click="seleccionarProducto(p)" :disabled="!p.disponible" :class="p.disponible ? 'hover:bg-pink-50' : 'cursor-not-allowed bg-gray-50 opacity-60'" class="block w-full text-left px-4 py-2 disabled:cursor-not-allowed">
                            <b class="block text-sm" x-text="p.prod_nombre"></b>
                            <span class="text-xs" :class="p.disponible ? 'text-gray-500' : 'font-semibold text-red-600'" x-text="p.disponible ? `S/ ${Number(p.prod_precio).toFixed(2)} · Stock ${p.prod_cantidad}` : `S/ ${Number(p.prod_precio).toFixed(2)} · Sin stock`"></span>
                        </button>
                    </template>
                    <p x-show="!buscandoProductos && !errorBusquedaProductos && busquedaProducto.length >= 2 && !resultadosProductos.length" class="px-4 py-3 text-sm text-gray-500">No se encontraron productos.</p>
                </div>
            </div>

            <div class="border rounded-lg divide-y">
                <template x-for="(item, index) in carrito">
                    <div class="p-3 flex items-center gap-3">
                        <span class="flex-1 text-sm" x-text="item.prod_nombre"></span>
                        <input type="hidden" :name="`detalles[${index}][fkproducto]`" :value="item.id_productos">
                        <input type="number" min="1" :max="item.prod_cantidad" x-model="item.cantidad" @input="montoProducto = total" :name="`detalles[${index}][cantidad]`" class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-sm">
                        <input type="hidden" :name="`detalles[${index}][precio_unitario]`" :value="item.prod_precio">
                        <span class="font-semibold text-sm" x-text="`S/ ${(item.prod_precio * item.cantidad).toFixed(2)}`"></span>
                        <button type="button" @click="carrito.splice(index, 1); $nextTick(() => montoProducto = total)" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </template>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <select name="cobros[0][fkmetodo]" required class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Primer método</option>
                    <template x-for="m in metodos">
                        <option :value="m.id_metod" x-text="m.metod_nombre"></option>
                    </template>
                </select>
                <input name="cobros[0][monto]" x-model.number="montoProducto" type="number" min="0" step=".01" :max="total" required placeholder="Importe del primer método" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <button type="button" @click="segundoPagoProducto = !segundoPagoProducto; montoProducto2 = 0" class="text-sm text-pink-700 font-medium" x-text="segundoPagoProducto ? 'Quitar segundo método' : '+ Agregar segundo método'"></button>
            <div x-show="segundoPagoProducto" class="grid sm:grid-cols-2 gap-3">
                <select name="cobros[1][fkmetodo]" :required="segundoPagoProducto" :disabled="!segundoPagoProducto" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Segundo método</option>
                    <template x-for="m in metodos"><option :value="m.id_metod" x-text="m.metod_nombre"></option></template>
                </select>
                <input name="cobros[1][monto]" x-model.number="montoProducto2" :required="segundoPagoProducto" :disabled="!segundoPagoProducto" type="number" min=".01" step=".01" :max="Math.max(0, total - Number(montoProducto || 0))" placeholder="Importe del segundo método" class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <input x-show="pagadoProducto < total" name="fecha_acordada" value="{{ old('fecha_acordada') }}" type="date" :required="!sinAlumno && pagadoProducto < total" min="{{ today()->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg" aria-label="Fecha acordada de pago">
            <p x-show="sinAlumno && pagadoProducto < total" class="text-sm text-red-600">La venta rápida debe quedar totalmente pagada.</p>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="font-semibold">Total:</span>
                <span class="text-2xl font-bold text-pink-600" x-text="`S/ ${total.toFixed(2)}`"></span>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="modalProductos = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" :disabled="cargandoProductos" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition disabled:opacity-50 disabled:cursor-not-allowed">Registrar venta</button>
            </div>
        </form>
    </x-modal-form>

    <x-modal-form show="modalMembresia" title="Venta de membresía" subtitle="La vigencia se calcula desde el plan" size="lg">
        <form method="POST" action="{{ route('ventas.store') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="_formulario" value="venta-membresia">
            <p x-show="errorCargaMembresia" x-cloak class="rounded-lg bg-red-50 p-3 text-sm text-red-700" x-text="errorCargaMembresia"></p>
            <input type="hidden" name="tipo_venta" value="membresia">
            <input type="hidden" name="fkalum" :value="membresiaAnonima ? '' : alumno?.id_alumno">

            <input type="hidden" name="fkmem" :value="membresiaId">

            <label x-show="membresiaSeleccionada && Number(membresiaSeleccionada.mem_duracion) === 1 && !membresiaTieneRangoFijo" x-cloak class="flex items-center gap-2 p-3 bg-amber-50 rounded-lg text-sm">
                <input type="checkbox" x-model="membresiaAnonima"> Pase diario anónimo (sin alumno)
            </label>

            <div class="relative" x-show="!membresiaAnonima" @click.outside="resultados = []">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar alumno por DNI</label>
                <input x-model="busquedaAlumno" @input.debounce.250ms="buscarAlumno" inputmode="numeric" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Escriba al menos 3 dígitos">
                <p x-show="errorBusquedaAlumno" x-cloak class="mt-1 text-sm text-red-600" x-text="errorBusquedaAlumno"></p>
                <div x-show="buscandoAlumno || resultados.length || (busquedaAlumno.length >= 3 && !alumno)" x-cloak class="absolute z-20 w-full bg-white border shadow rounded-lg mt-1 max-h-60 overflow-y-auto">
                    <p x-show="buscandoAlumno" class="px-4 py-3 text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</p>
                    <template x-for="a in resultados">
                        <button type="button" @click="seleccionarAlumno(a)" class="block w-full text-left px-4 py-2 hover:bg-pink-50 text-sm" x-text="`${a.alum_nombre} ${a.alum_apellido} · DNI ${a.alum_numDoc}`"></button>
                    </template>
                    <p x-show="!buscandoAlumno && busquedaAlumno.length >= 3 && !resultados.length && !alumno" class="px-4 py-3 text-sm text-gray-500">No se encontraron alumnos.</p>
                </div>
                <p x-show="alumno" x-cloak class="mt-2 text-sm text-green-700" x-text="`Alumno seleccionado: ${alumno?.alum_nombre} ${alumno?.alum_apellido} · DNI ${alumno?.alum_numDoc}`"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede donde asistirá <span class="text-red-500">*</span></label>
                <select name="fksede" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id_sede }}" @selected(old('fksede', auth()->user()->fksede) == $sede->id_sede)>{{ $sede->sede_nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="relative" @click.outside="resultadosMembresias = []">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar membresía por nombre</label>
                <input x-model="busquedaMembresia" @input.debounce.250ms="buscarMembresias" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Escriba al menos 2 caracteres">
                <p x-show="errorBusquedaMembresias" x-cloak class="mt-1 text-sm text-red-600" x-text="errorBusquedaMembresias"></p>
                <div x-show="buscandoMembresias || resultadosMembresias.length || (busquedaMembresia.length >= 2 && !membresiaSeleccionada)" x-cloak class="absolute z-20 w-full bg-white border shadow rounded-lg mt-1 max-h-60 overflow-y-auto">
                    <p x-show="buscandoMembresias" class="px-4 py-3 text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</p>
                    <template x-for="m in resultadosMembresias">
                        <button type="button" @click="seleccionarMembresia(m)" class="block w-full text-left px-4 py-2 hover:bg-pink-50">
                            <b class="block text-sm" x-text="m.mem_nombre"></b>
                            <span class="text-xs text-gray-500" x-text="`S/ ${Number(m.mem_precio).toFixed(2)} · ${descripcionMembresia(m)}`"></span>
                        </button>
                    </template>
                    <p x-show="!buscandoMembresias && !errorBusquedaMembresias && busquedaMembresia.length >= 2 && !resultadosMembresias.length && !membresiaSeleccionada" class="px-4 py-3 text-sm text-gray-500">No se encontraron membresías activas.</p>
                </div>
            </div>

            <div x-show="membresiaSeleccionada" x-cloak class="p-3 border border-pink-200 bg-pink-50 rounded-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900" x-text="membresiaSeleccionada?.mem_nombre"></p>
                        <p class="text-sm text-gray-600" x-text="`S/ ${Number(membresiaSeleccionada?.mem_precio || 0).toFixed(2)} · ${descripcionMembresia(membresiaSeleccionada)}`"></p>
                    </div>
                    <button type="button" @click="limpiarMembresia" class="text-sm font-medium text-pink-700 hover:text-pink-900">Cambiar</button>
                </div>
            </div>

            <template x-if="membresiaSeleccionada && !membresiaTieneRangoFijo">
                <div x-show="!membresiaAnonima">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" x-model="fechaInicioMembresia" :required="!membresiaAnonima" :disabled="membresiaAnonima" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </template>

            <div x-show="membresiaSeleccionada && !membresiaAnonima && (membresiaTieneRangoFijo || fechaInicioMembresia)" x-cloak class="p-3 bg-purple-50 rounded-lg text-sm text-purple-900">
                <p class="font-medium">Vista previa de vigencia</p>
                <p x-text="`${formatearFecha(fechaInicioVigencia)} al ${formatearFecha(fechaFinVigencia)}`"></p>
            </div>
            <p x-show="membresiaAnonima" x-cloak class="p-3 bg-purple-50 rounded-lg text-sm text-purple-900">El pase diario anónimo tendrá vigencia únicamente en la fecha de la venta.</p>

            <div class="grid sm:grid-cols-2 gap-3">
                <select name="cobros[0][fkmetodo]" required class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Primer método</option>
                    <template x-for="m in metodos">
                        <option :value="m.id_metod" x-text="m.metod_nombre"></option>
                    </template>
                </select>
                <input name="cobros[0][monto]" x-model.number="montoMembresia" type="number" min="0" step=".01" :max="membresiaSeleccionada?.mem_precio || 0" required placeholder="Importe del primer método" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <button type="button" @click="segundoPagoMembresia = !segundoPagoMembresia; montoMembresia2 = 0" class="text-sm text-pink-700 font-medium" x-text="segundoPagoMembresia ? 'Quitar segundo método' : '+ Agregar segundo método'"></button>
            <div x-show="segundoPagoMembresia" class="grid sm:grid-cols-2 gap-3">
                <select name="cobros[1][fkmetodo]" :required="segundoPagoMembresia" :disabled="!segundoPagoMembresia" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Segundo método</option>
                    <template x-for="m in metodos"><option :value="m.id_metod" x-text="m.metod_nombre"></option></template>
                </select>
                <input name="cobros[1][monto]" x-model.number="montoMembresia2" :required="segundoPagoMembresia" :disabled="!segundoPagoMembresia" type="number" min=".01" step=".01" :max="Math.max(0, Number(membresiaSeleccionada?.mem_precio || 0) - Number(montoMembresia || 0))" placeholder="Importe del segundo método" class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <input x-show="membresiaSeleccionada && pagadoMembresia < Number(membresiaSeleccionada.mem_precio)" name="fecha_acordada" value="{{ old('fecha_acordada') }}" type="date" :required="!membresiaAnonima && membresiaSeleccionada && pagadoMembresia < Number(membresiaSeleccionada.mem_precio)" min="{{ today()->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg" aria-label="Fecha acordada de pago">
            <p x-show="membresiaAnonima && membresiaSeleccionada && pagadoMembresia < Number(membresiaSeleccionada.mem_precio)" class="text-sm text-red-600">El pase diario anónimo debe quedar totalmente pagado.</p>

            <div class="flex gap-3">
                <button type="button" @click="modalMembresia = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" :disabled="!membresiaSeleccionada || (!membresiaAnonima && !alumno) || (!membresiaAnonima && !membresiaTieneRangoFijo && !fechaInicioMembresia) || (membresiaAnonima && pagadoMembresia < Number(membresiaSeleccionada?.mem_precio || 0)) || cargandoMembresia" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition disabled:opacity-50 disabled:cursor-not-allowed">Registrar venta</button>
            </div>
        </form>
    </x-modal-form>

    <x-modal-form show="modalAnular" title="Anular venta" subtitle="La venta seguirá visible y se restaurará el stock">
        <form :action="`{{ url('/ventas') }}/${anularId}/anular`" method="POST" class="flex flex-col gap-4">
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
        modalProductos: @js($errors->any() && in_array(old('tipo_venta'), ['producto', 'rapida'], true)),
        modalMembresia: @js($errors->any() && old('tipo_venta') === 'membresia'),
        modalAnular: false,
        anularId: null,
        metodos: [],
        cargandoProductos: false,
        datosProductosOk: false,
        cargandoMembresia: false,
        datosMembresiaOk: false,
        carrito: [],
        busquedaProducto: '',
        resultadosProductos: [],
        buscandoProductos: false,
        errorBusquedaProductos: '',
        errorCargaProductos: '',
        sinAlumno: @js(old('tipo_venta') === 'rapida'),
        alumno: null,
        busquedaAlumno: '',
        resultados: [],
        buscandoAlumno: false,
        errorBusquedaAlumno: '',
        membresiaId: '',
        membresiaSeleccionada: null,
        busquedaMembresia: '',
        resultadosMembresias: [],
        buscandoMembresias: false,
        errorBusquedaMembresias: '',
        errorCargaMembresia: '',
        fechaInicioMembresia: @js(old('fecha_inicio', '')),
        montoProducto: Number(@js(old('cobros.0.monto', 0))),
        montoProducto2: Number(@js(old('cobros.1.monto', 0))),
        segundoPagoProducto: @js(old('cobros.1.fkmetodo') !== null),
        montoMembresia: Number(@js(old('cobros.0.monto', 0))),
        montoMembresia2: Number(@js(old('cobros.1.monto', 0))),
        segundoPagoMembresia: @js(old('cobros.1.fkmetodo') !== null),
        membresiaAnonima: false,
        get pagadoProducto() {
            return Number(this.montoProducto || 0) + Number(this.montoProducto2 || 0);
        },
        get pagadoMembresia() {
            return Number(this.montoMembresia || 0) + Number(this.montoMembresia2 || 0);
        },
        get total() {
            return this.carrito.reduce((s, i) => s + Number(i.prod_precio) * Number(i.cantidad), 0);
        },
        get membresiaTieneRangoFijo() {
            return Boolean(this.membresiaSeleccionada?.fecha_inicio_fija && this.membresiaSeleccionada?.fecha_fin_fija);
        },
        get fechaInicioVigencia() {
            return this.membresiaTieneRangoFijo
                ? this.membresiaSeleccionada.fecha_inicio_fija.substring(0, 10)
                : this.fechaInicioMembresia;
        },
        get fechaFinVigencia() {
            if (!this.membresiaSeleccionada) return '';
            if (this.membresiaTieneRangoFijo) return this.membresiaSeleccionada.fecha_fin_fija.substring(0, 10);
            if (!this.fechaInicioMembresia) return '';

            const fecha = new Date(`${this.fechaInicioMembresia}T00:00:00`);
            fecha.setDate(fecha.getDate() + Math.max(0, Number(this.membresiaSeleccionada.mem_duracion) - 1));

            return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
        },
        async obtenerJson(url) {
            const respuesta = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const esJson = respuesta.headers.get('content-type')?.includes('application/json');

            if (!respuesta.ok || !esJson) {
                if (respuesta.status === 401 || respuesta.status === 419 || respuesta.redirected) {
                    throw new Error('Tu sesión expiró. Recarga la página e inicia sesión nuevamente.');
                }

                throw new Error('No se pudo completar la consulta. Inténtalo nuevamente.');
            }

            return respuesta.json();
        },
        async restaurarModal() {
            if (this.modalProductos) await this.cargarMetodosProductos(this.sinAlumno);
            if (this.modalMembresia) await this.cargarMetodosMembresia();
        },
        async aperturaDesdeAlumno() {
            const dni = @js(request('dni'));
            if (@js(request('abrir')) !== 'membresia' || !dni) return;
            await this.abrirMembresia();
            this.busquedaAlumno = String(dni).replace(/\D/g, '');
            await this.buscarAlumno();
            if (this.busquedaAlumno === String(dni).replace(/\D/g, '') && this.resultados.length === 1) {
                this.seleccionarAlumno(this.resultados[0]);
            }
        },
        async cargarMetodosProductos(rapida) {
            this.errorCargaProductos = '';
            if (this.datosProductosOk) return;
            this.cargandoProductos = true;
            try {
                const url = rapida ? '{{ route('ventas.datos.rapida') }}' : '{{ route('ventas.datos.producto') }}';
                const datos = await this.obtenerJson(url);
                this.metodos = datos.metodos;
                this.datosProductosOk = true;
            } catch (error) {
                this.errorCargaProductos = error.message;
            } finally {
                this.cargandoProductos = false;
            }
        },
        async cargarMetodosMembresia() {
            this.errorCargaMembresia = '';
            if (this.datosMembresiaOk) return;
            this.cargandoMembresia = true;
            try {
                const datos = await this.obtenerJson('{{ route('ventas.datos.membresia') }}');
                this.metodos = datos.metodos;
                this.datosMembresiaOk = true;
            } catch (error) {
                this.errorCargaMembresia = error.message;
            } finally {
                this.cargandoMembresia = false;
            }
        },
        async abrirProductos(rapida) {
            this.sinAlumno = rapida;
            this.carrito = [];
            this.reiniciarAlumno();
            this.busquedaProducto = '';
            this.resultadosProductos = [];
            this.montoProducto = 0;
            this.montoProducto2 = 0;
            this.segundoPagoProducto = false;
            this.modalProductos = true;
            await this.cargarMetodosProductos(rapida);
        },
        async abrirMembresia() {
            this.montoMembresia = 0;
            this.montoMembresia2 = 0;
            this.segundoPagoMembresia = false;
            this.membresiaAnonima = false;
            this.reiniciarAlumno();
            this.limpiarMembresia();
            this.modalMembresia = true;
            await this.cargarMetodosMembresia();
        },
        agregar(p) {
            let i = this.carrito.find(x => x.id_productos === p.id_productos);
            if (i) {
                if (i.cantidad < p.prod_cantidad) i.cantidad++;
            } else {
                this.carrito.push({ ...p, cantidad: 1 });
            }
            this.montoProducto = this.total;
        },
        reiniciarAlumno() {
            this.alumno = null;
            this.busquedaAlumno = '';
            this.resultados = [];
            this.buscandoAlumno = false;
            this.errorBusquedaAlumno = '';
        },
        seleccionarAlumno(alumno) {
            this.alumno = alumno;
            this.busquedaAlumno = String(alumno.alum_numDoc);
            this.resultados = [];
        },
        async buscarAlumno() {
            const consulta = this.busquedaAlumno.replace(/\D/g, '');
            this.busquedaAlumno = consulta;
            this.errorBusquedaAlumno = '';

            if (this.alumno && consulta !== String(this.alumno.alum_numDoc)) {
                this.alumno = null;
            }
            if (consulta.length < 3) {
                this.resultados = [];
                this.buscandoAlumno = false;
                return;
            }

            this.buscandoAlumno = true;
            try {
                const resultados = await this.obtenerJson(`{{ route('ventas.alumnos.buscar') }}?q=${encodeURIComponent(consulta)}`);
                if (this.busquedaAlumno === consulta) this.resultados = resultados;
            } catch (error) {
                if (this.busquedaAlumno === consulta) {
                    this.resultados = [];
                    this.errorBusquedaAlumno = error.message;
                }
            } finally {
                if (this.busquedaAlumno === consulta) this.buscandoAlumno = false;
            }
        },
        async buscarProductos() {
            const consulta = this.busquedaProducto.trim();
            this.errorBusquedaProductos = '';
            if (consulta.length < 2) {
                this.resultadosProductos = [];
                this.buscandoProductos = false;
                return;
            }

            this.buscandoProductos = true;
            try {
                const resultados = await this.obtenerJson(`{{ route('ventas.productos.buscar') }}?q=${encodeURIComponent(consulta)}`);
                if (this.busquedaProducto.trim() === consulta) this.resultadosProductos = resultados;
            } catch (error) {
                if (this.busquedaProducto.trim() === consulta) {
                    this.resultadosProductos = [];
                    this.errorBusquedaProductos = error.message;
                }
            } finally {
                if (this.busquedaProducto.trim() === consulta) this.buscandoProductos = false;
            }
        },
        seleccionarProducto(producto) {
            if (!producto.disponible) return;
            this.agregar(producto);
            this.busquedaProducto = '';
            this.resultadosProductos = [];
        },
        async buscarMembresias() {
            const consulta = this.busquedaMembresia.trim();
            this.errorBusquedaMembresias = '';
            if (this.membresiaSeleccionada && consulta !== this.membresiaSeleccionada.mem_nombre) {
                this.membresiaId = '';
                this.membresiaSeleccionada = null;
                this.fechaInicioMembresia = '';
                this.membresiaAnonima = false;
            }
            if (consulta.length < 2) {
                this.resultadosMembresias = [];
                this.buscandoMembresias = false;
                return;
            }

            this.buscandoMembresias = true;
            try {
                const resultados = await this.obtenerJson(`{{ route('ventas.membresias.buscar') }}?q=${encodeURIComponent(consulta)}`);
                if (this.busquedaMembresia.trim() === consulta) this.resultadosMembresias = resultados;
            } catch (error) {
                if (this.busquedaMembresia.trim() === consulta) {
                    this.resultadosMembresias = [];
                    this.errorBusquedaMembresias = error.message;
                }
            } finally {
                if (this.busquedaMembresia.trim() === consulta) this.buscandoMembresias = false;
            }
        },
        seleccionarMembresia(membresia) {
            this.membresiaSeleccionada = membresia;
            this.membresiaId = membresia.id_mem;
            this.busquedaMembresia = membresia.mem_nombre;
            this.resultadosMembresias = [];
            this.fechaInicioMembresia = '';
            this.membresiaAnonima = false;
            this.montoMembresia = Number(membresia.mem_precio);
            this.montoMembresia2 = 0;
        },
        limpiarMembresia() {
            this.membresiaSeleccionada = null;
            this.membresiaId = '';
            this.busquedaMembresia = '';
            this.resultadosMembresias = [];
            this.fechaInicioMembresia = '';
            this.membresiaAnonima = false;
            this.montoMembresia = 0;
            this.montoMembresia2 = 0;
        },
        descripcionMembresia(membresia) {
            if (!membresia) return '';
            if (membresia.fecha_inicio_fija && membresia.fecha_fin_fija) {
                return `${this.formatearFecha(membresia.fecha_inicio_fija)} al ${this.formatearFecha(membresia.fecha_fin_fija)}`;
            }

            return `${membresia.mem_duracion} día${Number(membresia.mem_duracion) === 1 ? '' : 's'}`;
        },
        formatearFecha(valor) {
            if (!valor) return '';
            const [anio, mes, dia] = valor.substring(0, 10).split('-');
            return `${dia}/${mes}/${anio}`;
        },
        anular(id) {
            this.anularId = id;
            this.modalAnular = true;
        }
    }
}
</script>
@endpush
