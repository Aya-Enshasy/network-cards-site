<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminNetworkController extends Controller
{
    public function index(): View
    {
        $networks = Network::query()
            ->with('owner')
            ->withCount(['orders', 'cards'])
            ->latest()
            ->get();

        return view('admin.networks.index', compact('networks'));
    }

    public function create(): View
    {
        return view('admin.networks.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:150'],
            'owner_phone' => ['nullable', 'string', 'max:30'],
            'owner_password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'wallet_number' => ['nullable', 'string', 'max:120'],
            'bank_account' => ['nullable', 'string', 'max:120'],
            'bank_transfer_details' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $owner = User::updateOrCreate(
            ['email' => $data['owner_email']],
            [
                'name' => $data['owner_name'],
                'phone' => $data['owner_phone'] ?? null,
                'password' => Hash::make($data['owner_password']),
                'role' => 'network_owner',
            ],
        );

        Network::create([
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'wallet_number' => $data['wallet_number'] ?? null,
            'bank_account' => $data['bank_account'] ?? null,
            'bank_transfer_details' => $data['bank_transfer_details'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.networks.index')->with('success', 'تم إنشاء الشبكة وصاحبها.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $counter = 2;

        while (Network::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
