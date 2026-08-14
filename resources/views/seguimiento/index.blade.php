@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Seguimiento</h1>
    </div>

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex overflow-x-auto">
                <a href="{{ route('seguimiento.vencimientos', request()->query()) }}" 
                   class="px-6 py-3 text-sm font-medium whitespace-nowrap transition {{ $tab === 'por_vencer' ? 'border-b-2 border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-clock mr-2"></i>Próximos Vencimientos
                </a>
                <a href="{{ route('seguimiento.vencidos', request()->query()) }}" 
                   class="px-6 py-3 text-sm font-medium whitespace-nowrap transition {{ $tab === 'vencidos' ? 'border-b-2 border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Vencidos
                </a>
                <a href="{{ route('seguimiento.index', array_merge(request()->query(), ['tab' => 'pagos_pendientes'])) }}" 
                   class="px-6 py-3 text-sm font-medium whitespace-nowrap transition {{ $tab === 'pagos_pendientes' ? 'border-b-2 border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-money-bill mr-2"></i>Pagos Pendientes
                </a>
            </nav>
        </div>

        <div class="p-4">
            <form method="GET" action="{{ route('seguimiento.index') }}" class="flex flex-col lg:flex-row gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                
                <select name="sede" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todas las sedes</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id_sede }}" {{ ($filtros['sede'] ?? '') == $sede->id_sede ? 'selected' : '' }}>{{ $sede->sede_nombre }}</option>
                    @endforeach
                </select>

                <select name="empleado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos los empleados</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}" {{ ($filtros['empleado'] ?? '') == $empleado->id ? 'selected' : '' }}>{{ $empleado->name }}</option>
                    @endforeach
                </select>

                <select name="mes" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos los meses</option>
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ ($filtros['mes'] ?? '') == $i ? 'selected' : '' }}>{{ DateTime::createFromFormat('!m', $i)->format('F') }}</option>
                    @endfor
                </select>

                @if($tab === 'por_vencer')
                <select name="dias" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="5" {{ ($filtros['dias'] ?? 5) == 5 ? 'selected' : '' }}>Próximos 5 días</option>
                    <option value="7" {{ ($filtros['dias'] ?? '') == 7 ? 'selected' : '' }}>Próximos 7 días</option>
                    <option value="15" {{ ($filtros['dias'] ?? '') == 15 ? 'selected' : '' }}>Próximos 15 días</option>
                    <option value="30" {{ ($filtros['dias'] ?? '') == 30 ? 'selected' : '' }}>Próximos 30 días</option>
                </select>
                @endif

                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if($tab === 'pagos_pendientes')
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DNI</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Teléfono</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Pagado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                        @else
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DNI</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Teléfono</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Sede</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($registros as $registro)
                        @if($tab === 'pagos_pendientes')
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $registro->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $registro->alumno->nombreCompleto ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $registro->alumno->alum_numDoc ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $registro->alumno->alum_telefo ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($registro->venta_total, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-green-600 font-semibold hidden md:table-cell">S/ {{ number_format($registro->monto_pagado, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-red-600 font-semibold">S/ {{ number_format($registro->saldo, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($registro->estado_pago === 'parcial')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pendiente</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($registro->alumno && $registro->alumno->alum_telefo)
                                        <a href="{{ route('seguimiento.whatsapp', [$registro->alumno->id_alumno, 'tipo' => 'pago_pendiente']) }}" 
                                           target="_blank" 
                                           class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                            <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">Sin teléfono</span>
                                    @endif
                                </td>
                            </tr>
                        @else
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $registro->alumno->nombreCompleto ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $registro->alumno->alum_numDoc ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $registro->alumno->alum_telefo ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $registro->membresia->mem_nombre ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($registro->fecha_fin)->format('d/m/Y') }}
                                    @if($tab === 'vencidos')
                                        <span class="text-xs text-red-600 block">
                                            ({{ \Carbon\Carbon::parse($registro->fecha_fin)->diffInDays(now()) }} días)
                                        </span>
                                    @else
                                        <span class="text-xs text-yellow-600 block">
                                            ({{ now()->diffInDays(\Carbon\Carbon::parse($registro->fecha_fin)) }} días)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">{{ $registro->alumno->sede->sede_nombre ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($tab === 'vencidos')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencida</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Por vencer</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($registro->alumno && $registro->alumno->alum_telefo)
                                        <a href="{{ route('seguimiento.whatsapp', [$registro->alumno->id_alumno, 'tipo' => $tab === 'vencidos' ? 'vencido' : 'vencimiento']) }}" 
                                           target="_blank" 
                                           class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                            <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">Sin teléfono</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                @if($tab === 'por_vencer')
                                    No hay membresías próximas a vencer.
                                @elseif($tab === 'vencidos')
                                    No hay membresías vencidas.
                                @else
                                    No hay pagos pendientes.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registros->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $registros->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
