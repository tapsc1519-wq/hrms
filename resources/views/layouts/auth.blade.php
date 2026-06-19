<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $siteTitle = \App\Models\Setting::get('site_title', 'OpsBridge');
        $siteSubtitle = \App\Models\Setting::get('site_subtitle', 'Workforce Operations');
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { min-height: 100%; margin: 0; font-family: 'Inter', sans-serif; }
    body { background: #f8fafc; color: #0f172a; }

    .auth-page {
        min-height: 100vh;
        padding: 0;
    }
    .auth-shell {
        background: #ffffff;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        display: grid;
        grid-template-columns: minmax(440px, 58vw) minmax(420px, 42vw);
        min-height: 100vh;
        overflow: hidden;
    }

    .auth-left {
        background: #101827;
        color: #e5edf6;
        display: grid;
        grid-template-rows: auto 1fr;
        min-height: 100vh;
        padding: clamp(2rem, 5vw, 4.25rem);
        position: relative;
    }
    #auth-particles {
        inset: 0;
        overflow: hidden;
        pointer-events: none;
        position: absolute;
        z-index: 0;
    }
    #auth-particles canvas {
        display: block;
        height: 100% !important;
        width: 100% !important;
    }
    .auth-left::before {
        background-image: linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
        background-size: 46px 46px;
        content: "";
        inset: 0;
        opacity: .18;
        position: absolute;
        z-index: 0;
    }
    .auth-left > * { position: relative; z-index: 1; }
    .auth-left > #auth-particles { position: absolute; z-index: 0; }

    .auth-brand {
        align-items: center;
        display: flex;
        gap: .8rem;
    }
    .brand-icon,
    .form-header-icon {
        align-items: center;
        background: #2563eb;
        border-radius: 14px;
        color: #fff;
        display: flex;
        flex-shrink: 0;
        justify-content: center;
        overflow: hidden;
    }
    .brand-icon { box-shadow: 0 14px 30px rgba(37,99,235,.36); font-size: 1.2rem; height: 46px; width: 46px; }
    .brand-icon img { height: 100%; object-fit: contain; padding: 4px; width: 100%; }
    .form-header-icon {
        box-shadow: 0 14px 30px rgba(37,99,235,.22);
        font-size: 1.45rem;
        height: 62px;
        margin-bottom: 1.25rem;
        width: 62px;
    }
    .form-header-icon img { height: 100%; object-fit: contain; padding: 6px; width: 100%; }
    .brand-name { color: #fff; font-size: 1.06rem; font-weight: 900; line-height: 1.1; }
    .brand-sub { color: #93a4bb; font-size: .72rem; font-weight: 750; margin-top: .18rem; }

    .auth-story { align-self: center; max-width: 790px; padding-bottom: 2vh; }
    .auth-kicker {
        align-items: center;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.14);
        border-radius: 999px;
        color: #bfdbfe;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 850;
        gap: .45rem;
        letter-spacing: .06em;
        margin-bottom: 1rem;
        padding: .42rem .76rem;
        text-transform: uppercase;
    }
    .auth-story h1 {
        color: #fff;
        font-size: clamp(2.25rem, 3.6vw, 3.7rem);
        font-weight: 900;
        letter-spacing: 0;
        line-height: 1.02;
        margin: 0 0 1rem;
        max-width: 760px;
    }
    .auth-story p {
        color: #cbd5e1;
        font-size: .98rem;
        line-height: 1.7;
        margin: 0;
        max-width: 760px;
    }

    .auth-capabilities {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 1.65rem;
        max-width: 770px;
    }
    .capability {
        align-items: flex-start;
        background: rgba(255,255,255,.065);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        gap: .72rem;
        min-height: 132px;
        padding: 1rem;
    }
    .capability i {
        align-items: center;
        background: rgba(96, 165, 250, .13);
        border-radius: 9px;
        color: #93c5fd;
        display: inline-flex;
        flex-shrink: 0;
        height: 30px;
        justify-content: center;
        width: 30px;
    }
    .capability-title { color: #f8fafc; font-size: .9rem; font-weight: 900; line-height: 1.2; }
    .capability-text { color: #b7c6dc; font-size: .76rem; line-height: 1.38; margin-top: .25rem; }



    .auth-right {
        align-items: center;
        background: #f6f8fb;
        display: flex;
        justify-content: center;
        min-height: 100vh;
        padding: clamp(1.5rem, 4vw, 4rem);
    }
    .auth-form-wrap {
        background: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        padding: 0;
        width: min(100%, 430px);
    }

    .form-header { margin-bottom: 20px; }
    .form-header h2 { color: #0f172a; font-size: 1.62rem; font-weight: 900; line-height: 1.16; margin: 0 0 .42rem; }
    .form-header p { color: #64748b; font-size: .84rem; line-height: 1.5; margin: 0; max-width: 390px; }
    .auth-alert {
        align-items: flex-start;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 11px;
        color: #b91c1c;
        display: flex;
        font-size: .82rem;
        gap: .6rem;
        margin-bottom: 1rem;
        padding: .75rem .9rem;
    }
    .field-group { margin-bottom: 20px; }
    .field-label { color: #475569; display: block; font-size: .7rem; font-weight: 850; letter-spacing: .05em; margin-bottom: .4rem; text-transform: uppercase; }
    .field-wrap { position: relative; }
    .field-icon {
        color: #94a3b8;
        font-size: .95rem;
        left: .9rem;
        pointer-events: none;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1;
    }
    .field-wrap:focus-within .field-icon { color: #2563eb; }
    .field-input {
        background: #fff;
        border: 1.5px solid #dbe3ee;
        border-radius: 12px;
        color: #0f172a;
        font-size: .92rem;
        outline: none;
        padding: .78rem .95rem .78rem 2.65rem;
        transition: border-color .18s, box-shadow .18s;
        width: 100%;
    }
    .field-input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,.1); }
    .field-input::placeholder { color: #b8c4d2; }
    .field-help { color: #64748b; font-size: .68rem; font-weight: 600; line-height: 1.32; margin-top: .3rem; }
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
    .auth-row { align-items: center; display: flex; justify-content: space-between; margin-bottom: .95rem; }
    .custom-check { align-items: center; color: #64748b; cursor: pointer; display: flex; font-size: .82rem; font-weight: 650; gap: .5rem; }
    .custom-check input { accent-color: #2563eb; height: 16px; width: 16px; }
    .auth-tabs {
        background: #e9eef6;
        border: 1px solid #dce5f0;
        border-radius: 14px;
        display: grid;
        gap: .25rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-bottom: 1.15rem;
        padding: .25rem;
    }
    .auth-tab {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 10px;
        color: #64748b;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 850;
        gap: .45rem;
        justify-content: center;
        min-height: 40px;
        padding: .55rem .75rem;
        transition: background .16s, color .16s, box-shadow .16s;
    }
    .auth-tab.active {
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
        color: #0f172a;
    }
    .auth-tab i { color: #2563eb; font-size: .9rem; }
    .auth-pane { display: none; }
    .auth-pane.active { display: block; }
    .btn-signin {
        align-items: center;
        background: #2563eb;
        border: 0;
        border-radius: 12px;
        box-shadow: 0 12px 26px rgba(37,99,235,.24);
        color: #fff;
        display: flex;
        font-size: .95rem;
        font-weight: 850;
        gap: .55rem;
        justify-content: center;
        padding: .86rem 1rem;
        transition: background .16s, transform .16s, box-shadow .16s;
        width: 100%;
    }
    .btn-signin:hover { background: #1d4ed8; box-shadow: 0 16px 32px rgba(37,99,235,.3); transform: translateY(-1px); }
    .btn-signin.loading { opacity: .75; pointer-events: none; }
    .auth-method-title {
        align-items: center;
        color: #0f172a;
        display: flex;
        font-size: .86rem;
        font-weight: 900;
        gap: .5rem;
        line-height: 1.2;
        margin: .95rem 0 .58rem;
    }
    .auth-method-title:first-child { margin-top: 0; }
    .auth-method-title.compact { margin-top: .68rem; }
    .auth-method-title i { color: #2563eb; font-size: .98rem; }
    .auth-divider { align-items: center; color: #94a3b8; display: flex; font-size: .68rem; font-weight: 850; gap: .75rem; margin: .95rem 0 .58rem; text-transform: uppercase; }
    .sso-form {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
    }
    .sso-form .field-group { margin-bottom: .88rem; }
    .sso-grid { display: grid; gap: .65rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sso-btn {
        align-items: center;
        background: #fff;
        border: 1.5px solid #dbe3ee;
        border-radius: 11px;
        color: #334155;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 800;
        gap: .48rem;
        justify-content: center;
        min-height: 43px;
        padding: .65rem .75rem;
        text-decoration: none;
        transition: border-color .16s, background .16s, transform .16s;
    }
    .sso-btn:hover { background: #f8fafc; border-color: #bfdbfe; color: #0f172a; transform: translateY(-1px); }
    .sso-btn.disabled, .sso-btn:disabled { cursor: not-allowed; opacity: .48; transform: none; }
    .sso-note { color: #64748b; font-size: .66rem; font-weight: 600; line-height: 1.35; margin-top: .46rem; text-align: center; }
    .sso-btn i { font-size: .98rem; }
    .sso-btn.google i { color: #dc2626; }
    .sso-btn.microsoft i { color: #2563eb; }    .auth-divider::before,
    .auth-divider::after { background: #e2e8f0; content: ""; flex: 1; height: 1px; }
    .trial-cta {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        display: flex;
        gap: .85rem;
        justify-content: space-between;
        padding: .78rem;
    }
    .trial-cta-title { color: #0f172a; font-size: .82rem; font-weight: 850; line-height: 1.25; }
    .trial-cta-text { color: #64748b; font-size: .72rem; line-height: 1.35; margin-top: .16rem; }
    .trial-cta-link {
        background: #fff;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        color: #2563eb;
        flex-shrink: 0;
        font-size: .74rem;
        font-weight: 850;
        padding: .5rem .68rem;
        text-decoration: none;
    }
    .trial-cta-link:hover { background: #eff6ff; color: #1d4ed8; }
    .auth-switch-link { color: #64748b; font-size: .82rem; font-weight: 650; margin-top: .86rem; text-align: center; }
    .auth-switch-link a { color: #2563eb; font-weight: 850; text-decoration: none; }
    .auth-switch-link a:hover { text-decoration: underline; }
    .form-section-label { color: #0f172a; font-size: .74rem; font-weight: 850; letter-spacing: .05em; margin: 1.05rem 0 .65rem; text-transform: uppercase; }
    .form-note {
        align-items: flex-start;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        color: #1e40af;
        display: flex;
        font-size: .78rem;
        font-weight: 650;
        gap: .55rem;
        line-height: 1.45;
        margin-bottom: .82rem;
        padding: .78rem .88rem;
    }
    .form-note i { flex-shrink: 0; margin-top: .06rem; }

    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .spin { animation: spin .7s linear infinite; display: inline-block; }

    @media (min-width: 981px) {
        html, body { height: 100%; overflow: hidden; }
        .auth-page,
        .auth-shell,
        .auth-left,
        .auth-right { height: 100vh; min-height: 100vh; }
        .auth-right { padding-block: clamp(1rem, 2.4vh, 2.25rem); }
        .auth-form-wrap { max-height: calc(100vh - 2rem); }
    }

    @media (max-width: 980px) {
        html, body { overflow: auto; }
        #auth-particles { display: none; }
        .auth-page { padding: 0; }
        .auth-shell { border: 0; border-radius: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .auth-left { min-height: auto; padding: 1.35rem; }
        .auth-story { display: none; }
        .auth-right { align-items: flex-start; flex: 1; min-height: auto; overflow-y: auto; padding: 1.25rem 1rem 1.5rem; }
        .auth-form-wrap { box-shadow: none; width: 100%; }
    }

    @media (min-width: 981px) and (max-height: 900px) {
        .auth-left { padding-block: 2rem; }
        .auth-right { padding-block: 1rem; }
        .auth-form-wrap { width: min(100%, 440px); }
        .form-header { margin-bottom: 20px; }
        .form-header h2 { font-size: 1.45rem; margin-bottom: .22rem; }
        .form-header p { font-size: .8rem; line-height: 1.38; }
        .auth-alert, .form-note { margin-bottom: .7rem; padding: .6rem .72rem; }
        .auth-tabs { margin-bottom: .82rem; }
        .auth-tab { min-height: 36px; padding: .45rem .65rem; }
        .auth-method-title { font-size: .78rem; margin: .78rem 0 .5rem; }
        .field-group { margin-bottom: 20px; }
        .field-label { font-size: .64rem; margin-bottom: .28rem; }
        .field-input { border-radius: 10px; font-size: .86rem; padding-block: .6rem; }
        .field-help { font-size: .66rem; margin-top: .25rem; }
        .auth-row { margin-bottom: .75rem; }
        .btn-signin { border-radius: 10px; padding: .68rem .9rem; }
        .auth-divider { margin: .72rem 0 .52rem; }
        .sso-form { border-radius: 0; padding: 0; }
        .sso-form .field-group { margin-bottom: .78rem; }
        .sso-btn { min-height: 38px; padding: .52rem .65rem; }
        .sso-note { font-size: .66rem; margin-top: .38rem; }
        .trial-cta { border-radius: 11px; padding: .68rem; }
        .trial-cta-title { font-size: .76rem; }
        .trial-cta-text { font-size: .66rem; }
        .auth-switch-link { font-size: .76rem; margin-top: .72rem; }
    }

    @media (min-width: 981px) and (max-height: 740px) {
        .auth-left { padding-block: 1.4rem; }
        .auth-brand { transform: scale(.92); transform-origin: left top; }
        .auth-story h1 { font-size: 2.2rem; margin-bottom: .65rem; }
        .auth-story p { font-size: .82rem; line-height: 1.45; }
        .auth-kicker { font-size: .64rem; margin-bottom: .65rem; padding: .32rem .62rem; }
        .form-header-icon { height: 48px; margin-bottom: .72rem; width: 48px; }
        .auth-capabilities { gap: .5rem; margin-top: .9rem; }
        .capability { border-radius: 11px; min-height: 92px; padding: .68rem; }
        .capability i { height: 28px; width: 28px; }
        .capability-title { font-size: .72rem; }
        .capability-text { font-size: .62rem; }
        .form-header { margin-bottom: 20px; }
        .form-header h2 { font-size: 1.3rem; }
        .form-header p { font-size: .74rem; }
        .auth-tabs { border-radius: 12px; margin-bottom: 20px; }
        .auth-tab { border-radius: 9px; font-size: .72rem; min-height: 32px; padding: .34rem .55rem; }
        .auth-method-title { font-size: .72rem; margin: .55rem 0 .36rem; }
        .field-group { margin-bottom: 20px; }
        .field-label { font-size: .58rem; margin-bottom: .2rem; }
        .field-input { font-size: .8rem; padding-bottom: .48rem; padding-top: .48rem; }
        .field-help { font-size: .6rem; line-height: 1.25; }
        .auth-row { margin-bottom: .55rem; }
        .custom-check { font-size: .72rem; }
        .btn-signin { font-size: .82rem; padding: .54rem .75rem; }
        .auth-divider { font-size: .62rem; margin: .52rem 0 .36rem; }
        .sso-form { padding: 0; }
        .sso-btn { font-size: .74rem; min-height: 34px; padding: .42rem .58rem; }
        .sso-note { font-size: .6rem; margin-top: .28rem; }
        .trial-cta { padding: .52rem; }
        .trial-cta-title { font-size: .7rem; }
        .trial-cta-text { display: none; }
        .trial-cta-link { font-size: .68rem; padding: .42rem .55rem; }
        .form-note { font-size: .7rem; margin-bottom: .52rem; padding: .52rem .62rem; }
        .auth-switch-link { font-size: .7rem; margin-top: .52rem; }
    }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-shell">
        <section class="auth-left">
            <div id="auth-particles" aria-hidden="true"></div>

            <div class="auth-brand">
                <div class="brand-icon">
                    @if($siteLogo)
                        <img src="{{ Storage::url($siteLogo) }}" alt="{{ $siteTitle }}">
                    @else
                        <i class="bi bi-grid-1x2-fill"></i>
                    @endif
                </div>
                <div>
                    <div class="brand-name">{{ $siteTitle }}</div>
                    <div class="brand-sub">{{ $siteSubtitle }}</div>
                </div>
            </div>

            <div class="auth-story">
                <div class="auth-kicker"><i class="bi bi-layers-fill"></i> Modular Business Suite</div>
                <h1>One portal for people, assets, software and payroll.</h1>
                <p>A subscription-ready platform where every organization gets only the modules they need: HRMS, ITAM, SAM, Payroll, Support and Supplier collaboration.</p>

                <div class="auth-capabilities">
                    <div class="capability">
                        <i class="bi bi-person-vcard-fill"></i>
                        <div><div class="capability-title">HRMS</div><div class="capability-text">Employees, documents, attendance and leaves.</div></div>
                    </div>
                    <div class="capability">
                        <i class="bi bi-box-seam-fill"></i>
                        <div><div class="capability-title">ITAM</div><div class="capability-text">Assets, assignments, handovers and maintenance.</div></div>
                    </div>
                    <div class="capability">
                        <i class="bi bi-display-fill"></i>
                        <div><div class="capability-title">SAM</div><div class="capability-text">Software licenses, seats and compliance.</div></div>
                    </div>
                    <div class="capability">
                        <i class="bi bi-cash-stack"></i>
                        <div><div class="capability-title">Payroll</div><div class="capability-text">Salary setup, payroll runs and payslips.</div></div>
                    </div>
                    <div class="capability">
                        <i class="bi bi-headset"></i>
                        <div><div class="capability-title">Support</div><div class="capability-text">Tickets, comments and supporting files.</div></div>
                    </div>
                    <div class="capability">
                        <i class="bi bi-building"></i>
                        <div><div class="capability-title">Supplier</div><div class="capability-text">Purchase visibility and supplier portal.</div></div>
                    </div>
                </div>
            </div>


        </section>

        <section class="auth-right">
            <div class="auth-form-wrap">
                @yield('content')
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
if (
    window.particlesJS
    && document.getElementById('auth-particles')
    && window.matchMedia('(min-width: 981px)').matches
    && !window.matchMedia('(prefers-reduced-motion: reduce)').matches
) {
    particlesJS('auth-particles', {
        particles: {
            number: {
                value: 118,
                density: { enable: true, value_area: 760 }
            },
            color: { value: ['#ffffff', '#bfdbfe', '#67e8f9', '#c4b5fd', '#f0abfc'] },
            shape: {
                type: ['circle', 'star'],
                stroke: { width: 0, color: '#000000' }
            },
            opacity: {
                value: 0.62,
                random: true,
                anim: {
                    enable: true,
                    speed: 0.35,
                    opacity_min: 0.12,
                    sync: false
                }
            },
            size: {
                value: 2.8,
                random: true,
                anim: {
                    enable: true,
                    speed: 0.7,
                    size_min: 0.35,
                    sync: false
                }
            },
            line_linked: {
                enable: true,
                distance: 105,
                color: '#93c5fd',
                opacity: 0.07,
                width: 0.7
            },
            move: {
                enable: true,
                speed: 0.28,
                direction: 'none',
                random: true,
                straight: false,
                out_mode: 'out',
                bounce: false
            }
        },
        interactivity: {
            detect_on: 'canvas',
            events: {
                onhover: { enable: false },
                onclick: { enable: false },
                resize: true
            }
        },
        retina_detect: true
    });
}
</script>
@stack('scripts')
</body>
</html>
