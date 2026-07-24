
<span {{ $attributes->merge([
    'class' => 'inline-block rounded-full font-medium ' . 
    $getVariantClasses() . ' ' . 
    $getSizeClasses()
]) }}>
    {{ $slot }}
</span>