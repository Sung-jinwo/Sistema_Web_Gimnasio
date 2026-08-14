@php
    $colorClasses = $getColorClasses();
@endphp

<div class="bg-white rounded-lg shadow-sm p-6 border-l-4 {{ $colorClasses['border'] }}">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm text-gray-500 mb-1">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
            @if($subtitle)
                <p class="text-xs text-gray-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="w-12 h-12 {{ $colorClasses['bg'] }} rounded-lg flex items-center justify-center">
            <i class="fas {{ $icon }} {{ $colorClasses['text'] }} text-xl"></i>
        </div>
    </div>
</div>
