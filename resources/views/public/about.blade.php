<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Niyantron</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --ink:#102033; --muted:#607085; --line:#dce5ee; }
        body { background:#f7fafc; color:var(--ink); font-family:Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .nav-wrap { backdrop-filter:blur(16px); background:rgba(255,255,255,.9); border-bottom:1px solid var(--line); position:fixed; top:0; left:0; right:0; z-index:20; }
        .brand-mark { align-items:center; background:#102033; border-radius:10px; color:#fff; display:inline-flex; font-weight:900; height:34px; justify-content:center; width:34px; }
        .hero { min-height:58vh; padding:8.5rem 0 4rem; position:relative; overflow:hidden; }
        .hero:before { background:linear-gradient(135deg,#fff 0%,#eef6ff 55%,#f7fafc 100%); content:""; inset:0; position:absolute; z-index:-1; }
        h1 { font-size:clamp(2.2rem,5vw,4.5rem); font-weight:900; letter-spacing:0; line-height:1.05; }
        h2 { font-weight:900; letter-spacing:0; }
        .lead { color:var(--muted); line-height:1.75; }
        .btn { border-radius:999px; font-weight:800; padding:.72rem 1.1rem; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:0 16px 40px rgba(16,32,51,.07); height:100%; padding:1.5rem; }
        .icon-box { align-items:center; border-radius:14px; display:inline-flex; height:44px; justify-content:center; width:44px; }
        .section { padding:5rem 0; }
        .story-card { background:#102033; border-radius:24px; color:#fff; padding:2rem; }
        .principle { border-left:4px solid #1e66f5; padding-left:1rem; }
        @media (max-width:767.98px) { .hero { padding-top:7rem; } .nav-links { display:none!important; } }
    </style>
</head>
<body>
    <nav class="nav-wrap">
        <div class="container py-3 d-flex justify-content-between align-items-center">
            <a href="/" class="d-flex align-items-center gap-2 text-dark text-decoration-none"><span class="brand-mark">N</span><strong>Niyantron</strong></a>
            <div class="nav-links d-flex align-items-center gap-4 small fw-bold">
                <a href="/" class="text-dark text-decoration-none">Home</a>
                <a href="/about" class="text-dark text-decoration-none">About</a>
                <a href="/contact" class="text-dark text-decoration-none">Contact</a>
                <a href="https://partner.niyantron.com" class="text-dark text-decoration-none">Partners</a>
            </div>
            <a href="https://opsbridge.niyantron.com/login" class="btn btn-primary btn-sm">Product Login</a>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-7">
                        <h1>We are building a product company for operational control.</h1>
                        <p class="lead mt-4">Niyantron is the mother company behind OpsBridge and future business products. Our direction is simple: help organizations control the moving parts of daily operations with software that is clear, accountable and practical.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="story-card">
                            <div class="small text-white-50 text-uppercase fw-bold mb-2">Company belief</div>
                            <h3 class="fw-bold">Good software should make responsibility visible.</h3>
                            <p class="text-white-50 mb-0">Assets, people, vendors, subscriptions and partners should not disappear into spreadsheets and messages. They should be owned, tracked and acted on.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="row g-3">
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-primary-subtle text-primary"><i class="bi bi-layers"></i></span><h5 class="fw-bold mt-3">Mother brand</h5><p class="text-muted mb-0">Niyantron manages multiple products under one trusted company identity.</p></div></div>
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-success-subtle text-success"><i class="bi bi-hdd-network"></i></span><h5 class="fw-bold mt-3">First product</h5><p class="text-muted mb-0">OpsBridge is our first live product for IT assets, employees, software and repairs.</p></div></div>
                    <div class="col-md-4"><div class="panel"><span class="icon-box bg-warning-subtle text-warning"><i class="bi bi-person-workspace"></i></span><h5 class="fw-bold mt-3">Partner growth</h5><p class="text-muted mb-0">Partners help us onboard organizations and grow product adoption with commission visibility.</p></div></div>
                </div>
            </div>
        </section>

        <section class="section pt-0">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-5">
                        <h2>Our product principles</h2>
                        <p class="lead">Every Niyantron product should feel focused to the user, while the platform remains connected behind the scenes.</p>
                    </div>
                    <div class="col-lg-7">
                        <div class="principle mb-4"><h5 class="fw-bold">Clarity before complexity</h5><p class="text-muted mb-0">Users should understand what a page does, what action is expected, and what is pending.</p></div>
                        <div class="principle mb-4"><h5 class="fw-bold">Separate products, common control</h5><p class="text-muted mb-0">Organizations should experience each product independently while Niyantron manages subscriptions and partners centrally.</p></div>
                        <div class="principle"><h5 class="fw-bold">Real workflows, not decoration</h5><p class="text-muted mb-0">We build around actual operational steps: onboarding, approvals, assets, payments, repair, disposal and compliance.</p></div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('public.partials.advisor')
</body>
</html>
