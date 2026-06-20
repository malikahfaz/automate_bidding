<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ rightSidebar: false, notifDropdown: false }"
    x-cloak>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Smarter Auction Bidding | Ellect Mobility Bids</title>

    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            const isDark = saved === null ? true : saved === 'dark';
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            window.__initialDarkMode = isDark;
        })();
    </script>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="icon" type="image/x-icon" href="https://bids.ellectmobility.com/storage/settings/1779606890_favicon.webp">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                dark: window.__initialDarkMode,
                toggle() {
                    this.dark = !this.dark;
                    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                    if (this.dark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            });
        });
    </script>
</head>
<body class="bg-lightBg dark:bg-darkBg text-slate-900 dark:text-slate-100 transition-colors duration-300">

    <!-- Navbar -->
    @include('layouts.navigation')

    <!-- MAIN CONTENT -->
    <main class="max-w-[1600px] mx-auto px-4 lg:px-8 py-6">
        
        <!-- Hero Section -->
        <header class="relative py-8 sm:py-12 md:py-20 overflow-hidden" id="home">
            <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="text-center md:text-left order-2 md:order-1">
                    <span class="inline-block px-3 py-1 text-[10px] sm:text-xs font-bold bg-primary/20 text-primary dark:text-primary rounded-full mb-4">Auction Platform</span>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 leading-tight">
                        <span class="text-primary">Bid smarter. Win faster.</span><br>Real time automation.
                    </h1>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 mb-6 max-w-lg mx-auto md:mx-0">
                        Connecting premium North American smartphones and tablets to the world. High-quality inventory from Aden Group LLC, delivered worldwide.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center md:justify-start">
                        <a href="{{ route('auctions.index') }}" class="bg-primary text-slate-950 px-6 py-2.5 rounded-xl font-bold text-sm hover:opacity-90 transition text-center shadow-lg shadow-primary/20">View Inventory</a>
                        <a href="#how-it-works" class="border border-slate-300 dark:border-slate-700 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-slate-100 dark:hover:bg-slate-800/50 transition text-center text-slate-700 dark:text-slate-300">Learn More</a>
                    </div>
                </div>
                <div class="relative order-1 md:order-2 max-w-md mx-auto md:max-w-full">
                    <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&q=80&w=800" alt="Smartphone" class="rounded-2xl shadow-2xl w-full border border-slate-200 dark:border-slate-800">
                    <div class="absolute -bottom-4 -left-4 sm:-bottom-6 sm:-left-6 bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-primary font-bold text-lg sm:text-xl">100% Verified</p>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-300">Data Sanitized & Tested</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Certifications -->
        <section class="py-12 sm:py-20 bg-slate-100 dark:bg-slate-900/50 rounded-2xl my-8 border border-slate-200/50 dark:border-slate-850" id="certifications">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <h2 class="text-2xl sm:text-3xl font-bold mb-2">Security & Compliance</h2>
                <p class="text-slate-500 dark:text-slate-400 mb-8 sm:mb-12 text-sm sm:text-base">Our management systems are certified by Amtivo (USA) Inc.</p>
                
                <div class="flex flex-col md:flex-row justify-center items-center gap-8 md:gap-12">
                    <!-- RIOS Logo -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition text-center w-64">
                        <img src="https://bids.ellectmobility.com/storage/settings/1779603002_favicon.png" alt="RIOS Certified" class="h-20 mx-auto mb-4 object-contain">
                        <h3 class="text-lg font-bold mb-1">RIOS:2016 Certified</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Recycling Industry Operating Standard</p>
                    </div>

                    <!-- R2 Logo -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition text-center w-64">
                        <img src="https://bids.ellectmobility.com/storage/settings/1779603020_favicon.png" alt="R2v3 Certified" class="h-20 mx-auto mb-4 object-contain">
                        <h3 class="text-lg font-bold mb-1">R2v3 Standard</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Sustainable Electronics Reuse & Recycling</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section class="py-12 sm:py-20" id="features">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                    <div class="text-center p-4 sm:p-6 border-r border-slate-200 dark:border-slate-800 last:border-0">
                        <span class="text-2xl sm:text-3xl mb-2 block">🔒</span>
                        <h4 class="font-bold text-sm sm:text-base">Secure Payment</h4>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">Safe transfers.</p>
                    </div>
                    <div class="text-center p-4 sm:p-6 border-r border-slate-200 dark:border-slate-800 last:border-0">
                        <span class="text-2xl sm:text-3xl mb-2 block">✅</span>
                        <h4 class="font-bold text-sm sm:text-base">Quality Checked</h4>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">Robotic grading.</p>
                    </div>
                    <div class="text-center p-4 sm:p-6 border-r border-slate-200 dark:border-slate-800 last:border-0">
                        <span class="text-2xl sm:text-3xl mb-2 block">📦</span>
                        <h4 class="font-bold text-sm sm:text-base">Large Inventory</h4>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">Direct carrier stock.</p>
                    </div>
                    <div class="text-center p-4 sm:p-6">
                        <span class="text-2xl sm:text-3xl mb-2 block">🚚</span>
                        <h4 class="font-bold text-sm sm:text-base">Fast Shipping</h4>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">FedEx preferred.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dynamic Featured Auctions (If Any) -->
        @if(isset($featuredAuctions) && count($featuredAuctions) > 0)
            <section class="py-12 sm:py-20">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-12">
                        <div>
                            <span class="inline-block px-3 py-1 text-[10px] font-bold bg-primary/20 text-primary rounded-full mb-2">Automated Bidding</span>
                            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Featured Live Auctions</h2>
                            <p class="mt-2 text-slate-500 dark:text-slate-450 text-sm">Real-time status synced directly via browser automation</p>
                        </div>
                        <a href="{{ route('auctions.index') }}" class="mt-4 sm:mt-0 text-sm font-semibold text-primary hover:opacity-80 flex items-center gap-1">
                            Browse all auctions <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($featuredAuctions as $auction)
                            <div class="flex flex-col rounded-2xl bg-white dark:bg-[#0b1120] border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden group transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl">
                                <div class="p-6 flex-1 flex flex-col justify-between">
                                    <div>
                                        <!-- Platform Badge -->
                                        <div class="flex items-center justify-between mb-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $auction->platform === 'bstock' ? 'bg-primary/20 text-slate-850 dark:text-primary border border-primary/25' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/25' }}">
                                                {{ strtoupper($auction->platform) }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border border-emerald-500/25">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                                LIVE
                                            </span>
                                        </div>

                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-snug group-hover:text-primary transition-colors duration-250">
                                            <a href="{{ route('auctions.show', $auction->id) }}">{{ $auction->title }}</a>
                                        </h3>
                                    </div>

                                    <div class="mt-6 border-t border-slate-100 dark:border-slate-800/80 pt-6">
                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <span class="block text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Current Bid</span>
                                                <span class="text-lg font-bold text-slate-900 dark:text-white">${{ number_format($auction->current_bid, 2) }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Min Increment</span>
                                                <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">+${{ number_format($auction->bid_increment, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center text-xs text-slate-500 dark:text-slate-400">
                                                <i class="fa-solid fa-clock mr-1.5 opacity-70"></i>
                                                <span>Ends in: <strong class="text-slate-700 dark:text-white">{{ $auction->time_remaining }}</strong></span>
                                            </div>
                                            <a href="{{ route('auctions.show', $auction->id) }}" class="inline-flex justify-center items-center px-4 py-2 text-xs font-bold text-slate-900 bg-primary hover:bg-primary/90 rounded-lg transition-colors shadow-md shadow-primary/20">
                                                Bid Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- Live Trading Inventory Table -->
        <section class="py-12 sm:py-20 bg-slate-50 dark:bg-[#0b1120]/40 rounded-2xl my-4 border border-slate-200 dark:border-slate-800/70" id="inventory">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="text-xl sm:text-2xl font-bold mb-6 flex items-center gap-2">
                    <span class="text-primary"><i class="fa-solid fa-chart-simple"></i></span> Live Trading Inventory
                </h2>
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <table class="w-full text-left text-[12px] sm:text-sm min-w-[500px]">
                        <thead class="bg-slate-100 dark:bg-slate-900 text-slate-500 dark:text-slate-400 uppercase font-bold">
                            <tr>
                                <th class="px-4 py-3 sm:px-6 sm:py-4">Brand</th>
                                <th class="px-4 py-3 sm:px-6 sm:py-4">Model</th>
                                <th class="px-4 py-3 sm:px-6 sm:py-4">Quantity</th>
                                <th class="px-4 py-3 sm:px-6 sm:py-4">Starting Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850">
                            <tr class="hover:bg-primary/5 transition bg-white dark:bg-[#0f172a]/20">
                                <td class="px-4 py-3 sm:px-6 sm:py-4 font-medium">Apple</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">iPhone 15 Pro Max</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">4338</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">Starting from 405.00 USD</td>
                            </tr>
                            <tr class="hover:bg-primary/5 transition bg-white dark:bg-[#0f172a]/20">
                                <td class="px-4 py-3 sm:px-6 sm:py-4 font-medium">Apple</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">iPhone 14 Pro Max</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">4203</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">Starting from 298.00 USD</td>
                            </tr>
                            <tr class="hover:bg-primary/5 transition bg-white dark:bg-[#0f172a]/20">
                                <td class="px-4 py-3 sm:px-6 sm:py-4 font-medium">Samsung</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">Galaxy S23</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">395</td>
                                <td class="px-4 py-3 sm:px-6 sm:py-4">Starting from 211.00 USD</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- How It Works / Centralized Automation -->
        <section class="py-12 sm:py-20" id="how-it-works">
            <div class="max-w-7xl mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold bg-primary/20 text-primary rounded-full mb-2">Process Overview</span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Continuous Automation Flow</h2>
                    <p class="mt-4 text-slate-500 dark:text-slate-400 text-sm">How the centralized system places and maintains bids</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Step 1 -->
                    <div class="p-6 bg-white dark:bg-[#0b1120] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-primary/10">01</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-2 mb-3">Sync Auctions</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Continuous cron jobs fetch current price details, increment rates, and countdown timer parameters directly from platform servers.
                        </p>
                    </div>
                    <!-- Step 2 -->
                    <div class="p-6 bg-white dark:bg-[#0b1120] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-primary/10">02</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-2 mb-3">Place Bids</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Users submit bids on our portal. Bid execution events are pushed immediately to a secure Redis queue.
                        </p>
                    </div>
                    <!-- Step 3 -->
                    <div class="p-6 bg-white dark:bg-[#0b1120] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-primary/10">03</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-2 mb-3">Automation Engine</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Queue worker spins up Playwright headlessly, logs into master platform credentials, navigates to the auction page, and commits the bid.
                        </p>
                    </div>
                    <!-- Step 4 -->
                    <div class="p-6 bg-white dark:bg-[#0b1120] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <span class="absolute -top-4 -left-2 text-6xl font-black text-primary/10">04</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-2 mb-3">Proxy Monitoring</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            For Ivalua, the daemon checks bid state every few seconds. If outbid, it submits the next increment bid up to the user's defined maximum.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust / Security Section -->
        <section class="mx-auto max-w-7xl px-4 py-12 sm:py-20" id="security">
            <div class="rounded-3xl bg-gradient-to-r from-primary/10 to-indigo-900/10 dark:from-primary/5 dark:to-indigo-950/20 border border-primary/20 p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="max-w-xl">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white sm:text-3xl">Enterprise Credentials Protection</h2>
                    <p class="mt-4 text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Master login credentials for external storefronts are encrypted using Laravel's Crypt system with OpenSSL AES-256-GCM. Platform logins are managed entirely by our secure background daemons. Credentials are never exposed to any standard system user.
                    </p>
                </div>
                <div class="shrink-0 flex items-center justify-center w-24 h-24 rounded-full bg-primary/10 border border-primary/25 text-primary">
                    <i class="fa-solid fa-shield-halved text-4xl"></i>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-100 dark:bg-[#0b1120] pt-16 pb-8 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            
            <!-- Main Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 mb-16">
                
                <!-- Column 1: Logo Section -->
                <div class="lg:col-span-1">
                    <div class="flex flex-col">
                        <img src="https://bids.ellectmobility.com/storage/settings/1781094476_logo.png" alt="Logo" class="h-8 w-auto object-contain self-start">
                    </div>
                </div>

                <!-- Column 2: Learn More -->
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-6">Learn More</h4>
                    <ul class="space-y-4 text-[13px] font-medium text-slate-500 dark:text-slate-400">
                        <li><a href="#" class="hover:text-primary transition">Circular Economy Services</a></li>
                        <li><a href="#" class="hover:text-primary transition">Remarketing Services</a></li>
                        <li><a href="#" class="hover:text-primary transition">Who We Partner With</a></li>
                    </ul>
                </div>

                <!-- Column 3: About -->
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-6">About</h4>
                    <ul class="space-y-4 text-[13px] font-medium text-slate-500 dark:text-slate-400">
                        <li><a href="#" class="hover:text-primary transition">Careers</a></li>
                        <li><a href="#" class="hover:text-primary transition">Legal</a></li>
                    </ul>
                </div>

                <!-- Column 4: Main Office -->
                <div class="lg:col-span-1">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-6">Main Office</h4>
                    <ul class="space-y-4 text-[13px] font-medium text-slate-500 dark:text-slate-400">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-location-dot mt-1 text-slate-900 dark:text-white"></i>
                            <span>7231 Saint Louis Ave, #E, Skokie, IL, 60076, USA.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-solid fa-phone text-slate-900 dark:text-white"></i>
                            <span>+1708-247-0037</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-solid fa-envelope text-slate-900 dark:text-white"></i>
                            <a href="mailto:wholesalebids@ellectmobility.com" class="hover:text-primary">wholesalebids@ellectmobility.com</a>
                        </li>
                    </ul>
                </div>

                <!-- Column 5: Follow Us -->
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-6">Follow Us</h4>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 rounded-xl bg-[#dce4ee] dark:bg-slate-800 flex items-center justify-center text-[#3b5998] dark:text-white hover:scale-110 transition shadow-sm">
                            <i class="fa-brands fa-facebook-f text-lg"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-[#dce4ee] dark:bg-slate-800 flex items-center justify-center text-[#0077b5] dark:text-white hover:scale-110 transition shadow-sm">
                            <i class="fa-brands fa-linkedin-in text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Certification Logos -->
            <div class="flex flex-wrap justify-center items-center gap-12 mb-12 py-8 border-t border-slate-200 dark:border-slate-800/50">
                <div class="flex flex-col items-center">
                    <img src="https://bids.ellectmobility.com/storage/settings/1779603002_favicon.png" alt="R2 Certified" class="h-20 object-contain transition duration-500">
                </div>
                <div class="flex flex-col items-center">
                    <img src="https://bids.ellectmobility.com/storage/settings/1779603020_favicon.png" alt="RIOS Certified" class="h-16 object-contain transition duration-500">
                </div>
            </div>

            <!-- Copyright -->
            <div class="text-center text-[12px] font-bold text-slate-400 dark:text-slate-650 uppercase tracking-widest">
                &copy; 2026 ELLECT MOBILITY. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
