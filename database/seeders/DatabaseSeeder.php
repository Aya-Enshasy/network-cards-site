<?php

namespace Database\Seeders;

use App\Models\CardPackage;
use App\Models\Network;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
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
                'name' => 'صاحب Net Zone',
                'phone' => '0599111222',
                'password' => Hash::make('password'),
                'role' => 'network_owner',
            ],
        );

        $network = Network::updateOrCreate(
            ['owner_id' => $owner->id],
            [
                'name' => 'Net Zone',
                'slug' => 'net-zone',
                'description' => 'متجر بطاقات إنترنت سريع وآمن.',
                'status' => 'active',
                'wallet_number' => '0599 123 456',
                'bank_account' => 'PS92 PALS 0000 0000 1234 5678',
                'bank_transfer_details' => 'حوّل المبلغ ثم ارفع صورة الوصل من صفحة الدفع.',
            ],
        );

        $package = CardPackage::updateOrCreate(
            [
                'network_id' => $network->id,
                'name' => 'بطاقة 2 شيكل',
            ],
            [
                'duration_hours' => 10,
                'price' => 2,
                'speed' => '3 ميجا',
                'description' => 'اشتراك يومي 10 ساعات بسرعة 3 ميجا.',
                'active' => true,
            ],
        );

        $network->packages()
            ->whereKeyNot($package->id)
            ->get()
            ->each(function (CardPackage $stalePackage) use ($package): void {
                $stalePackage->cards()->update(['package_id' => $package->id]);
                $stalePackage->orderItems()->update(['package_id' => $package->id]);
                $stalePackage->delete();
            });
    }
}
