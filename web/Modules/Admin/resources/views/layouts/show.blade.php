<x-admin-layout>
    <x-container>
        <x-section>
            <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-200">
                {{ __('admin::crud.show') }} @lang('admin::crud.' . $route . '.title')
            </h2>

            <div class="space-y-4">
                @foreach ($fields as $name => $attributes)
                    @php
                        $value = $model->$name;
                    @endphp

                    @if ($name === 'password')
                        @continue
                    @endif

                    @if (!$attributes['hidden'])
                        {{-- Check if this is a file upload field --}}
                        @if (isset($attributes['type']) && in_array($attributes['type'], ['file-upload-image', 'file-upload-document']))
                            @php
                                // Get existing files from the field configuration or model relationship
                                $existingFiles = $attributes['existing'] ?? [];

                                // If no existing files in attributes, try to get from model relationship
                                if (empty($existingFiles) && method_exists($model, 'rFiles')) {
                                    $existingFiles = \App\Utils\FileMasterHelper::buildFilePondFilesForEdit(
                                        $model->rFiles()->get(),
                                    );
                                }

                                // Determine upload type
                                $uploadType =
                                    $attributes['type'] === 'file-upload-image'
                                        ? 'image'
                                        : ($attributes['type'] === 'file-upload-document'
                                            ? 'document'
                                            : 'auto');
                            @endphp

                            @if (!empty($existingFiles))
                                <div class="border-b border-gray-200 pb-4 dark:border-gray-700">
                                    <x-form.file-display :files="$existingFiles" :label="$attributes['label'] ?? ''" :uploadType="$uploadType" />
                                </div>
                            @endif
                        @elseif (is_string($value) && !empty($value) && is_json_string($value))
                            {{-- Display JSON fields with pretty formatting --}}
                            @include('components.json-display', [
                                'value' => $value,
                                'label' => $attributes['label'],
                            ])
                        @else
                            <div class="border-b border-gray-200 pb-4 dark:border-gray-700">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ $attributes['label'] }}
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if (!empty($attributes['options']) && isset($attributes['options'][$value]))
                                        {{ $attributes['options'][$value] }}
                                    @else
                                        {{ $value ?? '' }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>

            <div class="flex justify-end mt-6">
                <x-button style="light" href="{{ route($route . '.index') }}">
                    {{ __('admin::crud.back') }}
                </x-button>
                @canAccess($route . '.edit')
                <x-button href="{{ route($route . '.edit', $model->id) }}">
                    {{ __('admin::crud.edit') }}
                </x-button>
                @endcanAccess
            </div>
        </x-section>
    </x-container>
</x-admin-layout>
