<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <a href="{{ route('admin.auctions.index') }}" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-300 mb-2">
                    ← Back to events
                </a>
                <div class="flex items-center gap-3 flex-wrap">
                    @if($auction->auction_group)
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-sm font-bold text-indigo-300">
                            {{ $auction->auction_group }}
                        </span>
                    @endif
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ $auction->title }}</h2>
                </div>
                <p class="mt-1 text-sm text-slate-400">
                    Event #{{ $auction->external_event_id ?? $auction->id }}
                    · {{ $auction->lots_count }} lots
                    · {{ strtoupper($auction->platform) }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('admin.auctions.sync', $auction->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-xl transition-colors">
                        Sync All Lots
                    </button>
                </form>
                @if($topLot)
                    <a href="{{ route('auctions.show', $topLot->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-lg shadow-emerald-900/30 transition-colors">
                        Bid on Top Lot
                    </a>
                @endif
                <a href="{{ $auction->external_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl transition-colors">
                    Open Ivalua
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Event summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/80 p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Highest Bid</p>
            <p class="mt-2 text-3xl font-bold text-emerald-400">${{ number_format($highestBid, 2) }}</p>
            @if($topLot)
                <p class="mt-1 text-xs text-slate-500 font-mono">{{ $topLot->external_lot_id }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/80 p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Lots</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ $auction->lots_count }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/80 p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</p>
            <p class="mt-2 text-xl font-bold {{ $auction->status === 'active' ? 'text-emerald-400' : 'text-slate-400' }}">
                {{ ucfirst($auction->status) }}
            </p>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/80 p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Last Synced</p>
            <p class="mt-2 text-sm font-semibold text-slate-300">
                {{ $auction->last_synced_at ? $auction->last_synced_at->diffForHumans() : 'Never' }}
            </p>
        </div>
    </div>

    {{-- Lots table --}}
    <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 overflow-hidden shadow-xl shadow-black/20">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-white">All Lots in This Event</h3>
            <span class="text-xs text-slate-500">Sorted by highest bid first</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/90">
                        <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Lot ID</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Title</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Current Bid</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Increment</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($auction->lots as $lot)
                        <tr class="hover:bg-slate-800/30 transition-colors {{ $loop->first && $highestBid > 0 ? 'bg-emerald-500/5' : '' }}">
                            <td class="px-6 py-3.5">
                                <span class="font-mono text-sm font-semibold text-indigo-300">{{ $lot->external_lot_id }}</span>
                                @if($loop->first && $highestBid > 0)
                                    <span class="ml-2 inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-500/15 text-emerald-400">Top</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 max-w-xs">
                                <p class="text-sm text-slate-200 truncate" title="{{ $lot->title }}">{{ $lot->title }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ $lot->time_remaining ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <span class="text-base font-bold {{ $lot->current_bid > 0 ? 'text-white' : 'text-slate-600' }}">
                                    ${{ number_format($lot->current_bid, 2) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-sm text-slate-400">
                                +${{ number_format($lot->bid_increment, 2) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-semibold {{ $lot->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-500' }}">
                                    {{ ucfirst($lot->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('auctions.show', $lot->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    @if($lot->status === 'active' && $lot->is_active)
                                        <a href="{{ route('auctions.show', $lot->id) }}#bid-form"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Bid
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 text-sm">
                                No lots imported for this event yet. Run import or sync.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
