<?php

namespace Tests\Feature;

use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use App\Models\Order;
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

    public function test_customer_can_submit_checkout_order(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo-cloud',
            'services.cloudinary.api_key' => 'demo-key',
            'services.cloudinary.api_secret' => 'demo-secret',
            'services.cloudinary.receipt_folder' => 'network-site/payment-receipts',
        ]);

        [$network, $package] = $this->storeFixture();
        $publicId = 'network-site/payment-receipts/net-zone/2026/06/test-receipt';

        $response = $this
            ->withSession(["checkout.{$network->id}" => [$package->id => 1]])
            ->post(route('orders.store', $network->slug), [
                'customer_name' => 'Test Customer',
                'phone' => '0599000000',
                'notes' => 'Test note',
                'receipt_url' => "https://res.cloudinary.com/demo-cloud/image/upload/v1780000000/{$publicId}.jpg",
                'receipt_public_id' => $publicId,
                'receipt_original_name' => 'receipt.jpg',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test Customer',
            'phone' => '0599000000',
            'network_id' => $network->id,
            'total_amount' => 2,
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);
        $this->assertDatabaseHas('payment_receipts', [
            'image_public_id' => $publicId,
            'status' => 'pending',
        ]);
        $this->assertNotNull(Order::first()?->access_token);
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
