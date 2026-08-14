<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none">
        <i class="fas fa-bell text-xl"></i>
        @if($totalNoLeidas > 0)
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                {{ $totalNoLeidas > 9 ? '9+' : $totalNoLeidas }}
            </span>
        @endif
    </button>

    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
        
        <div class="p-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Notificaciones</h3>
            @if($totalNoLeidas > 0)
                <form action="{{ route('notificaciones.marcar-todas') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-pink-600 hover:text-pink-700">
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($notificaciones as $notificacion)
                <div class="p-3 border-b border-gray-100 hover:bg-gray-50">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-{{ $notificacion->color }}-100 flex items-center justify-center">
                                <i class="fas {{ $notificacion->icono }} text-{{ $notificacion->color }}-600 text-sm"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $notificacion->titulo }}</p>
                            <p class="text-xs text-gray-600 mt-1">{{ $notificacion->mensaje }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $notificacion->fecha_formato }}</p>
                        </div>
                        <form action="{{ route('notificaciones.leida', $notificacion->id_notificacion) }}" method="POST" class="flex-shrink-0">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600" title="Marcar como leída">
                                <i class="fas fa-check text-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-3xl mb-2"></i>
                    <p class="text-sm">No tienes notificaciones</p>
                </div>
            @endforelse
        </div>

        <div class="p-3 border-t border-gray-200">
            <a href="{{ route('notificaciones.index') }}" class="block text-center text-sm text-pink-600 hover:text-pink-700 font-medium">
                Ver todas las notificaciones
            </a>
        </div>
    </div>
</div>
