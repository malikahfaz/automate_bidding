<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Jobs\SyncAuctionJob;
use Illuminate\Http\Request;

class AdminAuctionController extends Controller
{
    /**
     * Display a listing of auctions.
     */
    public function index()
    {
        $auctions = Auction::latest()->paginate(15);
        return view('admin.auctions.index', compact('auctions'));
    }

    /**
     * Show the form for creating a new auction.
     */
    public function create()
    {
        return view('admin.auctions.create');
    }

    /**
     * Store a newly created auction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:bstock,ivalua',
            'external_url' => 'required|url',
            'title' => 'nullable|string|max:255',
            'current_bid' => 'nullable|numeric|min:0',
            'bid_increment' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'is_active' => 'boolean'
        ]);

        $auction = Auction::create([
            'platform' => $request->platform,
            'external_url' => $request->external_url,
            'title' => $request->title ?? 'New ' . ucfirst($request->platform) . ' Auction',
            'current_bid' => $request->current_bid ?? 0.00,
            'bid_increment' => $request->bid_increment ?? 0.00,
            'is_featured' => $request->has('is_featured'),
            'is_active' => $request->has('is_active'),
            'status' => 'active'
        ]);

        // Immediately queue a sync job to pull live details
        SyncAuctionJob::dispatch($auction->id);

        return redirect()->route('admin.auctions.index')
            ->with('success', 'Auction URL added successfully! Details will sync in the background.');
    }

    /**
     * Show the form for editing the specified auction.
     */
    public function edit($id)
    {
        $auction = Auction::findOrFail($id);
        return view('admin.auctions.edit', compact('auction'));
    }

    /**
     * Update the specified auction.
     */
    public function update(Request $request, $id)
    {
        $auction = Auction::findOrFail($id);

        $request->validate([
            'platform' => 'required|in:bstock,ivalua',
            'external_url' => 'required|url',
            'title' => 'nullable|string|max:255',
            'current_bid' => 'required|numeric|min:0',
            'bid_increment' => 'required|numeric|min:0',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'status' => 'required|in:active,ended,paused,failed'
        ]);

        $auction->update([
            'platform' => $request->platform,
            'external_url' => $request->external_url,
            'title' => $request->title,
            'current_bid' => $request->current_bid,
            'bid_increment' => $request->bid_increment,
            'is_featured' => $request->has('is_featured'),
            'is_active' => $request->has('is_active'),
            'status' => $request->status
        ]);

        return redirect()->route('admin.auctions.index')
            ->with('success', 'Auction details updated successfully.');
    }

    /**
     * Delete the specified auction.
     */
    public function destroy($id)
    {
        $auction = Auction::findOrFail($id);
        $auction->delete();

        return redirect()->route('admin.auctions.index')
            ->with('success', 'Auction deleted successfully.');
    }

    /**
     * Manually trigger a sync for a single auction.
     */
    public function syncSingle($id)
    {
        $auction = Auction::findOrFail($id);
        SyncAuctionJob::dispatch($auction->id);

        return back()->with('success', 'Sync job dispatched! Refresh in a few seconds to see updated details.');
    }
}
