<?php

namespace App\Http\Controllers;

use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $networkIds = $this->accessibleNetworkIds();

        $todayOrders = Order::query()
            ->whereIn('network_id', $networkIds)
            ->whereDate('created_at', today())
            ->count();

        $todaySales = Order::query()
            ->whereIn('network_id', $networkIds)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', today())
            ->sum('total_amount');

        $availableCards = HotspotCard::query()
            ->whereIn('network_id', $networkIds)
            ->where('status', 'available')
            ->count();

        $pendingOrders = Order::query()
            ->whereIn('network_id', $networkIds)
            ->where('order_status', 'pending')
            ->count();

        $recentOrders = Order::query()
            ->whereIn('network_id', $networkIds)
            ->with(['network', 'items.package'])
            ->latest()
            ->limit(8)
            ->get();

        $lowStockPackages = CardPackage::query()
            ->whereIn('network_id', $networkIds)
            ->with('network')
            ->withCount('availableCards')
            ->get()
            ->filter(fn (CardPackage $package) => $package->available_cards_count < 50)
            ->values();

        $networks = Network::query()
            ->whereIn('id', $networkIds)
            ->withCount(['orders', 'cards'])
            ->orderBy('name')
            ->get();

        return view('dashboard.index', compact(
            'todayOrders',
            'todaySales',
            'availableCards',
            'pendingOrders',
            'recentOrders',
            'lowStockPackages',
            'networks',
        ));
    }

    /**
     * @return array<int, int>
     */
    private function accessibleNetworkIds(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Network::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $user->networks()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
