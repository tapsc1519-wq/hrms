<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id', 'reply_id', 'user_id',
        'original_name', 'file_path', 'mime_type', 'file_size',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(TicketReply::class, 'reply_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function getFileIconAttribute(): string
    {
        $mime = $this->mime_type ?? '';
        if (str_starts_with($mime, 'image/'))        return 'bi-file-earmark-image';
        if ($mime === 'application/pdf')             return 'bi-file-earmark-pdf';
        if (str_contains($mime, 'word'))             return 'bi-file-earmark-word';
        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) return 'bi-file-earmark-excel';
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed'))    return 'bi-file-earmark-zip';
        return 'bi-file-earmark';
    }

    public function getIconColorAttribute(): string
    {
        $mime = $this->mime_type ?? '';
        if (str_starts_with($mime, 'image/'))   return '#3b82f6';
        if ($mime === 'application/pdf')         return '#ef4444';
        if (str_contains($mime, 'word'))         return '#2563eb';
        if (str_contains($mime, 'excel'))        return '#16a34a';
        return '#64748b';
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
