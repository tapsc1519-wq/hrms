@extends('layouts.app')
@section('title', 'New Support Ticket')

@section('content')
<div class="page-header">
    <a href="{{ route('staff.tickets.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> My Tickets</a>
    <h4>Raise a Support Ticket</h4>
    <p>Describe your issue and we'll get back to you as soon as possible.</p>
</div>

<form action="{{ route('staff.tickets.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Category --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-tags"></i></span>
                Ticket Category
            </div>
            <div class="form-card-body">
                <label class="form-label">What is your request about? <span class="req">*</span></label>
                <div class="row g-3 mt-1">
                    @php
                    $cats = [
                        'fixed_assets'  => ['Fixed Assets',      'bi-box-seam',  '#dbeafe','#1d4ed8','Issues with physical IT assets'],
                        'itam_support'  => ['ITAM Tool Support', 'bi-pc-display','#f5f3ff','#7c3aed','Help with this platform'],
                        'other_support' => ['Other Support',     'bi-headset',   '#f0fdf4','#166534','General help & enquiries'],
                    ];
                    @endphp
                    @foreach($cats as $val => [$label, $icon, $bg, $fg, $desc])
                    <div class="col-md-4">
                        <label class="d-block h-100">
                            <input type="radio" name="category" value="{{ $val }}" class="d-none cat-radio"
                                   {{ old('category', 'fixed_assets') == $val ? 'checked':'' }}>
                            <div class="cat-card rounded-3 p-3 border text-center h-100"
                                 data-bg="{{ $bg }}" data-fg="{{ $fg }}"
                                 style="cursor:pointer;transition:all .2s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;min-height:100px">
                                <span style="width:42px;height:42px;border-radius:10px;background:{{ $bg }};color:{{ $fg }};display:flex;align-items:center;justify-content:center">
                                    <i class="bi {{ $icon }}" style="font-size:1.2rem"></i>
                                </span>
                                <div style="font-weight:700;font-size:.85rem;color:#334155">{{ $label }}</div>
                                <div style="font-size:.72rem;color:#94a3b8;line-height:1.3">{{ $desc }}</div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('category')<div class="text-danger mt-2" style="font-size:.82rem">{{ $message }}</div>@enderror

                {{-- Asset selector shown only for Fixed Assets (mandatory for that category) --}}
                <div id="assetField" class="mt-3" style="{{ old('category','fixed_assets') == 'fixed_assets' ? '' : 'display:none' }}">
                    <label class="form-label">Related Asset <span class="req">*</span></label>
                    <select name="asset_id" id="assetSelect"
                            class="form-select @error('asset_id') is-invalid @enderror">
                        <option value="">— Select the related asset —</option>
                        @foreach($assets as $a)
                        <option value="{{ $a->id }}" {{ old('asset_id') == $a->id ? 'selected':'' }}>
                            {{ $a->name }}@if($a->asset_tag) ({{ $a->asset_tag }})@endif
                        </option>
                        @endforeach
                    </select>
                    @error('asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- Issue Details --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-chat-square-text"></i></span>
                Issue Details
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Subject <span class="req">*</span></label>
                        <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject') }}" required
                               placeholder="Brief one-line summary of your issue">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description <span class="req">*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="6" required
                                  placeholder="Describe your issue in detail. Include what happened, what you expected, any error messages, and steps you already tried…">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Attachments --}}
                    <div class="col-12">
                        @include('partials._attachment_upload', ['inputName' => 'attachments[]', 'zoneId' => 'create'])
                        @error('attachments.*')<div class="text-danger mt-1" style="font-size:.82rem">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-flag"></i></span>
                Priority
            </div>
            <div class="form-card-body">
                <label class="form-label">How urgent is this? <span class="req">*</span></label>
                <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                    <option value="low"    {{ old('priority') == 'low'    ? 'selected':'' }}>🟢 Low — Not urgent</option>
                    <option value="normal" {{ old('priority','normal') == 'normal' ? 'selected':'' }}>🔵 Normal — Standard</option>
                    <option value="high"   {{ old('priority') == 'high'   ? 'selected':'' }}>🟠 High — Impacts work</option>
                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected':'' }}>🔴 Urgent — Work blocked</option>
                </select>
                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text mt-2">Please select accurately so we can prioritise correctly.</div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-teal"><i class="bi bi-lightbulb"></i></span>
                Tips for Faster Resolution
            </div>
            <div class="form-card-body">
                <ul class="list-unstyled mb-0" style="font-size:.82rem;color:#64748b;line-height:2">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Be specific about the problem</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Attach screenshots if helpful</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Include asset tags if relevant</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Mention error messages exactly</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>List steps you already tried</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('staff.tickets.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-send"></i> Submit Ticket
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.cat-radio').forEach(function(radio) {
    radio.addEventListener('change', updateCategoryUI);
});
function updateCategoryUI() {
    document.querySelectorAll('.cat-card').forEach(function(card) {
        card.style.borderColor = '';
        card.style.background  = '';
    });
    var checked = document.querySelector('.cat-radio:checked');
    if (checked) {
        var card = checked.parentElement.querySelector('.cat-card');
        card.style.borderColor = '#3b82f6';
        card.style.background  = '#eff6ff';
    }
    var isFixed = document.querySelector('[name=category][value=fixed_assets]').checked;
    var assetField  = document.getElementById('assetField');
    var assetSelect = document.getElementById('assetSelect');
    assetField.style.display = isFixed ? 'block' : 'none';
    if (isFixed) {
        assetSelect.setAttribute('required', 'required');
    } else {
        assetSelect.removeAttribute('required');
        assetSelect.value = ''; // clear selection when hidden
    }
}
updateCategoryUI();
</script>
@endpush
