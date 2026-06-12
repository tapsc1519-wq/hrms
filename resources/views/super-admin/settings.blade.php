@extends('layouts.app')
@section('title', 'Platform Settings')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4><i class="bi bi-gear-fill me-2 text-primary"></i>Platform Settings</h4>
        <p>Customize the branding shown across the entire application.</p>
    </div>
</div>

<form method="POST" action="{{ route('super-admin.settings.update') }}" enctype="multipart/form-data">
@csrf @method('PUT')

<div class="row g-4">

    {{-- Left column --}}
    <div class="col-lg-7">

        {{-- Branding Text --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-type-bold"></i></div>
                Application Name
            </div>
            <div class="form-card-body">
                <div class="mb-3">
                    <label class="form-label">Title <span class="req">*</span></label>
                    <input type="text" name="site_title" value="{{ old('site_title', $settings['site_title']) }}"
                           class="form-control @error('site_title') is-invalid @enderror"
                           placeholder="e.g. ITAM" maxlength="60" required>
                    <div class="form-text">Shown in the sidebar header and browser tab.</div>
                    @error('site_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-0">
                    <label class="form-label">Sub-Title</label>
                    <input type="text" name="site_subtitle" value="{{ old('site_subtitle', $settings['site_subtitle']) }}"
                           class="form-control @error('site_subtitle') is-invalid @enderror"
                           placeholder="e.g. Asset Management" maxlength="80">
                    <div class="form-text">Small text shown below the title in the sidebar.</div>
                    @error('site_subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- Logo --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-purple"><i class="bi bi-image-fill"></i></div>
                Application Logo
            </div>
            <div class="form-card-body">
                <div class="d-flex align-items-start gap-4 flex-wrap">
                    {{-- Current logo preview --}}
                    <div style="flex-shrink:0">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:.5rem">Current Logo</div>
                        <div id="logoPreviewWrap"
                             style="width:120px;height:80px;border-radius:12px;border:2px dashed #e2e8f0;
                                    display:flex;align-items:center;justify-content:center;background:#f8fafc;overflow:hidden">
                            @if($settings['site_logo'])
                                <img id="logoPreviewImg" src="{{ Storage::url($settings['site_logo']) }}"
                                     style="max-width:100%;max-height:100%;object-fit:contain">
                            @else
                                <div id="logoPreviewImg"
                                     style="width:44px;height:44px;border-radius:10px;
                                            background:linear-gradient(135deg,#3b82f6,#6366f1);
                                            display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem">
                                    <i class="bi bi-cpu-fill"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div style="flex:1;min-width:200px">
                        <label class="form-label">Upload New Logo</label>
                        <input type="file" name="site_logo" id="logoInput" accept="image/*"
                               class="form-control @error('site_logo') is-invalid @enderror"
                               onchange="previewFile(this, 'logoPreviewWrap', 'logoPreviewImg')">
                        <div class="form-text">PNG, JPG, SVG or WebP · Max 2 MB · Recommended: transparent PNG, at least 200×80 px.</div>
                        @error('site_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        @if($settings['site_logo'])
                        <form method="POST" action="{{ route('super-admin.settings.remove-logo') }}" class="mt-2"
                              onsubmit="return confirm('Remove the current logo?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Remove Logo
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Favicon --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-amber"><i class="bi bi-star-fill"></i></div>
                Favicon
            </div>
            <div class="form-card-body">
                <div class="d-flex align-items-start gap-4 flex-wrap">
                    {{-- Current favicon preview --}}
                    <div style="flex-shrink:0">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:.5rem">Current Favicon</div>
                        <div id="faviconPreviewWrap"
                             style="width:64px;height:64px;border-radius:10px;border:2px dashed #e2e8f0;
                                    display:flex;align-items:center;justify-content:center;background:#f8fafc;overflow:hidden">
                            @if($settings['site_favicon'])
                                <img id="faviconPreviewImg" src="{{ Storage::url($settings['site_favicon']) }}"
                                     style="width:32px;height:32px;object-fit:contain">
                            @else
                                <i id="faviconPreviewImg" class="bi bi-globe2" style="font-size:1.5rem;color:#cbd5e1"></i>
                            @endif
                        </div>
                    </div>

                    <div style="flex:1;min-width:200px">
                        <label class="form-label">Upload New Favicon</label>
                        <input type="file" name="site_favicon" id="faviconInput"
                               accept=".ico,.png,.jpg,.jpeg,.svg"
                               class="form-control @error('site_favicon') is-invalid @enderror"
                               onchange="previewFile(this, 'faviconPreviewWrap', 'faviconPreviewImg')">
                        <div class="form-text">ICO, PNG or SVG · Max 512 KB · Recommended: 32×32 or 64×64 px.</div>
                        @error('site_favicon')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        @if($settings['site_favicon'])
                        <form method="POST" action="{{ route('super-admin.settings.remove-favicon') }}" class="mt-2"
                              onsubmit="return confirm('Remove the current favicon?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Remove Favicon
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Right column — live preview --}}
    <div class="col-lg-5">
        <div class="form-card" style="position:sticky;top:80px">
            <div class="form-card-header">
                <div class="icon-wrap icon-teal"><i class="bi bi-eye-fill"></i></div>
                Live Preview
            </div>
            <div class="form-card-body p-0" style="background:#0b1120;border-radius:0 0 16px 16px;overflow:hidden">

                {{-- Sidebar brand mock --}}
                <div style="padding:1.3rem 1.4rem 1.1rem;border-bottom:1px solid rgba(255,255,255,.06);
                            background:rgba(0,0,0,.2);display:flex;align-items:center;gap:.75rem">
                    <div id="prevIconWrap"
                         style="width:38px;height:38px;border-radius:10px;flex-shrink:0;overflow:hidden;
                                background:linear-gradient(135deg,#3b82f6,#6366f1);
                                display:flex;align-items:center;justify-content:center;
                                color:#fff;font-size:1.1rem;box-shadow:0 4px 12px rgba(59,130,246,.4)">
                        @if($settings['site_logo'])
                            <img src="{{ Storage::url($settings['site_logo']) }}"
                                 id="prevLogoImg" style="width:100%;height:100%;object-fit:contain">
                        @else
                            <i class="bi bi-cpu-fill" id="prevLogoImg"></i>
                        @endif
                    </div>
                    <div>
                        <div id="prevTitle"
                             style="color:#fff;font-weight:800;font-size:1.05rem;line-height:1.2;letter-spacing:-.2px">
                            {{ $settings['site_title'] }}
                        </div>
                        <div id="prevSubtitle"
                             style="font-size:.65rem;color:#64748b;text-transform:uppercase;letter-spacing:1.2px;font-weight:500;margin-top:.1rem">
                            {{ $settings['site_subtitle'] }}
                        </div>
                    </div>
                </div>

                {{-- Browser tab mock --}}
                <div style="padding:1rem 1.4rem">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#475569;margin-bottom:.5rem">
                        Browser Tab
                    </div>
                    <div style="background:#1e293b;border-radius:8px;padding:.5rem .85rem;
                                display:inline-flex;align-items:center;gap:.5rem;max-width:220px">
                        @if($settings['site_favicon'])
                            <img id="prevFavicon" src="{{ Storage::url($settings['site_favicon']) }}"
                                 style="width:14px;height:14px;object-fit:contain;flex-shrink:0">
                        @else
                            <i id="prevFavicon" class="bi bi-globe2" style="font-size:.75rem;color:#64748b;flex-shrink:0"></i>
                        @endif
                        <span id="prevTabTitle"
                              style="font-size:.75rem;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            Dashboard — {{ $settings['site_title'] }}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<div class="form-actions mt-2">
    <a href="{{ route('super-admin.dashboard') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Save Settings
    </button>
</div>

</form>
@endsection

@push('scripts')
<script>
// Live text preview
document.querySelector('[name=site_title]').addEventListener('input', function() {
    document.getElementById('prevTitle').textContent    = this.value || 'ITAM';
    document.getElementById('prevTabTitle').textContent = 'Dashboard — ' + (this.value || 'ITAM');
});
document.querySelector('[name=site_subtitle]').addEventListener('input', function() {
    document.getElementById('prevSubtitle').textContent = this.value || '';
});

// Live image preview
function previewFile(input, wrapId, imgId) {
    if (!input.files || !input.files[0]) return;
    var wrap = document.getElementById(wrapId);
    var existing = document.getElementById(imgId);
    var reader = new FileReader();

    reader.onload = function(e) {
        // Replace whatever is inside the wrap with an img
        var img = document.createElement('img');
        img.id = imgId;
        img.src = e.target.result;
        img.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain;width:100%;height:100%';

        // Update sidebar preview if logo changed
        if (input.id === 'logoInput') {
            var prevIcon = document.getElementById('prevLogoImg');
            var prevWrap = document.getElementById('prevIconWrap');
            prevWrap.style.background = 'transparent';
            prevWrap.style.padding = '2px';
            var pImg = document.createElement('img');
            pImg.id = 'prevLogoImg';
            pImg.src = e.target.result;
            pImg.style.cssText = 'width:100%;height:100%;object-fit:contain;border-radius:8px';
            prevIcon.replaceWith(pImg);
        }

        // Update favicon preview in tab mock
        if (input.id === 'faviconInput') {
            var pFav = document.getElementById('prevFavicon');
            var fImg = document.createElement('img');
            fImg.id = 'prevFavicon';
            fImg.src = e.target.result;
            fImg.style.cssText = 'width:14px;height:14px;object-fit:contain;flex-shrink:0';
            pFav.replaceWith(fImg);
        }

        existing.replaceWith(img);
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
@endpush
