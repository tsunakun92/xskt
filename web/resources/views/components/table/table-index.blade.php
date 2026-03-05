<div class="relative overflow-x-auto">
    {{-- Table Header --}}
    @include('components.table.table-title', ['route' => $route])

    {{-- Table --}}
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                @foreach ($headers as $header)
                    <th scope="col"
                        class="px-6 py-3 {{ $loop->first ? 'rounded-s-lg' : '' }} {{ $header === 'action' ? 'w-32' : '' }} {{ $loop->last ? 'rounded-e-lg' : '' }}">
                        @if ($header === 'action')
                            @lang("admin::crud.$header")
                            @continue
                        @endif
                        @lang("admin::crud.$route.$header")
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr class="bg-white dark:bg-gray-800">
                    @foreach ($headers as $header)
                        @switch ($header)
                            @case('action')
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-end space-x-2">
                                        @include('components.table.table-actions', [
                                            'row' => $row,
                                            'route' => $route,
                                        ])
                                    </div>
                                </td>
                            @break

                            @case('status')
                                <td class="px-6 py-4">
                                    <x-badge style="{{ $row->status ? 'green' : 'red' }}">{{ $row->getStatusName() }}</x-badge>
                                </td>
                            @break

                            @default
                                <td class="px-6 py-4">
                                    {{ $row[$header] }}
                                </td>
                        @endswitch
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $rows->links() }}
    </div>
</div>
