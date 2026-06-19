<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAuctionController;
use App\Http\Controllers\Admin\PlatformAccountController;
use Illuminate\Support\Facades\Route;

// Public Homepage
Route::get('/', function () {
    // Fetch featured active auctions for homepage cards
    $featuredAuctions = \App\Models\Auction::where('is_active', true)
        ->where('is_featured', true)
        ->latest()
        ->take(4)
        ->get();
    return view('welcome', compact('featuredAuctions'));
});

// Marketplace / Browsing (Guest readable)
Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/{id}', [AuctionController::class, 'show'])->name('auctions.show');
Route::get('/auctions/{id}/polling', [AuctionController::class, 'pollState'])->name('auctions.polling');

// Authenticated User Flow
Route::middleware(['auth', 'verified'])->group(function () {
    // Normal Dashboard fallback
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('auctions.index');
    })->name('dashboard');

    // Bidding operations
    Route::post('/auctions/{id}/bid', [AuctionController::class, 'placeBid'])->name('auctions.bid');
    Route::post('/auctions/{id}/proxy', [AuctionController::class, 'setProxyBid'])->name('auctions.proxy');
    Route::post('/auctions/{id}/proxy/cancel', [AuctionController::class, 'cancelProxyBid'])->name('auctions.proxy.cancel');

    // User dashboard pages
    Route::get('/my-bids', [AuctionController::class, 'myBids'])->name('my-bids');
    Route::get('/my-proxies', [AuctionController::class, 'myProxies'])->name('my-proxies');

    // Breeze Profile CRUD
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Panel (Protected by auth and admin middleware)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Overview Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Auctions CRUD
    Route::resource('auctions', AdminAuctionController::class)->except(['show']);
    Route::post('auctions/{id}/sync', [AdminAuctionController::class, 'syncSingle'])->name('auctions.sync');

    // Platform Credentials CRUD
    Route::get('platform-accounts', [PlatformAccountController::class, 'index'])->name('platform-accounts.index');
    Route::put('platform-accounts/{id}', [PlatformAccountController::class, 'update'])->name('platform-accounts.update');

    // Bids & Retry Management
    Route::get('bids', [AdminDashboardController::class, 'bidsIndex'])->name('bids.index');
    Route::post('bids/{id}/retry', [AdminDashboardController::class, 'retryBid'])->name('bids.retry');

    // Logs & Users
    Route::get('logs', [AdminDashboardController::class, 'logsIndex'])->name('logs.index');
    Route::get('users', [AdminDashboardController::class, 'usersIndex'])->name('users.index');
});

require __DIR__.'/auth.php';
