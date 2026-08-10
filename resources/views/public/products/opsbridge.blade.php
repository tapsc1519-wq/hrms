@extends('public.layouts.product')
@section('title','OpsBridge - IT Operations and Workforce Control')
@section('description','Control IT assets, employees, software, endpoint devices, maintenance and compliance from one connected operational workspace.')
@section('scene-glow','#2ee4ff')
@section('login-url','https://opsbridge.niyantron.com/login')
@section('login-label','Open OpsBridge')
@section('content')
<main>
<section class="hero"><div class="container hero-grid">
    <div class="hero-copy"><span class="kicker"><i class="bi bi-hdd-network"></i>Niyantron OpsBridge</span><h1>Every asset. Every employee. <span class="gradient-text">One operational truth.</span></h1><p>OpsBridge connects IT assets, endpoint intelligence, employees, software governance, maintenance and compliance so teams can act from evidence instead of fragmented spreadsheets.</p><div class="actions"><a class="btn btn-primary" href="https://opsbridge.niyantron.com/login">Open OpsBridge <i class="bi bi-arrow-right"></i></a><a class="btn btn-secondary" href="/contact">Request a guided demo</a></div></div>
    <div class="scene" aria-hidden="true"><div class="orbit o1"></div><div class="orbit o2"></div><i class="node n1"></i><i class="node n2"></i><i class="node n3"></i><div class="scene-core"><div class="command-head"><div><small>OPSBRIDGE CONTROL GRAPH</small><h3>Live operations</h3></div><span class="status">● Connected</span></div><div class="command-body"><div class="signal"><div class="signal-top"><span>Endpoint intelligence</span><span class="status">1,248 online</span></div><p>Discovery, hardware health, software evidence and remote actions.</p></div><div class="signal"><div class="signal-top"><span>Asset lifecycle</span><span class="status">Traceable</span></div><p>Procurement to assignment, repair, recovery and responsible disposal.</p></div><div class="signal"><div class="signal-top"><span>People operations</span><span class="status">In sync</span></div><p>Employee records, attendance, leave, payroll and self-service.</p></div></div></div></div>
</div></section>
<section class="section alt"><div class="container"><div class="section-head"><div class="eyebrow">Connected capabilities</div><h2>One platform for the moving parts of daily operations.</h2><p>Each module works independently, while shared identity, permissions, audit history and reporting keep the organization connected.</p></div><div class="feature-grid">
@foreach([
['bi-box-seam','IT Asset Management','Track acquisition, ownership, assignment, location, custody, warranty, repair and disposal for every asset.'],
['bi-cpu','Endpoint Agent','Enroll Windows, Linux and macOS devices for automated inventory, discovery, health signals and controlled commands.'],
['bi-disc','Software Governance','Reconcile installations with entitlements, control requests, detect compliance gaps and produce audit evidence.'],
['bi-people','HRMS','Manage employee profiles, documents, attendance, leave, shifts, payroll and employee self-service.'],
['bi-tools','Maintenance and AMC','Plan preventive maintenance, manage breakdowns, vendors, service contracts, repairs and return-to-service.'],
['bi-shield-check','Security and Audit','Role-based access, tenant separation, immutable activity history and management-ready compliance reporting.'],
['bi-cart-check','Procurement','Control suppliers, purchase requests, approvals, purchase orders and receiving without losing asset lineage.'],
['bi-recycle','Lifecycle and Disposal','Recover assets, approve transfers, sanitize devices and maintain evidence through resale or disposal.'],
['bi-graph-up-arrow','Executive Analytics','See asset exposure, software risk, workforce status, service performance and operational exceptions.']
] as $i=>$feature)<article class="feature"><span class="number">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span><div class="icon"><i class="bi {{ $feature[0] }}"></i></div><h3>{{ $feature[1] }}</h3><p>{{ $feature[2] }}</p></article>@endforeach
</div></div></section>
<section class="section"><div class="container"><div class="section-head"><div class="eyebrow">From request to evidence</div><h2>Workflows people can follow without chasing context.</h2><p>OpsBridge guides each role toward the next action while preserving approval boundaries and a complete operational record.</p></div><div class="flow"><div class="flow-step"><span>1</span><h3>Request</h3><p>An employee or team raises a structured requirement.</p></div><div class="flow-step"><span>2</span><h3>Review</h3><p>Approvers see policy, availability, cost and risk context.</p></div><div class="flow-step"><span>3</span><h3>Execute</h3><p>Teams assign, procure, repair, recover or remediate.</p></div><div class="flow-step"><span>4</span><h3>Prove</h3><p>Every decision becomes searchable evidence and reporting.</p></div></div><div class="outcomes"><div class="outcome"><strong>One record</strong><p>Hardware, software, people and service history stay connected instead of living in separate files.</p></div><div class="outcome"><strong>Clear ownership</strong><p>Every request, asset and exception has a custodian, responsible team and visible next action.</p></div></div></div></section>
<div class="container cta"><div><div class="eyebrow">See your operations clearly</div><h2>Bring control to assets, endpoints and people.</h2><p>Start with the modules you need and expand without rebuilding your operational foundation.</p></div><div class="actions"><a class="btn btn-primary" href="/contact">Request OpsBridge demo</a><a class="btn btn-secondary" href="https://opsbridge.niyantron.com/login">Customer login</a></div></div>
</main>
@endsection
