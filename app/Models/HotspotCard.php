<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HotspotCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_id',
        'package_id',
        'card_code',
        'card_password',
        'package_label',
        'imported_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CardPackage::class, 'package_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_cards')
            ->withPivot(['card_code', 'card_password', 'package_label'])
            ->withTimestamps();
    }
}
