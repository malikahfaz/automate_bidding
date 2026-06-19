<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-white leading-tight">
            {{ __('Bid Execution & Retry Manager') }}
        </h2>
    </x-slot>

    <!-- Filters -->
    <div class="mb-8 p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
        <form action="{{ route('admin.bids.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
            <!-- Platform -->
            <div class="w-full sm:w-48">
                <label for="platform" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Platform</label>
                <select name="platform" id="platform" 
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                    <option value="all" {{ request('platform') === 'all' ? 'selected' : '' }}>All Platforms</option>
                    <option value="bstock" {{ request('platform') === 'bstock' ? 'selected' : '' }}>B-Stock</option>
                    <option value="ivalua" {{ request('platform') === 'ivalua' ? 'selected' : '' }}>Ivalua</option>
                </select>
            </div>

            <!-- Status -->
            <div class="w-full sm:w-48">
                <label for="status" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Execution Status</label>
                <select name="status" id="status" 
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="successful" {{ request('status') === 'successful' ? 'selected' : '' }}>Successful</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="max_reached" {{ request('status') === 'max_reached' ? 'selected' : '' }}>Max Reached</option>
                </select>
            </div>

            <button type="submit" class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition shadow">
                Filter Bids
            </button>
        </form>
    </div>

    <!-- Bids Table -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20 overflow-hidden">
        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-800">
                        <thead>
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 pl-4">Bid ID</th>
                                <th class="py-3.5">User</th>
                                <th class="py-3.5">Auction Item</th>
                                <th class="py-3.5">Bid Amount</th>
                                <th class="py-3.5">Status</th>
                                <th class="py-3.5">Submitted</th>
                                <th class="py-3.5 pr-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/50 text-xs text-slate-300">
                            @forelse($bids as $bid)
                                <tr>
                                    <td class="py-4 pl-4 font-mono font-bold text-slate-400">#{{ $bid->id }}</td>
                                    <td class="py-4">
                                        <span class="font-semibold text-white block">{{ $bid->user->name }}</span>
                                        <span class="text-[10px] text-slate-500">{{ $bid->user->email }}</span>
                                    </td>
                                    <td class="py-4 font-semibold text-white max-w-xs truncate" title="{{ $bid->auction ? $bid->auction->title : 'Deleted' }}">
                                        @if($bid->auction)
                                            <span class="inline-flex px-2 py-0.5 rounded text-[8px] font-bold mr-1 {{ $bid->auction->platform === 'bstock' ? 'bg-blue-500/10 text-blue-400' : 'bg-indigo-500/10 text-indigo-400' }}">
                                                {{ strtoupper($bid->auction->platform) }}
                                            </span>
                                            {{ $bid->auction->title }}
                                        @else
                                            <span class="text-slate-500">Deleted Auction</span>
                                        @endif
                                    </td>
                                    <td class="py-4 font-bold text-white">${{ number_format($bid->amount, 2) }}</td>
                                    <td class="py-4">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                'processing' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                                'successful' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                'failed' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                'max_reached' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                            ];
                                            $color = $statusColors[$bid->status] ?? 'bg-slate-800 text-slate-500';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $color }}">
                                            {{ strtoupper($bid->status) }}
                                        </span>
                                        @if($bid->status === 'failed')
                                            <span class="block text-[10px] text-rose-400/80 mt-1 max-w-[200px] truncate" title="{{ $bid->failure_reason }}">
                                                {{ $bid->failure_reason }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-slate-500">
                                        {{ $bid->created_at->format('M d, h:i A') }}
                                    </td>
                                    <td class="py-4 pr-4">
                                        <div class="flex justify-end items-center space-x-3">
                                            @if($bid->status === 'failed')
                                                <!-- Retry Button -->
                                                <form action="{{ route('admin.bids.retry', $bid->id) }}" method="POST" class="inline" onsubmit="return confirm('Do you want to retry this failed bid? This will push a new bid job to the queue.');">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-bold text-blue-400 hover:text-blue-300">
                                                        Retry Bid
                                                    </button>
                                                </form>
                                            @endif
                                            @if($bid->auction)
                                                <a href="{{ route('auctions.show', $bid->auction_id) }}" target="_blank" class="text-xs font-semibold text-slate-400 hover:text-white">
                                                    View Item
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-600">
                                        No user bids found.
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
</x-admin-layout>
