{{-- resources/views/components/page-loader.blade.php --}}

<div 
    x-show="isLoading"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
    
    <div class="bg-white rounded-lg p-8 shadow-lg flex flex-col items-center gap-4">
        <!-- Spinner -->
        <div class="relative w-12 h-12">
            <svg class="animate-spin w-12 h-12 text-purple-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        
        <!-- Mensaje -->
        <p class="text-sm font-medium text-gray-900">{{ $message }}</p>
    </div>
</div>