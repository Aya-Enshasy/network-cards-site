<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardPackage extends Model
{
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'network_id',
        'name',
        'duration_hours',
        'price',
        'speed',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(HotspotCard::class, 'package_id');
    }

    public function availableCards(): HasMany
    {
        return $this->cards()->where('status', 'available');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'package_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(CardImport::class, 'package_id');
    }
}
