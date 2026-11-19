<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosImport extends Model
{
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'original_filename',
        'stored_path',
        'file_hash',
        'status',
        'rows_processed',
        'rows_successful',
        'rows_failed',
        'failure_reason',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(PosImportError::class);
    }
}
