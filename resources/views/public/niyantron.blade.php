<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Niyantron - Business Control Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --ink:#102033; --muted:#607085; --line:#dce5ee; --blue:#1e66f5; --green:#12a878; }
        body { background:#f7fafc; color:var(--ink); font-family:Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .site-nav { backdrop-filter:blur(18px); background:rgba(255,255,255,.88); border-bottom:1px solid rgba(220,229,238,.8); left:0; position:fixed; right:0; top:0; z-index:20; }
        .brand-mark { align-items:center; background:#102033; border-radius:10px; color:#fff; display:inline-flex; font-weight:900; height:34px; justify-content:center; width:34px; }
        .hero { min-height:92vh; overflow:hidden; padding:8.5rem 0 4rem; position:relative; }
        .hero:before { background:radial-gradient(circle at 18% 18%, rgba(30,102,245,.18), transparent 30%), radial-gradient(circle at 82% 12%, rgba(18,168,120,.18), transparent 28%), linear-gradient(135deg,#fff 0%,#edf6ff 55%,#f8fbff 100%); content:""; inset:0; position:absolute; z-index:-1; }
        h1 { font-size:clamp(2.35rem,6vw,5.2rem); font-weight:900; letter-spacing:0; line-height:1.02; }
        h2 { font-size:clamp(1.75rem,3vw,2.6rem); font-weight:900; letter-spacing:0; }
        .lead { color:var(--muted); font-size:1.05rem; line-height:1.7; }
        .pill { align-items:center; background:#fff; border:1px solid var(--line); border-radius:999px; display:inline-flex; font-size:.8rem; font-weight:800; gap:.45rem; padding:.45rem .8rem; }
        .btn { border-radius:999px; font-weight:800; padding:.72rem 1.1rem; }
        .hero-visual { background:#102033; border:1px solid rgba(255,255,255,.18); border-radius:24px; box-shadow:0 28px 70px rgba(16,32,51,.24); color:#fff; overflow:hidden; padding:1rem; }
        .screen-bar { align-items:center; border-bottom:1px solid rgba(255,255,255,.12); display:flex; gap:.5rem; padding:.25rem .25rem .9rem; }
        .dot { border-radius:50%; height:10px; width:10px; }
        .app-grid { display:grid; gap:.75rem; grid-template-columns:1fr 1fr; padding-top:1rem; }
        .app-tile { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); border-radius:16px; min-height:112px; padding:1rem; }
        .app-tile strong { display:block; font-size:1.2rem; margin-top:.65rem; }
        .section { padding:5rem 0; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:0 16px 40px rgba(16,32,51,.07); height:100%; padding:1.4rem; }
        .icon-box { align-items:center; border-radius:14px; display:inline-flex; height:42px; justify-content:center; width:42px; }
        .product-card { background:#fff; border:1px solid var(--line); border-radius:20px; box-shadow:0 20px 50px rgba(16,32,51,.08); overflow:hidden; }
        .product-preview { background:linear-gradient(135deg,#102033,#1e66f5); color:#fff; min-height:190px; padding:1.2rem; }
        .metric { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); border-radius:14px; padding:.8rem; }
        .cta-band { background:#102033; border-radius:24px; color:#fff; padding:2rem; }
        @media (max-width:767.98px) { .hero { padding-top:7rem; } .app-grid { grid-template-columns:1fr; } .nav-links { display:none!important; } }
    </style>
</head>
<body>
    <nav class="site-nav">
        <div class="container py-3 d-flex align-items-center justify-content-between">
            <a href="/" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                <span class="brand-mark">N</span><strong>Niyantron</strong>
            </a>
            <div class="nav-links d-flex align-items-center gap-4 small fw-bold">
                <a href="#products" class="text-decoration-none text-dark">Products</a>
                <a href="#platform" class="text-decoration-none text-dark">Platform</a>
                <a href="https://partner.niyantron.com" class="text-decoration-none text-dark">Partners</a>
            </div>
            <a href="https://opsbridge.niyantron.com/login" class="btn btn-primary btn-sm">Product Login</a>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="pill mb-4"><i class="bi bi-command text-primary"></i> One company. Multiple control products.</span>
                        <h1>Business operations, controlled from one product family.</h1>
                        <p class="lead mt-4">Niyantron builds focused SaaS products for organizations that need clear control over assets, employees, vendors, subscriptions, partners and daily operations.</p>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a href="https://opsbridge.niyantron.com" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Explore OpsBridge</a>
                            <a href="https://partner.niyantron.com" class="btn btn-outline-dark"><i class="bi bi-people me-1"></i>Become a Partner</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="hero-visual">
                            <div class="screen-bar">
                                <span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span>
                                <span class="ms-auto small text-white-50">niyantron platform</span>
                            </div>
                            <div class="app-grid">
                                <div class="app-tile"><i class="bi bi-hdd-network fs-4 text-info"></i><strong>OpsBridge</strong><span class="text-white-50 small">ITAM, HRMS, SAM, AMC, Disposal</span></div>
                                <div class="app-tile"><i class="bi bi-person-workspace fs-4 text-warning"></i><strong>Partners</strong><span class="text-white-50 small">Leads, commissions, onboarding</span></div>
                                <div class="app-tile"><i class="bi bi-buildings fs-4 text-success"></i><strong>Tenants</strong><span class="text-white-50 small">Organizations and access</span></div>
                                <div class="app-tile"><i class="bi bi-graph-up-arrow fs-4 text-primary"></i><strong>Growth</strong><span class="text-white-50 small">Subscriptions and renewals</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="products" class="section">
            <div class="container">
                <div class="row align-items-end mb-4">
                    <div class="col-lg-7">
                        <h2>First product: OpsBridge</h2>
                        <p class="lead mb-0">A practical control center for IT operations, assets, employees, vendors and software compliance.</p>
                    </div>
                </div>
                <div class="product-card">
                    <div class="row g-0">
                        <div class="col-lg-5 product-preview">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <strong>OpsBridge</strong><span class="badge bg-success">Live</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-6"><div class="metric"><div class="small text-white-50">Assets</div><div class="fs-3 fw-bold">ITAM</div></div></div>
                                <div class="col-6"><div class="metric"><div class="small text-white-50">Employees</div><div class="fs-3 fw-bold">HRMS</div></div></div>
                                <div class="col-6"><div class="metric"><div class="small text-white-50">Software</div><div class="fs-3 fw-bold">SAM</div></div></div>
                                <div class="col-6"><div class="metric"><div class="small text-white-50">Repairs</div><div class="fs-3 fw-bold">AMC</div></div></div>
                            </div>
                        </div>
                        <div class="col-lg-7 p-4 p-lg-5">
                            <h3 class="fw-bold">OpsBridge connects operational details that usually live in separate tools.</h3>
                            <div class="row g-3 mt-3">
                                <div class="col-md-6"><div class="panel shadow-none"><span class="icon-box bg-primary-subtle text-primary"><i class="bi bi-box-seam"></i></span><strong class="d-block mt-3">Asset lifecycle</strong><p class="text-muted mb-0 small">Procurement, assignments, repairs, AMC, disposal and buyers.</p></div></div>
                                <div class="col-md-6"><div class="panel shadow-none"><span class="icon-box bg-success-subtle text-success"><i class="bi bi-shield-check"></i></span><strong class="d-block mt-3">Endpoint control</strong><p class="text-muted mb-0 small">Device enrollment, discovery and endpoint commands.</p></div></div>
                                <div class="col-md-6"><div class="panel shadow-none"><span class="icon-box bg-warning-subtle text-warning"><i class="bi bi-disc"></i></span><strong class="d-block mt-3">Software governance</strong><p class="text-muted mb-0 small">Discovery, licenses, policies, audit packs and remediation.</p></div></div>
                                <div class="col-md-6"><div class="panel shadow-none"><span class="icon-box bg-info-subtle text-info"><i class="bi bi-people"></i></span><strong class="d-block mt-3">Employee workflows</strong><p class="text-muted mb-0 small">Employee portal, attendance, leave, payroll and self-service.</p></div></div>
                            </div>
                            <a href="https://opsbridge.niyantron.com" class="btn btn-primary mt-4">Open OpsBridge</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="platform" class="section pt-0">
            <div class="container">
                <div class="row g-3">
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-primary-subtle text-primary"><i class="bi bi-grid-3x3-gap"></i></span><h5 class="fw-bold mt-3">Product family</h5><p class="text-muted mb-0">Host multiple products while each customer sees a focused product experience.</p></div></div>
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-success-subtle text-success"><i class="bi bi-person-workspace"></i></span><h5 class="fw-bold mt-3">Partner ecosystem</h5><p class="text-muted mb-0">Partners generate leads, track opportunities and earn commission on product sales.</p></div></div>
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-warning-subtle text-warning"><i class="bi bi-kanban"></i></span><h5 class="fw-bold mt-3">Common control</h5><p class="text-muted mb-0">One platform team manages products, subscriptions, customers and onboarding.</p></div></div>
                </div>
                <div class="cta-band d-lg-flex align-items-center justify-content-between gap-4 mt-5">
                    <div>
                        <h2 class="mb-2">Build with us from the first chapter.</h2>
                        <p class="mb-lg-0 text-white-50">Join the Niyantron partner network and help organizations adopt better operational control.</p>
                    </div>
                    <a href="https://partner.niyantron.com" class="btn btn-light flex-shrink-0 mt-3 mt-lg-0">Partner Program</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
