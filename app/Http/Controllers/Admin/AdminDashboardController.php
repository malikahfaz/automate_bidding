<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\ProxyBid;
use App\Models\AutomationLog;
use App\Models\User;
use App\Jobs\PlaceBidJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard overview.
     */
    public function index()
    {
        $stats = [
            'total_auctions' => Auction::count(),
            'active_auctions' => Auction::where('is_active', true)->where('status', 'active')->count(),
            'total_bids' => Bid::count(),
            'successful_bids' => Bid::where('status', 'successful')->count(),
            'failed_bids' => Bid::where('status', 'failed')->count(),
            'active_proxies' => ProxyBid::where('status', 'active')->count(),
        ];

        // Recent activity lists
        $recentBids = Bid::with(['auction', 'user'])->latest()->take(5)->get();
        $recentLogs = AutomationLog::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentBids', 'recentLogs'));
    }

    /**
     * List all bids submitted by users.
     */
    public function bidsIndex(Request $request)
    {
        $query = Bid::with(['auction', 'user']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform') && $request->platform !== 'all') {
            $query->whereHas('auction', function ($q) use ($request) {
                $q->where('platform', $request->platform);
            });
        }

        $bids = $query->latest()->paginate(15)->withQueryString();

        return view('admin.bids.index', compact('bids'));
    }

    /**
     * Retry a failed bid.
     */
    public function retryBid($id)
    {
        $failedBid = Bid::findOrFail($id);

        if ($failedBid->status !== 'failed') {
            return back()->with('error', 'Only failed bids can be retried.');
        }

        $auction = $failedBid->auction;
        if (!$auction || !$auction->is_active || $auction->status !== 'active') {
            return back()->with('error', 'Cannot retry: The auction is no longer active.');
        }

        // Re-verify that next bid is still logical or current bid hasn't bypassed it
        if ($failedBid->amount < $auction->current_bid + $auction->bid_increment) {
            return back()->with('error', 'Cannot retry: Current bid has increased since this bid failed. Please place a new bid.');
        }

        // Create new bid copy with status pending
        $newBid = Bid::create([
            'auction_id' => $failedBid->auction_id,
            'user_id' => $failedBid->user_id,
            'bid_type' => $failedBid->bid_type,
            'amount' => $failedBid->amount,
            'max_amount' => $failedBid->max_amount,
            'status' => 'pending'
        ]);

        // Dispatch job
        PlaceBidJob::dispatch($newBid->id)->onQueue('bids');

        return back()->with('success', 'Retry job dispatched successfully! A new bid placement attempt is processing.');
    }

    /**
     * List all automation log records.
     */
    public function logsIndex(Request $request)
    {
        $query = AutomationLog::with(['auction', 'user']);

        if ($request->filled('platform') && $request->platform !== 'all') {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        return view('admin.logs.index', compact('logs'));
    }

    /**
     * List registered users.
     */
    public function usersIndex()
    {
        $users = User::paginate(20);
        return view('admin.users.index', compact('users'));
    }
}
