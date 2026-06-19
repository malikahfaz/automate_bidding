<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-white leading-tight">
            {{ __('Automation Crawler Logs') }}
        </h2>
    </x-slot>

    <!-- Filters -->
    <div class="mb-8 p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-xl shadow-slate-950/20">
        <form action="{{ route('admin.logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <!-- Platform -->
            <div>
                <label for="platform" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Platform</label>
                <select name="platform" id="platform" 
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                    <option value="all" {{ request('platform') === 'all' ? 'selected' : '' }}>All Platforms</option>
                    <option value="bstock" {{ request('platform') === 'bstock' ? 'selected' : '' }}>B-Stock</option>
                    <option value="ivalua" {{ request('platform') === 'ivalua' ? 'selected' : '' }}>Ivalua</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Log Status</label>
                <select name="status" id="status" 
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success Only</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed Only</option>
                    <option value="info" {{ request('status') === 'info' ? 'selected' : '' }}>Info Only</option>
                </select>
            </div>

            <!-- Action -->
            <div>
                <label for="action" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Action Type</label>
                <select name="action" id="action" 
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-2.5">
                    <option value="all" {{ request('action') === 'all' ? 'selected' : '' }}>All Actions</option>
                    <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                    <option value="sync" {{ request('action') === 'sync' ? 'selected' : '' }}>Sync</option>
                    <option value="place-bid" {{ request('action') === 'place-bid' ? 'selected' : '' }}>Place Bid</option>
                </select>
            </div>

            <button type="submit" class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition shadow">
                Filter Logs
            </button>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20 overflow-hidden">
        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-800">
                        <thead>
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 pl-4">Platform</th>
                                <th class="py-3.5">Action</th>
                                <th class="py-3.5">Status</th>
                                <th class="py-3.5">Log Message</th>
                                <th class="py-3.5 text-center">Payload / SS</th>
                                <th class="py-3.5 pr-4 text-right">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/50 text-xs text-slate-300">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="py-4 pl-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold {{ $log->platform === 'bstock' ? 'bg-blue-500/10 text-blue-400' : 'bg-indigo-500/10 text-indigo-400' }}">
                                            {{ strtoupper($log->platform) }}
                                        </span>
                                    </td>
                                    <td class="py-4 font-semibold text-white">
                                        {{ strtoupper($log->action) }}
                                    </td>
                                    <td class="py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $log->status === 'success' ? 'bg-emerald-500/10 text-emerald-400' : ($log->status === 'failed' ? 'bg-rose-500/10 text-rose-400' : 'bg-amber-500/10 text-amber-400') }}">
                                            {{ strtoupper($log->status) }}
                                        </span>
                                    </td>
                                    <td class="py-4 max-w-sm leading-normal">
                                        <span class="block font-medium text-slate-200" title="{{ $log->message }}">{{ $log->message }}</span>
                                        @if($log->auction)
                                            <span class="block text-[9px] text-slate-500">Auction ID: #{{ $log->auction_id }} ({{ $log->auction->title }})</span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-center space-y-1">
                                        @if($log->payload)
                                            <button type="button" onclick="alert(JSON.stringify({!! json_encode($log->payload) !!}, null, 2))" class="inline-flex items-center px-2 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] font-bold text-slate-300 rounded border border-slate-700">
                                                JSON Data
                                            </button>
                                        @endif
                                        @if($log->screenshot_path)
                                            <a href="{{ asset($log->screenshot_path) }}" target="_blank" class="block text-[10px] font-bold text-blue-400 hover:underline">
                                                Screenshot
                                            </a>
                                        @endif
                                    </td>
                                    <td class="py-4 pr-4 text-right text-slate-500">
                                        {{ $log->created_at->format('M d, H:i:s A') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-600">
                                        No logs captured.
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
            {{ $logs->links() }}
        </div>
    </div>
</x-admin-layout>
