@extends('layouts.app')

@section('content')
@section('page-title','Auditoría')
@section('page-subtitle','Trazabilidad de cambios críticos: quién, cuándo y qué modificó')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Auditoría</h1>
            <p class="text-gray-600">Registro de operaciones críticas del sistema</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('auditoria.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                <select name="usuario" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" {{ ($filtros['usuario'] ?? '') == $usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Módulo</label>
                <select name="modulo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    @foreach($modulos as $key => $label)
                        <option value="{{ $key }}" {{ ($filtros['modulo'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Acción</label>
                <select name="accion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todas</option>
                    @foreach($acciones as $key => $label)
                        <option value="{{ $key }}" {{ ($filtros['accion'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="{{ $filtros['fecha_inicio'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="{{ $filtros['fecha_fin'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div class="flex items-end lg:col-span-5">
                <button type="submit" class="w-full px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Tabla de Logs --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modelo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">IP</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Detalles</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->fecha_formato }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $log->usuario->name ?? 'Sistema' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $colores = [
                                    'crear' => 'bg-green-100 text-green-800',
                                    'editar' => 'bg-blue-100 text-blue-800',
                                    'eliminar' => 'bg-red-100 text-red-800',
                                    'aprobar' => 'bg-emerald-100 text-emerald-800',
                                    'rechazar' => 'bg-orange-100 text-orange-800',
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $colores[$log->accion] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $log->accion_formato }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $modulos[$log->modulo] ?? $log->modulo }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->modelo }} #{{ $log->modelo_id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('auditoria.show', $log->id_audit_log) }}" class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron registros de auditoría.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
