@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Notificaciones</h1>
        @if($totalNoLeidas > 0)
            <form action="{{ route('notificaciones.marcar-todas') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    <i class="fas fa-check-double mr-2"></i> Marcar todas como leídas
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm mb-4">
        <div class="p-4 border-b border-gray-200">
            <form method="GET" action="{{ route('notificaciones.index') }}" class="flex flex-col sm:flex-row gap-3">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="solo_no_leidas" value="1" 
                           {{ request('solo_no_leidas') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                    <span class="ml-2 text-sm text-gray-700">Solo no leídas</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition text-sm">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-200">
            @forelse($notificaciones as $notificacion)
                <div class="p-4 hover:bg-gray-50 {{ !$notificacion->leida ? 'bg-blue-50' : '' }}">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-{{ $notificacion->color }}-100 flex items-center justify-center">
                                <i class="fas {{ $notificacion->icono }} text-{{ $notificacion->color }}-600"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $notificacion->titulo }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ $notificacion->mensaje }}</p>
                                    <p class="text-xs text-gray-400 mt-2">
                                        <i class="fas fa-clock mr-1"></i>{{ $notificacion->fecha_formato }}
                                    </p>
                                </div>
                                @if(!$notificacion->leida)
                                    <form action="{{ route('notificaciones.leida', $notificacion->id_notificacion) }}" method="POST" class="flex-shrink-0">
                                        @csrf
                                        <button type="submit" class="text-pink-600 hover:text-pink-700" title="Marcar como leída">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 flex-shrink-0">
                                        <i class="fas fa-check mr-1"></i>Leída
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay notificaciones</h3>
                    <p class="text-sm">No tienes notificaciones para mostrar</p>
                </div>
            @endforelse
        </div>

        @if($notificaciones->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $notificaciones->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
