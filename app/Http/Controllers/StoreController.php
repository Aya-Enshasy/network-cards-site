<?php

namespace App\Http\Controllers;

use App\Models\Network;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function home(): View
    {
        $network = Network::query()
            ->where('status', 'active')
            ->with(['activePackages' => fn ($query) => $query->withCount('availableCards')])
            ->first();

        $networks = Network::query()->where('status', 'active')->orderBy('name')->get();

        return view('store.index', [
            'network' => $network,
            'networks' => $networks,
        ]);
    }

    public function show(Network $network): View
    {
        abort_if($network->status !== 'active', 404);

        $network->load(['activePackages' => fn ($query) => $query->withCount('availableCards')]);
        $networks = Network::query()->where('status', 'active')->orderBy('name')->get();

        return view('store.index', compact('network', 'networks'));
    }

    public function startCheckout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $network = Network::query()->where('status', 'active')->findOrFail($data['network_id']);
        $packages = $network->activePackages()
            ->withCount('availableCards')
            ->get()
            ->keyBy('id');
        $cart = [];

        foreach ($data['quantities'] as $packageId => $quantity) {
            $package = $packages->get((int) $packageId);
            $availableCards = (int) ($package?->available_cards_count ?? 0);
            $quantity = (int) $quantity;

            if ($package && $quantity > 0 && $availableCards > 0) {
                $cart[(int) $package->id] = min($quantity, $availableCards, 100);
            }
        }

        if ($cart === []) {
            return back()->with('error', 'اختر بطاقة واحدة على الأقل لإكمال الطلب.');
        }

        session(["checkout.{$network->id}" => $cart]);

        return redirect()->route('checkout.show', $network->slug);
    }

    public function checkout(Network $network): View|RedirectResponse
    {
        abort_if($network->status !== 'active', 404);

        [$cart, $packages] = $this->checkoutSelection($network, session("checkout.{$network->id}", []));

        if ($cart === []) {
            session()->forget("checkout.{$network->id}");

            return redirect()->route('store.network', $network->slug)->with('error', 'اختر البطاقات أولا.');
        }

        session(["checkout.{$network->id}" => $cart]);

        if ($packages->isEmpty()) {
            session()->forget("checkout.{$network->id}");

            return redirect()->route('store.network', $network->slug)->with('error', 'الباقات المختارة غير متاحة حاليا.');
        }

        $summary = $packages->map(function ($package) use ($cart) {
            $quantity = $cart[$package->id] ?? 0;

            return [
                'package' => $package,
                'quantity' => $quantity,
                'subtotal' => $package->price * $quantity,
            ];
        });

        return view('store.checkout', [
            'network' => $network,
            'summary' => $summary,
            'total' => $summary->sum('subtotal'),
        ]);
    }

    /**
     * @return array{0: array<int, int>, 1: Collection<int, \App\Models\CardPackage>}
     */
    private function checkoutSelection(Network $network, mixed $rawCart): array
    {
        if (! is_array($rawCart)) {
            return [[], collect()];
        }

        $packageIds = collect(array_keys($rawCart))
            ->map(fn (mixed $packageId): int => (int) $packageId)
            ->filter(fn (int $packageId): bool => $packageId > 0)
            ->unique()
            ->values()
            ->all();

        if ($packageIds === []) {
            return [[], collect()];
        }

        $packages = $network->activePackages()
            ->whereIn('id', $packageIds)
            ->withCount('availableCards')
            ->get()
            ->keyBy('id');
        $cart = [];

        foreach ($rawCart as $packageId => $quantity) {
            if (! is_scalar($quantity)) {
                continue;
            }

            $package = $packages->get((int) $packageId);
            $quantity = (int) $quantity;
            $availableCards = (int) ($package?->available_cards_count ?? 0);

            if (! $package || $quantity <= 0 || $availableCards <= 0) {
                continue;
            }

            $cart[(int) $package->id] = min($quantity, $availableCards, 100);
        }

        $selectedPackages = $packages
            ->filter(fn ($package): bool => array_key_exists((int) $package->id, $cart))
            ->values();

        return [$cart, $selectedPackages];
    }
}
