<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-white leading-tight">
            {{ __('Active Proxy Bids') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20 overflow-hidden">
            <h3 class="text-base font-bold text-white mb-6">Proxy Bidding Configurations</h3>

            <div class="flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead>
                                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3.5 pl-4">Auction Name</th>
                                    <th class="py-3.5">Max Bid Limit</th>
                                    <th class="py-3.5">Current Auto-Bid</th>
                                    <th class="py-3.5">Status</th>
                                    <th class="py-3.5">Configured At</th>
                                    <th class="py-3.5 pr-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850/50 text-sm text-slate-300">
                                @forelse($proxies as $proxy)
                                    <tr>
                                        <td class="py-4 pl-4 font-semibold text-white">
                                            @if($proxy->auction)
                                                <a href="{{ route('auctions.show', $proxy->auction_id) }}" class="hover:text-blue-400 transition-colors">
                                                    {{ $proxy->auction->title }}
                                                </a>
                                            @else
                                                <span class="text-slate-500">Deleted Auction</span>
                                            @endif
                                        </td>
                                        <td class="py-4 font-bold text-white">
                                            ${{ number_format($proxy->max_amount, 2) }}
                                        </td>
                                        <td class="py-4 font-semibold text-slate-200">
                                            ${{ number_format($proxy->current_auto_bid, 2) }}
                                        </td>
                                        <td class="py-4">
                                            @php
                                                $statusColors = [
                                                    'active' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                    'paused' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                    'stopped' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                    'completed' => 'bg-slate-800 text-slate-400 border-slate-700',
                                                    'cancelled' => 'bg-slate-800 text-slate-400 border-slate-700',
                                                ];
                                                $color = $statusColors[$proxy->status] ?? 'bg-slate-800 text-slate-400';
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $color }}">
                                                {{ strtoupper($proxy->status) }}
                                            </span>
                                            @if($proxy->stop_reason)
                                                <span class="block text-[10px] text-slate-500 mt-1 max-w-xs truncate" title="{{ $proxy->stop_reason }}">
                                                    {{ $proxy->stop_reason }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 text-xs text-slate-500">
                                            {{ $proxy->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="py-4 pr-4 text-right">
                                            <div class="flex justify-end items-center space-x-3">
                                                @if($proxy->status === 'active' && $proxy->auction)
                                                    <form action="{{ route('auctions.proxy.cancel', $proxy->auction_id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-xs font-semibold text-rose-400 hover:text-rose-300">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($proxy->auction)
                                                    <a href="{{ route('auctions.show', $proxy->auction_id) }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300">
                                                        View Details
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-500">
                                            No proxy bids configured yet. Go to <a href="{{ route('auctions.index') }}" class="text-blue-400 hover:underline">Marketplace</a> to enable automated bidding on Ivalua.
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
                {{ $proxies->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
