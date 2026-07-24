@props(['show' => 'false'])

<div 
    x-show="{{ $show }}"
    x-cloak
    {{ $attributes->merge(['class' => 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4']) }}
    @click.self="{{ $show }} = false">
    
    <div class="bg-white rounded-lg w-full {{ $getSizeClass() }} {{ $scrollable ? 'max-h-[90vh]' : '' }} overflow-hidden flex flex-col shadow-2xl">
        
        <!-- Header Mejorado -->
        @if($title)
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r {{ $getHeaderColorClass() }} flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($icon)
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                {!! $icon !!}
                            </div>
                        @endif
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $title }}</h3>
                            @if($subtitle)
                                <p class="text-white/90 text-sm mt-0.5">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>
                    <button 
                        @click="{{ $show }} = false"
                        class="text-white/80 hover:text-white transition-colors flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- Contenido con Scroll Opcional -->
        <div class="{{ $scrollable ? 'flex-1 overflow-y-auto' : '' }} p-6">
            {{ $slot }}
        </div>

        <!-- Footer -->
        @isset($footer)
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>