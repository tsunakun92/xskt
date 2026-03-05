{{--
    Form Input Component with Width Options

    Example Usage:
    <x-form.input name="email" label="Email" type="email" />
    <x-form.input name="username" label="Username" width="w-64" />
    <x-form.input name="code" label="Code" width="200px" />
    <x-form.input name="percentage" label="Percentage" width="25%" />
--}}

@props([
    'type' => 'text',
    'name' => '',
    'id' => '',
    'label' => '',
    'value' => '',
    'step' => null,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'hidden' => false,
    'datalist' => [],
    'width' => 'w-full',
])

@php
    // Set default id if not provided
    $id = $id ?: $name;

    // Default step for time input
    $computedStep = $step ?? ($type === 'time' ? '1' : null);

    // Handle different width formats
    $widthClass = '';
    if (
        str_contains($width, 'px') ||
        str_contains($width, '%') ||
        str_contains($width, 'rem') ||
        str_contains($width, 'em')
    ) {
        // Custom width with units (px, %, rem, em)
        $widthClass = '';
        $inlineStyle = "width: {$width};";
    } elseif (str_starts_with($width, 'w-')) {
        // Tailwind width class
        $widthClass = $width;
        $inlineStyle = '';
    } else {
        // Default to full width
        $widthClass = 'w-full';
        $inlineStyle = '';
    }
@endphp

<div class="@if ($hidden) hidden @endif">
    @if ($label)
        <label for="{{ $id }}"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    <div class="relative mt-1">
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
            value="{{ old($name) ?? $value }}" list="{{ $id }}-autocomplete" {{ $required ? 'required' : '' }}
            {{ $readonly ? 'readonly' : '' }} {{ $disabled ? 'disabled' : '' }}
            {{ $computedStep ? "step=$computedStep" : '' }}
            @if ($inlineStyle) style="{{ $inlineStyle }}" @endif
            {{ $attributes->merge(['class' => "block {$widthClass} border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" . ($type === 'time' ? ' time-input pr-10' : '')]) }} />
        @if ($type === 'time')
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        @endif
    </div>

    @if (!empty($datalist))
        <datalist id="{{ $id }}-autocomplete">
            @foreach ($datalist as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>
    @endif

    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
