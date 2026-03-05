@php

use Modules\Admin\Models\Setting;

@endphp
<x-admin-layout>
    <x-container>
        <x-section>
            <x-slot name="header">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">
                        {{ __('admin::crud.users.setting') }} - {{ $model->name }}
                    </h2>
                </div>
            </x-slot>
            <x-form action="{{ route('users.setting', $model->id) }}" class="space-y-4">
                @csrf

                @foreach ($userSettings as $setting)
                    @php
                        $value = old('settings.' . $setting['id'], $setting['value']);
                        $label = $setting['key'];
                        if (!empty($setting['description'])) {
                            $label .= ' (' . $setting['description'] . ')';
                        }
                        $checkboxKeys = ['dark_mode', 'notification_enabled'];
                        $selectKeys = ['language', 'timezone'];
                    @endphp

                    @if (in_array($setting['key'], $checkboxKeys, true))
                        <div class="flex items-center gap-3">
                            {{-- Ensure unchecked checkbox still submits a value --}}
                            <input type="hidden" name="settings[{{ $setting['id'] }}]" value="0" />

                            <input id="setting-{{ $setting['id'] }}" type="checkbox"
                                name="settings[{{ $setting['id'] }}]" value="1" @checked((string) $value === '1' || $value === 1 || $value === true)
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600" />
                            <label for="setting-{{ $setting['id'] }}" class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $label }}
                            </label>

                            @error('settings.' . $setting['id'])
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @elseif (in_array($setting['key'], $selectKeys, true))
                        @php
                            $options = $setting['key'] === 'language' ? Setting::LANGUAGES : Setting::TIMEZONES;
                        @endphp
                        <x-form.select name="settings[{{ $setting['id'] }}]" :label="$label" :options="$options"
                            :selected="$value" />
                    @else
                        <x-input type="text" name="settings[{{ $setting['id'] }}]" :label="$label"
                            :value="$value" :placeholder="__('admin::crud.leave_empty_for_default') . ': ' . $setting['default_value']" :datalist="[]" />
                    @endif
                @endforeach

                <div class="flex justify-end">
                    <x-button style="light" href="{{ route($route . '.index') }}">
                        {{ __('admin::crud.back') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin::crud.submit') }}
                    </x-button>
                </div>
            </x-form>
        </x-section>
    </x-container>
</x-admin-layout>
