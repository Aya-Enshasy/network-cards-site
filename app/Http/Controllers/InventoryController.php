<?php

namespace App\Http\Controllers;

use App\Models\CardPackage;
use App\Models\Network;
use App\Services\CardImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $networks = $this->accessibleNetworks();
        $network = $this->selectedNetwork($request, $networks);

        $packages = collect();

        if ($network) {
            $packages = $network->packages()
                ->withCount([
                    'cards',
                    'availableCards',
                    'cards as sold_cards_count' => fn ($query) => $query->where('status', 'sold'),
                ])
                ->orderBy('price')
                ->get();

            $imports = $network->cardImports()
                ->with(['package', 'uploader'])
                ->latest()
                ->limit(10)
                ->get();
        } else {
            $imports = collect();
        }

        return view('dashboard.inventory', compact('networks', 'network', 'packages', 'imports'));
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
            'wallet_number' => ['nullable', 'string', 'max:120'],
            'bank_account' => ['nullable', 'string', 'max:120'],
            'bank_transfer_details' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Network::findOrFail($data['network_id'])->update($data);

        return back()->with('success', 'تم تحديث بيانات الدفع.');
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
}
