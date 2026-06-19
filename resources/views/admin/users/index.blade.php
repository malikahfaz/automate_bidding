<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-white leading-tight">
            {{ __('Users Management') }}
        </h2>
    </x-slot>

    <!-- Users Table -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20 overflow-hidden max-w-4xl">
        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-800">
                        <thead>
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 pl-4">User ID</th>
                                <th class="py-3.5">Name</th>
                                <th class="py-3.5">Email</th>
                                <th class="py-3.5">Role</th>
                                <th class="py-3.5 pr-4 text-right">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/50 text-xs text-slate-300">
                            @forelse($users as $user)
                                <tr>
                                    <td class="py-4 pl-4 font-mono text-slate-400">#{{ $user->id }}</td>
                                    <td class="py-4 font-bold text-white">{{ $user->name }}</td>
                                    <td class="py-4 font-medium text-slate-300">{{ $user->email }}</td>
                                    <td class="py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ $user->isAdmin() ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-slate-800 text-slate-500 border border-slate-700' }}">
                                            {{ strtoupper($user->role) }}
                                        </span>
                                    </td>
                                    <td class="py-4 pr-4 text-right text-slate-500">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-600">
                                        No registered users.
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
            {{ $users->links() }}
        </div>
    </div>
</x-admin-layout>
