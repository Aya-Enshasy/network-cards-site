<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_id',
        'package_id',
        'uploaded_by',
        'file_name',
        'imported_count',
        'duplicate_count',
        'failed_count',
        'sample_errors',
    ];

    protected function casts(): array
    {
        return [
            'sample_errors' => 'array',
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
