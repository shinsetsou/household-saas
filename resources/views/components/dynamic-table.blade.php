<div class="flex justify-end mb-4">
    <label for="itemsPerPage" class="mr-2">Items per page:</label>
    <select id="itemsPerPage" name="itemsPerPage" class="border text-black border-gray-300 rounded px-2 py-1" onchange="updateItemsPerPage(this.value)">
        <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
        <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
        <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
    </select>
</div>

<div class="overflow-x-auto">
    <table class="min-w-full bg-white dark:bg-gray-800">
        <thead class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-200 uppercase text-sm leading-normal">
            <tr>
                @foreach($columns as $key => $displayName)
                    <th class="py-3 px-6 text-left">{{ $displayName }}</th>
                @endforeach
                @if ($showActions ?? false)
                    <th class="py-3 px-6 text-left">Actions</th>
                @endif
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
                        @elseif($key === 'uncredited')
                            <td class="py-3 px-6 text-left">
                                @if ($item->$key)
                                    <span class="text-red-500">Uncredited - Please see with Treasurer/Admin</span>
                                @else
                                    <span class="text-green-500">Credited</span>
                                @endif
                            </td>
                        @else
                            <td class="py-3 px-6 text-left">{{ $item->$key }}</td>
                        @endif
                    @endforeach
                    @if ($showActions ?? false)
                        <td class="py-3 px-6 text-left">
                            @foreach($actions as $action)
                                <form action="{{ route($action['route'], [$organization, $item->reference_number]) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method($action['method'])
                                    <button type="submit" class="text-sm text-secondary px-2 py-1 rounded-md shadow-md {{ $action['button_class'] }}">{{ $action['button_label'] }}</button>
                                </form>
                            @endforeach
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

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
