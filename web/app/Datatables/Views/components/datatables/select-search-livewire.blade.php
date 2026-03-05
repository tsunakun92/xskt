<div class="mb-4 @if ($hidden) hidden @endif">
    @if ($label)
        <label for="filter_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}@if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="mt-1 relative">
        <select id="filter_{{ $name }}" name="{{ $name }}" wire:model.live="selected"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white @error($name) border-red-500 @enderror @if ($disabled || ($searchDependsOn && empty($dependsValue))) opacity-50 cursor-not-allowed @endif"
            @if ($disabled || ($searchDependsOn && empty($dependsValue))) disabled @endif @if ($required) required @endif>
            @if ($hasError)
                <option value="">{{ $textError }}</option>
            @elseif($searchDependsOn && empty($dependsValue))
                <option value="">{{ $textDefault }}</option>
            @elseif(empty($options))
                <option value="">{{ $textNotFound }}</option>
            @else
                <option value="" @if (empty($selected)) selected @endif>{{ $textPleaseSelect }}
                </option>
                @foreach ($options as $value => $label)
                    <option value="{{ $value }}" @if ($value == $selected && !empty($selected)) selected @endif>
                        {{ $label }}</option>
                @endforeach
            @endif
        </select>

    </div>

    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

    @if ($hasError && $errorMessage)
        <p class="mt-2 text-sm text-red-600">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ $errorMessage }}
        </p>
    @endif
</div>
