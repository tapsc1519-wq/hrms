<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Niyantron Partner Program</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --ink:#102033; --muted:#607085; --line:#dce5ee; }
        body { background:#f7fafc; color:var(--ink); font-family:Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .nav-wrap { backdrop-filter:blur(16px); background:rgba(255,255,255,.88); border-bottom:1px solid var(--line); left:0; position:fixed; right:0; top:0; z-index:20; }
        .brand-mark { align-items:center; background:#102033; border-radius:10px; color:#fff; display:inline-flex; font-weight:900; height:34px; justify-content:center; width:34px; }
        .hero { min-height:90vh; overflow:hidden; padding:8.5rem 0 4rem; position:relative; }
        .hero:before { background:linear-gradient(135deg,#fff 0%,#eef7f4 48%,#eef4ff 100%); content:""; inset:0; position:absolute; z-index:-1; }
        h1 { font-size:clamp(2.35rem,6vw,5rem); font-weight:900; letter-spacing:0; line-height:1.02; }
        h2 { font-size:clamp(1.75rem,3vw,2.6rem); font-weight:900; letter-spacing:0; }
        .lead { color:var(--muted); font-size:1.05rem; line-height:1.7; }
        .pill { align-items:center; background:#fff; border:1px solid var(--line); border-radius:999px; display:inline-flex; font-size:.8rem; font-weight:800; gap:.45rem; padding:.45rem .8rem; }
        .btn { border-radius:999px; font-weight:800; padding:.72rem 1.1rem; }
        .partner-visual { background:#102033; border-radius:24px; box-shadow:0 28px 70px rgba(16,32,51,.24); color:#fff; overflow:hidden; padding:1.2rem; }
        .deal-row { align-items:center; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); border-radius:16px; display:flex; gap:1rem; margin-top:.8rem; padding:.85rem; }
        .deal-icon { align-items:center; border-radius:12px; display:inline-flex; flex-shrink:0; height:42px; justify-content:center; width:42px; }
        .section { padding:5rem 0; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:0 16px 40px rgba(16,32,51,.07); height:100%; padding:1.4rem; }
        .icon-box { align-items:center; border-radius:14px; display:inline-flex; height:42px; justify-content:center; width:42px; }
        .step { align-items:flex-start; display:flex; gap:1rem; }
        .step-no { align-items:center; background:#102033; border-radius:12px; color:#fff; display:inline-flex; flex-shrink:0; font-weight:900; height:38px; justify-content:center; width:38px; }
        .cta { background:#102033; border-radius:24px; color:#fff; padding:2rem; }
        @media (max-width:767.98px) { .hero { padding-top:7rem; } .nav-links { display:none!important; } }
    </style>
</head>
<body>
    <nav class="nav-wrap">
        <div class="container py-3 d-flex justify-content-between align-items-center">
            <a href="https://niyantron.com" class="d-flex align-items-center gap-2 text-dark text-decoration-none">
                <span class="brand-mark">N</span><strong>Niyantron Partners</strong>
            </a>
            <div class="nav-links d-flex align-items-center gap-4 small fw-bold">
                <a href="#program" class="text-dark text-decoration-none">Program</a>
                <a href="#flow" class="text-dark text-decoration-none">Flow</a>
                <a href="https://platform.niyantron.com/login" class="text-dark text-decoration-none">Partner Login</a>
            </div>
            <a href="https://platform.niyantron.com/login" class="btn btn-primary btn-sm">Login</a>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="pill mb-4"><i class="bi bi-cash-coin text-success"></i> Earn commission by onboarding organizations</span>
                        <h1>Partner with Niyantron and grow with OpsBridge.</h1>
                        <p class="lead mt-4">Help organizations adopt OpsBridge for IT assets, employee workflows, software compliance, repairs and disposal. Track leads, conversions and commissions from the partner portal.</p>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a href="https://platform.niyantron.com/login" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Partner Login</a>
                            <a href="#program" class="btn btn-outline-dark"><i class="bi bi-info-circle me-1"></i>View Program</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="partner-visual">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <strong>Partner Pipeline</strong><span class="badge bg-success">Commission ready</span>
                            </div>
                            <div class="deal-row"><span class="deal-icon bg-primary"><i class="bi bi-funnel"></i></span><div><strong>Lead Created</strong><div class="text-white-50 small">Capture company, contact and product interest.</div></div></div>
                            <div class="deal-row"><span class="deal-icon bg-info"><i class="bi bi-kanban"></i></span><div><strong>Sales Follow-up</strong><div class="text-white-50 small">Move lead through demo, proposal and conversion.</div></div></div>
                            <div class="deal-row"><span class="deal-icon bg-success"><i class="bi bi-building-check"></i></span><div><strong>Organization Onboarded</strong><div class="text-white-50 small">Customer subscription starts under your partner record.</div></div></div>
                            <div class="deal-row"><span class="deal-icon bg-warning"><i class="bi bi-currency-rupee"></i></span><div><strong>Commission Tracked</strong><div class="text-white-50 small">Commission is calculated after customer payment.</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="program" class="section">
            <div class="container">
                <div class="row align-items-end mb-4">
                    <div class="col-lg-7">
                        <h2>A partner program built for real onboarding work.</h2>
                        <p class="lead mb-0">Niyantron partners help find, educate and onboard organizations. The platform keeps the commercial flow visible.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-primary-subtle text-primary"><i class="bi bi-person-plus"></i></span><h5 class="fw-bold mt-3">Bring qualified leads</h5><p class="text-muted mb-0">Introduce organizations that need ITAM, HRMS, SAM, AMC or disposal workflows.</p></div></div>
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-success-subtle text-success"><i class="bi bi-graph-up-arrow"></i></span><h5 class="fw-bold mt-3">Track opportunity status</h5><p class="text-muted mb-0">Use the partner portal to see leads, converted customers and commission progress.</p></div></div>
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-warning-subtle text-warning"><i class="bi bi-wallet2"></i></span><h5 class="fw-bold mt-3">Earn commission</h5><p class="text-muted mb-0">Commission is mapped to product subscription and recorded when the customer pays.</p></div></div>
                </div>
            </div>
        </section>

        <section id="flow" class="section pt-0">
            <div class="container">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-5">
                        <h2>How the flow works</h2>
                        <p class="lead">We keep the commercial process simple so partners and the platform team stay aligned.</p>
                    </div>
                    <div class="col-lg-7">
                        <div class="panel">
                            <div class="step mb-4"><span class="step-no">1</span><div><h6 class="fw-bold mb-1">Partner submits or shares a qualified lead</h6><p class="text-muted mb-0">Company, contact person, expected value and interested product are recorded.</p></div></div>
                            <div class="step mb-4"><span class="step-no">2</span><div><h6 class="fw-bold mb-1">Niyantron converts the lead into an organization</h6><p class="text-muted mb-0">The customer gets OpsBridge subscription and first admin login.</p></div></div>
                            <div class="step mb-4"><span class="step-no">3</span><div><h6 class="fw-bold mb-1">Customer onboarding is tracked</h6><p class="text-muted mb-0">Checklist shows admin login, modules, subscription, first login and setup completion.</p></div></div>
                            <div class="step"><span class="step-no">4</span><div><h6 class="fw-bold mb-1">Commission is generated after payment</h6><p class="text-muted mb-0">Super Admin approves and marks partner commission as paid.</p></div></div>
                        </div>
                    </div>
                </div>
                <div class="cta d-lg-flex justify-content-between align-items-center gap-4 mt-5">
                    <div>
                        <h2 class="mb-2">Ready to work with Niyantron?</h2>
                        <p class="text-white-50 mb-lg-0">Partner self-application is coming next. For now, the platform team creates partner access manually.</p>
                    </div>
                    <a href="https://platform.niyantron.com/login" class="btn btn-light mt-3 mt-lg-0 flex-shrink-0">Partner Login</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
