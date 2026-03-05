{{--
    Amenities Flags Component (bitwise checkbox list)

    Expected behavior:
    - Render list of amenities (bit positions) with checkboxes name="amenities[]"
    - Submit a hidden marker "amenities_present" so server can detect "all unchecked" and set flags = 0

    Usage:
    <x-form.amenities-flags :label="$label" :options="$options" :selectedBits="$selectedBits" />
--}}

@props([
    'label' => '',
    'options' => [], // [bit => label]
    'selectedBits' => [], // [int, int, ...]
    'flags' => null, // integer amenities_flags (optional; used to auto-check on edit)
    'required' => false,
    'disabled' => false,
    'hidden' => false,
    'name' => 'amenities',
])

@php
    $selectedBits = collect($selectedBits)->map(fn($v) => (int) $v)->unique()->values()->toArray();
    $id = $name . '-flags';
@endphp

<div class="@if ($hidden) hidden @endif">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    {{-- marker to allow "all unchecked" submit --}}
    <input type="hidden" name="{{ $name }}_present" value="1">

    <div class="mt-2 grid grid-cols-3 gap-2">
        @foreach ($options as $bit => $optionLabel)
            @php
                $bit = (int) $bit;
                // Priority: old input (amenities[]) -> selectedBits prop -> derive from flags integer
                $oldBits = old($name);
                if (is_array($oldBits)) {
                    $isChecked = in_array($bit, $oldBits, false);
                } elseif (!empty($selectedBits)) {
                    $isChecked = in_array($bit, $selectedBits, true);
                } else {
                    $flagsInt = (int) ($flags ?? 0);
                    $isChecked = ($flagsInt & (1 << $bit)) > 0;
                }
                $checkboxId = $id . '-' . $bit;
            @endphp
            <label for="{{ $checkboxId }}"
                class="flex items-center gap-2 rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2">
                <input id="{{ $checkboxId }}" type="checkbox" name="{{ $name }}[]" value="{{ $bit }}"
                    {{ $isChecked ? 'checked' : '' }} {{ $required ? 'required' : '' }}
                    {{ $disabled ? 'disabled' : '' }}
                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                <span class="text-sm text-gray-800 dark:text-gray-100">{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>

    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
