<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosImportError extends Model
{
    protected $fillable = [
        'pos_import_id',
        'row_number',
        'row_data',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'row_data' => 'array',
        ];
    }

    public function posImport(): BelongsTo
    {
        return $this->belongsTo(PosImport::class);
    }
}
