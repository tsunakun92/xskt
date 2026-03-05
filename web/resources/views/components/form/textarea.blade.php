{{--
    Form Textarea Component with Width Options

    Example Usage:
    <x-form.textarea name="description" label="Description" />
    <x-form.textarea name="notes" label="Notes" width="w-96" rows="6" />
    <x-form.textarea name="content" label="Content" width="500px" />
    <x-form.textarea name="summary" label="Summary" width="75%" />
--}}

@props([
    'name' => '',
    'id' => '',
    'label' => '',
    'value' => '',
    'rows' => 3,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'hidden' => false,
    'width' => 'w-full',
])

@php
    // Set default id if not provided
    $id = $id ?: $name;

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
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }} {{ $disabled ? 'disabled' : '' }}
        @if ($inlineStyle) style="{{ $inlineStyle }}" @endif
        {{ $attributes->merge(['class' => "mt-1 block {$widthClass} border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"]) }}>{{ old($name) ?? $value }}</textarea>
    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
