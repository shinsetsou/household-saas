{{--
    Reusable dynamic table component.
    Props: $columns (assoc array key=>label), $items (paginator), $itemsPerPage, $showActions, $actions, $organization
--}}

{{-- Items per page control --}}
<div class="flex justify-between items-center px-5 py-3 border-b border-gray-100 bg-gray-50">
    <span class="text-xs text-gray-500">{{ $items->total() }} record{{ $items->total() !== 1 ? 's' : '' }}</span>
    <div class="flex items-center gap-2">
        <label for="itemsPerPage" class="text-xs font-medium text-gray-500">Rows:</label>
        <select id="itemsPerPage" name="itemsPerPage" onchange="updateItemsPerPage(this.value)"
                class="text-xs text-gray-700 bg-white border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
        </select>
    </div>
</div>

{{-- Table --}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                @foreach($columns as $key => $displayName)
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ $displayName }}
                    </th>
                @endforeach
                @if ($showActions ?? false)
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($items as $item)
                <tr class="hover:bg-gray-50 transition-colors">
                    @foreach($columns as $key => $displayName)
                        @if($key === 'photo')
                            <td class="px-5 py-3.5 text-sm">
                                @if($item->$key)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($item->$key) }}" target="_blank"
                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg overflow-hidden border border-gray-200 hover:ring-2 hover:ring-indigo-500 transition">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($item->$key) }}" alt="Photo" class="w-full h-full object-cover">
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                        @elseif($key === 'created_at')
                            <td class="px-5 py-3.5 text-sm text-gray-600">
                                {{ $item->$key->format('M d, Y') }}
                            </td>

                        @elseif($key === 'status')
                            <td class="px-5 py-3.5 text-sm">
                                @if($item->$key === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Paid</span>
                                @elseif($item->$key === 'lacking')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Lacking</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ ucfirst($item->$key) }}</span>
                                @endif
                            </td>

                        @elseif($key === 'admin_verified')
                            <td class="px-5 py-3.5 text-sm">
                                @if($item->$key == 1)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Verified
                                    </span>
                                @elseif($item->$key == 3)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Disproved
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        Pending
                                    </span>
                                @endif
                            </td>

                        @elseif($key === 'uncredited')
                            <td class="px-5 py-3.5 text-sm">
                                @if ($item->$key)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Uncredited</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Credited</span>
                                @endif
                            </td>

                        @elseif($key === 'amount' || $key === 'total_amount')
                            <td class="px-5 py-3.5 text-sm font-semibold text-gray-900">
                                &#8369;{{ number_format($item->$key, 2) }}
                            </td>

                        @else
                            <td class="px-5 py-3.5 text-sm text-gray-700">{{ $item->$key }}</td>
                        @endif
                    @endforeach

                    @if ($showActions ?? false)
                        <td class="px-5 py-3.5 text-sm text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                @foreach($actions as $action)
                                    <form action="{{ route($action['route'], [$organization, $item->reference_number]) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method($action['method'])
                                        @if(isset($action['additional_params']))
                                            @foreach($action['additional_params'] as $params)
                                                @foreach($params as $paramKey => $paramValue)
                                                    <input type="hidden" name="{{ $paramKey }}" value="{{ $paramValue }}">
                                                @endforeach
                                            @endforeach
                                        @endif
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors border {{ $action['button_class'] }}">
                                            {{ $action['button_label'] }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($showActions ?? false ? 1 : 0) }}"
                        class="px-5 py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2zm0-4h5V8h-5v2zM9 8H4v2h5V8z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-500">No records found</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($items->hasPages())
<div class="px-5 py-4 border-t border-gray-100">
    {{ $items->appends(['itemsPerPage' => $itemsPerPage])->links() }}
</div>
@endif

<script>
    function updateItemsPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('itemsPerPage', value);
        window.location.href = url.toString();
    }
</script>
