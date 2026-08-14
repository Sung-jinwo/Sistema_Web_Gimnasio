@extends('layouts.app')

@section('content')
<div x-data="{ 
    showVentaRapidaModal: false, 
    showVentaProductoModal: false, 
    showVentaMembresiaModal: false,
    productos: [],
    alumnos: [],
    membresias: [],
    metodos: [],
    productoSeleccionado: null,
    alumnoSeleccionado: null,
    membresiaSeleccionada: null,
    cantidad: 1,
    
    cargarDatosRapida() {
        fetch('{{ route('ventas.datos.rapida') }}')
            .then(res => res.json())
            .then(data => {
                this.productos = data.productos;
                this.metodos = data.metodos;
            });
    },
    
    cargarDatosProducto() {
        fetch('{{ route('ventas.datos.producto') }}')
            .then(res => res.json())
            .then(data => {
                this.alumnos = data.alumnos;
                this.productos = data.productos;
                this.metodos = data.metodos;
            });
    },
    
    cargarDatosMembresia() {
        fetch('{{ route('ventas.datos.membresia') }}')
            .then(res => res.json())
            .then(data => {
                this.alumnos = data.alumnos;
                this.membresias = data.membresias;
                this.metodos = data.metodos;
            });
    }
}" class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Ventas</h1>
        <div class="flex flex-wrap gap-2">
            @can('create', App\Models\Venta::class)
            <button type="button" @click="showVentaRapidaModal = true; cargarDatosRapida()" class="inline-flex items-center px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                <i class="fas fa-bolt mr-1"></i> Venta Rápida
            </button>
            <button type="button" @click="showVentaProductoModal = true; cargarDatosProducto()" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                <i class="fas fa-box mr-1"></i> Venta Producto
            </button>
            <button type="button" @click="showVentaMembresiaModal = true; cargarDatosMembresia()" class="inline-flex items-center px-3 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition text-sm">
                <i class="fas fa-award mr-1"></i> Venta Membresía
            </button>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por alumno..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="tipo_venta" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los tipos</option>
                <option value="producto" {{ request('tipo_venta') === 'producto' ? 'selected' : '' }}>Producto</option>
                <option value="membresia" {{ request('tipo_venta') === 'membresia' ? 'selected' : '' }}>Membresía</option>
                <option value="rapida" {{ request('tipo_venta') === 'rapida' ? 'selected' : '' }}>Rápida</option>
            </select>
            <select name="estado_venta" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="completado" {{ request('estado_venta') === 'completado' ? 'selected' : '' }}>Completado</option>
                <option value="reservado" {{ request('estado_venta') === 'reservado' ? 'selected' : '' }}>Reservado</option>
                <option value="incompleto" {{ request('estado_venta') === 'incompleto' ? 'selected' : '' }}>Incompleto</option>
            </select>
            <select name="estado_pago" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los pagos</option>
                <option value="pagado" {{ request('estado_pago') === 'pagado' ? 'selected' : '' }}>Pagado</option>
                <option value="parcial" {{ request('estado_pago') === 'parcial' ? 'selected' : '' }}>Parcial</option>
                <option value="pendiente" {{ request('estado_pago') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto/Membresía</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Método</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Venta</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Pago</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ventas as $venta)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $venta->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($venta->tipo_venta === 'rapida')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Rápida</span>
                            @elseif($venta->tipo_venta === 'producto')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Producto</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">Membresía</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $venta->alumno->nombreCompleto ?? 'Venta rápida' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $venta->producto->prod_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($venta->venta_total, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $venta->metodo->metod_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($venta->estado_venta === 'completado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completado</span>
                            @elseif($venta->estado_venta === 'reservado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Reservado</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Incompleto</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($venta->estado_pago === 'pagado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>
                            @elseif($venta->estado_pago === 'parcial')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                @can('update', $venta)
                                <button type="button" onclick="editVenta({{ $venta->id_venta }})" class="text-blue-600 hover:text-blue-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                                @can('delete', $venta)
                                <form action="{{ route('ventas.destroy', $venta->id_venta) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar esta venta?')">
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
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No se encontraron ventas.</td>
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

    @include('ventas.modals.rapida')
    @include('ventas.modals.producto')
    @include('ventas.modals.membresia')
</div>

@push('scripts')
<script>
function editVenta(id) {
    alert('Editar venta ' + id + ' - Funcionalidad pendiente');
}
</script>
@endpush
@endsection
