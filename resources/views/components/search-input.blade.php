
<div class="relative">
    <svg class="absolute left-3 top-3 text-gray-400 w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
    </svg>
    <input
        type="text"
        x-model="{{ $model }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors']) }}>
</div>