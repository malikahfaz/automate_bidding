<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\ProxyBid;
use App\Models\BidHistory;
use App\Jobs\PlaceBidJob;
use App\Jobs\IvaluaProxyBidJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuctionController extends Controller
{
    /**
     * Display a listing of auctions (Marketplace).
     */
    public function index(Request $request)
    {
        $query = Auction::query();

        // Search title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter platform
        if ($request->filled('platform') && $request->platform !== 'all') {
            $query->where('platform', $request->platform);
        }

        // Filter status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            // Default show active
            $query->where('is_active', true);
        }

        // Sort options
        $sort = $request->get('sort', 'ending_soon');
        if ($sort === 'ending_soon') {
            $query->orderBy('ends_at', 'asc');
        } elseif ($sort === 'bid_asc') {
            $query->orderBy('current_bid', 'asc');
        } elseif ($sort === 'bid_desc') {
            $query->orderBy('current_bid', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        }

        $auctions = $query->paginate(12)->withQueryString();

        return view('auctions.index', compact('auctions'));
    }

    /**
     * Display the specified auction.
     */
    public function show($id)
    {
        $auction = Auction::with(['bidHistories.user', 'bids.user', 'activeProxyBid' => function ($q) {
            $q->where('user_id', Auth::id());
        }])->findOrFail($id);

        return view('auctions.show', compact('auction'));
    }

    /**
     * Submit a normal bid to the queue.
     */
    public function placeBid(Request $request, $id)
    {
        $auction = Auction::findOrFail($id);

        if (!$auction->is_active || $auction->status !== 'active') {
            return back()->with('error', 'This auction is no longer active.');
        }

        // Validate bid amount
        $minBid = $auction->current_bid + $auction->bid_increment;
        
        $request->validate([
            'amount' => 'required|numeric|min:' . $minBid,
        ], [
            'amount.min' => 'Your bid must be at least ' . number_format($minBid, 2) . ' (Current bid + bid increment).',
        ]);

        // Check if there's already a pending or processing bid job for this auction
        // to prevent duplicate executions.
        $hasPending = Bid::where('auction_id', $auction->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($hasPending) {
            return back()->with('error', 'A bid is currently being processed for this auction. Please wait a few seconds and try again.');
        }

        // Create Bid Record
        $bid = Bid::create([
            'auction_id' => $auction->id,
            'user_id' => Auth::id(),
            'bid_type' => 'normal',
            'amount' => $request->amount,
            'status' => 'pending'
        ]);

        // Dispatch Bid Execution Job
        PlaceBidJob::dispatch($bid->id);

        return back()->with('success', 'Your bid has been submitted to the execution queue. It will be placed shortly!');
    }

    /**
     * Enable custom proxy bidding for Ivalua.
     */
    public function setProxyBid(Request $request, $id)
    {
        $auction = Auction::findOrFail($id);

        if ($auction->platform !== 'ivalua') {
            return back()->with('error', 'Custom proxy bidding is only supported on Ivalua auctions.');
        }

        if (!$auction->is_active || $auction->status !== 'active') {
            return back()->with('error', 'This auction is no longer active.');
        }

        $minBid = $auction->current_bid + $auction->bid_increment;

        $request->validate([
            'max_amount' => 'required|numeric|min:' . $minBid,
        ], [
            'max_amount.min' => 'Max bid must be at least ' . number_format($minBid, 2) . ' (Current bid + bid increment).',
        ]);

        // Stop any existing active proxy bid for this user and auction
        ProxyBid::where('auction_id', $auction->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->update([
                'status' => 'stopped',
                'stopped_at' => now(),
                'stop_reason' => 'Replaced by new proxy configuration.'
            ]);

        // Create new active proxy bid
        ProxyBid::create([
            'auction_id' => $auction->id,
            'user_id' => Auth::id(),
            'max_amount' => $request->max_amount,
            'current_auto_bid' => $auction->current_bid,
            'status' => 'active',
            'started_at' => now()
        ]);

        // Immediately trigger check
        IvaluaProxyBidJob::dispatch($auction->id);

        return back()->with('success', 'Custom proxy bidding has been activated. The system will now auto-bid up to ' . number_format($request->max_amount, 2));
    }

    /**
     * Cancel an active proxy bid.
     */
    public function cancelProxyBid($id)
    {
        $proxy = ProxyBid::where('auction_id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->firstOrFail();

        $proxy->update([
            'status' => 'cancelled',
            'stopped_at' => now(),
            'stop_reason' => 'Cancelled by user.'
        ]);

        return back()->with('success', 'Proxy bidding has been cancelled.');
    }

    /**
     * Display user's bidding history.
     */
    public function myBids()
    {
        $bids = Bid::with('auction')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('auctions.my-bids', compact('bids'));
    }

    /**
     * Display user's active proxy bids.
     */
    public function myProxies()
    {
        $proxies = ProxyBid::with('auction')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('auctions.my-proxies', compact('proxies'));
    }

    /**
     * JSON Endpoint for AJAX polling to fetch real-time bid state updates.
     */
    public function pollState($id)
    {
        $auction = Auction::findOrFail($id);

        // Fetch last 10 bid histories
        $history = BidHistory::with('user')
            ->where('auction_id', $auction->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($item) {
                return [
                    'amount' => number_format($item->amount, 2),
                    'source' => ucfirst($item->source),
                    'user' => $item->user ? $item->user->name : 'External Bidder',
                    'time' => $item->created_at->diffForHumans(),
                    'status' => $item->status
                ];
            });

        // Get user's active proxy bid max amount
        $activeProxy = null;
        if (Auth::check()) {
            $proxyObj = ProxyBid::where('auction_id', $auction->id)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();
            if ($proxyObj) {
                $activeProxy = [
                    'max_amount' => number_format($proxyObj->max_amount, 2),
                    'current_auto_bid' => number_format($proxyObj->current_auto_bid, 2),
                ];
            }
        }

        return response()->json([
            'current_bid' => number_format($auction->current_bid, 2),
            'bid_increment' => number_format($auction->bid_increment, 2),
            'time_remaining' => $auction->time_remaining,
            'status' => ucfirst($auction->status),
            'history' => $history,
            'proxy' => $activeProxy
        ]);
    }
}
