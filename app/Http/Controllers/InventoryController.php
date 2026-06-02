<?php

namespace App\Http\Controllers;

use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use App\Services\CardImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $networks = $this->accessibleNetworks();
        $network = $this->selectedNetwork($request, $networks);

        $packages = collect();
        $cards = collect();
        if ($network) {
            $packages = $network->packages()
                ->withCount([
                    'cards',
                    'availableCards',
                    'cards as sold_cards_count' => fn ($query) => $query->where('status', 'sold'),
                ])
                ->orderBy('price')
                ->get();

            $cards = HotspotCard::query()
                ->where('hotspot_cards.network_id', $network->id)
                ->with('package')
                ->leftJoin('order_cards', 'order_cards.hotspot_card_id', '=', 'hotspot_cards.id')
                ->leftJoin('orders', 'orders.id', '=', 'order_cards.order_id')
                ->select('hotspot_cards.*', 'orders.order_number as used_order_number', 'orders.customer_name as used_customer_name')
                ->orderByRaw("CASE WHEN hotspot_cards.status = 'sold' THEN 0 ELSE 1 END")
                ->orderByRaw('CASE WHEN hotspot_cards.imported_at IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('hotspot_cards.imported_at')
                ->orderByDesc('hotspot_cards.id')
                ->limit(1000)
                ->get();

            $imports = $network->cardImports()
                ->with(['package', 'uploader'])
                ->latest()
                ->limit(10)
                ->get();
        } else {
            $imports = collect();
        }

        return view('dashboard.inventory', compact('networks', 'network', 'packages', 'imports', 'cards'));
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $networkIds = $this->accessibleNetworks()->pluck('id')->all();

        $data = $request->validate([
            'network_id' => ['required', Rule::in($networkIds)],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('packages', 'name')->where(fn ($query) => $query->where('network_id', $request->input('network_id'))),
            ],
            'duration_hours' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'speed' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);

        CardPackage::create([
            ...$data,
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', 'تمت إضافة الباقة.');
    }

    public function updatePackage(Request $request, CardPackage $package): RedirectResponse
    {
        $this->authorizePackage($package);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('packages', 'name')
                    ->ignore($package->id)
                    ->where(fn ($query) => $query->where('network_id', $package->network_id)),
            ],
            'duration_hours' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'speed' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $package->update([
            ...$data,
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'تم تحديث الباقة.');
    }

    public function destroyPackage(CardPackage $package): RedirectResponse
    {
        $this->authorizePackage($package);

        if ($package->cards()->exists() || $package->orderItems()->exists()) {
            $package->update(['active' => false]);

            return back()->with('success', 'تم إخفاء الباقة لأنها تحتوي على بطاقات أو طلبات مرتبطة.');
        }

        $package->delete();

        return back()->with('success', 'تم حذف الباقة.');
    }

    public function importCards(Request $request, CardImportService $importer): RedirectResponse
    {
        $networkIds = $this->accessibleNetworks()->pluck('id')->all();

        $data = $request->validate([
            'network_id' => ['required', Rule::in($networkIds)],
            'package_id' => ['required', 'exists:packages,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $network = Network::findOrFail($data['network_id']);
        $package = CardPackage::where('network_id', $network->id)
            ->where('active', true)
            ->findOrFail($data['package_id']);
        $summary = $importer->import($request->file('file'), $network, $package, auth()->id());

        return back()
            ->with('success', "تم استيراد {$summary['imported']} بطاقة. المكرر: {$summary['duplicates']}، الفاشل: {$summary['failed']}.")
            ->with('import_errors', array_slice($summary['errors'], 0, 10));
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $networkIds = $this->accessibleNetworks()->pluck('id')->all();

        $data = $request->validate([
            'network_id' => ['required', Rule::in($networkIds)],
            'name' => ['required', 'string', 'max:120'],
            'owner_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'wallet_number' => ['nullable', 'string', 'max:120'],
            'bank_account' => ['nullable', 'string', 'max:120'],
            'bank_transfer_details' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $network = Network::findOrFail($data['network_id']);
        $user = auth()->user();

        $networkData = [
            'name' => $data['name'],
            'slug' => $this->uniqueNetworkSlug($data['name'], $network->id),
            'wallet_number' => $data['wallet_number'] ?? null,
            'bank_account' => $data['bank_account'] ?? null,
            'bank_transfer_details' => $data['bank_transfer_details'] ?? null,
            'description' => $data['description'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            if ($network->logo) {
                Storage::disk('public')->delete($network->logo);
            }

            $networkData['logo'] = $request->file('logo')->store('network-logos', 'public');
        }

        $network->update($networkData);

        $userData = ['name' => $data['owner_name']];

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $userData['avatar'] = $request->file('avatar')->store('owner-avatars', 'public');
        }

        $user->update($userData);

        return back()->with('success', 'تم تحديث بروفايل الشركة.');
    }

    private function selectedNetwork(Request $request, Collection $networks): ?Network
    {
        $selectedId = (int) $request->query('network_id');

        if ($selectedId > 0) {
            return $networks->firstWhere('id', $selectedId) ?? $networks->first();
        }

        return $networks->first();
    }

    private function accessibleNetworks(): Collection
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Network::query()->orderBy('name')->get();
        }

        return $user->networks()->orderBy('name')->get();
    }

    private function authorizePackage(CardPackage $package): void
    {
        abort_unless($this->accessibleNetworks()->pluck('id')->contains($package->network_id), 403);
    }

    private function uniqueNetworkSlug(string $name, int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'network';
        $slug = $base;
        $counter = 2;

        while (Network::query()->where('slug', $slug)->whereKeyNot($ignoreId)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
