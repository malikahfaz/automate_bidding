<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionLot;
use App\Jobs\SyncAuctionJob;
use Illuminate\Http\Request;

class AdminAuctionController extends Controller
{
    public function index(Request $request)
    {
        $query = Auction::withCount('lots');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_event_id', 'like', "%{$search}%")
                    ->orWhere('auction_group', 'like', "%{$search}%");
            });
        }

        if ($request->filled('platform') && $request->platform !== 'all') {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $auctions = $query
            ->withMax('lots as highest_bid', 'current_bid')
            ->orderByDesc('external_event_id')
            ->paginate(12)
            ->withQueryString();

        $topLots = AuctionLot::whereIn('auction_id', $auctions->pluck('id'))
            ->orderByDesc('current_bid')
            ->get()
            ->groupBy('auction_id')
            ->map(fn ($group) => $group->first());

        $stats = [
            'total_events' => Auction::count(),
            'total_lots' => AuctionLot::count(),
            'active_events' => Auction::where('is_active', true)->where('status', 'active')->count(),
            'ivalua_events' => Auction::where('platform', 'ivalua')->count(),
            'featured' => Auction::where('is_featured', true)->count(),
            'sync_errors' => Auction::whereNotNull('last_sync_error')->count(),
        ];

        return view('admin.auctions.index', compact('auctions', 'stats', 'topLots'));
    }

    public function show($id)
    {
        $auction = Auction::with(['lots' => fn ($q) => $q->orderByDesc('current_bid')])
            ->withCount('lots')
            ->findOrFail($id);

        $highestBid = $auction->lots->max('current_bid') ?? 0;
        $topLot = $auction->lots->first();

        return view('admin.auctions.show', compact('auction', 'highestBid', 'topLot'));
    }

    public function create()
    {
        return view('admin.auctions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:bstock,ivalua',
            'external_url' => 'required|url',
            'title' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $eventId = null;
        if (preg_match('/auction_console\/(\d+)/', $request->external_url, $m)) {
            $eventId = $m[1];
        }

        $auction = Auction::create([
            'platform' => $request->platform,
            'external_event_id' => $eventId,
            'external_url' => $request->external_url,
            'browse_url' => $request->platform === 'ivalua'
                ? 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet'
                : null,
            'title' => $request->title ?? 'New ' . ucfirst($request->platform) . ' Event',
            'is_featured' => $request->has('is_featured'),
            'is_active' => $request->has('is_active'),
            'status' => 'active',
        ]);

        SyncAuctionJob::dispatch($auction->id);

        return redirect()->route('admin.auctions.index')
            ->with('success', 'Auction event added. Run import or sync to pull lots.');
    }

    public function edit($id)
    {
        $auction = Auction::findOrFail($id);
        return view('admin.auctions.edit', compact('auction'));
    }

    public function update(Request $request, $id)
    {
        $auction = Auction::findOrFail($id);

        $request->validate([
            'platform' => 'required|in:bstock,ivalua',
            'external_url' => 'required|url',
            'title' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'status' => 'required|in:active,ended,paused,failed',
        ]);

        $eventId = $auction->external_event_id;
        if (preg_match('/auction_console\/(\d+)/', $request->external_url, $m)) {
            $eventId = $m[1];
        }

        $auction->update([
            'platform' => $request->platform,
            'external_event_id' => $eventId,
            'external_url' => $request->external_url,
            'title' => $request->title,
            'is_featured' => $request->has('is_featured'),
            'is_active' => $request->has('is_active'),
            'status' => $request->status,
        ]);

        return redirect()->route('admin.auctions.index')
            ->with('success', 'Auction event updated successfully.');
    }

    public function destroy($id)
    {
        $auction = Auction::findOrFail($id);
        $auction->delete();

        return redirect()->route('admin.auctions.index')
            ->with('success', 'Auction event deleted successfully.');
    }

    public function syncSingle($id)
    {
        $auction = Auction::findOrFail($id);
        SyncAuctionJob::dispatch($auction->id);

        return back()->with('success', 'Sync job dispatched for all lots in this event.');
    }
}
