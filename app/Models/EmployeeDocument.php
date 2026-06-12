<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'organization_id',
        'employee_profile_id',
        'uploaded_by',
        'document_type',
        'title',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->document_type));
    }

    public function getFileSizeHumanAttribute(): string
    {
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 1) . ' MB';
        }

        return number_format(max($this->file_size, 1) / 1024, 1) . ' KB';
    }

    public function getFileIconAttribute(): string
    {
        return match (true) {
            str_contains((string) $this->mime_type, 'pdf') => 'bi-file-earmark-pdf',
            str_contains((string) $this->mime_type, 'image') => 'bi-file-earmark-image',
            str_contains((string) $this->mime_type, 'word') => 'bi-file-earmark-word',
            str_contains((string) $this->mime_type, 'spreadsheet'), str_contains((string) $this->mime_type, 'excel') => 'bi-file-earmark-excel',
            default => 'bi-file-earmark',
        };
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
