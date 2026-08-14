@extends('layouts.app')

@section('content')
<div x-data="{ activeTab: 'info', showAsignarModal: false }" class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('alumnos.index') }}" class="inline-flex items-center text-pink-600 hover:text-pink-700">
            <i class="fas fa-arrow-left mr-2"></i> Volver al listado
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-pink-600 to-pink-700 px-6 py-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-white">{{ $alumno->nombreCompleto }}</h1>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-white/20 text-white rounded">
                            <i class="fas fa-id-card mr-1"></i> {{ $alumno->alum_numDoc }}
                        </span>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-white/20 text-white rounded">
                            <i class="fas fa-hashtag mr-1"></i> {{ $alumno->alum_codigo }}
                        </span>
                        @if($alumno->alum_estado)
                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-green-500 text-white rounded">Activo</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-red-500 text-white rounded">Inactivo</span>
                        @endif
                    </div>
                </div>
                @can('update', $alumno)
                <a href="{{ route('alumnos.edit', $alumno->id_alumno) }}" class="px-4 py-2 bg-white text-pink-600 rounded-lg hover:bg-gray-100 transition text-sm font-medium">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
                @endcan
            </div>
        </div>

        <div class="border-b border-gray-200">
            <nav class="flex overflow-x-auto">
                <button @click="activeTab = 'info'" :class="activeTab === 'info' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-info-circle mr-2"></i> Información
                </button>
                <button @click="activeTab = 'membresias'" :class="activeTab === 'membresias' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-award mr-2"></i> Membresías
                    <span class="ml-1 px-2 py-0.5 text-xs bg-pink-100 text-pink-600 rounded-full">{{ $membresias->count() }}</span>
                </button>
                <button @click="activeTab = 'pagos'" :class="activeTab === 'pagos' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-money-bill mr-2"></i> Pagos
                    <span class="ml-1 px-2 py-0.5 text-xs bg-pink-100 text-pink-600 rounded-full">{{ $pagos->count() }}</span>
                </button>
                <button @click="activeTab = 'asistencias'" :class="activeTab === 'asistencias' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-calendar-check mr-2"></i> Asistencias
                    <span class="ml-1 px-2 py-0.5 text-xs bg-pink-100 text-pink-600 rounded-full">{{ $asistencias->count() }}</span>
                </button>
            </nav>
        </div>
    </div>

    <div x-show="activeTab === 'info'" class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Información Personal</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500">Nombre completo</p>
                <p class="font-medium text-gray-900">{{ $alumno->nombreCompleto }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">DNI</p>
                <p class="font-medium text-gray-900">{{ $alumno->alum_numDoc }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Código de alumno</p>
                <p class="font-medium text-gray-900">{{ $alumno->alum_codigo }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Fecha de nacimiento</p>
                <p class="font-medium text-gray-900">{{ $alumno->fecha_nac ? \Carbon\Carbon::parse($alumno->fecha_nac)->format('d/m/Y') : '-' }} ({{ $alumno->alumEdad ?? '-' }} años)</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Sexo</p>
                <p class="font-medium text-gray-900">{{ $alumno->sexo->sexo_nombre ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Teléfono</p>
                <p class="font-medium text-gray-900">{{ $alumno->alum_telefo ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Correo electrónico</p>
                <p class="font-medium text-gray-900">{{ $alumno->alum_correro ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Dirección</p>
                <p class="font-medium text-gray-900">{{ $alumno->alum_direccion ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Sede</p>
                <p class="font-medium text-gray-900">{{ $alumno->sede->sede_nombre ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Fecha de registro</p>
                <p class="font-medium text-gray-900">{{ $alumno->created_at->format('d/m/Y H:i') }}</p>
            </div>
            @if($alumno->alum_condi)
            <div class="md:col-span-2">
                <p class="text-sm text-gray-500">Observaciones</p>
                <p class="font-medium text-gray-900">{{ $alumno->alum_condi }}</p>
            </div>
            @endif
        </div>
    </div>

    <div x-show="activeTab === 'membresias'" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-900">Historial de Membresías</h2>
            @can('create', App\Models\MembresiaAlumno::class)
            <button type="button" @click="showAsignarModal = true" class="inline-flex items-center px-3 py-1.5 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition text-sm">
                <i class="fas fa-plus mr-1"></i> Asignar Membresía
            </button>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modalidad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inicio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($membresias as $membresia)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $membresia['plan'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $membresia['modalidad'] === 'por_meses' ? 'Por meses' : 'Por fechas' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $membresia['inicio'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $membresia['vencimiento'] }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($membresia['monto'], 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($membresia['estado'] === 'Activa')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                            @elseif($membresia['estado'] === 'Por vencer')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Por vencer</span>
                            @elseif($membresia['estado'] === 'Vencida')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencida</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ $membresia['estado'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay membresías registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="activeTab === 'pagos'" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Historial de Pagos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pagos as $pago)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $pago['fecha']->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $pago['concepto'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $pago['metodo'] }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($pago['total'], 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($pago['estado'] === 'completo')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>
                            @elseif($pago['estado'] === 'incompleto')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay pagos registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="activeTab === 'asistencias'" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Historial de Asistencias</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hora</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($asistencias as $asistencia)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $asistencia['fecha'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $asistencia['hora'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $asistencia['sede'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">No hay asistencias registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('create', App\Models\MembresiaAlumno::class)
    <x-modal-form show="showAsignarModal" title="Asignar Membresía" subtitle="Seleccione un plan para el alumno" icon='<i class="fas fa-award text-white"></i>' size="md" headerColor="purple">
        <form method="POST" action="{{ route('membresias.asignar', $alumno->id_alumno) }}" class="space-y-4">
            @csrf
            <div>
                <label for="fkmem" class="block text-sm font-medium text-gray-700 mb-1">Plan de Membresía <span class="text-red-500">*</span></label>
                <select id="fkmem" name="fkmem" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccionar plan...</option>
                    @foreach(App\Models\Membresia::where('estado', 'A')->get() as $membresia)
                        <option value="{{ $membresia->id_mem }}">{{ $membresia->mem_nombre }} - S/ {{ number_format($membresia->mem_precio, 2) }} ({{ $membresia->mem_duracion }} días)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="modalidad" value="por_meses" checked class="form-radio text-pink-600" onchange="toggleAsignarModalidad(this.value)">
                        <span class="ml-2 text-sm text-gray-700">Por meses</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="modalidad" value="por_fechas" class="form-radio text-pink-600" onchange="toggleAsignarModalidad(this.value)">
                        <span class="ml-2 text-sm text-gray-700">Por fechas</span>
                    </label>
                </div>
            </div>

            <div id="asignarFechaInicioField">
                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio <span class="text-red-500">*</span></label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div id="asignarFechaFinField" style="display: none;">
                <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Fin <span class="text-red-500">*</span></label>
                <input type="date" id="fecha_fin" name="fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showAsignarModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    Asignar
                </button>
            </div>
        </form>
    </x-modal-form>
    @endcan
</div>

@push('scripts')
<script>
function toggleAsignarModalidad(valor) {
    const fechaInicioField = document.getElementById('asignarFechaInicioField');
    const fechaFinField = document.getElementById('asignarFechaFinField');
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');
    
    if (valor === 'por_fechas') {
        fechaFinField.style.display = 'block';
        fechaFinInput.setAttribute('required', 'required');
    } else {
        fechaFinField.style.display = 'none';
        fechaFinInput.removeAttribute('required');
    }
}
</script>
@endpush
@endsection
