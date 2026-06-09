<?php

namespace Tests\Unit;

use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use App\Models\User;
use App\Services\CardImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use ReflectionMethod;
use Tests\TestCase;

class CardImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_multi_sheet_block_and_packed_card_layouts(): void
    {
        $service = new CardImportService();
        $method = new ReflectionMethod($service, 'parseCardRows');
        $method->setAccessible(true);

        $cards = $method->invoke($service, [
            [
                'sheet' => 'Block layout',
                'rows' => [
                    ['Username', '531014756150', 'Username', '572014773150'],
                    ['Password', '121127', 'Password', '175273'],
                    ['Package', '10 3 2', 'Package', '10 3 2'],
                ],
            ],
            [
                'sheet' => 'Packed layout',
                'rows' => [
                    [
                        "Username  777742667150\nPassword   734781\nPackage     10 ساعات 3 ميجا 2شيكل اشتراك يومي\nUsername  790133011150\nPassword   131713\nPackage     10 ساعات 3 ميجا 2شيكل اشتراك يومي",
                    ],
                ],
            ],
        ]);

        $this->assertCount(4, $cards);
        $this->assertSame('531014756150', $cards[0]['code']);
        $this->assertSame('121127', $cards[0]['password']);
        $this->assertSame('777742667150', $cards[2]['code']);
        $this->assertSame('734781', $cards[2]['password']);
    }

    public function test_it_bulk_imports_cards_and_reports_duplicates(): void
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
            'card_code' => 'EXISTING-CARD',
            'card_password' => 'old-pass',
            'status' => 'available',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'cards-');
        file_put_contents($path, implode("\n", [
            'Username,Password,Package',
            'EXISTING-CARD,new-pass,10 3 2',
            'NEW-CARD-1,pass-1,10 3 2',
            'NEW-CARD-2,pass-2,10 3 2',
            'NEW-CARD-2,pass-duplicate,10 3 2',
            'MISSING-PASSWORD,,10 3 2',
        ]));

        $summary = (new CardImportService())->import(
            new UploadedFile($path, 'cards.csv', 'text/csv', null, true),
            $network,
            $package,
            $owner->id,
        );

        $this->assertSame(2, $summary['imported']);
        $this->assertSame(2, $summary['duplicates']);
        $this->assertSame(1, $summary['failed']);
        $this->assertDatabaseHas('hotspot_cards', [
            'network_id' => $network->id,
            'card_code' => 'NEW-CARD-1',
            'card_password' => 'pass-1',
        ]);
        $this->assertDatabaseHas('card_imports', [
            'network_id' => $network->id,
            'imported_count' => 2,
            'duplicate_count' => 2,
            'failed_count' => 1,
        ]);
    }
}
