<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-white leading-tight">
            {{ __('Platform Accounts Credentials') }}
        </h2>
    </x-slot>

    <div class="space-y-8 max-w-4xl">
        @foreach($accounts as $account)
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20">
                <div class="flex items-center justify-between border-b border-slate-850 pb-4 mb-6">
                    <div class="flex items-center space-x-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/10 text-blue-400 font-bold text-lg">
                            {{ strtoupper(substr($account->platform, 0, 1)) }}
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-white">{{ strtoupper($account->platform) }} Master Account</h3>
                            <span class="text-xs text-slate-500">Used for all centralized browser automation executions</span>
                        </div>
                    </div>
                    <div>
                        @php
                            $statusColors = [
                                'active' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'expired' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                'error' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                'disabled' => 'bg-slate-800 text-slate-400 border-slate-700',
                            ];
                            $color = $statusColors[$account->status] ?? 'bg-slate-800 text-slate-400';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $color }}">
                            {{ strtoupper($account->status) }}
                        </span>
                    </div>
                </div>

                <form action="{{ route('admin.platform-accounts.update', $account->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="email-{{ $account->id }}" class="block text-xs font-medium text-slate-400 mb-2">Account Email / Username</label>
                        <input type="email" name="email" id="email-{{ $account->id }}" value="{{ old('email', $account->email) }}" required
                            class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
                    </div>

                    <div>
                        <label for="password-{{ $account->id }}" class="block text-xs font-medium text-slate-400 mb-2">Password (Encrypted at Rest)</label>
                        <input type="password" name="password" id="password-{{ $account->id }}" placeholder="••••••••••••"
                            class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-200 py-3 pl-4">
                        <p class="mt-1.5 text-[10px] text-slate-500">Leave blank to keep existing encrypted password.</p>
                    </div>

                    <div>
                        <label for="status-{{ $account->id }}" class="block text-xs font-medium text-slate-400 mb-2">Account Status</label>
                        <select name="status" id="status-{{ $account->id }}"
                            class="w-full rounded-xl bg-slate-950 border-slate-800 focus:border-blue-500 focus:ring-blue-500 text-sm text-slate-300 py-3">
                            <option value="active" {{ $account->status === 'active' ? 'selected' : '' }}>Active / Verified</option>
                            <option value="expired" {{ $account->status === 'expired' ? 'selected' : '' }}>Expired Session</option>
                            <option value="error" {{ $account->status === 'error' ? 'selected' : '' }}>Login Error</option>
                            <option value="disabled" {{ $account->status === 'disabled' ? 'selected' : '' }}>Disabled (Skip Automation)</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex flex-col sm:flex-row justify-between items-start sm:items-center border-t border-slate-850 pt-6 gap-4">
                        <div class="text-xs text-slate-500 space-y-1">
                            <p>Last login check: <strong>{{ $account->last_login_at ? $account->last_login_at->format('M d, Y H:i') : 'Never' }}</strong></p>
                            @if($account->last_error)
                                <p class="text-rose-400">Last error: <em>{{ $account->last_error }}</em></p>
                            @endif
                        </div>
                        <button type="submit" class="inline-flex justify-center items-center px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow transition duration-150 shrink-0">
                            Save Credentials
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</x-admin-layout>
