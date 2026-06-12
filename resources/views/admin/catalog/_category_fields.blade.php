@php
    $icons = [
        'bi-laptop','bi-pc-display','bi-display','bi-phone-fill','bi-tablet-fill',
        'bi-printer-fill','bi-router-fill','bi-server','bi-hdd-fill','bi-keyboard',
        'bi-mouse3','bi-webcam-fill','bi-camera-fill','bi-headphones','bi-speaker-fill',
        'bi-projector-fill','bi-tv-fill','bi-telephone-fill','bi-cpu-fill','bi-memory',
        'bi-ethernet','bi-wifi','bi-bluetooth','bi-usb-symbol','bi-battery-full',
        'bi-box-seam-fill','bi-box-fill','bi-archive-fill','bi-folder-fill','bi-tools',
        'bi-wrench-adjustable-fill','bi-hammer','bi-gear-fill','bi-lightning-fill',
        'bi-shield-fill-check','bi-lock-fill','bi-key-fill','bi-badge-4k-fill',
    ];
    $currentIcon  = old('icon', $category->icon ?? 'bi-box');
    $currentSpecs = $category->spec_template ?? [];
    $suffix       = isset($edit) ? '_edit' : '';

    $presets = [
        'Laptop / Notebook'  => ['Processor','RAM','Storage','Display','Operating System','Graphics','Battery','Connectivity','Weight'],
        'Desktop / Tower'    => ['Processor','RAM','Storage','Operating System','Graphics','Power Supply','Form Factor'],
        'Monitor / Display'  => ['Screen Size','Resolution','Panel Type','Refresh Rate','Brightness','Ports','Response Time'],
        'Printer / Scanner'  => ['Print Technology','Print Speed (PPM)','Resolution (DPI)','Color / Mono','Paper Sizes','Connectivity','Duplex'],
        'Server'             => ['Processor','RAM','Storage','Form Factor','Operating System','Power Supply','RAID Support'],
        'Network Switch'     => ['Ports','Port Speed','PoE','Switching Capacity','Management','Form Factor'],
        'Network Router'     => ['WAN Ports','LAN Ports','WiFi Standard','Throughput','VPN Support','Management'],
        'Mobile / Phone'     => ['Processor','RAM','Storage','Display','Operating System','Battery','Connectivity','Camera'],
        'Tablet'             => ['Processor','RAM','Storage','Display','Operating System','Battery','Connectivity'],
        'UPS / Power'        => ['Capacity (VA)','Output Power (W)','Battery Type','Runtime','Input Voltage','Form Factor'],
        'IP Camera'          => ['Resolution','Frame Rate','Lens','IR Range','Connectivity','Storage','Weather Rating'],
        'Projector'          => ['Brightness (Lumens)','Resolution','Contrast Ratio','Throw Distance','Connectivity','Lamp Life'],
        'Generic / Other'    => ['Description','Part Number','Model Number','Specifications'],
    ];
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label fw-600" style="font-size:.78rem">Category Name <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $category->name ?? '') }}"
               placeholder="e.g. Laptops, Printers, Network Switches…">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-600" style="font-size:.78rem">Depreciation (Years)</label>
        <input type="number" name="depreciation_years" class="form-control"
               value="{{ old('depreciation_years', $category->depreciation_years ?? 3) }}"
               min="1" max="50">
    </div>
    <div class="col-12">
        <label class="form-label fw-600" style="font-size:.78rem">Description</label>
        <input type="text" name="description" class="form-control"
               value="{{ old('description', $category->description ?? '') }}"
               placeholder="Optional note…">
    </div>

    {{-- Icon picker --}}
    <div class="col-12">
        <label class="form-label fw-600" style="font-size:.78rem">Icon</label>
        <input type="hidden" name="icon" value="{{ $currentIcon }}">
        <div class="mb-2 d-flex align-items-center gap-2">
            <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:1.15rem;color:#3b82f6">
                <i class="bi {{ $currentIcon }} icon-preview{{ $suffix }}"></i>
            </div>
            <span style="font-size:.72rem;color:#64748b">Selected</span>
        </div>
        <div class="icon-picker-grid">
            @foreach($icons as $icon)
            <button type="button" class="icon-picker-btn{{ $suffix }} {{ $currentIcon === $icon ? 'selected' : '' }}"
                    data-icon="{{ $icon }}" title="{{ $icon }}">
                <i class="bi {{ $icon }}"></i>
            </button>
            @endforeach
        </div>
    </div>

    {{-- Spec Template --}}
    <div class="col-12">
        <div style="display:flex;align-items:center;gap:.75rem;margin:.1rem 0 .6rem">
            <div style="flex:1;height:1px;background:#f1f5f9"></div>
            <span style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;white-space:nowrap">
                <i class="bi bi-list-check me-1"></i>Specification Fields
            </span>
            <div style="flex:1;height:1px;background:#f1f5f9"></div>
        </div>

        {{-- Preset loader --}}
        <div class="d-flex gap-2 mb-2">
            <select id="specPreset{{ $suffix }}" class="form-select form-select-sm" style="max-width:240px">
                <option value="">— Load a preset template —</option>
                @foreach($presets as $label => $fields)
                <option value="{{ implode(',', $fields) }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="loadSpecPreset('{{ $suffix }}')">
                <i class="bi bi-lightning-fill me-1"></i>Load
            </button>
        </div>

        <div class="form-text mb-2">Define which specification fields apply to this category. Assets in this category will show exactly these fields.</div>

        {{-- Tags input --}}
        <div id="specTagsWrap{{ $suffix }}"
             style="min-height:52px;border:1.5px solid #e2e8f0;border-radius:11px;padding:.45rem .6rem;
                    display:flex;flex-wrap:wrap;gap:.35rem;align-items:center;cursor:text;background:#fff"
             onclick="document.getElementById('specTagInput{{ $suffix }}').focus()">
            @foreach($currentSpecs as $field)
            <span class="spec-tag" style="display:inline-flex;align-items:center;gap:.3rem;background:#eff6ff;
                          border:1px solid #bfdbfe;color:#1d4ed8;border-radius:6px;padding:.2rem .55rem;
                          font-size:.75rem;font-weight:600">
                {{ $field }}
                <button type="button" onclick="removeSpecTag(this)"
                        style="background:none;border:none;color:#93c5fd;cursor:pointer;padding:0;font-size:.7rem;line-height:1">✕</button>
            </span>
            @endforeach
            <input id="specTagInput{{ $suffix }}" type="text"
                   placeholder="{{ count($currentSpecs) ? 'Add more…' : 'Type a field name and press Enter…' }}"
                   style="border:none;outline:none;font-size:.82rem;min-width:160px;flex:1;background:transparent"
                   onkeydown="handleSpecTagKey(event, '{{ $suffix }}')">
        </div>
        <input type="hidden" name="spec_fields" id="specFieldsHidden{{ $suffix }}"
               value="{{ implode(',', $currentSpecs) }}">
        <div class="form-text mt-1">Press <kbd>Enter</kbd> or <kbd>,</kbd> to add a field. Click ✕ to remove.</div>
    </div>
</div>
