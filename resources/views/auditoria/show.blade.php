@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('auditoria.index') }}" class="inline-flex items-center text-pink-600 hover:text-pink-700">
            <i class="fas fa-arrow-left mr-2"></i> Volver al listado
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4">
            <h1 class="text-xl font-bold text-white">Detalle de Auditoría</h1>
            <p class="text-gray-300 text-sm">Registro #{{ $log->id_audit_log }}</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <p class="text-sm text-gray-500">Fecha y Hora</p>
                    <p class="font-medium text-gray-900">{{ $log->fecha_formato }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Usuario</p>
                    <p class="font-medium text-gray-900">{{ $log->usuario->name ?? 'Sistema' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Acción</p>
                    <p class="font-medium text-gray-900">
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
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Módulo</p>
                    <p class="font-medium text-gray-900">{{ ucfirst($log->modulo) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Modelo</p>
                    <p class="font-medium text-gray-900">{{ $log->modelo }} #{{ $log->modelo_id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Dirección IP</p>
                    <p class="font-medium text-gray-900">{{ $log->ip_address ?? '-' }}</p>
                </div>
            </div>

            @if($log->valores_antiguos)
            <div class="mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-arrow-circle-left text-red-500 mr-2"></i>
                    Valores Antiguos
                </h2>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <pre class="text-sm text-gray-800 overflow-x-auto">{{ json_encode($log->valores_antiguos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            @if($log->valores_nuevos)
            <div class="mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-arrow-circle-right text-green-500 mr-2"></i>
                    Valores Nuevos
                </h2>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <pre class="text-sm text-gray-800 overflow-x-auto">{{ json_encode($log->valores_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            @if($log->accion === 'editar' && $log->valores_antiguos && $log->valores_nuevos)
            <div class="mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-exchange-alt text-blue-500 mr-2"></i>
                    Cambios Realizados
                </h2>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Anterior</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nuevo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-200">
                            @foreach($log->valores_nuevos as $campo => $valorNuevo)
                                @if(isset($log->valores_antiguos[$campo]) && $log->valores_antiguos[$campo] != $valorNuevo)
                                <tr>
                                    <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $campo }}</td>
                                    <td class="px-3 py-2 text-sm text-red-600">{{ is_array($log->valores_antiguos[$campo]) ? json_encode($log->valores_antiguos[$campo]) : $log->valores_antiguos[$campo] }}</td>
                                    <td class="px-3 py-2 text-sm text-green-600">{{ is_array($valorNuevo) ? json_encode($valorNuevo) : $valorNuevo }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
