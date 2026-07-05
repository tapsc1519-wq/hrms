<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AssetRepairAttachment extends Model
{
    protected $fillable = [
        'organization_id',
        'asset_repair_id',
        'uploaded_by',
        'type',
        'visibility',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function repair(): BelongsTo
    {
        return $this->belongsTo(AssetRepair::class, 'asset_repair_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFileSizeHumanAttribute(): string
    {
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 1) . ' MB';
        }

        return number_format(max(1, $this->file_size) / 1024, 1) . ' KB';
    }

    public function getTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }
}
