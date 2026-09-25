@extends('layouts.app')

@section('title', 'Ficha del alumno - SIGG')
@section('page-title', 'Ficha del alumno')
@section('page-subtitle', 'Información, membresías, pagos y asistencias')

@section('content')
<div x-data="{ activeTab: 'info', showEditModal: {{ request()->boolean('editar') || $errors->any() ? 'true' : 'false' }}, selectedAlumno: @js($alumno->toArray()), editUrl: @js(route('alumnos.update', $alumno->id_alumno)), closeEditModal(){ this.showEditModal=false } }" class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('alumnos.index') }}" class="inline-flex items-center text-pink-600 hover:text-pink-700">
            <i class="fas fa-arrow-left mr-2"></i> Volver al listado
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-pink-600 to-pink-700 px-6 py-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden">
                    <img src="{{ asset('icon/icongym.png') }}" alt="Logo" class="w-12 h-12 rounded-full object-cover bg-white">
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
                <button type="button" @click="showEditModal = true" class="px-4 py-2 bg-white text-pink-600 rounded-lg hover:bg-gray-100 transition text-sm font-medium">
                    <i class="fas fa-pen-to-square mr-1"></i> Editar
                </button>
                @endcan
            </div>
        </div>

        <div class="border-b border-gray-200">
            <nav class="flex overflow-x-auto">
                <button @click="activeTab = 'info'" :class="activeTab === 'info' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-info-circle mr-2"></i> Información
                </button>
                <button @click="activeTab = 'membresias'" :class="activeTab === 'membresias' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-award mr-2"></i> Membresía
                    <span class="ml-1 px-2 py-0.5 text-xs bg-pink-100 text-pink-600 rounded-full">{{ $membresias->count() }}</span>
                </button>
                <button @click="activeTab = 'pagos'" :class="activeTab === 'pagos' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-money-bill mr-2"></i> Pago
                    <span class="ml-1 px-2 py-0.5 text-xs bg-pink-100 text-pink-600 rounded-full">{{ $pagos->count() }}</span>
                </button>
                <button @click="activeTab = 'asistencias'" :class="activeTab === 'asistencias' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-calendar-check mr-2"></i> Asistencia
                    <span class="ml-1 px-2 py-0.5 text-xs bg-pink-100 text-pink-600 rounded-full">{{ $asistencias->count() }}</span>
                </button>
                <button @click="activeTab = 'vigencia'" :class="activeTab === 'vigencia' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-snowflake mr-2"></i> Vigencia
                    <span class="ml-1 px-2 py-0.5 text-xs bg-pink-100 text-pink-600 rounded-full">{{ $vigencias->count() }}</span>
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
                <p class="font-medium text-gray-900">{{ $alumno->fecha_nac ? \Carbon\Carbon::parse($alumno->fecha_nac)->format('d/m/Y') : '-' }} ({{ $alumno->alum_eda ?? '-' }} años)</p>
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
            <h2 class="text-lg font-bold text-gray-900">Historial de Membresía</h2>
        </div>
        <div class="overflow-x-auto">
            <table data-responsive="off" class="min-w-full divide-y divide-gray-200">
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
            <h2 class="text-lg font-bold text-gray-900">Historial de Pago</h2>
        </div>
        <div class="overflow-x-auto">
            <table data-responsive="off" class="min-w-full divide-y divide-gray-200">
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
                            @if($pago['estado'] === 'pagado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>
                            @elseif($pago['estado'] === 'parcial')
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
            <h2 class="text-lg font-bold text-gray-900">Historial de Asistencia</h2>
        </div>
        <div class="overflow-x-auto">
            <table data-responsive="off" class="min-w-full divide-y divide-gray-200">
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

    <div x-show="activeTab === 'vigencia'" class="space-y-5">
        @php($servicioVigencia = app(App\Services\CongelamientoService::class))
        @forelse($vigencias as $vigencia)
            @php($estadoVigencia = $servicioVigencia->estadoParaFicha($vigencia))
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ $vigencia->membresia->mem_nombre ?? 'Membresía' }}</h2>
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($vigencia->fecha_inicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($vigencia->fecha_fin)->format('d/m/Y') }}</p>
                    </div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $estadoVigencia === 'Activa' ? 'bg-green-100 text-green-800' : ($estadoVigencia === 'Congelada' ? 'bg-blue-100 text-blue-800' : ($estadoVigencia === 'Congelamiento programado' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">{{ $estadoVigencia }}</span>
                </div>
                <div class="p-6 flex flex-wrap gap-2">
                    @can('membresias.congelar')
                        @if($servicioVigencia->puedeCongelar($vigencia))
                            <button type="button" @click="urlCongelar = '{{ route('membresias.congelar', $vigencia->id_membresia_alumno) }}'; modalCongelar = true" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                <i class="fas fa-snowflake mr-2"></i> Congelar
                            </button>
                        @endif
                        @foreach($vigencia->congelamientos->whereIn('estado', ['programado', 'activo']) as $cong)
                            @if($cong->estado === 'programado')
                                <form method="POST" action="{{ route('congelamientos.cancelar', $cong->id_congelamiento) }}" class="inline" onsubmit="return confirm('¿Cancelar la programación y revertir la extensión?')">
                                    @csrf
                                    <button class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">Cancelar programación</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('congelamientos.finalizar', $cong->id_congelamiento) }}" class="inline" onsubmit="return confirm('¿Finalizar anticipadamente conservando solo los días utilizados?')">
                                @csrf
                                <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">Finalizar anticipadamente</button>
                            </form>
                        @endforeach
                    @endcan
                    @can('membresias.ajustar_vigencia')
                        @if($servicioVigencia->puedeAjustar($vigencia))
                            <button type="button" @click="urlAjustar = '{{ route('membresias.ajustar-vigencia', $vigencia->id_membresia_alumno) }}'; modalAjustar = true" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition text-sm">
                                <i class="fas fa-calendar-pen mr-2"></i> Ajustar vencimiento
                            </button>
                        @endif
                    @endcan
                </div>
                @if($vigencia->congelamientos->isNotEmpty() || $vigencia->ajustesVigencia->isNotEmpty())
                <div class="px-6 pb-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">Historial (inmutable)</h3>
                    <div class="overflow-x-auto">
                        <table data-responsive="off" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Por</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($vigencia->congelamientos as $cong)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">Congelamiento · {{ ucfirst($cong->estado) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $cong->fecha_inicio->format('d/m/Y') }} al {{ $cong->fecha_fin->format('d/m/Y') }} ({{ $cong->dias }} día(s))</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $cong->motivo }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $cong->registradoPor->name ?? '-' }}</td>
                                </tr>
                                @endforeach
                                @foreach($vigencia->ajustesVigencia as $ajuste)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">Ajuste de vencimiento</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $ajuste->fecha_anterior->format('d/m/Y') }} → {{ $ajuste->fecha_nueva->format('d/m/Y') }} ({{ $ajuste->diferencia_dias >= 0 ? '+' : '' }}{{ $ajuste->diferencia_dias }} días)</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $ajuste->motivo }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $ajuste->administrador->name ?? '-' }} · {{ $ajuste->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm p-12 text-center text-gray-500">Sin membresías registradas.</div>
        @endforelse
    </div>

    <x-modal-form show="modalCongelar" title="Congelar membresía" subtitle="El vencimiento se extiende de inmediato por los días programados" size="md">
        <form :action="urlCongelar" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicial *</label>
                    <input type="date" name="fecha_inicio" required min="{{ today()->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha final *</label>
                    <input type="date" name="fecha_fin" required min="{{ today()->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo *</label>
                <textarea name="motivo" required maxlength="1000" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Motivo del congelamiento..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="modalCongelar = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Registrar</button>
            </div>
        </form>
    </x-modal-form>

    <x-modal-form show="modalAjustar" title="Ajustar vencimiento" subtitle="Indique directamente la nueva fecha final" size="md">
        <form :action="urlAjustar" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva fecha final *</label>
                <input type="date" name="fecha_nueva" required min="{{ today()->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo *</label>
                <textarea name="motivo" required maxlength="1000" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Motivo del ajuste..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="modalAjustar = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">Ajustar</button>
            </div>
        </form>
    </x-modal-form>

    @can('update', $alumno)
        @include('alumnos.edit')
    @endcan
</div>
@endsection
