<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $siteTitle = \App\Models\Setting::get('site_title', 'ITAM Suite');
        $siteSubtitle = \App\Models\Setting::get('site_subtitle', 'Business Operations Suite');
        $siteLogo = \App\Models\Setting::get('site_logo');
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    <title>@yield('title', 'Login') - {{ $siteTitle }}</title>
    @if($siteFavicon)
        <link rel="icon" href="{{ Storage::url($siteFavicon) }}">
    @endif

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; font-family: 'Inter', sans-serif; }
    body { background: #f3f6fb; overflow: hidden; }

    .auth-wrapper {
        display: grid;
        grid-template-columns: minmax(420px, 1.05fr) minmax(390px, .95fr);
        height: 100vh;
        min-height: 660px;
    }

    .auth-left {
        background:
            linear-gradient(135deg, rgba(2, 6, 23, .94), rgba(15, 23, 42, .92)),
            linear-gradient(135deg, #0f172a 0%, #172554 48%, #0f766e 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
        padding: clamp(1.5rem, 3vw, 2.5rem);
        position: relative;
    }

    .auth-left::before {
        background-image:
            linear-gradient(rgba(255,255,255,.055) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.055) 1px, transparent 1px);
        background-size: 44px 44px;
        content: "";
        inset: 0;
        opacity: .55;
        position: absolute;
    }

    .auth-left > * { position: relative; z-index: 1; }

    .auth-brand {
        align-items: center;
        display: flex;
        gap: .85rem;
    }

    .auth-brand-icon,
    .form-header-icon {
        align-items: center;
        background: linear-gradient(135deg, #3b82f6, #14b8a6);
        border-radius: 14px;
        color: #fff;
        display: flex;
        flex-shrink: 0;
        justify-content: center;
        overflow: hidden;
    }

    .auth-brand-icon { box-shadow: 0 10px 24px rgba(59,130,246,.35); font-size: 1.25rem; height: 44px; width: 44px; }
    .auth-brand-icon img,
    .form-header-icon img { height: 100%; object-fit: contain; padding: 4px; width: 100%; }
    .auth-brand-name { font-size: 1.12rem; font-weight: 800; line-height: 1.1; }
    .auth-brand-sub { color: rgba(203,213,225,.78); font-size: .68rem; font-weight: 700; letter-spacing: .08em; margin-top: .15rem; text-transform: uppercase; }

    .auth-hero { max-width: 610px; }
    .auth-hero-badge {
        align-items: center;
        background: rgba(255,255,255,.09);
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 999px;
        color: #bfdbfe;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 800;
        gap: .45rem;
        letter-spacing: .05em;
        margin-bottom: .9rem;
        padding: .36rem .7rem;
        text-transform: uppercase;
    }
    .auth-hero h1 {
        color: #fff;
        font-size: clamp(1.9rem, 3.8vw, 3rem);
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.08;
        margin: 0 0 .85rem;
    }
    .auth-hero p {
        color: rgba(226,232,240,.82);
        font-size: .92rem;
        line-height: 1.65;
        margin: 0;
        max-width: 540px;
    }

    .suite-grid {
        display: grid;
        gap: .72rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 1.35rem;
    }
    .suite-card {
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.13);
        border-radius: 14px;
        padding: .86rem;
    }
    .suite-card i { color: #93c5fd; font-size: 1.02rem; }
    .suite-title { color: #f8fafc; font-size: .8rem; font-weight: 800; margin-top: .45rem; }
    .suite-text { color: rgba(203,213,225,.72); font-size: .68rem; line-height: 1.35; margin-top: .18rem; }

    .auth-proof {
        border-top: 1px solid rgba(255,255,255,.12);
        display: flex;
        gap: 1.35rem;
        padding-top: 1rem;
    }
    .proof-value { color: #fff; font-size: 1.18rem; font-weight: 800; line-height: 1; }
    .proof-label { color: rgba(203,213,225,.68); font-size: .68rem; font-weight: 700; letter-spacing: .04em; margin-top: .28rem; text-transform: uppercase; }
    .auth-footer { color: rgba(203,213,225,.58); font-size: .72rem; }

    .auth-right {
        align-items: center;
        background: #f8fafc;
        display: flex;
        justify-content: center;
        overflow-y: auto;
        padding: 1.5rem;
    }
    .auth-form-wrap {
        max-width: 420px;
        width: 100%;
    }

    .form-header { margin-bottom: 1.35rem; }
    .form-header-icon { box-shadow: 0 8px 24px rgba(59,130,246,.28); font-size: 1.24rem; height: 50px; margin-bottom: 1rem; width: 50px; }
    .form-header h2 { color: #0f172a; font-size: 1.45rem; font-weight: 800; line-height: 1.2; margin: 0 0 .3rem; }
    .form-header p { color: #64748b; font-size: .86rem; line-height: 1.5; margin: 0; }

    .auth-alert {
        align-items: flex-start;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        color: #b91c1c;
        display: flex;
        font-size: .82rem;
        gap: .6rem;
        margin-bottom: 1rem;
        padding: .72rem .9rem;
    }

    .field-group { margin-bottom: 1rem; }
    .field-label { color: #475569; display: block; font-size: .7rem; font-weight: 800; letter-spacing: .06em; margin-bottom: .38rem; text-transform: uppercase; }
    .field-wrap { position: relative; }
    .field-icon {
        color: #94a3b8;
        font-size: .95rem;
        left: .88rem;
        pointer-events: none;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1;
    }
    .field-wrap:focus-within .field-icon { color: #2563eb; }
    .field-input {
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 11px;
        color: #0f172a;
        font-size: .9rem;
        outline: none;
        padding: .74rem .9rem .74rem 2.65rem;
        transition: border-color .18s, box-shadow .18s;
        width: 100%;
    }
    .field-input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,.1); }
    .field-input::placeholder { color: #cbd5e1; }
    .field-toggle {
        background: none;
        border: 0;
        color: #94a3b8;
        font-size: 1rem;
        line-height: 1;
        padding: .25rem;
        position: absolute;
        right: .82rem;
        top: 50%;
        transform: translateY(-50%);
    }
    .field-toggle:hover { color: #2563eb; }

    .auth-row { align-items: center; display: flex; justify-content: space-between; margin-bottom: 1.2rem; }
    .custom-check { align-items: center; color: #64748b; cursor: pointer; display: flex; font-size: .82rem; font-weight: 600; gap: .5rem; }
    .custom-check input { accent-color: #2563eb; height: 16px; width: 16px; }

    .btn-signin {
        align-items: center;
        background: linear-gradient(135deg, #2563eb, #14b8a6);
        border: 0;
        border-radius: 11px;
        box-shadow: 0 8px 22px rgba(37,99,235,.28);
        color: #fff;
        display: flex;
        font-size: .94rem;
        font-weight: 800;
        gap: .55rem;
        justify-content: center;
        padding: .82rem 1rem;
        transition: transform .16s, box-shadow .16s;
        width: 100%;
    }
    .btn-signin:hover { box-shadow: 0 12px 28px rgba(37,99,235,.36); transform: translateY(-1px); }
    .btn-signin.loading { opacity: .75; pointer-events: none; }

    .auth-divider { align-items: center; color: #94a3b8; display: flex; font-size: .72rem; font-weight: 800; gap: .75rem; margin: 1.05rem 0 .75rem; text-transform: uppercase; }
    .auth-divider::before,
    .auth-divider::after { background: #e2e8f0; content: ""; flex: 1; height: 1px; }

    .demo-toggle {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 8px;
        color: #64748b;
        display: flex;
        font-size: .78rem;
        font-weight: 700;
        gap: .42rem;
        justify-content: center;
        padding: .55rem;
        width: 100%;
    }
    .demo-toggle:hover { background: #eff6ff; color: #2563eb; }
    .demo-toggle i { font-size: .72rem; transition: transform .2s; }
    .demo-toggle.open i { transform: rotate(180deg); }
    .demo-box {
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        display: none;
        margin-top: .5rem;
        overflow: hidden;
    }
    .demo-box.show { display: block; }
    .demo-row {
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        display: flex;
        gap: .7rem;
        padding: .62rem .9rem;
    }
    .demo-row:hover { background: #f8fafc; }
    .demo-role-dot { border-radius: 50%; flex-shrink: 0; height: 8px; width: 8px; }
    .demo-row-info { flex: 1; min-width: 0; }
    .demo-row-role { color: #334155; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .demo-row-email { color: #64748b; font-size: .74rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .demo-fill-btn { background: #eff6ff; border: 0; border-radius: 6px; color: #2563eb; font-size: .68rem; font-weight: 800; padding: .25rem .55rem; }
    .demo-pass-note { background: #f8fafc; color: #64748b; font-size: .7rem; padding: .55rem .9rem; text-align: center; }

    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .spin { animation: spin .7s linear infinite; display: inline-block; }

    @media (max-width: 980px) {
        body { overflow: auto; }
        .auth-wrapper { display: block; height: auto; min-height: 100vh; }
        .auth-left { display: none; }
        .auth-right { min-height: 100vh; padding: 1.25rem; }
        .auth-form-wrap { background: #fff; border-radius: 18px; box-shadow: 0 18px 50px rgba(15,23,42,.12); padding: 1.5rem; }
    }

    @media (max-height: 760px) and (min-width: 981px) {
        .auth-wrapper { min-height: 620px; }
        .auth-left { padding: 1.4rem 2rem; }
        .suite-grid { gap: .55rem; margin-top: 1rem; }
        .suite-card { padding: .68rem; }
        .auth-hero h1 { font-size: 2.1rem; }
        .auth-hero p { font-size: .86rem; }
        .auth-proof { padding-top: .8rem; }
        .form-header { margin-bottom: 1rem; }
        .field-group { margin-bottom: .8rem; }
    }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-left">
        <div class="auth-brand">
            <div class="auth-brand-icon">
                @if($siteLogo)
                    <img src="{{ Storage::url($siteLogo) }}" alt="{{ $siteTitle }}">
                @else
                    <i class="bi bi-grid-1x2-fill"></i>
                @endif
            </div>
            <div>
                <div class="auth-brand-name">{{ $siteTitle }}</div>
                <div class="auth-brand-sub">{{ $siteSubtitle }}</div>
            </div>
        </div>

        <div class="auth-hero">
            <div class="auth-hero-badge"><i class="bi bi-layers-fill"></i> Modular Business Suite</div>
            <h1>One portal for people, assets, software and payroll.</h1>
            <p>
                A subscription-ready platform where every organization gets only the modules they need:
                HRMS, ITAM, SAM, Payroll, Support and Supplier collaboration.
            </p>

            <div class="suite-grid">
                <div class="suite-card">
                    <i class="bi bi-person-vcard-fill"></i>
                    <div class="suite-title">HRMS</div>
                    <div class="suite-text">Employees, documents, attendance and leaves.</div>
                </div>
                <div class="suite-card">
                    <i class="bi bi-box-seam-fill"></i>
                    <div class="suite-title">ITAM</div>
                    <div class="suite-text">Assets, assignments, handovers and maintenance.</div>
                </div>
                <div class="suite-card">
                    <i class="bi bi-display-fill"></i>
                    <div class="suite-title">SAM</div>
                    <div class="suite-text">Software licenses, seats and compliance.</div>
                </div>
                <div class="suite-card">
                    <i class="bi bi-cash-stack"></i>
                    <div class="suite-title">Payroll</div>
                    <div class="suite-text">Salary setup, payroll runs and payslips.</div>
                </div>
                <div class="suite-card">
                    <i class="bi bi-headset"></i>
                    <div class="suite-title">Support</div>
                    <div class="suite-text">Tickets, comments and supporting files.</div>
                </div>
                <div class="suite-card">
                    <i class="bi bi-building-fill"></i>
                    <div class="suite-title">Supplier</div>
                    <div class="suite-text">Purchase visibility and supplier portal.</div>
                </div>
            </div>
        </div>

        <div>
            <div class="auth-proof">
                <div>
                    <div class="proof-value">6</div>
                    <div class="proof-label">Modules</div>
                </div>
                <div>
                    <div class="proof-value">4</div>
                    <div class="proof-label">Portals</div>
                </div>
                <div>
                    <div class="proof-value">1-6</div>
                    <div class="proof-label">Trial Months</div>
                </div>
            </div>
            <div class="auth-footer mt-3">&copy; {{ date('Y') }} {{ $siteTitle }}. Modular SaaS platform.</div>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-form-wrap">
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
