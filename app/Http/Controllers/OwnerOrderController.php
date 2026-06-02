<?php

namespace App\Http\Controllers;

use App\Models\HotspotCard;
use App\Models\Network;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class OwnerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $networkIds = $this->accessibleNetworkIds();
        $status = $request->query('status');

        $orders = Order::query()
            ->whereIn('network_id', $networkIds)
            ->when($status, fn ($query) => $query->where('order_status', $status))
            ->with(['network', 'items.package'])
            ->latest()
            ->paginate(15);

        return view('dashboard.orders.index', compact('orders', 'status'));
    }

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);

        $order->load(['network', 'items.package', 'receipt.reviewer', 'deliveredCards.package']);

        return view('dashboard.orders.show', compact('order'));
    }

    public function approve(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->order_status === 'completed') {
            return back()->with('success', 'هذا الطلب مكتمل مسبقًا.');
        }

        try {
            DB::transaction(function () use ($order): void {
                $order->load('items');
                $cardsToAttach = [];

                foreach ($order->items as $item) {
                    $cards = HotspotCard::query()
                        ->where('network_id', $order->network_id)
                        ->where('package_id', $item->package_id)
                        ->where('status', 'available')
                        ->orderByRaw('CASE WHEN imported_at IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('imported_at')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->limit($item->quantity)
                        ->get();

                    if ($cards->count() < $item->quantity) {
                        throw new \RuntimeException('المخزون غير كاف لإكمال الطلب.');
                    }

                    foreach ($cards as $card) {
                        $card->update(['status' => 'sold']);
                        $cardsToAttach[$card->id] = [
                            'card_code' => $card->card_code,
                            'card_password' => $card->card_password,
                            'package_label' => $card->package_label,
                        ];
                    }
                }

                $order->deliveredCards()->attach($cardsToAttach);
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'completed',
                    'rejection_reason' => null,
                ]);

                $order->receipt?->update([
                    'status' => 'approved',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'تمت الموافقة على الطلب وإظهار البطاقات للعميل.');
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update([
            'payment_status' => 'rejected',
            'order_status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'] ?? 'تم رفض وصل الدفع. يرجى التواصل مع صاحب الشبكة.',
        ]);

        $order->receipt?->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'تم رفض الطلب.');
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless(in_array($order->network_id, $this->accessibleNetworkIds(), true), 403);
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
