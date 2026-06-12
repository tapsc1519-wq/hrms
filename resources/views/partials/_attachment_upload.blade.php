{{--
    File upload drop-zone (reusable).
    @param string $inputName  e.g. 'attachments[]'  (default 'attachments[]')
    @param string $zoneId     unique DOM id suffix   (default 'main')
--}}
@php
    $inputName = $inputName ?? 'attachments[]';
    $zoneId    = $zoneId    ?? 'main';
@endphp

<div class="attachment-upload-wrap">
    <label class="form-label mb-1">
        <i class="bi bi-paperclip me-1"></i>Attachments
        <span style="font-weight:400;color:#94a3b8;font-size:.78rem"> — optional, max 5 files, 5 MB each</span>
    </label>

    <div class="attach-drop-zone"
         data-target="attach-input-{{ $zoneId }}"
         data-preview="attach-preview-{{ $zoneId }}"
         style="border:2px dashed #cbd5e1;border-radius:10px;padding:1.1rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;background:#f8fafc">
        <i class="bi bi-cloud-arrow-up" style="font-size:1.7rem;color:#94a3b8;display:block;margin-bottom:.3rem"></i>
        <div style="font-size:.82rem;color:#64748b;font-weight:600">Click to browse or drag & drop</div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:.15rem">PNG, JPG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP</div>
    </div>

    <input type="file" id="attach-input-{{ $zoneId }}" name="{{ $inputName }}"
           multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
           style="display:none">

    <div id="attach-preview-{{ $zoneId }}" class="mt-2 d-flex flex-wrap gap-2"></div>
</div>
