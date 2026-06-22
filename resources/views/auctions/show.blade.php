<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $lot->auction->platform === 'bstock' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' }}">
                        {{ strtoupper($lot->auction->platform) }}
                    </span>
                    @if($lot->auction->auction_group || $lot->auction->external_event_id)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                            Event {{ $lot->auction->auction_group ?? $lot->auction->external_event_id }}
                        </span>
                    @endif
                    <span id="badge-live-status" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                        LIVE
                    </span>
                </div>
                <h2 class="font-extrabold text-2xl text-white leading-tight">
                    @if($lot->title && $lot->title !== $lot->external_lot_id)
                        {{ $lot->title }}
                    @else
                        {{ $lot->external_lot_id }}
                    @endif
                </h2>
                <p class="text-xs text-slate-500 font-mono mt-1">{{ $lot->external_lot_id }}</p>
                @if($lot->description)
                    <p class="text-sm text-slate-400 mt-2 leading-snug">{{ $lot->description }}</p>
                @endif
            </div>
            <a href="{{ route('auctions.index') }}" class="inline-flex justify-center items-center px-4 py-2 border border-slate-800 rounded-xl text-sm font-semibold text-slate-300 bg-slate-900 hover:text-white hover:bg-slate-850/80 transition-colors">
                Back to Marketplace
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left 2 Cols: Details and Bid Placement Form -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Target lot on Ivalua console (multiple lots share one console page) -->
                <div class="p-5 sm:p-6 rounded-2xl bg-indigo-500/5 border-2 border-indigo-500/25 shadow-xl shadow-slate-950/20">
                    <p class="text-[10px] uppercase tracking-wider font-bold text-indigo-400 mb-2">You are bidding on this Ivalua lot</p>
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                        <div>
                            <p class="text-3xl font-extrabold text-white font-mono tracking-tight">{{ $lot->external_lot_id }}</p>
                            @if($lot->title && $lot->title !== $lot->external_lot_id)
                                <p class="text-lg font-semibold text-slate-200 mt-2">{{ $lot->title }}</p>
                            @endif
                            @if($lot->description)
                                <p class="text-sm text-slate-400 mt-2">{{ $lot->description }}</p>
                            @endif
                            @if($consoleId)
                                <p class="text-sm text-slate-400 mt-1">
                                    Console event <span class="font-mono text-indigo-300">#{{ $consoleId }}</span>
                                    · {{ $consoleLots->count() }} lots on same page
                                </p>
                            @endif
                        </div>
                        <a href="{{ $lot->auction->external_url }}" target="_blank" rel="noopener"
                           class="shrink-0 inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-indigo-300 bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20">
                            Open on Ivalua
                            <svg class="w-3.5 h-3.5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-500 leading-relaxed">
                        Ivalua shows many lots on one console URL. Your bid is sent only to lot
                        <strong class="text-slate-300">{{ $lot->external_lot_id }}</strong> — automation finds that exact row and clicks its <strong class="text-slate-300">Bid</strong> link.
                    </p>
                </div>

                <!-- Auction Core info -->
                <div class="p-6 sm:p-8 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center sm:text-left">
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-850/65">
                            <span class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Current Bid</span>
                            <span id="txt-current-bid" class="text-3xl font-extrabold text-white">${{ number_format($lot->current_bid, 2) }}</span>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-850/65">
                            <span class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Bid Increment</span>
                            <span id="txt-bid-increment" class="text-2xl font-bold text-slate-200">+${{ number_format($lot->bid_increment, 2) }}</span>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-850/65">
                            <span class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Time Remaining</span>
                            <span id="txt-time-remaining" class="text-2xl font-bold text-blue-400">{{ $lot->time_remaining }}</span>
                            <span id="meta-ends-at" class="hidden">{{ $lot->ends_at ? $lot->ends_at->toISOString() : '' }}</span>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-slate-850 pt-6">
                        <h4 class="text-sm font-semibold text-slate-300 mb-2">External Auction URL</h4>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-850">
                            <span class="text-xs text-slate-400 truncate pr-4">{{ $lot->auction->external_url }}</span>
                            <a href="{{ $lot->auction->external_url }}" target="_blank" class="shrink-0 text-xs font-semibold text-blue-400 hover:text-blue-300 inline-flex items-center">
                                View Original
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bid Placement Forms -->
                @auth
                    @if(session('success'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div id="bid-execution-status" class="hidden mb-6 p-4 rounded-xl border text-sm"></div>

                    <div id="bid-form" class="p-6 sm:p-8 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
                        <h3 class="text-lg font-bold text-white mb-2">Place Your Bid</h3>
                        <p class="text-xs text-slate-500 mb-6">Bid here — system automatically places it on Ivalua using the master account.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Direct/Normal Bid Form -->
                            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-850/80">
                                <h4 class="text-sm font-bold text-white mb-2">Bid on Ivalua (via this site)</h4>
                                <p class="text-slate-400 text-xs mb-4">Amount is sent to Ivalua automatically — no need to open their website.</p>
                                
                                <form action="{{ route('auctions.bid', $lot->id) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="external_lot_id" value="{{ $lot->external_lot_id }}">
                                    <div>
                                        <label for="amount" class="block text-xs font-medium text-slate-400 mb-2">Bid Amount ($)</label>
                                        <div class="relative">
                                            <input type="number" step="0.01" name="amount" id="amount" 
                                                min="{{ $lot->current_bid + $lot->bid_increment }}" 
                                                value="{{ old('amount', $lot->current_bid + $lot->bid_increment) }}" 
                                                class="w-full rounded-xl bg-slate-900 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
                                        </div>
                                        <p class="mt-2 text-[10px] text-slate-500">
                                            Minimum required bid: <strong id="lbl-min-bid">${{ number_format($lot->current_bid + $lot->bid_increment, 2) }}</strong>
                                        </p>
                                    </div>
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow transition duration-150">
                                        Place Bid on {{ $lot->external_lot_id }}
                                    </button>
                                </form>
                            </div>

                            {{-- Custom Proxy Bid Form (Only for Ivalua) — disabled
                            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-850/80">
                                <h4 class="text-sm font-bold text-white mb-2">Automated Proxy Bidding</h4>
                                @if($lot->auction->platform === 'ivalua')
                                    <p class="text-slate-400 text-xs mb-4">Centralized engine will auto-bid in increments up to your maximum.</p>
                                    
                                    @if($lot->activeProxyBid)
                                        <div class="p-4 rounded-xl bg-blue-500/5 border border-blue-500/10 mb-4">
                                            <span class="block text-[10px] uppercase font-bold text-blue-400 mb-1">Active Config</span>
                                            <div class="flex justify-between text-xs text-slate-300">
                                                <span>Max Limit:</span>
                                                <strong id="val-proxy-max">${{ number_format($lot->activeProxyBid->max_amount, 2) }}</strong>
                                            </div>
                                            <div class="flex justify-between text-xs text-slate-300 mt-1">
                                                <span>Last Auto-Bid:</span>
                                                <strong id="val-proxy-current">${{ number_format($lot->activeProxyBid->current_auto_bid, 2) }}</strong>
                                            </div>
                                        </div>
                                        <form action="{{ route('auctions.proxy.cancel', $lot->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 text-sm font-semibold text-rose-400 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/20 rounded-xl transition duration-150">
                                                Cancel Proxy Bidding
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('auctions.proxy', $lot->id) }}" method="POST" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label for="max_amount" class="block text-xs font-medium text-slate-400 mb-2">Maximum Bid Limit ($)</label>
                                                <input type="number" step="0.01" name="max_amount" id="max_amount" 
                                                    min="{{ $lot->current_bid + $lot->bid_increment }}" 
                                                    value="{{ old('max_amount', $lot->current_bid + $lot->bid_increment * 5) }}" 
                                                    class="w-full rounded-xl bg-slate-900 border-slate-800 focus:border-indigo-500 focus:ring-indigo-500 text-sm text-slate-200 py-3 pl-4">
                                            </div>
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow transition duration-150">
                                                Enable Auto-Proxy
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <!-- For B-Stock -->
                                    <div class="h-full flex flex-col justify-center py-6 text-center">
                                        <svg class="mx-auto h-8 w-8 text-slate-700 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <h5 class="text-xs font-semibold text-slate-400">Locked for B-Stock</h5>
                                        <p class="mt-1 text-[10px] text-slate-500 leading-normal max-w-xs mx-auto">
                                            Submit proxy values directly using standard bids. B-Stock handles proxying natively on their servers.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            --}}
                        </div>
                    </div>
                @else
                    <div class="p-6 sm:p-8 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20 text-center">
                        <h4 class="text-base font-semibold text-white">Login required to place bids</h4>
                        <p class="mt-2 text-xs text-slate-400">Please sign in to your account to place a bid or setup automated proxy bidding.</p>
                        <div class="mt-6 flex items-center justify-center gap-4">
                            <a href="{{ route('login') }}" class="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-md">Log in</a>
                            <a href="{{ route('register') }}" class="text-xs font-semibold text-slate-300 hover:text-white">Register</a>
                        </div>
                    </div>
                @endauth
            </div>

            <!-- Right 1 Col: Warning Cards & Realtime History -->
            <div class="space-y-8">
                @if(isset($consoleLots) && $consoleLots->count() > 1)
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
                        <h3 class="text-sm font-bold text-white mb-1">Other lots on console @if($consoleId)#{{ $consoleId }}@endif</h3>
                        <p class="text-[10px] text-slate-500 mb-4">Same Ivalua page — each lot has its own bid page here.</p>
                        <div class="max-h-[22rem] overflow-y-auto overflow-x-hidden scrollbar-dark pr-1.5 -mr-0.5">
                            <ul class="space-y-2 text-xs">
                            @foreach($consoleLots as $sibling)
                                <li>
                                    <a href="{{ route('auctions.show', $sibling->id) }}"
                                       class="block px-3 py-2.5 rounded-lg border transition-colors {{ $sibling->id === $lot->id ? 'bg-indigo-500/15 border-indigo-500/30' : 'border-slate-850 hover:bg-slate-850 hover:border-slate-700' }}">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="font-mono text-[11px] shrink-0 {{ $sibling->id === $lot->id ? 'text-indigo-300 font-bold' : 'text-slate-500' }}">{{ $sibling->external_lot_id }}</span>
                                            <span class="font-semibold text-white shrink-0">${{ number_format($sibling->current_bid, 0) }}</span>
                                        </div>
                                        @if($sibling->title && $sibling->title !== $sibling->external_lot_id)
                                            <p class="mt-1 text-[11px] font-medium text-slate-200 leading-snug line-clamp-2">{{ $sibling->title }}</p>
                                        @endif
                                        @if($sibling->description)
                                            <p class="mt-0.5 text-[10px] text-slate-500 leading-snug line-clamp-2">{{ $sibling->description }}</p>
                                        @elseif($sibling->cosmetic_grade || $sibling->quantity)
                                            <p class="mt-0.5 text-[10px] text-slate-500">
                                                @if($sibling->cosmetic_grade) Grade {{ $sibling->cosmetic_grade }} @endif
                                                @if($sibling->quantity) · Qty {{ $sibling->quantity }} @endif
                                            </p>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Bid execution Warning -->
                <div class="p-6 rounded-2xl bg-amber-500/5 border border-amber-500/10 shadow-xl shadow-slate-950/20">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                        <div>
                            <h4 class="text-sm font-bold text-amber-400">Centralized Bid Execution</h4>
                            <p class="mt-2 text-[11px] text-amber-500/80 leading-normal">
                                Important: Bids placed on this portal will be compiled and executed on the remote platform using our master automation account. Make sure you have checked all lots prior to bidding.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bid History Table -->
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
                    <h3 class="text-sm font-bold text-white mb-4">Sync History (Last 10 Bids)</h3>
                    
                    <div class="flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <table class="min-w-full divide-y divide-slate-800">
                                    <thead>
                                        <tr class="text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            <th class="py-2 pl-4">Bid Value</th>
                                            <th class="py-2">Source</th>
                                            <th class="py-2 pr-4 text-right">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl-bid-history" class="divide-y divide-slate-850/50 text-xs">
                                        @forelse($lot->bidHistories->take(10) as $hist)
                                            <tr class="text-slate-300">
                                                <td class="py-2.5 pl-4 font-bold text-white">${{ number_format($hist->amount, 2) }}</td>
                                                <td class="py-2.5">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $hist->source === 'user' ? 'bg-blue-500/10 text-blue-400' : ($hist->source === 'system' ? 'bg-indigo-500/10 text-indigo-400' : 'bg-slate-800 text-slate-400') }}">
                                                        {{ ucfirst($hist->source) }}
                                                    </span>
                                                </td>
                                                <td class="py-2.5 pr-4 text-right text-[10px] text-slate-500">{{ $hist->created_at->diffForHumans() }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="py-6 text-center text-xs text-slate-600">No bids synced yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Polling & Countdown Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pollingUrl = "{{ route('auctions.polling', $lot->id) }}";
            
            // 1. AJAX Polling for real-time bid updates
            function pollState() {
                fetch(pollingUrl)
                    .then(response => response.json())
                    .then(data => {
                        // Update current bid
                        const currentBidEl = document.getElementById('txt-current-bid');
                        if (currentBidEl) currentBidEl.innerText = '$' + data.current_bid;
                        
                        // Update bid increment
                        const bidIncEl = document.getElementById('txt-bid-increment');
                        if (bidIncEl) bidIncEl.innerText = '+$' + data.bid_increment;
                        
                        // Update raw time remaining
                        const timeRemEl = document.getElementById('txt-time-remaining');
                        if (timeRemEl) timeRemEl.innerText = data.time_remaining;

                        // Update Status Badge
                        const statusBadge = document.getElementById('badge-live-status');
                        if (statusBadge) {
                            statusBadge.innerText = data.status;
                            if (data.status.toLowerCase() === 'active') {
                                statusBadge.className = "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/25";
                            } else {
                                statusBadge.className = "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-400 border border-slate-700";
                            }
                        }

                        // Update inputs limits
                        const bidInput = document.getElementById('amount');
                        const minBidLabel = document.getElementById('lbl-min-bid');
                        if (bidInput || minBidLabel) {
                            const curVal = parseFloat(data.current_bid.replace(/[^0-9.]/g, ''));
                            const incVal = parseFloat(data.bid_increment.replace(/[^0-9.]/g, ''));
                            const minVal = curVal + incVal;

                            if (bidInput) {
                                bidInput.min = minVal;
                            }
                            if (minBidLabel) {
                                minBidLabel.innerText = '$' + minVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }

                        // Update Proxy box info
                        if (data.proxy) {
                            const proxyMaxVal = document.getElementById('val-proxy-max');
                            const proxyCurVal = document.getElementById('val-proxy-current');
                            if (proxyMaxVal) proxyMaxVal.innerText = '$' + data.proxy.max_amount;
                            if (proxyCurVal) proxyCurVal.innerText = '$' + data.proxy.current_auto_bid;
                        }

                        // Update user's bid execution status
                        const statusBox = document.getElementById('bid-execution-status');
                        if (statusBox && data.my_bid) {
                            const st = data.my_bid.status;
                            let cls = 'bg-slate-800/50 border-slate-700 text-slate-300';
                            let label = 'Your last bid: $' + data.my_bid.amount;
                            if (st === 'pending' || st === 'processing') {
                                cls = 'bg-amber-500/10 border-amber-500/20 text-amber-300';
                                label = '⏳ Placing $' + data.my_bid.amount + ' on Ivalua lot {{ $lot->external_lot_id }}… (' + st + ')';
                            } else if (st === 'successful') {
                                cls = 'bg-emerald-500/10 border-emerald-500/20 text-emerald-300';
                                label = '✓ $' + data.my_bid.amount + ' placed on {{ $lot->external_lot_id }} successfully';
                            } else if (st === 'failed') {
                                cls = 'bg-rose-500/10 border-rose-500/20 text-rose-300';
                                label = '✗ Bid failed: ' + (data.my_bid.failure_reason || 'Unknown error');
                            }
                            statusBox.className = 'mb-6 p-4 rounded-xl border text-sm ' + cls;
                            statusBox.textContent = label;
                            statusBox.classList.remove('hidden');
                        }

                        // Update Bid History Table
                        const tbody = document.getElementById('tbl-bid-history');
                        if (tbody) {
                            if (data.history.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="3" class="py-6 text-center text-xs text-slate-600">No bids synced yet.</td></tr>';
                                return;
                            }

                            let html = '';
                            data.history.forEach(hist => {
                                let badgeClass = 'bg-slate-800 text-slate-400';
                                if (hist.source.toLowerCase() === 'user') {
                                    badgeClass = 'bg-blue-500/10 text-blue-400';
                                } else if (hist.source.toLowerCase() === 'system') {
                                    badgeClass = 'bg-indigo-500/10 text-indigo-400';
                                }

                                html += `<tr class="text-slate-300">
                                    <td class="py-2.5 pl-4 font-bold text-white">$${hist.amount}</td>
                                    <td class="py-2.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium ${badgeClass}">
                                            ${hist.source}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-right text-[10px] text-slate-500">${hist.time}</td>
                                </tr>`;
                            });
                            tbody.innerHTML = html;
                        }
                    })
                    .catch(err => console.error('Failed to poll auction state:', err));
            }

            // Start polling every 5 seconds
            setInterval(pollState, 5000);

            // 2. Client-side Live Countdown Timer
            const endsAtStr = document.getElementById('meta-ends-at').innerText;
            if (endsAtStr) {
                const endsAt = new Date(endsAtStr).getTime();
                const timerEl = document.getElementById('txt-time-remaining');

                if (endsAt && timerEl) {
                    const timerInterval = setInterval(function () {
                        const now = new Date().getTime();
                        const distance = endsAt - now;

                        if (distance < 0) {
                            clearInterval(timerInterval);
                            timerEl.innerText = "EXPIRED";
                            timerEl.className = "text-2xl font-bold text-rose-500";
                            return;
                        }

                        // Calculate time components
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        let timeString = "";
                        if (days > 0) timeString += days + "d ";
                        if (hours > 0 || days > 0) timeString += hours + "h ";
                        timeString += minutes + "m " + seconds + "s";

                        timerEl.innerText = timeString;
                    }, 1000);
                }
            }
        });
    </script>
</x-app-layout>
