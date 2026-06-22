<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-2xl text-white leading-tight">
                {{ __('Edit Auction details') }}
            </h2>
            <a href="{{ route('admin.auctions.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="max-w-xl p-6 sm:p-8 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
        <form action="{{ route('admin.auctions.update', $auction->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Platform -->
            <div>
                <label for="platform" class="block text-xs font-medium text-slate-400 mb-2">Platform Storefront Type</label>
                <select name="platform" id="platform" required
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-3">
                    <option value="bstock" {{ $auction->platform === 'bstock' ? 'selected' : '' }}>B-Stock Storefront</option>
                    <option value="ivalua" {{ $auction->platform === 'ivalua' ? 'selected' : '' }}>Ivalua Portal</option>
                </select>
                @error('platform')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- External URL -->
            <div>
                <label for="external_url" class="block text-xs font-medium text-slate-400 mb-2">External Auction Page URL</label>
                <input type="url" name="external_url" id="external_url" required value="{{ old('external_url', $auction->external_url) }}"
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
                @error('external_url')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="external_lot_id" class="block text-xs font-medium text-slate-400 mb-2">Ivalua Lot ID</label>
                <input type="text" name="external_lot_id" id="external_lot_id" placeholder="e.g. AETA991030" value="{{ old('external_lot_id', $auction->external_lot_id) }}"
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-medium text-slate-400 mb-2">Auction Title</label>
                <input type="text" name="title" id="title" required value="{{ old('title', $auction->title) }}"
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
                @error('title')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Price & Increment -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="current_bid" class="block text-xs font-medium text-slate-400 mb-2">Current Bid ($)</label>
                    <input type="number" step="0.01" name="current_bid" id="current_bid" value="{{ old('current_bid', $auction->current_bid) }}" required
                        class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
                    @error('current_bid')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="bid_increment" class="block text-xs font-medium text-slate-400 mb-2">Bid Increment ($)</label>
                    <input type="number" step="0.01" name="bid_increment" id="bid_increment" value="{{ old('bid_increment', $auction->bid_increment) }}" required
                        class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
                    @error('bid_increment')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Status selector -->
            <div>
                <label for="status" class="block text-xs font-medium text-slate-400 mb-2">Auction Status</label>
                <select name="status" id="status" required
                    class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-3">
                    <option value="active" {{ $auction->status === 'active' ? 'selected' : '' }}>Active / Open</option>
                    <option value="ended" {{ $auction->status === 'ended' ? 'selected' : '' }}>Ended / Closed</option>
                    <option value="paused" {{ $auction->status === 'paused' ? 'selected' : '' }}>Paused</option>
                    <option value="failed" {{ $auction->status === 'failed' ? 'selected' : '' }}>Failed Sync</option>
                </select>
                @error('status')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Checkbox toggles -->
            <div class="flex items-center gap-6 pt-2">
                <div class="flex items-center">
                    <input id="is_active" name="is_active" type="checkbox" value="1" {{ $auction->is_active ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-slate-850 bg-slate-950 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900">
                    <label for="is_active" class="ml-2 block text-xs text-slate-300">Enable Monitoring (Active)</label>
                </div>

                <div class="flex items-center">
                    <input id="is_featured" name="is_featured" type="checkbox" value="1" {{ $auction->is_featured ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-slate-850 bg-slate-950 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900">
                    <label for="is_featured" class="ml-2 block text-xs text-slate-300">Feature on Homepage</label>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="border-t border-slate-850 pt-6 flex justify-end gap-3">
                <button type="submit" class="inline-flex justify-center items-center px-6 py-3 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow transition duration-150">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
