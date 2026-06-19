<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-white leading-tight">
            {{ __('Dashboard Overview') }}
        </h2>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-10">
        <!-- Card 1 -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Auctions</span>
            <span class="text-3xl font-extrabold text-white">{{ $stats['total_auctions'] }}</span>
        </div>
        <!-- Card 2 -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Active Auctions</span>
            <span class="text-3xl font-extrabold text-blue-400">{{ $stats['active_auctions'] }}</span>
        </div>
        <!-- Card 3 -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Bids</span>
            <span class="text-3xl font-extrabold text-white">{{ $stats['total_bids'] }}</span>
        </div>
        <!-- Card 4 -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Success Bids</span>
            <span class="text-3xl font-extrabold text-emerald-400">{{ $stats['successful_bids'] }}</span>
        </div>
        <!-- Card 5 -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Failed Bids</span>
            <span class="text-3xl font-extrabold text-rose-400">{{ $stats['failed_bids'] }}</span>
        </div>
        <!-- Card 6 -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Active Proxies</span>
            <span class="text-3xl font-extrabold text-indigo-400">{{ $stats['active_proxies'] }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left: Recent Bids -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-base font-bold text-white">Recent Bid Submissions</h3>
                <a href="{{ route('admin.bids.index') }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300">View All</a>
            </div>

            <div class="flow-root">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="pb-3 pl-4">User</th>
                            <th class="pb-3">Amount</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3 pr-4 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850/50 text-xs text-slate-300">
                        @forelse($recentBids as $bid)
                            <tr>
                                <td class="py-3 pl-4 font-semibold text-white">
                                    {{ $bid->user->name }}
                                    <span class="block text-[10px] text-slate-500 font-medium">Auction ID: {{ $bid->auction_id }}</span>
                                </td>
                                <td class="py-3 font-bold text-slate-200">${{ number_format($bid->amount, 2) }}</td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $bid->status === 'successful' ? 'bg-emerald-500/10 text-emerald-400' : ($bid->status === 'failed' ? 'bg-rose-500/10 text-rose-400' : 'bg-amber-500/10 text-amber-400') }}">
                                        {{ strtoupper($bid->status) }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4 text-right text-slate-500">{{ $bid->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-600">No bids submitted recently.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Recent Automation Logs -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-base font-bold text-white">Recent Automation Actions</h3>
                <a href="{{ route('admin.logs.index') }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300">View Logs</a>
            </div>

            <div class="flow-root">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="pb-3 pl-4">Platform</th>
                            <th class="pb-3">Action</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3 pr-4 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850/50 text-xs text-slate-300">
                        @forelse($recentLogs as $log)
                            <tr>
                                <td class="py-3 pl-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium {{ $log->platform === 'bstock' ? 'bg-blue-500/10 text-blue-400' : 'bg-indigo-500/10 text-indigo-400' }}">
                                        {{ strtoupper($log->platform) }}
                                    </span>
                                </td>
                                <td class="py-3 font-semibold text-slate-200">
                                    {{ ucfirst($log->action) }}
                                    <span class="block text-[10px] text-slate-500 truncate max-w-xs font-normal">{{ $log->message }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $log->status === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                                        {{ strtoupper($log->status) }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4 text-right text-slate-500">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-600">No automation logs captured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>
