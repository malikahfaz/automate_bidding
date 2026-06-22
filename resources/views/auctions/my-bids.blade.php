<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-white leading-tight">
            {{ __('My Bids History') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20 overflow-hidden">
            <h3 class="text-base font-bold text-white mb-6">Bid Execution Log</h3>

            <div class="flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead>
                                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3.5 pl-4">Auction Name</th>
                                    <th class="py-3.5">Platform</th>
                                    <th class="py-3.5">Bid Amount</th>
                                    <th class="py-3.5">Status</th>
                                    <th class="py-3.5">Placed At</th>
                                    <th class="py-3.5 pr-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850/50 text-sm text-slate-300">
                                @forelse($bids as $bid)
                                    <tr>
                                        <td class="py-4 pl-4 font-semibold text-white">
                                            @if($bid->lot)
                                                <a href="{{ route('auctions.show', $bid->auction_lot_id) }}" class="hover:text-blue-400 transition-colors">
                                                    {{ $bid->lot->title }}
                                                </a>
                                            @elseif($bid->auction)
                                                <span class="text-slate-400">{{ $bid->auction->title }}</span>
                                            @else
                                                <span class="text-slate-500">Deleted Lot</span>
                                            @endif
                                        </td>
                                        <td class="py-4">
                                            @if($bid->lot?->auction ?? $bid->auction)
                                                @php $platform = $bid->lot?->auction?->platform ?? $bid->auction?->platform; @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $platform === 'bstock' ? 'bg-blue-500/10 text-blue-400' : 'bg-indigo-500/10 text-indigo-400' }}">
                                                    {{ strtoupper($platform) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-4 font-bold text-white">
                                            ${{ number_format($bid->amount, 2) }}
                                        </td>
                                        <td class="py-4">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                    'processing' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                                    'successful' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                    'failed' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                    'outbid' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                                                    'max_reached' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                                    'cancelled' => 'bg-slate-800 text-slate-400 border-slate-700',
                                                ];
                                                $color = $statusColors[$bid->status] ?? 'bg-slate-800 text-slate-400';
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $color }}">
                                                {{ strtoupper($bid->status) }}
                                            </span>
                                            @if($bid->status === 'failed')
                                                <span class="block text-[10px] text-rose-400/80 mt-1 max-w-xs truncate" title="{{ $bid->failure_reason }}">
                                                    {{ $bid->failure_reason }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 text-xs text-slate-500">
                                            {{ $bid->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="py-4 pr-4 text-right">
                                            @if($bid->auction_lot_id)
                                                <a href="{{ route('auctions.show', $bid->auction_lot_id) }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300">
                                                    View Live
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-500">
                                            No bids submitted yet. Visit the <a href="{{ route('auctions.index') }}" class="text-blue-400 hover:underline">Marketplace</a> to place your first bid!
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
                {{ $bids->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
