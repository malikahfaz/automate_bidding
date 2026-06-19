<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\ProxyBid;
use App\Models\PlatformAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Crypt;

class BiddingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $auction;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create standard test user
        $this->user = User::factory()->create([
            'role' => 'user'
        ]);

        // 2. Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // 3. Create dummy B-Stock platform account
        PlatformAccount::create([
            'platform' => 'bstock',
            'email' => 'sales@example.com',
            'encrypted_password' => Crypt::encryptString('Secret123%'),
            'status' => 'active'
        ]);

        // 4. Create active B-Stock auction
        $this->auction = Auction::create([
            'platform' => 'bstock',
            'external_url' => 'https://bids.ellectmobility.com/storefront/bstock?id=9999',
            'title' => 'Test Samsung Pallet',
            'current_bid' => 1000.00,
            'bid_increment' => 50.00,
            'time_remaining' => '2d 4h',
            'status' => 'active',
            'is_active' => true
        ]);
    }

    /**
     * Guest user cannot view my-bids or place bids.
     */
    public function test_guests_cannot_access_bidding_routes()
    {
        $response = $this->get(route('my-bids'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('auctions.bid', $this->auction->id), ['amount' => 1100]);
        $response->assertRedirect(route('login'));
    }

    /**
     * Placing a valid bid dispatches place bid action successfully.
     */
    public function test_user_can_place_valid_bid()
    {
        $response = $this->actingAs($this->user)
            ->post(route('auctions.bid', $this->auction->id), [
                'amount' => 1100.00 // current (1000) + inc (50) = 1050 min
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        // Assert bid was stored in DB (in testing it gets processed instantly)
        $this->assertDatabaseHas('bids', [
            'auction_id' => $this->auction->id,
            'user_id' => $this->user->id,
            'amount' => 1100.00,
            'status' => 'successful',
            'bid_type' => 'normal'
        ]);
    }

    /**
     * Bid below minimum value fails validation.
     */
    public function test_bid_amount_validation()
    {
        $response = $this->actingAs($this->user)
            ->post(route('auctions.bid', $this->auction->id), [
                'amount' => 1020.00 // less than 1050 min
            ]);

        $response->assertSessionHasErrors('amount');
        
        $this->assertDatabaseMissing('bids', [
            'auction_id' => $this->auction->id,
            'amount' => 1020.00
        ]);
    }

    /**
     * Active Ivalua proxy bidding creation works.
     */
    public function test_user_can_set_ivalua_proxy_bid()
    {
        // Create Ivalua auction
        $ivaluaAuction = Auction::create([
            'platform' => 'ivalua',
            'external_url' => 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet?id=88',
            'title' => 'Ivalua Auction Test',
            'current_bid' => 500.00,
            'bid_increment' => 20.00,
            'status' => 'active',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('auctions.proxy', $ivaluaAuction->id), [
                'max_amount' => 1000.00
            ]);

        $response->assertSessionHasNoErrors();
        
        // Assert proxy bid is stored
        $this->assertDatabaseHas('proxy_bids', [
            'auction_id' => $ivaluaAuction->id,
            'user_id' => $this->user->id,
            'max_amount' => 1000.00,
            'status' => 'active'
        ]);
    }

    /**
     * Standard users cannot access admin panel.
     */
    public function test_non_admin_cannot_access_admin_panel()
    {
        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    /**
     * Admin can access admin panel dashboard.
     */
    public function test_admin_can_access_admin_panel()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }
}
