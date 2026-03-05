<x-admin-layout>
    <x-container>
        <x-section>
            <x-slot name="header">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">
                        {{ $action === 'edit' ? __('api::crud.edit') : __('api::crud.create') }} @lang('api::crud.' . $route . '.title')
                    </h2>
                </div>
            </x-slot>
            <x-form action="{{ $actionUrl }}" class="space-y-4" enctype="multipart/form-data">
                @if ($action === 'edit')
                    @method('PUT')
                @else
                    @method('POST')
                @endif

                @foreach ($fields as $name => $attributes)
                    @php
                        $value = old(
                            $name,
                            $action === 'edit'
                                ? $model->$name ?? ($attributes['value'] ?? '')
                                : $attributes['value'] ?? '',
                        );
                    @endphp

                    @switch($attributes['type'])
                        @case('textarea')
                            <x-textarea :name="$name" :label="$attributes['label']" :value="$value" :required="$attributes['required']"
                                :readonly="$attributes['readonly']" :disabled="$attributes['disabled']" :hidden="$attributes['hidden']" :placeholder="$attributes['placeholder'] ?? ''" />
                        @break

                        @case('editor')
                            <x-form.editor :name="$name" :label="$attributes['label']" :value="$value" :required="$attributes['required']"
                                :readonly="$attributes['readonly']" :disabled="$attributes['disabled']" :hidden="$attributes['hidden']" />
                        @break

                        @case('select')
                            <x-select :name="$name" :label="$attributes['label']" :options="$attributes['options']" :selected="$value"
                                :required="$attributes['required']" :disabled="$attributes['disabled']" :hidden="$attributes['hidden']" />
                        @break

                        @default
                            <x-input :type="$attributes['type']" :name="$name" :label="$attributes['label']" :value="$value"
                                :required="$attributes['required']" :readonly="$attributes['readonly']" :disabled="$attributes['disabled']" :hidden="$attributes['hidden']" :placeholder="$attributes['placeholder'] ?? ''"
                                :datalist="$attributes['datalist'] ?? []" />
                    @endswitch
                @endforeach

                <div class="flex justify-end">
                    <x-button style="light" href="{{ route($route . '.index') }}">
                        {{ __('api::crud.back') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('api::crud.save') }}
                    </x-button>
                </div>
            </x-form>
        </x-section>
    </x-container>
</x-admin-layout>

