<?php

namespace App\Http\Controllers;

use App\Models\Network;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $packages = $network->activePackages()->pluck('id')->all();
        $cart = [];

        foreach ($data['quantities'] as $packageId => $quantity) {
            if ((int) $quantity > 0 && in_array((int) $packageId, $packages, true)) {
                $cart[(int) $packageId] = (int) $quantity;
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

        $cart = session("checkout.{$network->id}", []);

        if ($cart === []) {
            return redirect()->route('store.network', $network->slug)->with('error', 'اختر البطاقات أولا.');
        }

        $packages = $network->activePackages()
            ->whereIn('id', array_keys($cart))
            ->withCount('availableCards')
            ->get();

        if ($packages->isEmpty()) {
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
}
