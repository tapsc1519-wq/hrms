@extends('public.layouts.product')
@section('title','Niyantron ERP - Procurement, Quality, Inventory and Commerce')
@section('description','Run procurement, category-specific quality checks, serialized inventory, CRM, sales, finance and private B2B bidding in one enterprise ERP.')
@section('scene-glow','#7457ff')
@section('login-url','https://erp.niyantron.com/login')
@section('login-label','Sign in to ERP')
@section('content')
<main>
<section class="hero"><div class="container hero-grid">
    <div class="hero-copy"><span class="kicker"><i class="bi bi-command"></i>Niyantron Enterprise ERP</span><h1>Source intelligently. Inspect dynamically. <span class="gradient-text">Trade with control.</span></h1><p>A company-aware ERP for new and pre-owned products, category-specific quality workflows, serialized inventory, CRM, finance and private B2B commerce.</p><div class="actions"><a class="btn btn-primary" href="https://erp.niyantron.com/login">Sign in to Niyantron ERP <i class="bi bi-arrow-right"></i></a><a class="btn btn-secondary" href="/contact">Request an ERP walkthrough</a></div></div>
    <div class="scene" aria-hidden="true"><div class="orbit o1"></div><div class="orbit o2"></div><i class="node n1"></i><i class="node n2"></i><i class="node n3"></i><div class="scene-core"><div class="command-head"><div><small>ENTERPRISE CONTROL GRAPH</small><h3>Source to sale</h3></div><span class="status">● Live</span></div><div class="command-body"><div class="signal"><div class="signal-top"><span>Supplier selection</span><span class="status">Qualified</span></div><p>Capture supplier-site opportunities before committing the purchase.</p></div><div class="signal"><div class="signal-top"><span>Dynamic quality</span><span class="status">Category aware</span></div><p>Different forms, conditions, defects and grades for every category.</p></div><div class="signal"><div class="signal-top"><span>Inventory identity</span><span class="status">Traceable</span></div><p>Unit IDs, category barcode series, serials, IMEI and ownership lineage.</p></div></div></div></div>
</div></section>
<section class="section alt"><div class="container"><div class="section-head"><div class="eyebrow">Enterprise capabilities</div><h2>Built for real procurement and resale operations.</h2><p>Catalogue intelligence, quality configuration and inventory identity work together without mixing legal-company ownership or ledgers.</p></div><div class="feature-grid">
@foreach([
['bi-diagram-3','Multi-company Foundation','Organization, legal company, branch and warehouse hierarchy with company-specific accounting and inventory ownership.'],
['bi-boxes','Catalogue Intelligence','Shared categories with controlled brands, brand-dependent models, specifications, variants and category behavior.'],
['bi-cart-plus','Flexible Procurement','Direct purchase, approved-request purchase, identified-unit purchase and known-product bulk purchase with optional steps.'],
['bi-shield-check','Dynamic Quality Studio','Configure category-specific sections, parameters, conditions, defects, evidence, grades and readiness rules.'],
['bi-upc-scan','Barcode and Traceability','Generate internal unit IDs and category series while preserving serial numbers, IMEI and external identifiers.'],
['bi-building-check','Supplier Management','Onboard businesses or individual sellers, track compliance, quality, returns, terms and supplier ledgers.'],
['bi-person-lines-fill','CRM','Run purchase and sales calling, qualify leads, assign mature opportunities and continue into controlled transactions.'],
['bi-bag-check','Sales and Customers','Manage business or individual buyers, orders, fulfillment, invoicing, receipts, returns and customer ledgers.'],
['bi-bank','Finance and GST','Company-specific chart of accounts, payables, receivables, taxation, posting controls and audit-ready ledgers.'],
['bi-megaphone','Private Offers and Bidding','Publish stock or supplier-available opportunities to connected buyers for bids, counters and conditional awards.'],
['bi-graph-up','CEO Dashboard','Monitor revenue, cash, receivables, payables, committed purchases, inventory readiness and operational risk.'],
['bi-file-earmark-bar-graph','Reports','Decision-focused procurement, QC, inventory, sales and financial reports with export-ready evidence.']
] as $i=>$feature)<article class="feature"><span class="number">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span><div class="icon"><i class="bi {{ $feature[0] }}"></i></div><h3>{{ $feature[1] }}</h3><p>{{ $feature[2] }}</p></article>@endforeach
</div></div></section>
<section class="section"><div class="container"><div class="section-head"><div class="eyebrow">One guided operating flow</div><h2>From opportunity to accountable stock and sale.</h2><p>The route adapts to purchase type, QC location and category rules while keeping the user clear about what happens next.</p></div><div class="flow"><div class="flow-step"><span>1</span><h3>Discover</h3><p>Qualify a lead, supplier lot or direct purchase requirement.</p></div><div class="flow-step"><span>2</span><h3>Select and inspect</h3><p>Identify products and perform supplier-site or warehouse QC when required.</p></div><div class="flow-step"><span>3</span><h3>Receive and identify</h3><p>Create product variants, unit identities, barcodes and inventory ownership.</p></div><div class="flow-step"><span>4</span><h3>Sell and account</h3><p>Reserve, fulfill, invoice, collect and post to company-specific ledgers.</p></div></div><div class="outcomes"><div class="outcome"><strong>Dynamic by category</strong><p>A laptop, mobile, AC, printer, chair or consumable can own different specifications, tracking and quality behavior.</p></div><div class="outcome"><strong>Safe by company</strong><p>Catalogue intelligence may be shared, while accounting, inventory ownership and legal documents remain separated.</p></div></div></div></section>
<div class="container cta"><div><div class="eyebrow">Build an accountable commerce operation</div><h2>Connect sourcing, quality, stock, sales and finance.</h2><p>Use one guided ERP whether the business is operated by one person or a structured multi-team organization.</p></div><div class="actions"><a class="btn btn-primary" href="/contact">Request ERP demo</a><a class="btn btn-secondary" href="https://erp.niyantron.com/login">Customer login</a></div></div>
</main>
@endsection
