<?php

namespace Tests\Unit;

use App\Services\CardImportService;
use ReflectionMethod;
use Tests\TestCase;

class CardImportServiceTest extends TestCase
{
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
}
