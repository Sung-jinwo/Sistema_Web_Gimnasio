{{-- resources/views/components/select-filter.blade.php --}}

<select
    x-model="{{ $model }}"
    {{ $attributes->merge(['class' => 'px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors']) }}>
    <option value="todos">{{ $defaultLabel }}</option>
    @foreach($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>