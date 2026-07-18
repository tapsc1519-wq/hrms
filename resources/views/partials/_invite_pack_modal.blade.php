@if(session('invite_pack'))
@php($invitePack = session('invite_pack'))
<div class="modal fade" id="invitePackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Invite Message Ready</h5>
                    <small class="text-muted">{{ $invitePack['name'] }} - {{ $invitePack['email'] }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    Email sending can be connected later. For now, copy this message and share it manually by WhatsApp, email, or chat.
                </div>
                <textarea id="invitePackMessage" class="form-control font-monospace" rows="13">{{ $invitePack['message'] }}</textarea>
                <div class="row g-2 mt-3 small">
                    <div class="col-md-6">
                        <div class="text-muted">Login URL</div>
                        <div class="fw-semibold text-break">{{ $invitePack['login_url'] }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Temporary Password</div>
                        <div class="fw-semibold">{{ $invitePack['temporary_password'] }}</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span id="inviteCopyFeedback" class="text-success small fw-semibold me-auto" style="display:none">Copied</span>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="copyInvitePackMessage">
                    <i class="bi bi-clipboard-check me-1"></i>Copy Message
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('invitePackModal');
    if (modalElement && window.bootstrap) {
        new bootstrap.Modal(modalElement).show();
    }

    document.getElementById('copyInvitePackMessage')?.addEventListener('click', function () {
        var message = document.getElementById('invitePackMessage')?.value || '';
        navigator.clipboard.writeText(message).then(function () {
            var feedback = document.getElementById('inviteCopyFeedback');
            if (!feedback) return;
            feedback.style.display = 'inline';
            window.setTimeout(function () { feedback.style.display = 'none'; }, 1600);
        });
    });
});
</script>
@endpush
@endif
