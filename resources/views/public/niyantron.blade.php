<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Niyantron - World of Softwares</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --ink: #0b1220;
            --panel: #101a2d;
            --panel-soft: #16243d;
            --text: #eaf2ff;
            --muted: #9fb0ca;
            --line: rgba(255,255,255,.14);
            --blue: #4f8cff;
            --cyan: #2ee4ff;
            --green: #2de39f;
            --gold: #ffc857;
            --pink: #ff6bd6;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            background: #07101e;
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
        }

        .site-nav {
            backdrop-filter: blur(18px);
            background: rgba(7,16,30,.72);
            border-bottom: 1px solid var(--line);
            left: 0;
            position: fixed;
            right: 0;
            top: 0;
            z-index: 30;
        }
        .brand-mark {
            align-items: center;
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            border-radius: 10px;
            color: #fff;
            display: inline-flex;
            font-weight: 900;
            height: 34px;
            justify-content: center;
            width: 34px;
        }
        .site-nav a { color: var(--text); }
        .site-nav .nav-links a { color: #cbd8ea; }
        .site-nav .nav-links a:hover { color: #fff; }

        .btn {
            border-radius: 999px;
            font-weight: 800;
            padding: .72rem 1.1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--blue), #2868ff);
            border: 0;
            box-shadow: 0 14px 30px rgba(79,140,255,.28);
        }
        .btn-outline-light { border-color: rgba(255,255,255,.28); color: #fff; }

        .universe-hero {
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }
        #softwareUniverse {
            background: #07101e;
            display: block;
            height: 100%;
            inset: 0;
            position: absolute;
            width: 100%;
            z-index: 0;
        }
        .universe-hero::after {
            background:
                linear-gradient(90deg, rgba(7,16,30,.94) 0%, rgba(7,16,30,.78) 38%, rgba(7,16,30,.18) 72%, rgba(7,16,30,.55) 100%),
                linear-gradient(180deg, rgba(7,16,30,.2) 0%, rgba(7,16,30,.78) 100%);
            content: "";
            inset: 0;
            pointer-events: none;
            position: absolute;
            z-index: 1;
        }
        .hero-content {
            min-height: 100vh;
            padding: 8.5rem 0 4rem;
            position: relative;
            z-index: 2;
        }
        .hero-copy {
            max-width: 650px;
        }
        .kicker {
            align-items: center;
            background: rgba(255,255,255,.08);
            border: 1px solid var(--line);
            border-radius: 999px;
            color: #d9e8ff;
            display: inline-flex;
            font-size: 13px;
            font-weight: 850;
            gap: .5rem;
            padding: .48rem .82rem;
        }
        h1 {
            color: #fff;
            font-size: 72px;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.02;
            margin: 1.2rem 0 0;
        }
        h2 {
            color: #fff;
            font-size: 42px;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.08;
        }
        h3, h4, h5 { letter-spacing: 0; }
        .lead {
            color: var(--muted);
            font-size: 18px;
            line-height: 1.75;
        }
        .hero-lead { max-width: 580px; }

        .hero-product-panel {
            background: rgba(16,26,45,.78);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(0,0,0,.3);
            margin-top: 2rem;
            max-width: 620px;
            padding: 1rem;
        }
        .product-dock {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .dock-item {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.11);
            border-radius: 14px;
            color: #fff;
            min-height: 112px;
            padding: .85rem;
            text-decoration: none;
            transition: border-color .18s ease, background .18s ease, transform .18s ease;
        }
        .dock-item:hover {
            background: rgba(255,255,255,.1);
            border-color: rgba(79,140,255,.65);
            color: #fff;
            transform: translateY(-2px);
        }
        .dock-icon {
            align-items: center;
            border-radius: 11px;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            margin-bottom: .7rem;
            width: 34px;
        }
        .dock-item strong {
            display: block;
            font-size: 14px;
            line-height: 1.15;
        }
        .dock-item span {
            color: var(--muted);
            display: block;
            font-size: 12px;
            line-height: 1.35;
            margin-top: .25rem;
        }

        .scene-hint {
            align-items: center;
            bottom: 1.5rem;
            color: rgba(234,242,255,.72);
            display: inline-flex;
            font-size: 13px;
            gap: .5rem;
            position: absolute;
            right: 1.5rem;
            z-index: 2;
        }
        .scene-hint i { color: var(--cyan); }

        .section {
            background: #07101e;
            padding: 5rem 0;
            position: relative;
        }
        .section.alt { background: #0a1424; }
        .section-title {
            max-width: 760px;
            margin-bottom: 2rem;
        }
        .section-title p {
            color: var(--muted);
            font-size: 17px;
            line-height: 1.7;
            margin: .9rem 0 0;
        }
        .panel {
            background: linear-gradient(180deg, rgba(22,36,61,.96), rgba(16,26,45,.96));
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 20px 54px rgba(0,0,0,.22);
            height: 100%;
            padding: 1.35rem;
        }
        .panel p, .muted { color: var(--muted); }
        .icon-box {
            align-items: center;
            border-radius: 14px;
            display: inline-flex;
            height: 44px;
            justify-content: center;
            width: 44px;
        }
        .product-zone {
            background: linear-gradient(135deg, rgba(22,36,61,.96), rgba(9,20,36,.96));
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 28px 70px rgba(0,0,0,.24);
            overflow: hidden;
        }
        .product-command {
            background:
                linear-gradient(135deg, rgba(79,140,255,.95), rgba(46,228,255,.62)),
                #10223d;
            min-height: 360px;
            padding: 1.3rem;
        }
        .command-card {
            background: rgba(5,13,26,.46);
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 18px;
            color: #fff;
            padding: 1rem;
        }
        .command-grid {
            display: grid;
            gap: .9rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 1rem;
        }
        .software-node-list {
            display: grid;
            gap: .9rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .software-node {
            align-items: center;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 16px;
            display: flex;
            gap: .85rem;
            min-height: 84px;
            padding: 1rem;
        }
        .software-node strong { color: #fff; display: block; }
        .software-node span { color: var(--muted); font-size: 13px; }
        .timeline {
            border-left: 2px solid rgba(255,255,255,.13);
            padding-left: 1.3rem;
        }
        .timeline-item {
            margin-bottom: 1.35rem;
            position: relative;
        }
        .timeline-item::before {
            background: var(--cyan);
            border: 4px solid #07101e;
            border-radius: 50%;
            box-shadow: 0 0 0 1px rgba(255,255,255,.18);
            content: "";
            height: 18px;
            left: -1.93rem;
            position: absolute;
            top: .2rem;
            width: 18px;
        }
        .cta-band {
            background: linear-gradient(135deg, #11213a, #091426);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(0,0,0,.22);
            color: #fff;
            padding: 2rem;
        }
        footer {
            background: #050b15;
            border-top: 1px solid var(--line);
            color: var(--muted);
            padding: 1.5rem 0;
        }
        footer a { color: var(--muted); }
        footer a:hover { color: #fff; }

        @media (max-width: 991.98px) {
            h1 { font-size: 52px; }
            h2 { font-size: 34px; }
            .universe-hero::after {
                background: linear-gradient(180deg, rgba(7,16,30,.92), rgba(7,16,30,.74));
            }
            .product-dock { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 767.98px) {
            .site-nav .nav-links { display: none !important; }
            .hero-content { padding: 7rem 0 3rem; }
            h1 { font-size: 40px; }
            h2 { font-size: 30px; }
            .lead { font-size: 16px; }
            .product-dock,
            .command-grid,
            .software-node-list { grid-template-columns: 1fr; }
            .scene-hint { display: none; }
        }
    </style>
</head>
<body>
    <nav class="site-nav">
        <div class="container py-3 d-flex align-items-center justify-content-between">
            <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="brand-mark">N</span><strong>Niyantron</strong>
            </a>
            <div class="nav-links d-flex align-items-center gap-4 small fw-bold">
                <a href="#products" class="text-decoration-none">Software Worlds</a>
                <a href="#platform" class="text-decoration-none">Platform</a>
                <a href="/about" class="text-decoration-none">About</a>
                <a href="/contact" class="text-decoration-none">Contact</a>
                <a href="https://partner.niyantron.com" class="text-decoration-none">Partners</a>
            </div>
            <a href="https://opsbridge.niyantron.com/login" class="btn btn-primary btn-sm">Product Login</a>
        </div>
    </nav>

    <main>
        <section class="universe-hero" id="home">
            <canvas id="softwareUniverse" aria-label="Interactive Niyantron software universe"></canvas>
            <div class="container hero-content d-flex align-items-center">
                <div class="hero-copy">
                    <span class="kicker"><i class="bi bi-stars"></i> Enter the world of softwares</span>
                    <h1>Niyantron is a universe of business software products.</h1>
                    <p class="lead hero-lead mt-4">One mother company. Multiple focused products. A connected platform where organizations, partners, subscriptions and software worlds move together.</p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="#products" class="btn btn-primary"><i class="bi bi-rocket-takeoff me-1"></i>Explore Software Worlds</a>
                        <a href="https://partner.niyantron.com" class="btn btn-outline-light"><i class="bi bi-person-workspace me-1"></i>Partner With Us</a>
                    </div>

                    <div class="hero-product-panel">
                        <div class="product-dock">
                            <a class="dock-item" href="https://opsbridge.niyantron.com">
                                <span class="dock-icon" style="background:rgba(46,228,255,.16);color:var(--cyan)"><i class="bi bi-hdd-network"></i></span>
                                <strong>OpsBridge</strong>
                                <span>Live IT operations software</span>
                            </a>
                            <a class="dock-item" href="https://partner.niyantron.com">
                                <span class="dock-icon" style="background:rgba(45,227,159,.16);color:var(--green)"><i class="bi bi-person-workspace"></i></span>
                                <strong>Partner Hub</strong>
                                <span>Leads and commission world</span>
                            </a>
                            <a class="dock-item" href="#products">
                                <span class="dock-icon" style="background:rgba(255,200,87,.16);color:var(--gold)"><i class="bi bi-clipboard-data"></i></span>
                                <strong>Future Suite</strong>
                                <span>More software worlds coming</span>
                            </a>
                            <a class="dock-item" href="https://platform.niyantron.com/login">
                                <span class="dock-icon" style="background:rgba(255,107,214,.16);color:var(--pink)"><i class="bi bi-command"></i></span>
                                <strong>Platform Core</strong>
                                <span>Control center for products</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="scene-hint"><i class="bi bi-mouse"></i> Move pointer across the software worlds</div>
        </section>

        <section id="products" class="section">
            <div class="container">
                <div class="section-title">
                    <span class="kicker"><i class="bi bi-grid-3x3-gap"></i> Software worlds</span>
                    <h2 class="mt-3">Each product gets its own world. Niyantron keeps the universe connected.</h2>
                    <p>Customers should feel they are using a focused product. Behind the scenes, Niyantron keeps product access, partners, onboarding and subscriptions connected.</p>
                </div>

                <div class="product-zone">
                    <div class="row g-0">
                        <div class="col-lg-5 product-command">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <strong>OpsBridge World</strong>
                                <span class="badge bg-success">Live</span>
                            </div>
                            <div class="command-grid">
                                <div class="command-card"><div class="small text-white-50">Assets</div><div class="fs-3 fw-bold">ITAM</div></div>
                                <div class="command-card"><div class="small text-white-50">People</div><div class="fs-3 fw-bold">HRMS</div></div>
                                <div class="command-card"><div class="small text-white-50">Software</div><div class="fs-3 fw-bold">SAM</div></div>
                                <div class="command-card"><div class="small text-white-50">Lifecycle</div><div class="fs-3 fw-bold">AMC</div></div>
                            </div>
                        </div>
                        <div class="col-lg-7 p-4 p-lg-5">
                            <h3 class="fw-bold text-white">OpsBridge is the first live Niyantron product world.</h3>
                            <p class="muted mt-3">It connects IT assets, endpoint agents, software compliance, employees, vendors, repair workflows and disposal into one operating system for organizations.</p>
                            <div class="software-node-list mt-4">
                                <div class="software-node"><span class="icon-box bg-primary-subtle text-primary"><i class="bi bi-box-seam"></i></span><div><strong>Asset Control</strong><span>Procurement, assignment, repair and disposal</span></div></div>
                                <div class="software-node"><span class="icon-box bg-success-subtle text-success"><i class="bi bi-shield-check"></i></span><div><strong>Endpoint Layer</strong><span>Device enrollment, discovery and commands</span></div></div>
                                <div class="software-node"><span class="icon-box bg-warning-subtle text-warning"><i class="bi bi-disc"></i></span><div><strong>Software Governance</strong><span>Licenses, audit packs and remediation</span></div></div>
                                <div class="software-node"><span class="icon-box bg-info-subtle text-info"><i class="bi bi-people"></i></span><div><strong>Employee Operations</strong><span>Attendance, leave, payroll and self-service</span></div></div>
                            </div>
                            <a href="https://opsbridge.niyantron.com" class="btn btn-primary mt-4">Open OpsBridge</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="platform" class="section alt">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-5">
                        <span class="kicker"><i class="bi bi-layers"></i> Mother company architecture</span>
                        <h2 class="mt-3">One Niyantron platform. Many product experiences.</h2>
                        <p class="lead mt-3">As we launch more products, each one can have its own identity, URL and workflow. Niyantron remains the common layer for products, partners, subscriptions and growth.</p>
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div class="col-md-6"><div class="panel"><span class="icon-box bg-primary-subtle text-primary"><i class="bi bi-globe2"></i></span><h5 class="fw-bold mt-3 text-white">Product Worlds</h5><p class="mb-0">OpsBridge today, more product worlds tomorrow.</p></div></div>
                            <div class="col-md-6"><div class="panel"><span class="icon-box bg-success-subtle text-success"><i class="bi bi-person-workspace"></i></span><h5 class="fw-bold mt-3 text-white">Partner Network</h5><p class="mb-0">Partners bring leads and grow with product sales.</p></div></div>
                            <div class="col-md-6"><div class="panel"><span class="icon-box bg-warning-subtle text-warning"><i class="bi bi-kanban"></i></span><h5 class="fw-bold mt-3 text-white">Common Control</h5><p class="mb-0">Subscriptions, billing and onboarding stay visible.</p></div></div>
                            <div class="col-md-6"><div class="panel"><span class="icon-box bg-info-subtle text-info"><i class="bi bi-building-check"></i></span><h5 class="fw-bold mt-3 text-white">Organization Layer</h5><p class="mb-0">Customers get focused access to the products they use.</p></div></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-5">
                    <div class="col-lg-5">
                        <h2>Where the universe expands next</h2>
                        <p class="lead">The roadmap is not about adding pages. It is about creating product worlds that solve real operational problems.</p>
                    </div>
                    <div class="col-lg-7">
                        <div class="timeline">
                            <div class="timeline-item"><strong class="text-white">OpsBridge live foundation</strong><p class="mb-0 muted">ITAM, HRMS, SAM, endpoint agent, AMC and disposal modules under one product.</p></div>
                            <div class="timeline-item"><strong class="text-white">Partner-led growth</strong><p class="mb-0 muted">Partner onboarding, lead tracking, customer conversion and commission workflow.</p></div>
                            <div class="timeline-item"><strong class="text-white">Product family expansion</strong><p class="mb-0 muted">New software products can join the same Niyantron universe without confusing product users.</p></div>
                        </div>
                    </div>
                </div>

                <div class="cta-band d-lg-flex align-items-center justify-content-between gap-4 mt-5">
                    <div>
                        <h2 class="mb-2">Build with us from the first chapter.</h2>
                        <p class="mb-lg-0 muted">Join the Niyantron partner network and help organizations enter better software worlds for operational control.</p>
                    </div>
                    <a href="https://partner.niyantron.com" class="btn btn-light flex-shrink-0 mt-3 mt-lg-0">Partner Program</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3 small">
            <div><strong class="text-white">Niyantron</strong> is building a world of operational software products.</div>
            <div class="d-flex gap-3">
                <a href="/about" class="text-decoration-none">About</a>
                <a href="/contact" class="text-decoration-none">Contact</a>
                <a href="https://partner.niyantron.com" class="text-decoration-none">Partners</a>
            </div>
        </div>
    </footer>

    <script type="module">
        import * as THREE from 'https://unpkg.com/three@0.165.0/build/three.module.js';

        const canvas = document.getElementById('softwareUniverse');
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 100);
        const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

        const coreGroup = new THREE.Group();
        const orbitGroup = new THREE.Group();
        const pointer = new THREE.Vector2(0, 0);
        scene.add(coreGroup, orbitGroup);

        const ambient = new THREE.AmbientLight(0x8fb7ff, 1.3);
        const keyLight = new THREE.PointLight(0x4f8cff, 50, 18);
        keyLight.position.set(0, 4, 5);
        const rimLight = new THREE.PointLight(0x2ee4ff, 35, 16);
        rimLight.position.set(-4, -2, 4);
        scene.add(ambient, keyLight, rimLight);

        const starGeometry = new THREE.BufferGeometry();
        const starCount = 700;
        const positions = new Float32Array(starCount * 3);
        for (let i = 0; i < starCount; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 26;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 16;
            positions[i * 3 + 2] = -Math.random() * 18 - 3;
        }
        starGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const stars = new THREE.Points(starGeometry, new THREE.PointsMaterial({ color: 0x87a8d8, size: 0.018, transparent: true, opacity: 0.76 }));
        scene.add(stars);

        const coreMaterial = new THREE.MeshStandardMaterial({
            color: 0x4f8cff,
            emissive: 0x173a88,
            metalness: 0.55,
            roughness: 0.25,
        });
        const core = new THREE.Mesh(new THREE.IcosahedronGeometry(1.05, 3), coreMaterial);
        coreGroup.add(core);

        const halo = new THREE.Mesh(
            new THREE.TorusGeometry(1.55, 0.012, 12, 160),
            new THREE.MeshBasicMaterial({ color: 0x2ee4ff, transparent: true, opacity: 0.52 })
        );
        halo.rotation.x = Math.PI / 2.6;
        coreGroup.add(halo);

        const worlds = [
            { name: 'OpsBridge', color: 0x2ee4ff, radius: 3.3, speed: 0.45, size: 0.42, y: 0.15, url: 'https://opsbridge.niyantron.com' },
            { name: 'Partner Hub', color: 0x2de39f, radius: 4.25, speed: 0.32, size: 0.34, y: -0.35, url: 'https://partner.niyantron.com' },
            { name: 'Future Suite', color: 0xffc857, radius: 5.05, speed: 0.24, size: 0.3, y: 0.42, url: '#products' },
            { name: 'Platform Core', color: 0xff6bd6, radius: 2.55, speed: 0.58, size: 0.28, y: -0.65, url: 'https://platform.niyantron.com/login' },
        ];
        const worldMeshes = [];

        worlds.forEach((world, index) => {
            const ring = new THREE.Mesh(
                new THREE.TorusGeometry(world.radius, 0.006, 8, 180),
                new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.12 })
            );
            ring.rotation.x = Math.PI / 2;
            orbitGroup.add(ring);

            const material = new THREE.MeshStandardMaterial({
                color: world.color,
                emissive: world.color,
                emissiveIntensity: 0.24,
                metalness: 0.4,
                roughness: 0.28,
            });
            const mesh = new THREE.Mesh(new THREE.SphereGeometry(world.size, 36, 24), material);
            mesh.userData = { ...world, angle: index * 1.58 };
            orbitGroup.add(mesh);
            worldMeshes.push(mesh);
        });

        camera.position.set(0.4, 0.5, 8.6);

        function resize() {
            const width = canvas.clientWidth || window.innerWidth;
            const height = canvas.clientHeight || window.innerHeight;
            renderer.setSize(width, height, false);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
        }

        function animate() {
            requestAnimationFrame(animate);
            const elapsed = performance.now() * 0.001;

            core.rotation.x = elapsed * 0.22;
            core.rotation.y = elapsed * 0.35;
            halo.rotation.z = elapsed * 0.24;
            orbitGroup.rotation.x = -0.24 + pointer.y * 0.12;
            orbitGroup.rotation.y = pointer.x * 0.16;
            stars.rotation.y = elapsed * 0.012;

            worldMeshes.forEach((mesh) => {
                const data = mesh.userData;
                const angle = data.angle + elapsed * data.speed;
                mesh.position.set(Math.cos(angle) * data.radius, data.y + Math.sin(angle * 1.4) * 0.2, Math.sin(angle) * data.radius * 0.38);
                mesh.rotation.y = elapsed * 0.7;
                mesh.rotation.x = elapsed * 0.28;
            });

            renderer.render(scene, camera);
        }

        window.addEventListener('resize', resize);
        window.addEventListener('pointermove', (event) => {
            pointer.x = (event.clientX / window.innerWidth - 0.5) * 2;
            pointer.y = (event.clientY / window.innerHeight - 0.5) * -2;
        });
        canvas.addEventListener('click', (event) => {
            const bounds = canvas.getBoundingClientRect();
            const mouse = new THREE.Vector2(
                ((event.clientX - bounds.left) / bounds.width) * 2 - 1,
                -((event.clientY - bounds.top) / bounds.height) * 2 + 1
            );
            const raycaster = new THREE.Raycaster();
            raycaster.setFromCamera(mouse, camera);
            const hit = raycaster.intersectObjects(worldMeshes)[0];
            if (hit?.object?.userData?.url) {
                window.location.href = hit.object.userData.url;
            }
        });

        resize();
        animate();
    </script>
</body>
</html>
