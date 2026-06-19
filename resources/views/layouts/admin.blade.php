<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin Panel - Antigravity Bids</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body {
                font-family: 'Outfit', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased bg-slate-950 text-slate-100 min-h-screen">
        <div class="flex flex-col md:flex-row min-h-screen">
            
            <!-- Sidebar Navigation -->
            <aside class="w-full md:w-64 bg-slate-900 border-r border-slate-800/60 shrink-0">
                <div class="flex items-center h-16 px-6 border-b border-slate-800">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 text-white">
                        <svg class="w-7 h-7 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span class="font-extrabold text-base tracking-wide">Admin Bids</span>
                    </a>
                </div>

                <nav class="p-4 space-y-1">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        Dashboard
                    </a>
                    
                    <a href="{{ route('admin.auctions.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('admin.auctions.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        Auctions Manager
                    </a>

                    <a href="{{ route('admin.platform-accounts.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('admin.platform-accounts.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        Platform Credentials
                    </a>

                    <a href="{{ route('admin.bids.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('admin.bids.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        Bid & Retry Manager
                    </a>

                    <a href="{{ route('admin.logs.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('admin.logs.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        Automation Logs
                    </a>

                    <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        Users List
                    </a>

                    <div class="pt-6 border-t border-slate-800 my-6"></div>

                    <a href="{{ route('auctions.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl text-slate-500 hover:bg-slate-850 hover:text-slate-200 transition duration-150">
                        ← Back to Marketplace
                    </a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-h-screen">
                
                <!-- Admin Header Bar -->
                <header class="h-16 bg-slate-900 border-b border-slate-800/80 flex items-center justify-between px-8 shadow-sm">
                    <div class="text-sm font-bold text-slate-400">
                        Central Control Panel
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <span class="text-xs text-slate-500 font-semibold bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700">ADMIN</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-slate-400 hover:text-white">
                                Log Out
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Page Contents -->
                <main class="flex-1 p-8">
                    <!-- Notifications -->
                    @if(session('success'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center shadow-lg">
                            <svg class="h-5 w-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center shadow-lg">
                            <svg class="h-5 w-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @isset($header)
                        <div class="mb-8">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
