<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-white leading-tight">
                {{ __('Auction Marketplace') }}
            </h2>
            <p class="text-xs text-slate-400">
                Live lots synced from Ivalua browse + console pages
            </p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
            <form action="{{ route('auctions.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative">
                    <label for="search" class="sr-only">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search lots or lot ID..." 
                        class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 placeholder-slate-500 py-2.5 pl-4">
                </div>

                <div>
                    <label for="platform" class="sr-only">Platform</label>
                    <select name="platform" id="platform" 
                        class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                        <option value="all" {{ request('platform') === 'all' ? 'selected' : '' }}>All Platforms</option>
                        <option value="bstock" {{ request('platform') === 'bstock' ? 'selected' : '' }}>B-Stock</option>
                        <option value="ivalua" {{ request('platform') === 'ivalua' ? 'selected' : '' }}>Ivalua</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="sr-only">Status</label>
                    <select name="status" id="status" 
                        class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Active Only</option>
                        <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Ended Lots</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed Syncs</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <select name="sort" id="sort" 
                        class="flex-1 rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                        <option value="ending_soon" {{ request('sort') === 'ending_soon' ? 'selected' : '' }}>Ending Soon</option>
                        <option value="bid_asc" {{ request('sort') === 'bid_asc' ? 'selected' : '' }}>Current Bid (Low to High)</option>
                        <option value="bid_desc" {{ request('sort') === 'bid_desc' ? 'selected' : '' }}>Current Bid (High to Low)</option>
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Recently Added</option>
                    </select>
                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition shadow-md shadow-blue-900/20">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($lots as $lot)
                @php $event = $lot->auction; @endphp
                <div class="flex flex-col rounded-2xl bg-slate-900 border border-slate-850/80 hover:border-slate-700/80 shadow-xl shadow-slate-950/50 hover:shadow-slate-950 overflow-hidden group transition-all duration-300">
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $event->platform === 'bstock' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' }}">
                                    {{ strtoupper($event->platform) }}
                                </span>
                                
                                @if($lot->status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                        <span class="w-1 h-1 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                                        LIVE
                                    </span>
                                @elseif($lot->status === 'ended')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-400 border border-slate-700">
                                        ENDED
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        {{ strtoupper($lot->status) }}
                                    </span>
                                @endif
                            </div>

                            @if($event->auction_group || $event->external_event_id)
                                <p class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold mb-1">
                                    Event {{ $event->auction_group ?? $event->external_event_id }}
                                </p>
                            @endif

                            <h3 class="text-base font-bold text-white leading-snug group-hover:text-blue-400 transition-colors duration-250">
                                <a href="{{ route('auctions.show', $lot->id) }}">{{ $lot->title }}</a>
                            </h3>
                            <p class="text-[10px] text-slate-500 mt-1 font-mono">{{ $lot->external_lot_id }}</p>
                        </div>

                        <div class="mt-6 border-t border-slate-800/80 pt-6">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Current Bid</span>
                                    <span class="text-lg font-bold text-white">${{ number_format($lot->current_bid, 2) }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Increment</span>
                                    <span class="text-sm font-semibold text-slate-300">+${{ number_format($lot->bid_increment, 2) }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between border-t border-slate-850 pt-4">
                                <div class="flex items-center text-xs text-slate-400">
                                    <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    @if($lot->status === 'active')
                                        <span>Ends: <strong class="text-white">{{ $lot->time_remaining }}</strong></span>
                                    @else
                                        <span class="text-slate-500">Ended</span>
                                    @endif
                                </div>
                                
                                <a href="{{ route('auctions.show', $lot->id) }}" class="inline-flex justify-center items-center px-4 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow transition-colors">
                                    @if($lot->status === 'active')
                                        Bid / Automate
                                    @else
                                        View Details
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 px-6 text-center border border-slate-800 rounded-3xl bg-slate-900/40">
                    <svg class="mx-auto h-12 w-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-white">No lots found</h3>
                    <p class="mt-2 text-xs text-slate-500">Run <code class="text-slate-400">php artisan ivalua:import-auctions --limit=0</code> to import from Ivalua.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $lots->links() }}
        </div>
    </div>
</x-app-layout>
