<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-white leading-tight">
                {{ __('Auctions Manager') }}
            </h2>
            <a href="{{ route('admin.auctions.create') }}" class="inline-flex justify-center items-center px-4 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow transition duration-150">
                Add Auction URL
            </a>
        </div>
    </x-slot>

    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20 overflow-hidden">
        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-800">
                        <thead>
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 pl-4">Platform</th>
                                <th class="py-3.5">Auction Title</th>
                                <th class="py-3.5">Current Bid</th>
                                <th class="py-3.5">Toggles</th>
                                <th class="py-3.5">Status</th>
                                <th class="py-3.5">Last Synced</th>
                                <th class="py-3.5 pr-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/50 text-xs text-slate-300">
                            @forelse($auctions as $auction)
                                <tr>
                                    <td class="py-4 pl-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold {{ $auction->platform === 'bstock' ? 'bg-blue-500/10 text-blue-400' : 'bg-indigo-500/10 text-indigo-400' }}">
                                            {{ strtoupper($auction->platform) }}
                                        </span>
                                    </td>
                                    <td class="py-4 font-bold text-white max-w-sm truncate" title="{{ $auction->title }}">
                                        <a href="{{ route('auctions.show', $auction->id) }}" target="_blank" class="hover:text-blue-400 transition-colors">
                                            {{ $auction->title }}
                                        </a>
                                        <span class="block text-[10px] text-slate-500 font-normal truncate max-w-xs">{{ $auction->external_url }}</span>
                                    </td>
                                    <td class="py-4 font-semibold text-slate-100">
                                        ${{ number_format($auction->current_bid, 2) }}
                                        <span class="block text-[10px] text-slate-500 font-normal">+${{ number_format($auction->bid_increment, 2) }} inc</span>
                                    </td>
                                    <td class="py-4 space-y-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-semibold {{ $auction->is_active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-slate-800 text-slate-500' }}">
                                            {{ $auction->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-semibold {{ $auction->is_featured ? 'bg-yellow-500/15 text-yellow-400' : 'bg-slate-800 text-slate-500' }}">
                                            {{ $auction->is_featured ? 'FEATURED' : 'STANDARD' }}
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        @php
                                            $statusColors = [
                                                'active' => 'text-emerald-400',
                                                'ended' => 'text-slate-500',
                                                'paused' => 'text-amber-400',
                                                'failed' => 'text-rose-400',
                                            ];
                                            $color = $statusColors[$auction->status] ?? 'text-slate-400';
                                        @endphp
                                        <span class="font-bold {{ $color }}">
                                            {{ strtoupper($auction->status) }}
                                        </span>
                                        @if($auction->last_sync_error)
                                            <span class="block text-[9px] text-rose-400/80 max-w-[150px] truncate" title="{{ $auction->last_sync_error }}">
                                                {{ $auction->last_sync_error }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-slate-500">
                                        {{ $auction->last_synced_at ? $auction->last_synced_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="py-4 pr-4">
                                        <div class="flex justify-end items-center space-x-3">
                                            <!-- Manual Sync Button -->
                                            <form action="{{ route('admin.auctions.sync', $auction->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-semibold text-blue-400 hover:text-blue-300">
                                                    Sync Now
                                                </button>
                                            </form>
                                            
                                            <!-- Edit Link -->
                                            <a href="{{ route('admin.auctions.edit', $auction->id) }}" class="text-xs font-semibold text-slate-400 hover:text-white">
                                                Edit
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('admin.auctions.destroy', $auction->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this auction URL? All associated user bids will be cascade deleted.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-rose-400 hover:text-rose-300">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-600">
                                        No auction URLs added yet. Click "Add Auction URL" above to add your first aggregator target.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $auctions->links() }}
        </div>
    </div>
</x-admin-layout>
