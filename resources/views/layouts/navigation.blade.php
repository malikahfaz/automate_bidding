<nav class="sticky top-0 z-50 bg-white dark:bg-[#0b1120] border-b border-slate-200 dark:border-slate-800 glass-header" x-data="{ notifDropdown: false }">
    <div class="max-w-[1600px] mx-auto px-4 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            
            <!-- Logo -->
            <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                <img src="https://bids.ellectmobility.com/storage/settings/1781094476_logo.png" alt="Logo" class="h-8 w-auto object-contain group-hover:scale-110 transition-transform">
            </a>

            <!-- Center Nav (Desktop Only) -->
            <div class="hidden xl:flex items-center space-x-8 text-[12px] font-bold uppercase tracking-widest">
                <a href="{{ url('/') }}" class="transition flex items-center gap-2 hover:text-primary {{ request()->is('/') ? 'text-primary' : 'text-slate-500 dark:text-slate-400' }}">
                    <i class="fa-solid fa-house text-[10px] opacity-60"></i> Home
                </a>
                <a href="{{ route('auctions.index') }}" class="transition flex items-center gap-2 hover:text-primary {{ request()->routeIs('auctions.*') ? 'text-primary' : 'text-slate-500 dark:text-slate-400' }}">
                    <i class="fa-solid fa-gavel text-[10px] opacity-60"></i> Auctions
                </a>
                @auth
                    <a href="{{ route('my-bids') }}" class="transition flex items-center gap-2 hover:text-primary {{ request()->routeIs('my-bids') ? 'text-primary' : 'text-slate-500 dark:text-slate-400' }}">
                        <i class="fa-solid fa-file-invoice-dollar text-[10px] opacity-60"></i> My Bids
                    </a>
                    <a href="{{ route('my-proxies') }}" class="transition flex items-center gap-2 hover:text-primary {{ request()->routeIs('my-proxies') ? 'text-primary' : 'text-slate-500 dark:text-slate-400' }}">
                        <i class="fa-solid fa-robot text-[10px] opacity-60"></i> Active Proxies
                    </a>
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="transition flex items-center gap-2 hover:text-primary {{ request()->routeIs('admin.*') ? 'text-primary' : 'text-slate-500 dark:text-slate-400' }}">
                            <i class="fa-solid fa-screwdriver-wrench text-[10px] opacity-60"></i> Admin Panel
                        </a>
                    @endif
                @endauth
                <a href="{{ url('/#features') }}" class="transition flex items-center gap-2 hover:text-primary text-slate-500 dark:text-slate-400">
                    <i class="fa-solid fa-star text-[10px] opacity-60"></i> Features
                </a>
                <a href="{{ url('/#how-it-works') }}" class="transition flex items-center gap-2 hover:text-primary text-slate-500 dark:text-slate-400">
                    <i class="fa-solid fa-circle-question text-[10px] opacity-60"></i> How it Works
                </a>
            </div>

            <!-- Right Tools -->
            <div class="flex items-center gap-3 md:gap-5">
                
                <!-- Dark Mode Switcher -->
                <button @click="$store.theme.toggle()" 
                    class="hidden md:flex w-10 h-10 items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-all border border-transparent dark:border-slate-800">
                    <i class="fa-solid" :class="$store.theme.dark ? 'fa-sun text-yellow-400' : 'fa-moon text-slate-500'"></i>
                </button>
                
                @auth
                    <!-- Authenticated User Dropdown -->
                    <div class="hidden md:inline-flex relative" x-data="{ userDropdown: false }">
                        <button @click="userDropdown = !userDropdown" @click.outside="userDropdown = false" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold uppercase tracking-widest rounded-full transition text-slate-500 dark:text-slate-300">
                            <i class="fa-solid fa-user"></i>
                            <span>{{ Auth::user()->name }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-65"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div x-show="userDropdown" x-cloak class="absolute right-0 mt-12 w-48 bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-850 rounded-xl shadow-xl py-2 z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-primary">
                                <i class="fa-solid fa-user-gear mr-2"></i> Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full text-left block px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-primary">
                                    <i class="fa-solid fa-right-from-bracket mr-2"></i> Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <!-- Guest Sign In -->
                    <a href="{{ route('login') }}" 
                        class="hidden md:inline-flex px-6 py-2.5 bg-primary text-slate-900 text-[11px] font-black uppercase tracking-widest rounded-full hover:bg-primary/90 transition shadow-lg shadow-primary/20 items-center gap-2 italic">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In
                    </a>
                @endauth
                
                <!-- Sidebar Toggle Button -->
                <button @click="rightSidebar = true" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition border border-slate-200 dark:border-slate-800 group">
                    <i class="fa-solid fa-bars-staggered text-slate-500 group-hover:text-primary transition-colors"></i>
                    <span class="hidden sm:inline text-[10px] font-bold uppercase tracking-widest text-slate-500">Menu</span>
                </button>

            </div>
        </div>
    </div>
</nav>

<!-- Sidebar Drawer -->
<aside x-show="rightSidebar" x-cloak
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-500"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    class="fixed top-0 right-0 h-full w-[320px] md:w-[400px] bg-white dark:bg-[#0b1120] z-[70] shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
    
    <div class="p-6 flex flex-col h-full overflow-y-auto custom-scrollbar">
        
        <!-- Close Button & Title -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold uppercase tracking-widest dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-bars-staggered text-primary text-sm"></i> Menu
            </h2>
            <button @click="rightSidebar = false" class="text-slate-500 hover:text-primary transition-all hover:rotate-90">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>

        <!-- Guest Login / Signup Box or Authenticated Welcome Box -->
        @guest
            <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 mb-6 flex flex-col gap-3">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Access Account</p>
                <div class="flex gap-3">
                    <a href="{{ route('login') }}" 
                       class="flex-1 text-center py-3 bg-primary text-slate-900 text-[11px] font-black uppercase tracking-wider rounded-xl hover:bg-primary/90 transition shadow-md shadow-primary/20 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket text-[10px]"></i> Sign In
                    </a>
                    <a href="{{ route('register') }}" 
                       class="flex-1 text-center py-3 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-black uppercase tracking-wider rounded-xl hover:bg-slate-300 dark:hover:bg-slate-700 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-plus text-[10px]"></i> Sign Up
                    </a>
                </div>
            </div>
        @else
            <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 mb-6 flex flex-col gap-3">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Logged In As</p>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 text-center">{{ Auth::user()->name }}</p>
                <div class="flex gap-3">
                    <a href="{{ route('profile.edit') }}" 
                       class="flex-1 text-center py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-black uppercase tracking-wider rounded-xl hover:bg-slate-300 dark:hover:bg-slate-700 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-gear text-[10px]"></i> Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1 m-0">
                        @csrf
                        <button type="submit" 
                           class="w-full text-center py-2.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-[11px] font-black uppercase tracking-wider rounded-xl transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-right-from-bracket text-[10px]"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>
        @endguest
        
        <!-- Main Navigation Links -->
        <nav class="space-y-1.5 flex-1">
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">Explore</p>

            <a href="{{ url('/') }}" 
               class="flex items-center gap-4 p-3 rounded-xl transition-all {{ request()->is('/') ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary' }}">
                <i class="fa-solid fa-house w-5 text-center text-sm"></i> 
                <span class="text-sm font-semibold uppercase tracking-wider">Home</span>
            </a>

            <a href="{{ route('auctions.index') }}" 
               class="flex items-center gap-4 p-3 rounded-xl transition-all {{ request()->routeIs('auctions.*') ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary' }}">
                <i class="fa-solid fa-gavel w-5 text-center text-sm"></i>
                <span class="text-sm font-semibold uppercase tracking-wider">Auctions</span>
            </a>

            @auth
                <a href="{{ route('my-bids') }}" 
                   class="flex items-center gap-4 p-3 rounded-xl transition-all {{ request()->routeIs('my-bids') ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary' }}">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-sm"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider">My Bids</span>
                </a>

                <a href="{{ route('my-proxies') }}" 
                   class="flex items-center gap-4 p-3 rounded-xl transition-all {{ request()->routeIs('my-proxies') ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary' }}">
                    <i class="fa-solid fa-robot w-5 text-center text-sm"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider">Active Proxies</span>
                </a>

                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-4 p-3 rounded-xl transition-all {{ request()->routeIs('admin.*') ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary' }}">
                        <i class="fa-solid fa-screwdriver-wrench w-5 text-center text-sm"></i>
                        <span class="text-sm font-semibold uppercase tracking-wider">Admin Panel</span>
                    </a>
                @endif
            @endauth

            <a href="{{ url('/#features') }}" 
               class="flex items-center gap-4 p-3 rounded-xl transition-all text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary">
                <i class="fa-solid fa-star w-5 text-center text-sm"></i>
                <span class="text-sm font-semibold uppercase tracking-wider">Features</span>
            </a>

            <a href="{{ url('/#how-it-works') }}" 
               class="flex items-center gap-4 p-3 rounded-xl transition-all text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary">
                <i class="fa-solid fa-circle-question w-5 text-center text-sm"></i>
                <span class="text-sm font-semibold uppercase tracking-wider">How it Works</span>
            </a>

            <div class="pt-4">
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">Support & Legal</p>

                <a href="https://bids.ellectmobility.com/support" target="_blank"
                   class="flex items-center gap-4 p-3 rounded-xl transition-all text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary">
                    <i class="fa-solid fa-headset w-5 text-center text-sm"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider">Support</span>
                </a>

                <a href="https://bids.ellectmobility.com/faq" target="_blank"
                   class="flex items-center gap-4 p-3 rounded-xl transition-all text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary">
                    <i class="fa-solid fa-file-contract w-5 text-center text-sm"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider">FAQ</span>
                </a>

                <a href="https://bids.ellectmobility.com/privacy-policy" target="_blank"
                   class="flex items-center gap-4 p-3 rounded-xl transition-all text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary">
                    <i class="fa-solid fa-shield-halved w-5 text-center text-sm"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider">Privacy</span>
                </a>

                <a href="https://bids.ellectmobility.com/terms-of-service" target="_blank"
                   class="flex items-center gap-4 p-3 rounded-xl transition-all text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-primary">
                    <i class="fa-solid fa-gavel w-5 text-center text-sm"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider">Terms</span>
                </a>
            </div>
        </nav>

        <!-- Mobile Only Theme Toggle -->
        <div class="md:hidden mt-6 pt-4 border-t border-slate-200 dark:border-slate-800">
            <button @click="$store.theme.toggle()" 
                    class="w-full flex items-center justify-between gap-4 p-3 rounded-xl bg-slate-100 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 font-bold transition">
                <div class="flex items-center gap-3">
                    <i class="fa-solid w-5 text-center text-sm" :class="$store.theme.dark ? 'fa-sun text-yellow-400' : 'fa-moon text-indigo-400'"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider" x-text="$store.theme.dark ? 'Light Mode' : 'Dark Mode'"></span>
                </div>
                <i class="fa-solid fa-chevron-right text-[10px] opacity-50"></i>
            </button>
        </div>

    </div>
</aside>

<!-- Overlay backdrop -->
<div x-show="rightSidebar" x-cloak 
    @click="rightSidebar = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60]"></div>
