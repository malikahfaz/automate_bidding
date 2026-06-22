<?php

namespace App\Http\Controllers;

use App\Models\AuctionLot;
use App\Models\Bid;
use App\Models\ProxyBid;
use App\Models\BidHistory;
use App\Jobs\PlaceBidJob;
use App\Jobs\IvaluaProxyBidJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuctionController extends Controller
{
    public function index(Request $request)
    {
        $query = AuctionLot::with('auction');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('external_lot_id', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('platform') && $request->platform !== 'all') {
            $query->whereHas('auction', fn ($q) => $q->where('platform', $request->platform));
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->where('is_active', true);
        }

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

        $lots = $query->paginate(12)->withQueryString();

        return view('auctions.index', compact('lots'));
    }

    public function show($id)
    {
        $lot = AuctionLot::with([
            'auction',
            'bidHistories.user',
            'bids.user',
            'activeProxyBid' => function ($q) {
                $q->where('user_id', Auth::id());
            },
        ])->findOrFail($id);

        $consoleId = null;
        if (preg_match('/auction_console\/(\d+)/i', $lot->auction->external_url ?? '', $m)) {
            $consoleId = $m[1];
        }

        $consoleLots = AuctionLot::where('auction_id', $lot->auction_id)
            ->orderBy('external_lot_id')
            ->get(['id', 'external_lot_id', 'title', 'description', 'quantity', 'cosmetic_grade', 'current_bid', 'is_active', 'status']);

        return view('auctions.show', compact('lot', 'consoleId', 'consoleLots'));
    }

    public function placeBid(Request $request, $id)
    {
        $lot = AuctionLot::with('auction')->findOrFail($id);
        $auction = $lot->auction;

        if (!$lot->is_active || $lot->status !== 'active') {
            return back()->with('error', 'This lot is no longer active.');
        }

        $minBid = $lot->current_bid + $lot->bid_increment;

        $request->validate([
            'amount' => 'required|numeric|min:' . $minBid,
            'external_lot_id' => 'required|string|in:' . $lot->external_lot_id,
        ], [
            'amount.min' => 'Your bid must be at least $' . number_format($minBid, 2) . ' (current bid + increment). Price is refreshed every ~10s from Ivalua; the job re-checks live price before placing.',
            'external_lot_id.in' => 'Lot ID mismatch — please refresh this page and bid again.',
        ]);

        $hasPending = Bid::where('auction_lot_id', $lot->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($hasPending) {
            return back()->with('error', 'A bid is currently being processed for this lot. Please wait a few seconds and try again.');
        }

        $bid = Bid::create([
            'auction_id' => $auction->id,
            'auction_lot_id' => $lot->id,
            'user_id' => Auth::id(),
            'bid_type' => 'normal',
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        PlaceBidJob::dispatch($bid->id)->onQueue('bids');

        $consoleHint = '';
        if (preg_match('/auction_console\/(\d+)/i', $auction->external_url ?? '', $m)) {
            $consoleHint = " on Ivalua console {$m[1]}";
        }

        return back()->with('success', "Your bid on lot {$lot->external_lot_id}{$consoleHint} is queued — it will be placed within a few seconds.");
    }

    public function setProxyBid(Request $request, $id)
    {
        $lot = AuctionLot::with('auction')->findOrFail($id);
        $auction = $lot->auction;

        if ($auction->platform !== 'ivalua') {
            return back()->with('error', 'Custom proxy bidding is only supported on Ivalua auctions.');
        }

        if (!$lot->is_active || $lot->status !== 'active') {
            return back()->with('error', 'This lot is no longer active.');
        }

        $minBid = $lot->current_bid + $lot->bid_increment;

        $request->validate([
            'max_amount' => 'required|numeric|min:' . $minBid,
        ], [
            'max_amount.min' => 'Max bid must be at least ' . number_format($minBid, 2) . ' (Current bid + bid increment).',
        ]);

        ProxyBid::where('auction_lot_id', $lot->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->update([
                'status' => 'stopped',
                'stopped_at' => now(),
                'stop_reason' => 'Replaced by new proxy configuration.',
            ]);

        ProxyBid::create([
            'auction_id' => $auction->id,
            'auction_lot_id' => $lot->id,
            'user_id' => Auth::id(),
            'max_amount' => $request->max_amount,
            'current_auto_bid' => $lot->current_bid,
            'status' => 'active',
            'started_at' => now(),
        ]);

        IvaluaProxyBidJob::dispatch($lot->id);

        return back()->with('success', 'Custom proxy bidding has been activated. The system will now auto-bid up to ' . number_format($request->max_amount, 2));
    }

    public function cancelProxyBid($id)
    {
        $lot = AuctionLot::findOrFail($id);

        $proxy = ProxyBid::where('auction_lot_id', $lot->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->firstOrFail();

        $proxy->update([
            'status' => 'cancelled',
            'stopped_at' => now(),
            'stop_reason' => 'Cancelled by user.',
        ]);

        return back()->with('success', 'Proxy bidding has been cancelled.');
    }

    public function myBids()
    {
        $bids = Bid::with(['lot.auction', 'auction'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('auctions.my-bids', compact('bids'));
    }

    public function myProxies()
    {
        $proxies = ProxyBid::with(['lot.auction', 'auction'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('auctions.my-proxies', compact('proxies'));
    }

    public function pollState($id)
    {
        $lot = AuctionLot::with('auction')->findOrFail($id);
        $auction = $lot->auction;

        $history = BidHistory::with('user')
            ->where('auction_lot_id', $lot->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($item) {
                return [
                    'amount' => number_format($item->amount, 2),
                    'source' => ucfirst($item->source),
                    'user' => $item->user ? $item->user->name : 'External Bidder',
                    'time' => $item->created_at->diffForHumans(),
                    'status' => $item->status,
                ];
            });

        $activeProxy = null;
        $myBid = null;
        if (Auth::check()) {
            $proxyObj = ProxyBid::where('auction_lot_id', $lot->id)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();
            if ($proxyObj) {
                $activeProxy = [
                    'max_amount' => number_format($proxyObj->max_amount, 2),
                    'current_auto_bid' => number_format($proxyObj->current_auto_bid, 2),
                ];
            }

            $latestBid = Bid::where('auction_lot_id', $lot->id)
                ->where('user_id', Auth::id())
                ->latest()
                ->first();
            if ($latestBid) {
                $myBid = [
                    'amount' => number_format($latestBid->amount, 2),
                    'status' => $latestBid->status,
                    'failure_reason' => $latestBid->failure_reason,
                    'updated' => $latestBid->updated_at->diffForHumans(),
                ];
            }
        }

        return response()->json([
            'current_bid' => number_format($lot->current_bid, 2),
            'bid_increment' => number_format($lot->bid_increment, 2),
            'time_remaining' => $lot->time_remaining,
            'status' => ucfirst($lot->status),
            'event' => $auction->auction_group ?? $auction->external_event_id,
            'history' => $history,
            'proxy' => $activeProxy,
            'my_bid' => $myBid,
        ]);
    }
}
