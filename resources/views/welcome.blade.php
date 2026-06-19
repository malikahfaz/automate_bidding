<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Smarter Auction Bidding | Antigravity Bids</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800&display=swap" rel="stylesheet" />
        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body {
                font-family: 'Outfit', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased bg-slate-950 text-slate-100 overflow-x-hidden selection:bg-blue-500 selection:text-white">
        
        <!-- Header / Nav -->
        @include('layouts.navigation')

        <!-- Hero Section -->
        <div class="relative isolate overflow-hidden bg-slate-950 pt-14 pb-20 sm:pb-32">
            <!-- Glow background effects -->
            <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#3b82f6] to-[#60a5fa] opacity-10 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
            </div>

            <div class="mx-auto max-w-7xl px-6 lg:px-8 pt-10 sm:pt-16">
                <div class="mx-auto max-w-3xl text-center">
                    <div class="inline-flex items-center gap-x-2 px-3 py-1 rounded-full text-xs font-medium bg-blue-500/10 border border-blue-500/20 text-blue-400 mb-6 animate-pulse">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                        Centralized Browser Automation System
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-6xl bg-clip-text bg-gradient-to-r from-white via-slate-100 to-blue-400">
                        Smarter Auction Bidding, Centralized in One Platform
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-slate-300">
                        Track selected B-Stock and Ivalua auctions, submit bids, and automate proxy bidding from one powerful dashboard.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="{{ route('auctions.index') }}" class="rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/30 hover:bg-blue-500 hover:shadow-blue-500/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all duration-200">
                            View Auctions
                        </a>
                        @guest
                            <a href="{{ route('register') }}" class="text-sm font-semibold leading-6 text-slate-300 hover:text-white transition-all duration-200">
                                Create Account <span aria-hidden="true">→</span>
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="text-sm font-semibold leading-6 text-slate-300 hover:text-white transition-all duration-200">
                                Go to Dashboard <span aria-hidden="true">→</span>
                            </a>
                        @endguest
                    </div>
                </div>
            </div>

            <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
                <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#3b82f6] to-[#80c0ff] opacity-10 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"></div>
            </div>
        </div>

        <!-- Platforms Section -->
        <div class="bg-slate-900/40 border-y border-slate-900 py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-y-8 lg:grid-cols-3 lg:gap-x-8 items-center">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Supported Platforms</h2>
                        <p class="mt-4 text-slate-400 text-sm">
                            We use custom, resilient Playwright browser automation engines to communicate with external bidding networks without fragile APIs.
                        </p>
                    </div>
                    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- B-Stock card -->
                        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20 hover:border-blue-500/30 transition-all duration-200">
                            <div class="flex items-center space-x-3 mb-4">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/10 text-blue-400 font-bold text-lg">B</span>
                                <h3 class="text-lg font-bold text-white">B-Stock Storefronts</h3>
                            </div>
                            <p class="text-slate-400 text-xs leading-relaxed">
                                Complete multi-storefront sync. Direct pass-through of normal or proxy bid values. Relies on internal platform proxy logic.
                            </p>
                        </div>
                        <!-- Ivalua card -->
                        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20 hover:border-blue-500/30 transition-all duration-200">
                            <div class="flex items-center space-x-3 mb-4">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-400 font-bold text-lg">I</span>
                                <h3 class="text-lg font-bold text-white">Ivalua Portals</h3>
                            </div>
                            <p class="text-slate-400 text-xs leading-relaxed">
                                Includes custom proxy bidding engine. Set a max bid; our daemon checks live state continuously and auto-bids on your behalf.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Auctions -->
        <div class="mx-auto max-w-7xl px-6 lg:px-8 py-20 sm:py-28">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-12">
                <div>
                    <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Featured Live Auctions</h2>
                    <p class="mt-4 text-slate-400 text-sm">Real-time status synced directly via browser automation</p>
                </div>
                <a href="{{ route('auctions.index') }}" class="mt-4 sm:mt-0 text-sm font-semibold text-blue-400 hover:text-blue-300">
                    Browse all auctions <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($featuredAuctions as $auction)
                    <div class="flex flex-col rounded-2xl bg-slate-900 border border-slate-850/80 hover:border-slate-700/80 shadow-xl shadow-slate-950/50 hover:shadow-slate-950 overflow-hidden group transition-all duration-300">
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <!-- Platform Badge -->
                                <div class="flex items-center justify-between mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $auction->platform === 'bstock' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' }}">
                                        {{ strtoupper($auction->platform) }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                        <span class="w-1 h-1 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                                        LIVE
                                    </span>
                                </div>

                                <h3 class="text-lg font-bold text-white leading-snug group-hover:text-blue-400 transition-colors duration-250">
                                    <a href="{{ route('auctions.show', $auction->id) }}">{{ $auction->title }}</a>
                                </h3>
                            </div>

                            <div class="mt-6 border-t border-slate-800/80 pt-6">
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="block text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Current Bid</span>
                                        <span class="text-lg font-bold text-white">${{ number_format($auction->current_bid, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Min Increment</span>
                                        <span class="text-sm font-semibold text-slate-300">+${{ number_format($auction->bid_increment, 2) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-xs text-slate-400">
                                        <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Ends in: <strong class="text-white">{{ $auction->time_remaining }}</strong></span>
                                    </div>
                                    <a href="{{ route('auctions.show', $auction->id) }}" class="inline-flex justify-center items-center px-3.5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors">
                                        Bid Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 px-6 text-center border border-slate-800 rounded-2xl bg-slate-900/50">
                        <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0h-4M4 18h16a2 2 0 002-2v-3a2 2 0 00-2-2H4a2 2 0 00-2 2v3a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-4 text-sm font-semibold text-white">No featured auctions</h3>
                        <p class="mt-2 text-xs text-slate-500">Wait for the admin to add or featured auctions</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="bg-slate-900/60 border-t border-slate-900 py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Continuous Automation Flow</h2>
                    <p class="mt-4 text-slate-400 text-sm">How the centralized system places and maintains bids</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Step 1 -->
                    <div class="p-6 bg-slate-950 rounded-2xl border border-slate-800/80 relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-blue-500/10">01</span>
                        <h3 class="text-lg font-bold text-white mt-2 mb-3">Sync Auctions</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            Continuous cron jobs fetch current price details, increment rates, and countdown timer parameters directly from platform servers.
                        </p>
                    </div>
                    <!-- Step 2 -->
                    <div class="p-6 bg-slate-950 rounded-2xl border border-slate-800/80 relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-blue-500/10">02</span>
                        <h3 class="text-lg font-bold text-white mt-2 mb-3">Place Bids</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            Users submit bids on our portal. Bid execution events are pushed immediately to a secure Redis queue.
                        </p>
                    </div>
                    <!-- Step 3 -->
                    <div class="p-6 bg-slate-950 rounded-2xl border border-slate-800/80 relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-blue-500/10">03</span>
                        <h3 class="text-lg font-bold text-white mt-2 mb-3">Automation Engine</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            Queue worker spins up Playwright headlessly, logs into master platform credentials, navigates to the auction page, and commits the bid.
                        </p>
                    </div>
                    <!-- Step 4 -->
                    <div class="p-6 bg-slate-950 rounded-2xl border border-slate-800/80 relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-blue-500/10">04</span>
                        <h3 class="text-lg font-bold text-white mt-2 mb-3">Proxy Monitoring</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            For Ivalua, the daemon checks bid state every few seconds. If outbid, it submits the next increment bid up to the user's defined maximum.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trust / Security Section -->
        <div class="mx-auto max-w-7xl px-6 lg:px-8 py-20">
            <div class="rounded-3xl bg-gradient-to-r from-blue-900/20 to-indigo-900/20 border border-blue-500/10 p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="max-w-xl">
                    <h2 class="text-2xl font-bold text-white sm:text-3xl">Enterprise Credentials Protection</h2>
                    <p class="mt-4 text-slate-400 text-sm leading-relaxed">
                        Master login credentials for external storefronts are encrypted using Laravel's Crypt system with OpenSSL AES-256-GCM. Platform logins are managed entirely by our secure background daemons. Credentials are never exposed to any standard system user.
                    </p>
                </div>
                <div class="shrink-0 flex items-center justify-center w-24 h-24 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-slate-900 bg-slate-950 py-12">
            <div class="mx-auto max-w-7xl px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-6">
                <div class="flex items-center space-x-2">
                    <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="font-bold text-slate-200 tracking-tight">Antigravity Bids</span>
                </div>
                <p class="text-xs text-slate-500">
                    &copy; 2026 Antigravity Bids Bidding Systems. All rights reserved. Secure central automation.
                </p>
            </div>
        </footer>
    </body>
</html>
