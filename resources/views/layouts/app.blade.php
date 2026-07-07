<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteTitle    = \App\Models\Setting::get('site_title',    'ITAM');
        $siteSubtitle = \App\Models\Setting::get('site_subtitle', 'Asset Management');
        $siteLogo     = \App\Models\Setting::get('site_logo');
        $siteFavicon  = \App\Models\Setting::get('site_favicon');
    @endphp
    <title>@yield('title', 'Dashboard') — {{ $siteTitle }}</title>
    @if($siteFavicon)
        <link rel="icon" href="{{ Storage::url($siteFavicon) }}">
    @endif

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
    /* ═══════════════════════════════════════════════
       ROOT VARIABLES
    ═══════════════════════════════════════════════ */
    :root {
        --sidebar-width: 262px;
        --topbar-height: 66px;
        --body-bg: #f0f4f8;
        --accent: #3b82f6;
        --card-radius: 16px;
        --card-shadow: 0 2px 12px rgba(15,23,42,.08);
        --card-shadow-hover: 0 8px 30px rgba(15,23,42,.14);
        --font-xs: .75rem;
        --font-sm: .83rem;
        --font-base: .875rem;
        --font-md: .95rem;
        --font-lg: 1.08rem;
        --font-xl: 1.25rem;
        --font-2xl: 1.35rem;
    }

    * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
    body {
        background: var(--body-bg);
        color: #1e293b;
        font-size: var(--font-base);
        line-height: 1.45;
        overflow-x: hidden;
        text-rendering: geometricPrecision;
    }

    /* Portal typography contract */
    #main-content {
        font-size: var(--font-base);
        line-height: 1.45;
    }
    #main-content h1,
    #main-content h2,
    #main-content h3,
    #main-content h4,
    #main-content h5,
    #main-content h6 {
        letter-spacing: 0;
        line-height: 1.25;
    }
    #main-content h1 { font-size: var(--font-2xl); font-weight: 800; }
    #main-content h2 { font-size: var(--font-xl); font-weight: 800; }
    #main-content h3 { font-size: var(--font-lg); font-weight: 750; }
    #main-content h4 { font-size: var(--font-2xl); font-weight: 800; }
    #main-content h5 { font-size: var(--font-md); font-weight: 750; }
    #main-content h6 { font-size: var(--font-base); font-weight: 750; }
    #main-content p,
    #main-content li,
    #main-content dd,
    #main-content dt {
        font-size: var(--font-base);
        line-height: 1.48;
    }
    #main-content .small,
    #main-content small {
        font-size: var(--font-xs) !important;
        line-height: 1.42;
    }
    #main-content .lead {
        font-size: var(--font-md);
        line-height: 1.5;
    }
    #main-content code {
        font-size: var(--font-sm);
    }

    /* ═══════════════════════════════════════════════
       SIDEBAR
    ═══════════════════════════════════════════════ */
    #sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: linear-gradient(180deg, #0b1120 0%, #101827 60%, #0f172a 100%);
        position: fixed;
        top: 0; left: 0;
        z-index: 1000;
        transition: transform .3s ease;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 4px 0 24px rgba(0,0,0,.25);
    }

    /* Brand */
    #sidebar .sidebar-brand {
        flex-shrink: 0;
        padding: 1.3rem 1.4rem 1.1rem;
        border-bottom: 1px solid rgba(255,255,255,.06);
        background: rgba(0,0,0,.2);
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .sidebar-brand-icon {
        width: 38px; height: 38px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(59,130,246,.4);
    }
    .sidebar-brand-text h5 {
        color: #fff;
        font-weight: 800;
        margin: 0;
        font-size: 1.05rem;
        letter-spacing: -.2px;
    }
    .sidebar-brand-text span {
        font-size: .65rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        font-weight: 500;
    }

    /* Nav scroll area */
    #sidebar .sidebar-nav {
        flex: 1 1 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: .75rem 0 1rem;
    }
    #sidebar .sidebar-nav::-webkit-scrollbar { width: 3px; }
    #sidebar .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    #sidebar .sidebar-nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

    /* Section titles */
    .sidebar-section-title {
        padding: .8rem 1.35rem .25rem;
        font-size: .61rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 1.6px;
    }

    /* Nav links */
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .52rem .9rem;
        margin: .1rem .75rem;
        color: #8492a6;
        text-decoration: none;
        font-size: .835rem;
        font-weight: 500;
        border-radius: 10px;
        transition: all .2s;
    }
    .sidebar-link i {
        font-size: .95rem;
        width: 20px;
        flex-shrink: 0;
        text-align: center;
    }
    .sidebar-link:hover {
        background: rgba(255,255,255,.07);
        color: #e2e8f0;
    }
    .sidebar-link.active {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 4px 15px rgba(59,130,246,.38);
        font-weight: 600;
    }
    .sidebar-group-toggle {
        align-items: center;
        background: transparent;
        border: 0;
        color: #64748b;
        cursor: pointer;
        display: flex;
        font-size: .61rem;
        font-weight: 750;
        justify-content: space-between;
        letter-spacing: 1.4px;
        margin: .5rem .75rem .12rem;
        padding: .42rem .58rem;
        text-transform: uppercase;
        width: calc(100% - 1.5rem);
    }
    .sidebar-group-toggle:hover { color: #cbd5e1; }
    .sidebar-group-toggle i {
        font-size: .72rem;
        transition: transform .18s ease;
    }
    .sidebar-group-toggle.collapsed i { transform: rotate(-90deg); }
    .sidebar-group-body {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .sidebar-group-body.collapsed { display: none; }
    .sidebar-group-body .sidebar-link { margin-block: .05rem; }

    /* Sidebar user footer */
    .sidebar-footer {
        flex-shrink: 0;
        padding: .85rem 1rem;
        border-top: 1px solid rgba(255,255,255,.06);
        background: rgba(0,0,0,.15);
        display: flex;
        align-items: center;
        gap: .7rem;
    }
    .sidebar-footer-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .sidebar-footer-name {
        font-size: .8rem;
        font-weight: 600;
        color: #e2e8f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar-footer-role {
        font-size: .68rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ═══════════════════════════════════════════════
       MAIN LAYOUT
    ═══════════════════════════════════════════════ */
    #main-content {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        transition: margin-left .3s ease;
    }

    /* ═══════════════════════════════════════════════
       TOPBAR
    ═══════════════════════════════════════════════ */
    #topbar {
        height: var(--topbar-height);
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        padding: 0 1.75rem;
        position: sticky;
        top: 0;
        z-index: 999;
        box-shadow: 0 1px 0 rgba(0,0,0,.06);
    }
    .topbar-page-title {
        font-size: .9rem;
        font-weight: 700;
        color: #1e293b;
    }
    .topbar-breadcrumb {
        font-size: .75rem;
        color: #94a3b8;
    }
    .topbar-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .topbar-icon-btn {
        width: 38px; height: 38px;
        border-radius: 10px;
        border: 1.5px solid #e9ecef;
        background: #f8fafc;
        display: flex; align-items: center; justify-content: center;
        color: #64748b;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
        font-size: 1rem;
    }
    .topbar-icon-btn:hover { background: #eff6ff; border-color: #bfdbfe; color: #3b82f6; }
    .notification-trigger { position: relative; }
    .notification-trigger .notification-badge {
        align-items: center;
        background: #ef4444;
        border: 2px solid #fff;
        border-radius: 999px;
        color: #fff;
        display: inline-flex;
        font-size: .62rem;
        font-weight: 800;
        height: 19px;
        justify-content: center;
        line-height: 1;
        min-width: 19px;
        padding: 0 .25rem;
        position: absolute;
        right: -6px;
        top: -6px;
    }
    .notification-menu {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, .14);
        max-height: 430px;
        min-width: 360px;
        overflow: hidden;
        padding: 0;
    }
    .notification-menu-header {
        align-items: center;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        padding: .78rem .9rem;
    }
    .notification-menu-title {
        color: #0f172a;
        font-size: .84rem;
        font-weight: 800;
        line-height: 1.2;
    }
    .notification-menu-subtitle {
        color: #64748b;
        font-size: .68rem;
        line-height: 1.25;
        margin-top: .12rem;
    }
    .notification-list {
        max-height: 340px;
        overflow-y: auto;
        padding: .35rem;
    }
    .notification-item {
        align-items: flex-start;
        border-radius: 11px;
        color: inherit;
        display: flex;
        gap: .62rem;
        padding: .62rem;
        text-decoration: none;
        transition: background .16s, transform .16s;
    }
    .notification-item:hover {
        background: #f8fafc;
        transform: translateX(2px);
    }
    .notification-icon {
        align-items: center;
        border-radius: 10px;
        display: inline-flex;
        flex-shrink: 0;
        font-size: .88rem;
        height: 32px;
        justify-content: center;
        width: 32px;
    }
    .notification-icon.bg-purple {
        background: #ede9fe;
        color: #6d28d9;
    }
    .notification-item-title {
        color: #0f172a;
        font-size: .78rem;
        font-weight: 800;
        line-height: 1.25;
    }
    .notification-item-subtitle {
        color: #64748b;
        font-size: .68rem;
        line-height: 1.32;
        margin-top: .13rem;
    }
    .notification-count-chip {
        background: #eef2ff;
        border-radius: 999px;
        color: #3730a3;
        flex-shrink: 0;
        font-size: .66rem;
        font-weight: 800;
        line-height: 1;
        padding: .26rem .46rem;
    }
    .notification-empty {
        color: #64748b;
        padding: 1.5rem 1rem;
        text-align: center;
    }
    .notification-empty i {
        color: #22c55e;
        display: block;
        font-size: 1.8rem;
        margin-bottom: .35rem;
    }

    /* ═══════════════════════════════════════════════
       CONTENT AREA
    ═══════════════════════════════════════════════ */
    .content-area { padding: 1.75rem; }

    /* ═══════════════════════════════════════════════
       GRADIENT STAT CARDS
    ═══════════════════════════════════════════════ */
    .stat-card-gradient {
        border: none;
        border-radius: var(--card-radius);
        color: #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,.15);
        transition: transform .22s, box-shadow .22s;
        position: relative;
        overflow: hidden;
    }
    .stat-card-gradient::before {
        content: '';
        position: absolute;
        top: -25px; right: -25px;
        width: 110px; height: 110px;
        border-radius: 50%;
        background: rgba(255,255,255,.12);
        pointer-events: none;
    }
    .stat-card-gradient::after {
        content: '';
        position: absolute;
        bottom: -45px; right: 15px;
        width: 140px; height: 140px;
        border-radius: 50%;
        background: rgba(255,255,255,.07);
        pointer-events: none;
    }
    .stat-card-gradient:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 35px rgba(0,0,0,.22);
    }
    .stat-card-gradient .card-body { position: relative; z-index: 1; padding: 1.4rem 1.35rem; }
    .stat-card-gradient .stat-icon {
        width: 50px; height: 50px;
        border-radius: 13px;
        background: rgba(255,255,255,.22);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .stat-card-gradient .stat-number,
    .stat-card-gradient .stat-value {
        font-size: var(--font-xl);
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: 0;
        position: relative;
        z-index: 1;
        margin-top: .75rem;
    }
    .stat-card-gradient .stat-label {
        font-size: var(--font-xs);
        font-weight: 600;
        opacity: .85;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .stat-card-gradient .stat-sub {
        font-size: var(--font-xs);
        opacity: .75;
        margin-top: .5rem;
    }

    /* Gradient variants */
    .grad-blue    { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
    .grad-green   { background: linear-gradient(135deg, #10b981 0%, #047857 100%); }
    .grad-orange  { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); }
    .grad-purple  { background: linear-gradient(135deg, #8b5cf6 0%, #5b21b6 100%); }
    .grad-red     { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); }
    .grad-teal    { background: linear-gradient(135deg, #14b8a6 0%, #0f766e 100%); }
    .grad-indigo  { background: linear-gradient(135deg, #6366f1 0%, #3730a3 100%); }
    .grad-rose    { background: linear-gradient(135deg, #f43f5e 0%, #9f1239 100%); }
    .grad-sky     { background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); }

    /* Keep old stat-card for places not yet converted */
    .stat-card {
        border: none;
        border-radius: var(--card-radius);
        box-shadow: var(--card-shadow);
        background: #fff;
        transition: transform .2s, box-shadow .2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }

    /* ═══════════════════════════════════════════════
       TABLE / SECTION CARDS
    ═══════════════════════════════════════════════ */
    .table-card {
        background: #fff;
        border-radius: var(--card-radius);
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }
    .table-card .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.1rem 1.4rem;
        font-weight: 700;
        color: #1e293b;
        font-size: var(--font-sm);
        line-height: 1.25;
    }
    .table-card .card-body { padding: 1.25rem; }
    .table-card .card-header h1,
    .table-card .card-header h2,
    .table-card .card-header h3,
    .table-card .card-header h4,
    .table-card .card-header h5,
    .table-card .card-header h6,
    .form-card-header h1,
    .form-card-header h2,
    .form-card-header h3,
    .form-card-header h4,
    .form-card-header h5,
    .form-card-header h6 {
        font-size: var(--font-sm);
        line-height: 1.25;
        font-weight: 750;
        margin: 0;
        letter-spacing: 0;
        text-transform: none;
    }
    .table-card .card-header .small,
    .form-card-header .small {
        font-size: var(--font-xs);
        line-height: 1.35;
        font-weight: 500;
    }
    .table {
        --bs-table-color: #334155;
        font-size: var(--font-sm);
    }
    .table thead th {
        font-size: var(--font-xs);
        font-weight: 750;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .45px;
        white-space: nowrap;
    }
    .table tbody td {
        font-size: var(--font-sm);
        vertical-align: middle;
    }
    .table tbody td .fw-bold,
    .table tbody td .fw-600,
    .table tbody td .fw-700 {
        font-size: var(--font-sm);
        line-height: 1.3;
    }
    .ops-pagination {
        align-items: center;
        display: flex;
        gap: .9rem;
        justify-content: space-between;
        width: 100%;
    }
    .ops-pagination-actions,
    .ops-pagination-meta,
    .ops-page-list {
        align-items: center;
        display: flex;
        gap: .45rem;
    }
    .ops-pagination-actions { flex-shrink: 0; }
    .ops-pagination-meta {
        flex-wrap: wrap;
        justify-content: flex-end;
        min-width: 0;
    }
    .ops-pagination-summary {
        color: #64748b;
        font-size: var(--font-xs);
        font-weight: 600;
        margin-right: .35rem;
        white-space: nowrap;
    }
    .ops-pagination-summary span {
        color: #0f172a;
        font-weight: 800;
    }
    .ops-page-step,
    .ops-page-link {
        align-items: center;
        background: #fff;
        border: 1px solid #dbe4f0;
        color: #334155;
        display: inline-flex;
        font-size: var(--font-xs);
        font-weight: 750;
        justify-content: center;
        min-height: 32px;
        text-decoration: none;
        transition: all .16s ease;
    }
    .ops-page-step {
        border-radius: 8px;
        gap: .3rem;
        padding: .42rem .72rem;
    }
    .ops-page-link {
        border-radius: 7px;
        min-width: 32px;
        padding: .38rem .58rem;
    }
    .ops-page-step i {
        font-size: .72rem;
        line-height: 1;
    }
    .ops-page-step:hover,
    .ops-page-link:hover {
        background: #eef6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
        transform: translateY(-1px);
    }
    .ops-page-link.active {
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 6px 14px rgba(37, 99, 235, .22);
        color: #fff;
    }
    .ops-page-step.disabled,
    .ops-page-link.disabled {
        background: #f8fafc;
        border-color: #e2e8f0;
        box-shadow: none;
        color: #94a3b8;
        cursor: not-allowed;
        transform: none;
    }
    @media (max-width: 767.98px) {
        .ops-pagination {
            align-items: stretch;
            flex-direction: column;
        }
        .ops-pagination-actions,
        .ops-pagination-meta {
            justify-content: space-between;
            width: 100%;
        }
        .ops-pagination-summary {
            margin-right: 0;
            white-space: normal;
        }
        .ops-page-list {
            justify-content: flex-end;
            overflow-x: auto;
            padding-bottom: .12rem;
        }
    }
    .payroll-component-item {
        padding-top: .8rem !important;
        padding-bottom: .8rem !important;
    }
    .payroll-component-name {
        font-size: .84rem;
        line-height: 1.25;
        font-weight: 700;
        color: #1e293b;
        overflow-wrap: anywhere;
    }
    .payroll-component-meta {
        margin-top: .18rem;
        font-size: .72rem;
        line-height: 1.3;
        color: #64748b;
        font-weight: 500;
    }
    .payroll-component-item .badge {
        font-size: .66rem;
        padding: .28rem .48rem;
    }
    .payroll-component-item .btn-sm {
        width: 29px;
        height: 29px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
    }

    /* Badges */
    .badge {
        font-size: var(--font-xs);
        font-weight: 650;
        letter-spacing: .15px;
        line-height: 1.05;
    }
    .badge.fs-6 { font-size: var(--font-xs) !important; }

    /* Plain-language help panels */
    .help-panel {
        color: #334155;
        font-size: var(--font-sm);
        line-height: 1.45;
    }
    .help-panel-icon {
        width: 34px;
        height: 34px;
        background: #dbeafe;
        color: #1d4ed8;
    }
    .help-panel-title {
        color: #0f172a;
        font-size: var(--font-sm);
        font-weight: 750;
        line-height: 1.3;
    }
    .help-panel-section-title {
        color: #334155;
        font-size: var(--font-xs);
        font-weight: 750;
        line-height: 1.3;
        text-transform: uppercase;
        letter-spacing: .45px;
    }
    .help-panel ul {
        margin-bottom: 0;
        padding-left: 1.05rem;
    }
    .help-panel li {
        font-size: var(--font-sm);
        line-height: 1.45;
    }

    /* ═══════════════════════════════════════════════
       PAGE HEADER
    ═══════════════════════════════════════════════ */
    .page-header { margin-bottom: 1.35rem; }
    .page-header .back-link {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: var(--font-xs); font-weight: 700;
        color: #64748b; text-decoration: none;
        text-transform: uppercase; letter-spacing: .6px;
        margin-bottom: .5rem;
        transition: color .15s;
    }
    .page-header .back-link:hover { color: #3b82f6; }
    .page-header h4 {
        font-size: var(--font-2xl);
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: 0;
    }
    .page-header p {
        color: #64748b;
        font-size: var(--font-base);
        line-height: 1.45;
        margin: .25rem 0 0;
    }

    /* ═══════════════════════════════════════════════
       FORM DESIGN SYSTEM
    ═══════════════════════════════════════════════ */
    .form-control, .form-select {
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        padding: .55rem .9rem;
        font-size: var(--font-base);
        background-color: #f8fafc;
        transition: border-color .18s, box-shadow .18s, background-color .18s;
        color: #1e293b;
    }
    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3.5px rgba(59,130,246,.12);
        background-color: #fff;
        outline: none;
    }
    .form-control::placeholder { color: #a0aec0; font-size: var(--font-sm); }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #ef4444;
        background-color: #fef2f2;
    }
    .form-control.is-invalid:focus, .form-select.is-invalid:focus {
        box-shadow: 0 0 0 3.5px rgba(239,68,68,.12);
    }
    textarea.form-control { resize: vertical; min-height: 80px; }

    .form-label {
        font-size: var(--font-xs);
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: .35rem;
    }
    .req { color: #ef4444; margin-left: 2px; }
    .form-text { font-size: var(--font-xs); color: #94a3b8; margin-top: .3rem; }
    .invalid-feedback { font-size: var(--font-xs); }

    /* Form section cards */
    .form-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 8px rgba(0,0,0,.06);
        margin-bottom: 1.25rem;
    }
    .form-card-header {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .95rem 1.4rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e9ecef;
        font-size: var(--font-xs);
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: .7px;
    }
    .form-card-header .icon-wrap {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .form-card-body { padding: 1.4rem; }

    /* Icon wrap colour variants */
    .icon-blue   { background: #dbeafe; color: #2563eb; }
    .icon-green  { background: #dcfce7; color: #16a34a; }
    .icon-amber  { background: #fef3c7; color: #d97706; }
    .icon-red    { background: #fee2e2; color: #dc2626; }
    .icon-purple { background: #ede9fe; color: #7c3aed; }
    .icon-teal   { background: #ccfbf1; color: #0d9488; }
    .icon-slate  { background: #f1f5f9; color: #475569; }

    /* Form actions bar */
    .form-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .75rem;
        padding: 1.1rem 1.4rem;
        background: #f8fafc;
        border-top: 1px solid #e9ecef;
        border-radius: 0 0 16px 16px;
    }
    .form-actions .btn-cancel {
        color: #64748b;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        padding: .5rem 1.2rem;
        font-size: var(--font-base);
        font-weight: 500;
        text-decoration: none;
        transition: border-color .15s, color .15s;
    }
    .form-actions .btn-cancel:hover { border-color: #94a3b8; color: #334155; }
    .form-actions .btn-save {
        border-radius: 9px;
        padding: .55rem 1.5rem;
        font-size: var(--font-base);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }

    /* ═══════════════════════════════════════════════
       BUTTONS
    ═══════════════════════════════════════════════ */
    .btn {
        border-radius: 9px;
        font-size: var(--font-base);
        font-weight: 550;
        line-height: 1.25;
    }
    .btn-sm {
        font-size: var(--font-xs);
        line-height: 1.2;
    }
    .modal-title {
        font-size: var(--font-md);
        line-height: 1.3;
        letter-spacing: 0;
    }
    .btn-primary { background: linear-gradient(135deg,#3b82f6,#2563eb); border:none; box-shadow:0 2px 8px rgba(59,130,246,.3); }
    .btn-primary:hover { background: linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow:0 4px 14px rgba(59,130,246,.4); }
    .btn-outline-primary { border-color: #3b82f6; color: #3b82f6; }
    .btn-outline-primary:hover { background: #eff6ff; border-color: #2563eb; color: #2563eb; }

    /* Normalize older page-level inline font sizes on common UI controls */
    #main-content .table[style*="font-size"] {
        font-size: var(--font-sm) !important;
    }
    #main-content .btn[style*="font-size"] {
        font-size: var(--font-xs) !important;
    }
    #main-content .form-label[style*="font-size"] {
        font-size: var(--font-xs) !important;
    }
    #main-content .badge[style*="font-size"],
    #main-content .badge[class*="fs-"] {
        font-size: var(--font-xs) !important;
    }
    #main-content [style*="font-size:.6"],
    #main-content [style*="font-size: .6"],
    #main-content [style*="font-size:.7"],
    #main-content [style*="font-size: .7"] {
        font-size: var(--font-xs) !important;
        line-height: 1.42 !important;
    }
    #main-content [style*="font-size:.8"],
    #main-content [style*="font-size: .8"] {
        font-size: var(--font-sm) !important;
        line-height: 1.42 !important;
    }
    #main-content [style*="font-size:.9"],
    #main-content [style*="font-size: .9"] {
        font-size: var(--font-md) !important;
        line-height: 1.42 !important;
    }
    #main-content [style*="font-size:1rem"],
    #main-content [style*="font-size: 1rem"] {
        font-size: var(--font-base) !important;
        line-height: 1.42 !important;
    }
    #main-content :not(i).fs-1,
    #main-content :not(i).fs-2,
    #main-content :not(i).fs-3 {
        font-size: var(--font-xl) !important;
        line-height: 1.2 !important;
    }
    #main-content :not(i).fs-4,
    #main-content :not(i).fs-5 {
        font-size: var(--font-lg) !important;
        line-height: 1.25 !important;
    }
    #main-content div:not(.stat-number):not(.stat-value):not(.stat-mini-val)[style*="font-size:1."],
    #main-content span:not(.stat-number):not(.stat-value):not(.stat-mini-val)[style*="font-size:1."],
    #main-content h1[style*="font-size:1."],
    #main-content h2[style*="font-size:1."],
    #main-content h3[style*="font-size:1."],
    #main-content h4[style*="font-size:1."],
    #main-content h5[style*="font-size:1."],
    #main-content h6[style*="font-size:1."],
    #main-content td[style*="font-size:1."] {
        font-size: var(--font-md) !important;
        line-height: 1.32 !important;
        letter-spacing: 0 !important;
    }
    #main-content .stat-number[style*="font-size:1."],
    #main-content .stat-value[style*="font-size:1."],
    #main-content .stat-mini-val[style*="font-size:1."] {
        font-size: var(--font-xl) !important;
        line-height: 1.15 !important;
        letter-spacing: 0 !important;
    }
    #main-content dl[style*="font-size"],
    #main-content ul[style*="font-size"],
    #main-content .card-body[style*="font-size"] {
        font-size: var(--font-base) !important;
        line-height: 1.48;
    }

    /* ═══════════════════════════════════════════════
       RESPONSIVE
    ═══════════════════════════════════════════════ */
    @media (max-width: 768px) {
        #sidebar { transform: translateX(-100%); }
        #sidebar.show { transform: translateX(0); }
        #main-content { margin-left: 0; }
    }

    .guided-tour-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .58);
        z-index: 1990;
    }
    .guided-tour-highlight {
        position: relative;
        z-index: 2001 !important;
        border-radius: 14px;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .35), 0 18px 55px rgba(15, 23, 42, .28);
        outline: 2px solid rgba(59, 130, 246, .8);
        outline-offset: 3px;
    }
    .guided-tour-card {
        position: fixed;
        width: min(360px, calc(100vw - 32px));
        background: #fff;
        border: 1px solid #dbe5f0;
        border-radius: 14px;
        box-shadow: 0 22px 70px rgba(15, 23, 42, .28);
        z-index: 2002;
        padding: 1rem;
        visibility: hidden;
    }
    .guided-tour-progress {
        color: #64748b;
        font-size: var(--font-xs);
        font-weight: 700;
        text-transform: uppercase;
    }
    .guided-tour-title {
        color: #0f172a;
        font-size: var(--font-lg);
        font-weight: 800;
        margin: .35rem 0 .45rem;
    }
    .guided-tour-body {
        color: #475569;
        font-size: var(--font-sm);
        line-height: 1.5;
        margin-bottom: 1rem;
    }
    </style>
    @stack('styles')
</head>
<body>

<!-- ─────────────── SIDEBAR ─────────────── -->
<div id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon" style="{{ $siteLogo ? 'background:transparent;padding:2px;' : '' }}">
            @if($siteLogo)
                <img src="{{ Storage::url($siteLogo) }}"
                     style="width:100%;height:100%;object-fit:contain;border-radius:8px">
            @else
                <i class="bi bi-cpu-fill"></i>
            @endif
        </div>
        <div class="sidebar-brand-text">
            <h5>{{ $siteTitle }}</h5>
            <span>{{ $siteSubtitle }}</span>
        </div>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">
        @php
            $user = auth()->user();
            $org = $user?->organization;
            $hasItam = $user?->isSuperAdmin() || !$org || $org->hasModule('itam');
            $hasSam = $user?->isSuperAdmin() || !$org || $org->hasModule('sam');
            $hasHrms = $user?->isSuperAdmin() || !$org || $org->hasModule('hrms');
            $hasPayroll = $user?->isSuperAdmin() || !$org || $org->hasModule('payroll');
            $hasSupport = $user?->isSuperAdmin() || !$org || $org->hasModule('support');
            $hasSupplierPortal = $user?->isSuperAdmin() || !$org || $org->hasModule('supplier_portal');
            $canManageSupport = $hasSupport && $user?->hasPermission('tickets.manage');
            $canManageItam = $hasItam && (
                $user?->hasPermission('assets.view')
                || $user?->hasPermission('assets.create')
                || $user?->hasPermission('assets.edit')
                || $user?->hasPermission('assets.delete')
                || $user?->hasPermission('assets.import')
                || $user?->hasPermission('assets.catalog')
                || $user?->hasPermission('assignments.view')
                || $user?->hasPermission('assignments.create')
                || $user?->hasPermission('assignments.return')
                || $user?->hasPermission('requests.view')
                || $user?->hasPermission('requests.review')
                || $user?->hasPermission('requests.fulfill')
                || $user?->hasPermission('suppliers.manage')
                || $user?->hasPermission('vendors.manage')
                || $user?->hasPermission('purchase_orders.manage')
                || $user?->hasPermission('maintenance.manage')
                || $user?->hasPermission('asset.repairs.manage')
                || $user?->hasPermission('assets.disposal')
                || $user?->hasPermission('assets.disposal.request')
                || $user?->hasPermission('assets.disposal.approve')
                || $user?->hasPermission('assets.disposal.complete')
                || $user?->hasPermission('assets.disposal.view')
                || $user?->hasPermission('facilities.manage')
                || $user?->hasPermission('departments.manage')
                || $user?->hasPermission('reports.view')
            );
            $canManageHrms = $hasHrms && (
                $user?->hasPermission('hrms.dashboard')
                || $user?->hasPermission('employees.manage')
                || $user?->hasPermission('attendance.view')
                || $user?->hasPermission('attendance.manage')
                || $user?->hasPermission('attendance.regularizations.review')
                || $user?->hasPermission('leaves.manage')
                || $user?->hasPermission('leave_balances.manage')
                || $user?->hasPermission('hrms.settings')
            );
            $canManagePayroll = $hasPayroll && (
                $user?->hasPermission('payroll.setup')
                || $user?->hasPermission('payroll.run')
                || $user?->hasPermission('payroll.approve')
                || $user?->hasPermission('payroll.pay')
                || $user?->hasPermission('payroll.export')
            );
        @endphp

        {{-- ── SUPER ADMIN ─── --}}
        @if($user->isSuperAdmin())
            <div class="sidebar-section-title">Overview</div>
            <a href="{{ route('super-admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <div class="sidebar-section-title">Platform</div>
            <a href="{{ route('super-admin.organizations.index') }}"
               class="sidebar-link {{ request()->routeIs('super-admin.organizations.*') ? 'active' : '' }}">
                <i class="bi bi-building-fill"></i> Organizations
            </a>
            <a href="{{ route('super-admin.users.index') }}"
               class="sidebar-link {{ request()->routeIs('super-admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> All Accounts
            </a>
            <div class="sidebar-section-title">Configuration</div>
            <a href="{{ route('super-admin.pricing.edit') }}"
               class="sidebar-link {{ request()->routeIs('super-admin.pricing.*') ? 'active' : '' }}">
                <i class="bi bi-currency-rupee"></i> Pricing
            </a>
            <a href="{{ route('super-admin.payments.index') }}"
               class="sidebar-link {{ request()->routeIs('super-admin.payments.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Payments
            </a>
            <a href="{{ route('super-admin.settings') }}"
               class="sidebar-link {{ request()->routeIs('super-admin.settings') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        @endif

        {{-- ── ADMIN ─── --}}
        @if($user->isAdmin())
            <div class="sidebar-section-title">Overview</div>
            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>

            @if($hasItam)
            <div class="sidebar-section-title">Assets</div>
            <a href="{{ route('admin.assets.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.assets.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> Assets
            </a>
            <a href="{{ route('admin.catalog.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.catalog.*') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Asset Catalog
            </a>
            <a href="{{ route('admin.assignments.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.assignments.index') ? 'active' : '' }}">
                <i class="bi bi-person-check-fill"></i> Assignments
            </a>

            <a href="{{ route('admin.requests.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check-fill"></i> Requests
            </a>
            @if($user->hasPermission('assets.disposal') || $user->hasPermission('assets.disposal.view') || $user->hasPermission('assets.disposal.request') || $user->hasPermission('assets.disposal.approve') || $user->hasPermission('assets.disposal.complete'))
            <div class="sidebar-section-title">Asset Disposal</div>
            @if($user->hasPermission('assets.disposal.view'))
            <a href="{{ route('admin.asset-issues.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.asset-issues.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill"></i> Reported Issues
            </a>
            @endif
            @if($user->hasPermission('assets.disposal'))
            <a href="{{ route('admin.disposal-buyers.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposal-buyers.*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard"></i> Disposal Buyers
            </a>
            @endif
            @if($user->hasPermission('assets.disposal.request'))
            <a href="{{ route('admin.disposals.requests') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposals.requests') || request()->routeIs('admin.disposals.create') || request()->routeIs('admin.disposals.bulk') ? 'active' : '' }}">
                <i class="bi bi-send"></i> Disposal Requests
            </a>
            @endif
            @if($user->hasPermission('assets.disposal.approve'))
            <a href="{{ route('admin.disposals.approvals') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposals.approvals') ? 'active' : '' }}">
                <i class="bi bi-check2-square"></i> Disposal Approvals
            </a>
            @endif
            @if($user->hasPermission('assets.disposal.view'))
            <a href="{{ route('admin.disposals.history') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposals.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Disposal History
            </a>
            @endif
            @endif

            @if($user->hasPermission('asset.repairs.manage') || $user->hasPermission('asset.repairs.qc') || $user->hasPermission('asset.repairs.close') || $user->hasPermission('vendors.manage') || $user->hasPermission('reports.view'))
            <div class="sidebar-section-title">AMC & Repairs</div>
            @if($user->hasPermission('asset.repairs.manage') || $user->hasPermission('asset.repairs.qc') || $user->hasPermission('asset.repairs.close'))
            <a href="{{ route('admin.asset-repairs.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.asset-repairs.*') ? 'active' : '' }}">
                <i class="bi bi-wrench-adjustable-circle-fill"></i> Repair Jobs
            </a>
            @endif
            @if($user->hasPermission('asset.repairs.manage'))
            <a href="{{ route('admin.asset-amc-contracts.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.asset-amc-contracts.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i> AMC Contracts
            </a>
            @endif
            @if($user->hasPermission('vendors.manage'))
            <a href="{{ route('admin.vendors.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i> Repair Vendors
            </a>
            @endif
            @if($user->hasPermission('reports.view'))
            <a href="{{ route('admin.reports.maintenance') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.maintenance') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i> Repair Reports
            </a>
            @endif
            @endif

            <div class="sidebar-section-title">Procurement</div>
            <a href="{{ route('admin.suppliers.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                <i class="bi bi-building-fill"></i> Suppliers
            </a>
            <a href="{{ route('admin.purchase-orders.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.purchase-orders.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text-fill"></i> Purchase Orders
            </a>
            @endif

            @if($hasSam)
            <div class="sidebar-section-title">Software</div>
            <a href="{{ route('admin.sam-dashboard.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.sam-dashboard.*') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> SAM Overview
            </a>
            <a href="{{ route('admin.software.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software.*') ? 'active' : '' }}">
                <i class="bi bi-display-fill"></i> Software Catalog
            </a>
            @if($user->hasPermission('software.policies.manage'))
            <a href="{{ route('admin.software-policies.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-policies.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock-fill"></i> Software Policies
            </a>
            @endif
            <a href="{{ route('admin.software-licenses.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-licenses.index', 'admin.software-licenses.create', 'admin.software-licenses.show') ? 'active' : '' }}">
                <i class="bi bi-key-fill"></i> Licenses
            </a>
            <a href="{{ route('admin.software-licenses.renewals') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-licenses.renewals') ? 'active' : '' }}">
                <i class="bi bi-calendar2-check-fill"></i> Renewals
            </a>
            @if($user->hasPermission('software.optimization.view'))
            <a href="{{ route('admin.software-optimization.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-optimization.*') ? 'active' : '' }}">
                <i class="bi bi-graph-down-arrow"></i> Usage Optimization
            </a>
            @endif
            @if($user->hasPermission('software.requests.view'))
            <a href="{{ route('admin.software-requests.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-requests.*') ? 'active' : '' }}">
                <i class="bi bi-person-check-fill"></i> Software Requests
            </a>
            @endif
            <a href="{{ route('admin.software-discovery.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-discovery.*') ? 'active' : '' }}">
                <i class="bi bi-hdd-network-fill"></i> Discovery Inventory
            </a>
            @if($user->hasPermission('endpoint.view'))
            <a href="{{ route('admin.agent-sources.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.agent-sources.*') ? 'active' : '' }}">
                <i class="bi bi-pc-display-horizontal"></i> Endpoint Management
            </a>
            @endif
            <a href="{{ route('admin.software-normalization.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-normalization.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3-fill"></i> Normalization
            </a>
            <a href="{{ route('admin.software-compliance.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.software-compliance.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i> Compliance
            </a>
            @if($user->hasPermission('software.audit.export'))
            <a href="{{ route('admin.sam-audit.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.sam-audit.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-zip-fill"></i> Audit Pack
            </a>
            @endif
            @endif

            @php
                $canSeeHrmsMenu = $canManageHrms;
            @endphp
            @if($canSeeHrmsMenu)
            <div class="sidebar-section-title">HRMS</div>
            @if($user->hasPermission('hrms.dashboard'))
            <a href="{{ route('admin.hrms.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.hrms.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> HRMS Dashboard
            </a>
            @endif
            @if($user->hasPermission('employees.manage'))
            <a href="{{ route('admin.employees.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard-fill"></i> Employees
            </a>
            @endif
            @if($user->hasPermission('attendance.view'))
            <a href="{{ route('admin.attendance.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.attendance.index') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Attendance
            </a>
            <a href="{{ route('admin.attendance.summary') }}"
               class="sidebar-link {{ request()->routeIs('admin.attendance.summary') ? 'active' : '' }}">
                <i class="bi bi-calendar2-week"></i> Monthly Summary
            </a>
            @endif
            @if($user->hasPermission('attendance.regularizations.review'))
            <a href="{{ route('admin.attendance.regularizations') }}"
               class="sidebar-link {{ request()->routeIs('admin.attendance.regularizations*') ? 'active' : '' }}">
                <i class="bi bi-clock-fill"></i> Regularizations
            </a>
            @endif
            @if($user->hasPermission('hrms.settings'))
            <a href="{{ route('admin.hrms-shifts.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.hrms-shifts.*') ? 'active' : '' }}">
                <i class="bi bi-calendar3-week-fill"></i> Shifts
            </a>
            @endif
            @if($user->hasPermission('leaves.manage'))
            <a href="{{ route('admin.leaves.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.leaves.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i> Leaves
            </a>
            @endif
            @if($user->hasPermission('leave_balances.manage'))
            <a href="{{ route('admin.leave-balances.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.leave-balances.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-range-fill"></i> Leave Balances
            </a>
            @endif
            @if($user->hasPermission('hrms.settings'))
            <a href="{{ route('admin.hrms-settings.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.hrms-settings.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> HRMS Settings
            </a>
            @endif
            @endif

            @php
                $canSeePayrollMenu = $canManagePayroll;
            @endphp
            @if($canSeePayrollMenu)
            <div class="sidebar-section-title">Payroll</div>
            @if($user->hasPermission('payroll.setup'))
            <a href="{{ route('admin.payroll.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.payroll.index') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Salary Setup
            </a>
            @endif
            @if($user->hasPermission('payroll.run') || $user->hasPermission('payroll.approve') || $user->hasPermission('payroll.pay') || $user->hasPermission('payroll.export'))
            <a href="{{ route('admin.payroll.runs') }}"
               class="sidebar-link {{ request()->routeIs('admin.payroll.runs*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Payroll Runs
            </a>
            @endif
            @endif

            @if($hasSupport)
            <div class="sidebar-section-title">Support</div>
            <a href="{{ route('admin.tickets.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> Support Tickets
            </a>
            @endif

            <div class="sidebar-section-title">Operations</div>
            <a href="{{ route('admin.facilities.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.facilities.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Facilities
            </a>
            <a href="{{ route('admin.departments.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3-fill"></i> Departments
            </a>
            <a href="{{ route('admin.roles.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock-fill"></i> Roles & Permissions
            </a>
            <a href="{{ route('admin.sso-settings.edit') }}"
               class="sidebar-link {{ request()->routeIs('admin.sso-settings.*') ? 'active' : '' }}">
                <i class="bi bi-key-fill"></i> Organization SSO
            </a>

            @if($hasItam)
            <div class="sidebar-section-title">Reports</div>
            <a href="{{ route('admin.reports.assets') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.assets') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill"></i> Asset Report
            </a>
            <a href="{{ route('admin.reports.vendors') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.vendors') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i> Supplier Report
            </a>
            <a href="{{ route('admin.reports.maintenance') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.maintenance') ? 'active' : '' }}">
                <i class="bi bi-wrench-adjustable"></i> Maintenance Report
            </a>
            <a href="{{ route('admin.reports.depreciation') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.depreciation') ? 'active' : '' }}">
                <i class="bi bi-graph-down-arrow"></i> Depreciation
            </a>
            @endif
        @endif

        {{-- ── VENDOR ─── --}}
        @if($user->isSupplier() && $hasSupplierPortal)
            @php $portalPartner = $user->supplier; @endphp
            <div class="sidebar-section-title">Supplier Portal</div>
            <a href="{{ route('supplier.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('supplier.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            @if(in_array($portalPartner?->partner_type, ['supplier', 'both'], true))
            <a href="{{ route('supplier.purchase-orders.index') }}"
               class="sidebar-link {{ request()->routeIs('supplier.purchase-orders.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text-fill"></i> Purchase Orders
            </a>
            @endif
            @if(in_array($portalPartner?->partner_type, ['vendor', 'both'], true))
            <a href="{{ route('supplier.repairs.index') }}"
               class="sidebar-link {{ request()->routeIs('supplier.repairs.*') ? 'active' : '' }}">
                <i class="bi bi-wrench-adjustable"></i> Repair Jobs
            </a>
            @endif
        @endif

        {{-- ── STAFF ─── --}}
        @if($user->isStaff())
            <div class="sidebar-section-title">Overview</div>
            <a href="{{ route('staff.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            @if($hasHrms || $hasPayroll || $hasItam || $hasSam || $hasSupport)
            <div class="sidebar-section-title">Employee Self Service</div>
            @if($hasHrms)
            <a href="{{ route('staff.profile.show') }}"
               class="sidebar-link {{ request()->routeIs('staff.profile.*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard-fill"></i> My Profile
            </a>
            <a href="{{ route('staff.attendance.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.attendance.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> My Attendance
            </a>
            <a href="{{ route('staff.leaves.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.leaves.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i> My Leaves
            </a>
            @endif
            @if($hasPayroll)
            <a href="{{ route('staff.payslips.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.payslips.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> My Payslips
            </a>
            @endif
            @if($hasItam)
            <a href="{{ route('staff.my-assets.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.my-assets.index') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> My Assets
            </a>
            <a href="{{ route('staff.my-assets.repairs') }}"
               class="sidebar-link {{ request()->routeIs('staff.my-assets.repairs') ? 'active' : '' }}">
                <i class="bi bi-wrench-adjustable"></i> My Repairs
            </a>
            <a href="{{ route('staff.requests.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.requests.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-plus-fill"></i> My Requests
            </a>
            @endif
             @if($hasSam)
            <a href="{{ route('staff.my-software.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.my-software.*', 'staff.software-requests.*') ? 'active' : '' }}">
                <i class="bi bi-display-fill"></i> My Software
            </a>
            <a href="{{ route('staff.devices.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.devices.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i> Device Agent
            </a>
            @endif
            @if($hasSupport)
            <a href="{{ route('staff.tickets.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.tickets.*') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> Support Tickets
            </a>
            @endif
            @endif
            @if($canManageHrms)
            <div class="sidebar-section-title">HR Management</div>
            @if($user->hasPermission('hrms.dashboard'))
            <a href="{{ route('admin.hrms.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.hrms.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> HRMS Dashboard
            </a>
            @endif
            @if($user->hasPermission('employees.manage'))
            <a href="{{ route('admin.employees.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard-fill"></i> Employees
            </a>
            @endif
            @if($user->hasPermission('attendance.view'))
            <a href="{{ route('admin.attendance.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.attendance.index') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Attendance
            </a>
            <a href="{{ route('admin.attendance.summary') }}"
               class="sidebar-link {{ request()->routeIs('admin.attendance.summary') ? 'active' : '' }}">
                <i class="bi bi-calendar2-week"></i> Monthly Summary
            </a>
            @endif
            @if($user->hasPermission('attendance.regularizations.review'))
            <a href="{{ route('admin.attendance.regularizations') }}"
               class="sidebar-link {{ request()->routeIs('admin.attendance.regularizations*') ? 'active' : '' }}">
                <i class="bi bi-clock-fill"></i> Regularizations
            </a>
            @endif
            @if($user->hasPermission('leaves.manage'))
            <a href="{{ route('admin.leaves.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.leaves.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i> Manage Leaves
            </a>
            @endif
            @if($user->hasPermission('leave_balances.manage'))
            <a href="{{ route('admin.leave-balances.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.leave-balances.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-range-fill"></i> Leave Balances
            </a>
            @endif
            @if($user->hasPermission('hrms.settings'))
            <a href="{{ route('admin.hrms-shifts.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.hrms-shifts.*') ? 'active' : '' }}">
                <i class="bi bi-calendar3-week-fill"></i> Shifts
            </a>
            <a href="{{ route('admin.hrms-settings.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.hrms-settings.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> HRMS Settings
            </a>
            @endif
            @endif
            @if($canManagePayroll)
            <div class="sidebar-section-title">Payroll Management</div>
            @if($user->hasPermission('payroll.setup'))
            <a href="{{ route('admin.payroll.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.payroll.index') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Salary Setup
            </a>
            @endif
            @if($user->hasPermission('payroll.run') || $user->hasPermission('payroll.approve') || $user->hasPermission('payroll.pay') || $user->hasPermission('payroll.export'))
            <a href="{{ route('admin.payroll.runs') }}"
               class="sidebar-link {{ request()->routeIs('admin.payroll.runs*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Payroll Runs
            </a>
            @endif
            @endif


            @if($canManageItam)
            <div class="sidebar-section-title">ITAM Management</div>
            @if($user->hasPermission('assets.view'))
            <a href="{{ route('admin.assets.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.assets.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> Assets
            </a>
            @endif
            @if($user->hasPermission('assets.catalog'))
            <a href="{{ route('admin.catalog.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.catalog.*') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Asset Catalog
            </a>
            @endif
            @if($user->hasPermission('assets.import'))
            <a href="{{ route('admin.bulk-import.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.bulk-import.*') ? 'active' : '' }}">
                <i class="bi bi-upload"></i> Asset Import
            </a>
            @endif
            @if($user->hasPermission('assignments.view'))
            <a href="{{ route('admin.assignments.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.assignments.index') ? 'active' : '' }}">
                <i class="bi bi-person-check-fill"></i> Assignments
            </a>
            @endif
            @if($user->hasPermission('assignments.create'))

            @endif
            @if($user->hasPermission('requests.view'))
            <a href="{{ route('admin.requests.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check-fill"></i> Asset Requests
            </a>
            @endif
            @if($user->hasPermission('assets.disposal') || $user->hasPermission('assets.disposal.view') || $user->hasPermission('assets.disposal.request') || $user->hasPermission('assets.disposal.approve') || $user->hasPermission('assets.disposal.complete'))
            <div class="sidebar-section-title">Asset Disposal</div>
            @if($user->hasPermission('assets.disposal.view'))
            <a href="{{ route('admin.asset-issues.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.asset-issues.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill"></i> Reported Issues
            </a>
            @endif
            @if($user->hasPermission('assets.disposal'))
            <a href="{{ route('admin.disposal-buyers.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposal-buyers.*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard"></i> Disposal Buyers
            </a>
            @endif
            @if($user->hasPermission('assets.disposal.request'))
            <a href="{{ route('admin.disposals.requests') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposals.requests') || request()->routeIs('admin.disposals.create') || request()->routeIs('admin.disposals.bulk') ? 'active' : '' }}">
                <i class="bi bi-send"></i> Disposal Requests
            </a>
            @endif
            @if($user->hasPermission('assets.disposal.approve'))
            <a href="{{ route('admin.disposals.approvals') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposals.approvals') ? 'active' : '' }}">
                <i class="bi bi-check2-square"></i> Disposal Approvals
            </a>
            @endif
            @if($user->hasPermission('assets.disposal.view'))
            <a href="{{ route('admin.disposals.history') }}"
               class="sidebar-link {{ request()->routeIs('admin.disposals.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Disposal History
            </a>
            @endif
            @endif

            @if($user->hasPermission('asset.repairs.manage') || $user->hasPermission('asset.repairs.qc') || $user->hasPermission('asset.repairs.close') || $user->hasPermission('vendors.manage') || $user->hasPermission('reports.view'))
            <div class="sidebar-section-title">AMC & Repairs</div>
            @if($user->hasPermission('asset.repairs.manage') || $user->hasPermission('asset.repairs.qc') || $user->hasPermission('asset.repairs.close'))
            <a href="{{ route('admin.asset-repairs.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.asset-repairs.*') ? 'active' : '' }}">
                <i class="bi bi-wrench-adjustable-circle-fill"></i> Repair Jobs
            </a>
            @endif
            @if($user->hasPermission('asset.repairs.manage'))
            <a href="{{ route('admin.asset-amc-contracts.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.asset-amc-contracts.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i> AMC Contracts
            </a>
            @endif
            @if($user->hasPermission('vendors.manage'))
            <a href="{{ route('admin.vendors.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i> Repair Vendors
            </a>
            @endif
            @if($user->hasPermission('reports.view'))
            <a href="{{ route('admin.reports.maintenance') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.maintenance') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i> Repair Reports
            </a>
            @endif
            @endif

            @if($user->hasPermission('suppliers.manage') || $user->hasPermission('purchase_orders.manage'))
            <div class="sidebar-section-title">Procurement</div>
            @if($user->hasPermission('suppliers.manage'))
            <a href="{{ route('admin.suppliers.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                <i class="bi bi-building-fill"></i> Suppliers
            </a>
            @endif
            @if($user->hasPermission('purchase_orders.manage'))
            <a href="{{ route('admin.purchase-orders.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.purchase-orders.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text-fill"></i> Purchase Orders
            </a>
            @endif
            @endif
            @if($user->hasPermission('maintenance.manage'))
            <a href="{{ route('admin.maintenance.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.maintenance.*') ? 'active' : '' }}">
                <i class="bi bi-wrench-adjustable"></i> Maintenance
            </a>
            @endif
            @if($user->hasPermission('facilities.manage'))
            <a href="{{ route('admin.facilities.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.facilities.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Facilities
            </a>
            @endif
            @if($user->hasPermission('departments.manage'))
            <a href="{{ route('admin.departments.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3-fill"></i> Departments
            </a>
            @endif
            @if($user->hasPermission('reports.view'))
            <a href="{{ route('admin.reports.assets') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill"></i> Asset Reports
            </a>
            @endif
            @endif



            @if($canManageSupport)
            <div class="sidebar-section-title">Support Management</div>
            <a href="{{ route('admin.tickets.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> Manage Tickets
            </a>
            @endif
        @endif
    </nav>

    <!-- Footer user info -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-avatar">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div style="min-width:0;flex:1">
            <div class="sidebar-footer-name">{{ auth()->user()->name }}</div>
            <div class="sidebar-footer-role">{{ ucwords(str_replace('_',' ', auth()->user()->role)) }}</div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="flex-shrink-0">
            @csrf
            <button type="submit" class="topbar-icon-btn" title="Logout"
                    style="border-color:rgba(255,255,255,.08);background:rgba(255,255,255,.06);color:#64748b">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

<!-- ─────────────── MAIN ─────────────── -->
<div id="main-content">

    <!-- Topbar -->
    <div id="topbar">
        <button class="topbar-icon-btn d-md-none me-3" id="sidebarToggle" style="border:none;background:transparent;color:#64748b">
            <i class="bi bi-list fs-5"></i>
        </button>

        {{-- Page context in topbar --}}
        <div class="d-none d-lg-block">
            <div class="topbar-page-title">@yield('title', 'Dashboard')</div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            @php
                $actionNotifications = \App\Support\ActionNotificationService::forUser(auth()->user());
                $pageHelp = \App\Support\PageHelpRegistry::current();
                $pageTourKey = request()->route()?->getName() ?? request()->path();
                $pageHelpTourData = [
                    'title' => $pageHelp['title'] ?? 'Page Guide',
                    'what' => $pageHelp['what'] ?? '',
                    'sections' => $pageHelp['sections'] ?? [],
                    'actions' => $pageHelp['actions'] ?? [],
                    'next' => $pageHelp['next'] ?? '',
                ];
            @endphp
            <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" id="startPageTour">
                <i class="bi bi-stars"></i>
                <span class="d-none d-md-inline">Start Guide</span>
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" id="globalPageHelpButton" data-bs-toggle="modal" data-bs-target="#globalPageHelpModal">
                <i class="bi bi-question-circle"></i>
                <span class="d-none d-md-inline">Page Help</span>
            </button>
            @if(in_array(auth()->user()->role, ['admin', 'staff'], true))
            <div class="dropdown">
                <button class="topbar-icon-btn notification-trigger" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Action notifications">
                    <i class="bi bi-bell-fill"></i>
                    @if($actionNotifications['count'] > 0)
                        <span class="notification-badge">{{ $actionNotifications['count'] > 99 ? '99+' : $actionNotifications['count'] }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-menu">
                    <div class="notification-menu-header">
                        <div>
                            <div class="notification-menu-title">Action Center</div>
                            <div class="notification-menu-subtitle">Pending tasks and requests for your role</div>
                        </div>
                        <span class="badge bg-primary">{{ $actionNotifications['count'] }}</span>
                    </div>
                    <div class="notification-list">
                        @forelse($actionNotifications['items'] as $notice)
                            <a href="{{ $notice['url'] }}" class="notification-item">
                                @php
                                    $noticeColor = $notice['color'] === 'purple' ? 'purple' : $notice['color'];
                                @endphp
                                <span class="notification-icon {{ $noticeColor === 'purple' ? 'bg-purple' : 'bg-' . $noticeColor . '-subtle text-' . $noticeColor }}">
                                    <i class="bi {{ $notice['icon'] }}"></i>
                                </span>
                                <span class="flex-grow-1">
                                    <span class="notification-item-title">{{ $notice['title'] }}</span>
                                    <span class="notification-item-subtitle d-block">{{ $notice['subtitle'] }}</span>
                                </span>
                                <span class="notification-count-chip">{{ $notice['count'] }}</span>
                            </a>
                        @empty
                            <div class="notification-empty">
                                <i class="bi bi-check2-circle"></i>
                                <div class="fw-bold" style="font-size:.82rem;color:#0f172a">No pending actions</div>
                                <div style="font-size:.72rem">You are clear for now.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

            {{-- Date chip --}}
            <div class="d-none d-lg-flex align-items-center gap-2 px-3 py-1 rounded-3"
                 style="background:#f8fafc;border:1.5px solid #e9ecef;font-size:.78rem;color:#64748b;font-weight:500">
                <i class="bi bi-calendar3"></i>
                {{ now()->format('D, d-m-Y') }}
            </div>

            {{-- User dropdown --}}
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 py-1 px-2 rounded-3"
                        style="border:1.5px solid #e9ecef;font-size:.82rem;font-weight:600;color:#334155"
                        data-bs-toggle="dropdown">
                    <div class="topbar-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down" style="font-size:.65rem;opacity:.5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px;font-size:.85rem;border-radius:12px;border:1px solid #e9ecef">
                    <li class="px-3 pt-2 pb-1">
                        <div class="fw-700" style="color:#0f172a">{{ auth()->user()->name }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ auth()->user()->email }}</div>
                        <span class="badge bg-primary mt-1" style="font-size:.65rem">
                            {{ ucwords(str_replace('_',' ', auth()->user()->role)) }}
                        </span>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a href="{{ route('account.password.edit') }}" class="dropdown-item py-2">
                            <i class="bi bi-key me-2"></i>Change Password
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger py-2">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Flash messages -->
    <div class="px-4 pt-3">
        @php
            $billingOrg = auth()->user()?->organization;
            $trialDaysRemaining = $billingOrg?->trialDaysRemaining();
            $subscriptionDaysRemaining = $billingOrg?->subscriptionDaysRemaining();
        @endphp
        @if(($billingOrg ?? null) && auth()->user()?->role !== 'super_admin')
            @if(!$billingOrg->hasBillingAccess())
                <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm">
                    <i class="bi bi-lock-fill me-2"></i>{{ $billingOrg->billingAccessMessage() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif(!is_null($trialDaysRemaining) && $trialDaysRemaining <= 7)
                <div class="alert alert-warning alert-dismissible fade show rounded-3 border-0 shadow-sm">
                    <i class="bi bi-hourglass-split me-2"></i>
                    Your free trial {{ $trialDaysRemaining <= 0 ? 'ends today' : 'ends in ' . $trialDaysRemaining . ' day' . ($trialDaysRemaining === 1 ? '' : 's') }}.
                    Monthly subscription amount: &#8377;{{ number_format((float) $billingOrg->monthly_amount, 0) }}.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif(!is_null($subscriptionDaysRemaining) && $subscriptionDaysRemaining <= 7)
                <div class="alert alert-warning alert-dismissible fade show rounded-3 border-0 shadow-sm">
                    <i class="bi bi-calendar2-x me-2"></i>
                    Your paid subscription {{ $subscriptionDaysRemaining <= 0 ? 'ends today' : 'ends in ' . $subscriptionDaysRemaining . ' day' . ($subscriptionDaysRemaining === 1 ? '' : 's') }}.
                    Renewal amount: &#8377;{{ number_format((float) $billingOrg->monthly_amount, 0) }}.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        @endif
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    <!-- Page Content -->
    <div class="content-area">
        @yield('content')
    </div>
</div>

<div class="modal fade" id="globalPageHelpModal" tabindex="-1" aria-labelledby="globalPageHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="help-panel-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="bi bi-question-circle-fill"></i>
                    </div>
                    <h5 class="modal-title" id="globalPageHelpModalLabel">{{ $pageHelp['title'] ?? 'Page Help' }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body help-panel">
                @if(!empty($pageHelp['what']))
                    <p class="mb-3">{{ $pageHelp['what'] }}</p>
                @endif
                @if(!empty($pageHelp['how']))
                    <div class="help-panel-section-title mb-2">How to use this page</div>
                    <ul>
                        @foreach($pageHelp['how'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(!empty($pageHelp['sections']))
                    <div class="help-panel-section-title mt-3 mb-2">What you can find here</div>
                    <ul>
                        @foreach($pageHelp['sections'] as $section => $description)
                            <li><strong>{{ $section }}:</strong> {{ $description }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(!empty($pageHelp['actions']))
                    <div class="help-panel-section-title mt-3 mb-2">What buttons do</div>
                    <ul>
                        @foreach($pageHelp['actions'] as $button => $description)
                            <li><strong>{{ $button }}:</strong> {{ $description }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(!empty($pageHelp['next']))
                    <div class="mt-3"><strong>Next:</strong> {{ $pageHelp['next'] }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- Flatpickr — DD-MM-YYYY date pickers -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Apply to every input[type=date] in the page
    document.querySelectorAll('input[type="date"]').forEach(function (el) {
        // Get the current YYYY-MM-DD value (if any) to pre-fill
        var existing = el.value;
        flatpickr(el, {
            dateFormat:  'Y-m-d',   // value sent to server stays YYYY-MM-DD
            altInput:    true,       // show a human-friendly input to the user
            altFormat:   'd-m-Y',   // what the user sees: DD-MM-YYYY
            defaultDate: existing || null,
            allowInput:  true,
        });
    });
});
</script>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});

(function () {
    var nav = document.querySelector('#sidebar .sidebar-nav');
    if (!nav || nav.dataset.grouped === '1') return;
    nav.dataset.grouped = '1';

    Array.from(nav.querySelectorAll('.sidebar-section-title')).forEach(function (title, index) {
        var label = title.textContent.trim();
        if (!label) return;

        var body = document.createElement('div');
        body.className = 'sidebar-group-body';
        body.id = 'sidebarGroup' + index;

        var node = title.nextElementSibling;
        while (node && !node.classList.contains('sidebar-section-title')) {
            var next = node.nextElementSibling;
            body.appendChild(node);
            node = next;
        }

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'sidebar-group-toggle';
        toggle.setAttribute('aria-controls', body.id);
        toggle.innerHTML = '<span>' + label + '</span><i class="bi bi-chevron-down"></i>';

        title.replaceWith(toggle);
        toggle.insertAdjacentElement('afterend', body);

        var storageKey = 'sidebar-group:' + label;
        var hasActive = !!body.querySelector('.sidebar-link.active');
        var saved = localStorage.getItem(storageKey);
        var shouldOpen = hasActive || label.toLowerCase() === 'overview' || saved === 'open';

        function setOpen(open) {
            body.classList.toggle('collapsed', !open);
            toggle.classList.toggle('collapsed', !open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            localStorage.setItem(storageKey, open ? 'open' : 'closed');
        }

        setOpen(shouldOpen);
        toggle.addEventListener('click', function () {
            setOpen(body.classList.contains('collapsed'));
        });
    });
})();
</script>

{{-- ── Attachment drop-zone (global) ── --}}
<script>
(function () {
    function initDropZones() {
        document.querySelectorAll('.attach-drop-zone').forEach(function (zone) {
            if (zone._initialized) return;
            zone._initialized = true;
            var inputId   = zone.dataset.target;
            var previewId = zone.dataset.preview;
            var input     = document.getElementById(inputId);
            var preview   = document.getElementById(previewId);
            if (!input || !preview) return;
            zone.addEventListener('click', function () { input.click(); });
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                zone.style.borderColor = '#3b82f6';
                zone.style.background  = '#eff6ff';
            });
            zone.addEventListener('dragleave', function () {
                zone.style.borderColor = '';
                zone.style.background  = '';
            });
            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                zone.style.borderColor = '';
                zone.style.background  = '';
                mergeFiles(input, Array.from(e.dataTransfer.files));
                renderPreviews(input, preview);
            });
            input.addEventListener('change', function () { renderPreviews(input, preview); });
        });
    }
    function mergeFiles(input, newFiles) {
        var existing = Array.from(input.files || []);
        var combined = existing.concat(newFiles).slice(0, 5);
        var dt = new DataTransfer();
        combined.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
    }
    function removeFile(input, preview, idx) {
        var files = Array.from(input.files);
        files.splice(idx, 1);
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        renderPreviews(input, preview);
    }
    function renderPreviews(input, preview) {
        preview.innerHTML = '';
        Array.from(input.files).forEach(function (f, i) {
            var isImg = f.type.startsWith('image/');
            var size  = f.size >= 1048576 ? (f.size/1048576).toFixed(1)+' MB' : (f.size/1024).toFixed(1)+' KB';
            var card  = document.createElement('div');
            card.style.cssText = 'display:flex;align-items:center;gap:8px;background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:9px;padding:6px 10px;font-size:.78rem;max-width:220px';
            if (isImg) {
                var img = document.createElement('img');
                img.style.cssText = 'width:36px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0';
                var reader = new FileReader();
                reader.onload = function (e2) { img.src = e2.target.result; };
                reader.readAsDataURL(f);
                card.appendChild(img);
            } else {
                var ico = document.createElement('i');
                ico.className = 'bi bi-file-earmark';
                ico.style.cssText = 'font-size:1.2rem;color:#64748b;flex-shrink:0';
                card.appendChild(ico);
            }
            var info = document.createElement('div');
            info.style.cssText = 'overflow:hidden;flex:1;min-width:0';
            info.innerHTML = '<div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#334155">'+escHtml(f.name)+'</div>'
                           + '<div style="color:#94a3b8;font-size:.7rem">'+size+'</div>';
            card.appendChild(info);
            var del = document.createElement('button');
            del.type = 'button';
            del.innerHTML = '<i class="bi bi-x-lg"></i>';
            del.style.cssText = 'border:none;background:none;color:#94a3b8;cursor:pointer;padding:0;flex-shrink:0';
            (function(ci,cp,ci2){ del.addEventListener('click', function(){ removeFile(ci,cp,ci2); }); })(input,preview,i);
            card.appendChild(del);
            preview.appendChild(card);
        });
    }
    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    document.addEventListener('DOMContentLoaded', initDropZones);
    var obs = new MutationObserver(initDropZones);
    obs.observe(document.body, { childList: true, subtree: true });
})();
</script>

<script>
(function () {
    var configuredTourSteps = @json($pageHelp['tour'] ?? []);
    var pageHelpData = @json($pageHelpTourData);
    var tourSteps = normalizeConfiguredSteps(configuredTourSteps);
    var tourKey = @json('page-tour-complete:' . $pageTourKey);
    var startButton = document.getElementById('startPageTour');
    if (!startButton) return;

    var currentIndex = 0;
    var activeTarget = null;
    var backdrop = null;
    var card = null;

    function startTour() {
        tourSteps = normalizeConfiguredSteps(configuredTourSteps);
        if (tourSteps.length === 0) {
            tourSteps = buildAutomaticTour();
        }
        if (tourSteps.length === 0) return;
        currentIndex = 0;
        ensureShell();
        showStep();
    }

    function ensureShell() {
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'guided-tour-backdrop';
            backdrop.addEventListener('click', finishTour);
            document.body.appendChild(backdrop);
        }
        if (!card) {
            card = document.createElement('div');
            card.className = 'guided-tour-card';
            card.setAttribute('role', 'dialog');
            card.setAttribute('aria-live', 'polite');
            document.body.appendChild(card);
        }
    }

    function showStep() {
        if (!moveToAvailableStep(currentIndex >= tourSteps.length ? -1 : 1)) {
            finishTour();
            return;
        }

        clearHighlight();
        var step = tourSteps[currentIndex] || {};
        activeTarget = getTarget(step);
        if (activeTarget) {
            activeTarget.classList.add('guided-tour-highlight');
            activeTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }

        renderCard(step);
        window.setTimeout(positionCard, activeTarget ? 260 : 0);
    }

    function renderCard(step) {
        var isFirst = currentIndex === 0;
        var isLast = currentIndex === tourSteps.length - 1;
        card.style.visibility = 'hidden';
        card.innerHTML =
            '<div class="guided-tour-progress">Step ' + (currentIndex + 1) + ' of ' + tourSteps.length + '</div>' +
            '<div class="guided-tour-title">' + escapeHtml(step.title || 'Guide') + '</div>' +
            '<div class="guided-tour-body">' + escapeHtml(step.body || '') + '</div>' +
            '<div class="d-flex align-items-center justify-content-between gap-2">' +
                '<button type="button" class="btn btn-link btn-sm text-muted px-0" data-tour-action="skip">Skip</button>' +
                '<div class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-outline-secondary btn-sm" data-tour-action="back" ' + (isFirst ? 'disabled' : '') + '>Back</button>' +
                    '<button type="button" class="btn btn-primary btn-sm" data-tour-action="next">' + (isLast ? 'Finish' : 'Next') + '</button>' +
                '</div>' +
            '</div>';
        card.querySelector('[data-tour-action="skip"]').addEventListener('click', finishTour);
        card.querySelector('[data-tour-action="back"]').addEventListener('click', previousStep);
        card.querySelector('[data-tour-action="next"]').addEventListener('click', nextStep);
    }

    function positionCard() {
        if (!card) return;
        var margin = 16;
        var cardRect = card.getBoundingClientRect();
        var left = Math.max(margin, (window.innerWidth - cardRect.width) / 2);
        var top = Math.max(margin, (window.innerHeight - cardRect.height) / 2);

        if (activeTarget) {
            var targetRect = activeTarget.getBoundingClientRect();
            left = targetRect.right + margin;
            top = targetRect.top;

            if (left + cardRect.width > window.innerWidth - margin) {
                left = targetRect.left - cardRect.width - margin;
            }
            if (left < margin) {
                left = Math.min(window.innerWidth - cardRect.width - margin, Math.max(margin, targetRect.left));
                top = targetRect.bottom + margin;
            }
            if (top + cardRect.height > window.innerHeight - margin) {
                top = window.innerHeight - cardRect.height - margin;
            }
            if (top < margin) {
                top = margin;
            }
        }

        card.style.left = left + 'px';
        card.style.top = top + 'px';
        card.style.visibility = 'visible';
    }

    function nextStep() {
        if (currentIndex >= tourSteps.length - 1) {
            finishTour();
            return;
        }
        currentIndex += 1;
        showStep();
    }

    function previousStep() {
        if (currentIndex === 0) return;
        currentIndex -= 1;
        if (!moveToAvailableStep(-1)) {
            currentIndex = 0;
        }
        showStep();
    }

    function moveToAvailableStep(direction) {
        var attempts = 0;
        while (attempts < tourSteps.length) {
            var step = tourSteps[currentIndex] || {};
            if (getTarget(step)) {
                return true;
            }
            currentIndex += direction;
            if (currentIndex < 0 || currentIndex >= tourSteps.length) {
                return false;
            }
            attempts += 1;
        }
        return false;
    }

    function getTarget(step) {
        if (step.selector) {
            return document.querySelector(step.selector);
        }
        return step.target ? document.querySelector('[data-tour="' + cssEscape(step.target) + '"]') : null;
    }

    function normalizeConfiguredSteps(steps) {
        if (!Array.isArray(steps)) return [];
        return steps.filter(function (step) {
            return step && (step.target || step.selector);
        });
    }

    function buildAutomaticTour() {
        var steps = [];
        var used = [];

        addStep(steps, used, '.page-header, .suite-hero, .employee-hero', pageHelpData.title || 'Page Overview', pageHelpData.what || 'Start here to understand the purpose of this page and the main actions available.');
        addStep(steps, used, '.stat-card-gradient, .stat-card, .summary-card, .module-card, .mini-stat, .attention-item', 'Key Summary', 'These summary cards show the most important counts or status indicators for this page.');
        addStep(steps, used, '.table-card form[method="GET"], form[method="GET"]', 'Filters and Search', 'Use these controls to narrow records before reviewing, exporting, or taking action.');
        addStep(steps, used, '.table-card .card-header .btn, .page-header .btn, .suite-card .btn, .dash-card .btn, .content-area > .d-flex .btn', 'Page Actions', firstActionDescription());

        Array.from(document.querySelectorAll('.table-card, .suite-card, .dash-card')).slice(0, 5).forEach(function (cardElement, index) {
            var title = cardTitle(cardElement) || (index === 0 ? 'Main Records' : 'Page Section');
            addElementStep(steps, used, cardElement, title, sectionDescription(title) || 'Review this section for related records, details, and available actions.');
        });

        addStep(steps, used, '.content-area form:not([method="GET"])', 'Form Details', 'Complete the required information here, then use the available save or submit action when ready.');
        addStep(steps, used, '#globalPageHelpButton', 'Page Help', 'Use Page Help anytime for a written reference of what this page contains and what each action means.');

        return steps.slice(0, 8);
    }

    function addStep(steps, used, selector, title, body) {
        var element = document.querySelector(selector);
        if (!element) return;
        addElementStep(steps, used, element, title, body);
    }

    function addElementStep(steps, used, element, title, body) {
        if (!element || used.indexOf(element) !== -1 || !isVisible(element)) return;
        var generatedId = element.getAttribute('data-auto-tour');
        if (!generatedId) {
            generatedId = 'auto-' + (used.length + 1);
            element.setAttribute('data-auto-tour', generatedId);
        }
        used.push(element);
        steps.push({
            selector: '[data-auto-tour="' + generatedId + '"]',
            title: title,
            body: body
        });
    }

    function isVisible(element) {
        var rect = element.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function cardTitle(cardElement) {
        var header = cardElement.querySelector('.card-header .fw-semibold, .card-header, .panel-title, .section-title, .module-title, h4, h5');
        return header ? header.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    function sectionDescription(title) {
        var sections = pageHelpData.sections || {};
        if (sections[title]) return sections[title];
        var lowerTitle = String(title || '').toLowerCase();
        var match = Object.keys(sections).find(function (key) {
            return lowerTitle.indexOf(key.toLowerCase()) !== -1 || key.toLowerCase().indexOf(lowerTitle) !== -1;
        });
        return match ? sections[match] : '';
    }

    function firstActionDescription() {
        var actions = pageHelpData.actions || {};
        var firstKey = Object.keys(actions)[0];
        return firstKey ? firstKey + ': ' + actions[firstKey] : 'These buttons are the main actions available from this page.';
    }

    function finishTour() {
        clearHighlight();
        if (backdrop) backdrop.remove();
        if (card) card.remove();
        backdrop = null;
        card = null;
        try {
            localStorage.setItem(tourKey, '1');
        } catch (e) {}
    }

    function clearHighlight() {
        if (activeTarget) {
            activeTarget.classList.remove('guided-tour-highlight');
            activeTarget = null;
        }
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/"/g, '\\"');
    }

    startButton.addEventListener('click', startTour);
    window.addEventListener('resize', positionCard);
    document.addEventListener('keydown', function (event) {
        if (!card) return;
        if (event.key === 'Escape') finishTour();
        if (event.key === 'ArrowRight') nextStep();
        if (event.key === 'ArrowLeft') previousStep();
    });
})();
</script>

@stack('scripts')
</body>
</html>
