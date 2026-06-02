<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Network extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'logo',
        'description',
        'status',
        'wallet_number',
        'bank_account',
        'bank_transfer_details',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(CardPackage::class);
    }

    public function activePackages(): HasMany
    {
        return $this->packages()->where('active', true);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(HotspotCard::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cardImports(): HasMany
    {
        return $this->hasMany(CardImport::class);
    }
}
