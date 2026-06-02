<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(): View
    {
        $networkIds = $this->accessibleNetworkIds();

        $paidOrders = Order::query()
            ->whereIn('network_id', $networkIds)
            ->where('payment_status', 'paid')
            ->get(['id', 'total_amount', 'created_at']);

        $dailySales = collect(range(6, 0))->map(function (int $daysAgo) use ($paidOrders) {
            $date = today()->subDays($daysAgo);

            return [
                'label' => $date->format('m/d'),
                'amount' => $paidOrders
                    ->filter(fn (Order $order) => $order->created_at->isSameDay($date))
                    ->sum('total_amount'),
            ];
        });

        $monthlySales = collect(range(5, 0))->map(function (int $monthsAgo) use ($paidOrders) {
            $month = today()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $month->format('Y-m'),
                'amount' => $paidOrders
                    ->filter(fn (Order $order) => $order->created_at->format('Y-m') === $month->format('Y-m'))
                    ->sum('total_amount'),
            ];
        });

        $topPackages = OrderItem::query()
            ->whereHas('order', fn ($query) => $query
                ->whereIn('network_id', $networkIds)
                ->where('payment_status', 'paid'))
            ->with('package')
            ->get()
            ->groupBy('package_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->package?->name ?? 'باقة محذوفة',
                    'quantity' => $items->sum('quantity'),
                    'amount' => $items->sum('subtotal'),
                ];
            })
            ->sortByDesc('quantity')
            ->take(5)
            ->values();

        return view('dashboard.reports', compact('dailySales', 'monthlySales', 'topPackages'));
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
