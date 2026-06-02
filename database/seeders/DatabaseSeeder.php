<?php

namespace Database\Seeders;

use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@vinex.test'],
            [
                'name' => 'مدير النظام',
                'phone' => '0599000000',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ],
        );

        $owner = User::updateOrCreate(
            ['email' => 'owner@gptnet.test'],
            [
                'name' => 'صاحب شبكة GPT Net',
                'phone' => '0599111222',
                'password' => Hash::make('password'),
                'role' => 'network_owner',
            ],
        );

        $network = Network::updateOrCreate(
            ['slug' => 'gpt-net'],
            [
                'owner_id' => $owner->id,
                'name' => 'GPT Net',
                'description' => 'بطاقات إنترنت جاهزة للدفع عبر جوال باي أو بنك فلسطين.',
                'status' => 'active',
                'wallet_number' => '0599 123 456',
                'bank_account' => 'PS92 PALS 0000 0000 1234 5678',
                'bank_transfer_details' => 'التحويل البنكي متاح بعد التواصل مع صاحب الشبكة لتأكيد اسم المحول.',
            ],
        );

        $packages = collect([
            [
                'name' => 'بطاقة 2 شيكل',
                'duration_hours' => 10,
                'price' => 2,
                'speed' => 'تحميل مفتوح',
                'description' => 'مناسبة للاستخدام السريع والدراسة.',
                'prefix' => 'GPT2',
                'stock' => 140,
            ],
            [
                'name' => 'بطاقة 1 شيكل',
                'duration_hours' => 8,
                'price' => 1,
                'speed' => 'تصفح مرن',
                'description' => 'خيار اقتصادي وسريع للاستخدام الخفيف.',
                'prefix' => 'GPT1',
                'stock' => 110,
            ],
        ])->map(function (array $data) use ($network) {
            $package = CardPackage::updateOrCreate(
                [
                    'network_id' => $network->id,
                    'name' => $data['name'],
                ],
                [
                    'duration_hours' => $data['duration_hours'],
                    'price' => $data['price'],
                    'speed' => $data['speed'],
                    'description' => $data['description'],
                    'active' => true,
                ],
            );

            for ($index = 1; $index <= $data['stock']; $index++) {
                HotspotCard::updateOrCreate(
                    [
                        'network_id' => $network->id,
                        'card_code' => sprintf('%s-%04d', $data['prefix'], $index),
                    ],
                    [
                        'package_id' => $package->id,
                        'card_password' => sprintf('%s-PASS-%04d', $data['prefix'], $index),
                        'status' => 'available',
                    ],
                );
            }

            return $package;
        });

        $network->packages()
            ->whereNotIn('name', $packages->pluck('name')->all())
            ->get()
            ->each(function (CardPackage $package): void {
                if ($package->orderItems()->exists()) {
                    $package->update(['active' => false]);

                    return;
                }

                $package->cards()->delete();
                $package->delete();
            });

        $sampleOrder = Order::whereIn('order_number', ['ORD-2026-000001', 'GPT-260601-1001'])->first() ?? new Order();
        $sampleOrder->fill([
            'order_number' => 'ORD-2026-000001',
            'access_token' => $sampleOrder->access_token ?: Str::upper(Str::random(48)),
            'customer_name' => 'عميل تجريبي',
            'phone' => '0599333444',
            'notes' => 'طلب تجريبي لمراجعة شاشة الموافقة.',
            'network_id' => $network->id,
            'total_amount' => 3,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'rejection_reason' => null,
        ])->save();

        $sampleOrder->items()->delete();
        $sampleOrder->items()->create([
            'package_id' => $packages[0]->id,
            'quantity' => 1,
            'price' => $packages[0]->price,
            'subtotal' => $packages[0]->price,
        ]);

        $sampleOrder->items()->create([
            'package_id' => $packages[1]->id,
            'quantity' => 1,
            'price' => $packages[1]->price,
            'subtotal' => $packages[1]->price,
        ]);

        $sampleOrder->receipt()->firstOrCreate([
            'status' => 'pending',
        ], [
            'notes' => 'طلب تجريبي لمراجعة شاشة الموافقة.',
        ]);
    }
}
