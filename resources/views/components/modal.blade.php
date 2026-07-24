{{-- resources/views/components/modal.blade.php --}}

@props(['show' => 'false', 'maxWidth' => 'md'])

<div 
    x-show="{{ $show }}"
    x-cloak
    {{ $attributes->merge(['class' => 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4']) }}
    @click.self="{{ $show }} = false">
    
    <div class="bg-white rounded-lg w-full {{ $getSizeClass() }} shadow-xl">
        @if($title)
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
                    @if($dismissible)
                        <button 
                            @click="{{ $show }} = false"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>