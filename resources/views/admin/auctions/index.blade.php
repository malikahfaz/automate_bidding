<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-white tracking-tight">Auction Events</h2>
                <p class="mt-1 text-sm text-slate-400">Ivalua browse consoles synced into your database</p>
            </div>
            <a href="{{ route('admin.auctions.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg shadow-blue-900/30 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Event
            </a>
        </div>
    </x-slot>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <div class="rounded-2xl border border-slate-800/80 bg-gradient-to-br from-slate-900 to-slate-900/50 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Events</p>
            <p class="mt-1 text-2xl font-bold text-white">{{ number_format($stats['total_events']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-gradient-to-br from-slate-900 to-slate-900/50 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Lots</p>
            <p class="mt-1 text-2xl font-bold text-blue-400">{{ number_format($stats['total_lots']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-gradient-to-br from-slate-900 to-slate-900/50 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Active</p>
            <p class="mt-1 text-2xl font-bold text-emerald-400">{{ number_format($stats['active_events']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-gradient-to-br from-slate-900 to-slate-900/50 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Ivalua</p>
            <p class="mt-1 text-2xl font-bold text-indigo-400">{{ number_format($stats['ivalua_events']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-gradient-to-br from-slate-900 to-slate-900/50 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Featured</p>
            <p class="mt-1 text-2xl font-bold text-amber-400">{{ number_format($stats['featured']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-gradient-to-br from-slate-900 to-slate-900/50 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Sync Errors</p>
            <p class="mt-1 text-2xl font-bold {{ $stats['sync_errors'] > 0 ? 'text-rose-400' : 'text-slate-500' }}">{{ number_format($stats['sync_errors']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-2xl border border-slate-800/80 bg-slate-900/80 p-4">
        <form action="{{ route('admin.auctions.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title, group, or event ID..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-slate-200 placeholder-slate-500 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <select name="platform" class="md:w-40 py-2.5 px-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-slate-300 focus:border-blue-500 focus:ring-blue-500">
                <option value="all" @selected(request('platform', 'all') === 'all')>All Platforms</option>
                <option value="ivalua" @selected(request('platform') === 'ivalua')>Ivalua</option>
                <option value="bstock" @selected(request('platform') === 'bstock')>B-Stock</option>
            </select>
            <select name="status" class="md:w-36 py-2.5 px-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-slate-300 focus:border-blue-500 focus:ring-blue-500">
                <option value="all" @selected(request('status', 'all') === 'all')>All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="paused" @selected(request('status') === 'paused')>Paused</option>
                <option value="ended" @selected(request('status') === 'ended')>Ended</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-white transition-colors">
                Apply
            </button>
            @if(request()->hasAny(['search', 'platform', 'status']))
                <a href="{{ route('admin.auctions.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white transition-colors text-center">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 overflow-hidden shadow-xl shadow-black/20">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/90">
                        <th class="px-6 py-4 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Event</th>
                        <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Platform</th>
                        <th class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">Lots</th>
                        <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Highest Bid</th>
                        <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Last Sync</th>
                        <th class="px-6 py-4 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($auctions as $auction)
                        <tr class="group hover:bg-slate-800/30 transition-colors">
                            {{-- Event --}}
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3 min-w-[220px]">
                                    @if($auction->auction_group)
                                        <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-xs font-bold text-indigo-300">
                                            {{ $auction->auction_group }}
                                        </span>
                                    @else
                                        <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-xl bg-slate-800 border border-slate-700 text-xs font-bold text-slate-500">
                                            —
                                        </span>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-white text-sm leading-snug truncate" title="{{ $auction->title }}">
                                            {{ $auction->title }}
                                        </p>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            @if($auction->external_event_id)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-800 border border-slate-700 text-[10px] font-mono font-medium text-slate-300">
                                                    #{{ $auction->external_event_id }}
                                                </span>
                                            @endif
                                            @if($auction->is_featured)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/10 border border-amber-500/20 text-[10px] font-semibold text-amber-400">
                                                    ★ Featured
                                                </span>
                                            @endif
                                        </div>
                                        <a href="{{ $auction->external_url }}" target="_blank" rel="noopener"
                                           class="mt-2 inline-flex items-center gap-1 text-[11px] text-slate-500 hover:text-blue-400 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            Open on Ivalua
                                        </a>
                                    </div>
                                </div>
                            </td>

                            {{-- Platform --}}
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $auction->platform === 'bstock' ? 'bg-sky-500/10 text-sky-400 ring-1 ring-sky-500/20' : 'bg-violet-500/10 text-violet-400 ring-1 ring-violet-500/20' }}">
                                    {{ strtoupper($auction->platform) }}
                                </span>
                            </td>

                            {{-- Lots --}}
                            <td class="px-4 py-4 text-center">
                                <span class="text-lg font-bold text-white">{{ $auction->lots_count ?? 0 }}</span>
                                <span class="block text-[10px] text-slate-500 uppercase tracking-wide">in DB</span>
                            </td>

                            {{-- Highest bid --}}
                            <td class="px-4 py-4 text-right">
                                @if(($auction->highest_bid ?? 0) > 0)
                                    <span class="text-lg font-bold text-emerald-400">${{ number_format($auction->highest_bid, 2) }}</span>
                                    @if(isset($topLots[$auction->id]))
                                        <span class="block text-[10px] text-slate-500 font-mono">{{ $topLots[$auction->id]->external_lot_id }}</span>
                                    @endif
                                @else
                                    <span class="text-sm text-slate-600">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-4">
                                @php
                                    $statusStyles = [
                                        'active' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
                                        'ended' => 'bg-slate-700/50 text-slate-400 ring-slate-600/30',
                                        'paused' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
                                        'failed' => 'bg-rose-500/10 text-rose-400 ring-rose-500/20',
                                    ];
                                    $statusStyle = $statusStyles[$auction->status] ?? 'bg-slate-700/50 text-slate-400 ring-slate-600/30';
                                @endphp
                                <div class="space-y-1.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold ring-1 {{ $statusStyle }}">
                                        @if($auction->status === 'active' && $auction->is_active)
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        @endif
                                        {{ ucfirst($auction->status) }}
                                    </span>
                                    @if(!$auction->is_active)
                                        <span class="block text-[10px] text-slate-500">Disabled in admin</span>
                                    @endif
                                    @if($auction->last_sync_error)
                                        <p class="text-[10px] text-rose-400/90 max-w-[140px] truncate" title="{{ $auction->last_sync_error }}">
                                            {{ $auction->last_sync_error }}
                                        </p>
                                    @endif
                                </div>
                            </td>

                            {{-- Last sync --}}
                            <td class="px-4 py-4">
                                @if($auction->last_synced_at)
                                    <p class="text-sm font-medium text-slate-300">{{ $auction->last_synced_at->diffForHumans() }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $auction->last_synced_at->format('M j, g:i A') }}</p>
                                @else
                                    <span class="text-sm text-slate-600">Never synced</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                    <a href="{{ route('admin.auctions.show', $auction->id) }}"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    @if(isset($topLots[$auction->id]))
                                        <a href="{{ route('auctions.show', $topLots[$auction->id]->id) }}#bid-form"
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Bid
                                        </a>
                                    @endif
                                    <form action="{{ route('admin.auctions.sync', $auction->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Sync lots"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/20 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.auctions.edit', $auction->id) }}" title="Edit"
                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 border border-slate-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.auctions.destroy', $auction->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this event and all related lots?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto w-14 h-14 rounded-2xl bg-slate-800 flex items-center justify-center mb-4">
                                    <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-300">No auction events found</p>
                                <p class="mt-1 text-xs text-slate-500">Run <code class="text-slate-400">php artisan automation:stack</code> or add an event manually.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($auctions->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/50">
                {{ $auctions->links() }}
            </div>
        @endif
    </div>

    <p class="mt-4 text-xs text-slate-600 text-right">
        Showing {{ $auctions->firstItem() ?? 0 }}–{{ $auctions->lastItem() ?? 0 }} of {{ $auctions->total() }} events
    </p>
</x-admin-layout>
