<form method="{{ $method }}" action="{{ $action }}" {{ $attributes }}>
    @csrf
    {{ $slot }}
</form>
