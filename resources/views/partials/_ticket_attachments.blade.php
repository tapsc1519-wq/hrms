{{--
    Renders a list of TicketAttachment objects.
    Usage: @include('partials._ticket_attachments', ['attachments' => $collection])
--}}
@if($attachments->isNotEmpty())
<div class="mt-3 pt-2" style="border-top:1px dashed #e2e8f0">
    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700;color:#94a3b8;margin-bottom:.5rem">
        <i class="bi bi-paperclip me-1"></i>Attachments ({{ $attachments->count() }})
    </div>
    <div class="d-flex flex-wrap gap-2">
        @foreach($attachments as $att)
        <a href="{{ $att->url }}" target="_blank" rel="noopener"
           class="d-flex align-items-center gap-2 rounded-2 px-2 py-1 text-decoration-none"
           style="background:#f1f5f9;border:1.5px solid #e2e8f0;font-size:.78rem;max-width:220px;color:#334155;transition:background .15s"
           onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'"
           title="{{ $att->original_name }}">
            @if($att->is_image)
                <img src="{{ $att->url }}" alt=""
                     style="width:32px;height:32px;object-fit:cover;border-radius:4px;flex-shrink:0">
            @else
                <i class="bi {{ $att->file_icon }} flex-shrink-0" style="font-size:1.1rem;color:{{ $att->icon_color }}"></i>
            @endif
            <div style="overflow:hidden">
                <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    {{ $att->original_name }}
                </div>
                <div style="color:#94a3b8;font-size:.7rem">{{ $att->file_size_human }}</div>
            </div>
            <i class="bi bi-download flex-shrink-0" style="color:#94a3b8;margin-left:auto"></i>
        </a>
        @endforeach
    </div>
</div>
@endif
