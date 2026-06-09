<?php

namespace Tests\Feature;

use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_loads_with_a_valid_cart(): void
    {
        [$network, $package] = $this->storeFixture();

        $response = $this
            ->withSession(["checkout.{$network->id}" => [$package->id => 1]])
            ->get(route('checkout.show', $network->slug));

        $response
            ->assertOk()
            ->assertSee('data-cloudinary-receipt-form', false);
    }

    public function test_checkout_redirects_instead_of_crashing_with_a_stale_cart(): void
    {
        [$network, $package] = $this->storeFixture();

        $response = $this
            ->withSession(["checkout.{$network->id}" => [$package->id => ['bad']]])
            ->get(route('checkout.show', $network->slug));

        $response->assertRedirect(route('store.network', $network->slug));
        $this->assertSame([], session("checkout.{$network->id}", []));
    }

    /**
     * @return array{0: Network, 1: CardPackage}
     */
    private function storeFixture(): array
    {
        $owner = User::factory()->create(['role' => 'network_owner']);
        $network = Network::create([
            'owner_id' => $owner->id,
            'name' => 'Net Zone',
            'slug' => 'net-zone',
            'status' => 'active',
        ]);
        $package = CardPackage::create([
            'network_id' => $network->id,
            'name' => 'بطاقة 2 شيكل',
            'duration_hours' => 10,
            'price' => 2,
            'speed' => '3 ميجا',
            'active' => true,
        ]);

        HotspotCard::create([
            'network_id' => $network->id,
            'package_id' => $package->id,
            'card_code' => 'TEST-CARD-1',
            'card_password' => '123456',
            'status' => 'available',
        ]);

        return [$network, $package];
    }
}
