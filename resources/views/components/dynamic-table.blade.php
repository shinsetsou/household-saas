<div>
    <div class="flex justify-end mb-4">
        <label for="itemsPerPage" class="mr-2">Items per page:</label>
        <select id="itemsPerPage" name="itemsPerPage" class="border text-black border-gray-300 rounded px-2 py-1" onchange="updateItemsPerPage(this.value)">
            <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
        </select>
    </div>

    <table class="min-w-full bg-white dark:bg-gray-800">
        <thead class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-200 uppercase text-sm leading-normal">
            <tr>
                @foreach($columns as $key => $displayName)
                    <th class="py-3 px-6 text-left">{{ $displayName }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="text-gray-600 dark:text-gray-300 text-sm font-light">
            @foreach($items as $item)
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-900">
                    @foreach($columns as $key => $displayName)
                        @if($key === 'photo')
                            <td class="py-3 px-6 text-left">
                                <a href="{{ asset('storage/' . $item->$key) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $item->$key) }}" alt="" class="h-10 w-10 rounded-full">
                                </a>
                            </td>
                        @elseif($key === 'created_at')
                            <td class="py-3 px-6 text-left">{{ $item->$key->format('m-d-Y') }}</td>
                        @else
                            <td class="py-3 px-6 text-left">{{ $item->$key }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $items->appends(['itemsPerPage' => $itemsPerPage])->links() }}
    </div>
</div>

<script>
    function updateItemsPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('itemsPerPage', value);
        window.location.href = url.toString();
    }
</script>
