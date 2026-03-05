<div
    {{ $attributes->merge(['class' => $layout === 'full' ? 'w-full px-2' : 'max-w-7xl mx-auto sm:px-6 lg:px-8 pb-6']) }}>
    {{ $slot }}
</div>
