<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AmoraCare is a child adoption guidance and donation management system for AMOR Village Orphanage.">
    <title>AmoraCare | Child Adoption Guidance and Donation Management</title>

    <style>
        :root {
            --brand: #a84627;
            --brand-dark: #73301d;
            --brand-soft: #fff1eb;
            --accent: #f2a24a;
            --green: #26735b;
            --green-soft: #eaf7f1;
            --blue: #315f8f;
            --blue-soft: #edf5fc;
            --ink: #201b18;
            --muted: #6f6660;
            --line: #e9dfd8;
            --paper: #fffdfb;
            --surface: #ffffff;
            --soft: #f8f4f1;
            --shadow-sm: 0 12px 34px rgba(74, 44, 29, .08);
            --shadow-lg: 0 28px 70px rgba(74, 44, 29, .14);
            --radius: 24px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; overflow-x: clip; color: var(--ink); background: var(--paper); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; -webkit-font-smoothing: antialiased; }
        body.menu-open { overflow: hidden; }
        a { color: inherit; }
        img { display: block; max-width: 100%; }
        button, input { font: inherit; }
        [id] { scroll-margin-top: 110px; }
        .container { width: min(1160px, calc(100% - 40px)); margin-inline: auto; }

        .site-header { position: fixed; inset: 0 0 auto; z-index: 50; padding: 16px 0; transition: padding .2s ease, background .2s ease, box-shadow .2s ease; }
        .site-header.is-scrolled { padding: 9px 0; border-bottom: 1px solid rgba(233, 223, 216, .9); background: rgba(255, 253, 251, .94); box-shadow: 0 10px 30px rgba(74, 44, 29, .06); backdrop-filter: blur(18px); }
        .nav-shell { min-height: 68px; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 9px 10px 9px 16px; border: 1px solid rgba(233, 223, 216, .92); border-radius: 20px; background: rgba(255, 255, 255, .9); box-shadow: var(--shadow-sm); backdrop-filter: blur(18px); }
        .brand { display: inline-flex; align-items: center; gap: 11px; color: var(--ink); text-decoration: none; }
        .brand img { width: 52px; height: 52px; object-fit: contain; }
        .brand-copy { display: grid; line-height: 1.1; }
        .brand-copy strong { color: var(--brand-dark); font-family: Georgia, "Times New Roman", serif; font-size: 23px; letter-spacing: -.03em; }
        .brand-copy small { margin-top: 4px; color: var(--muted); font-size: 10px; font-weight: 750; letter-spacing: .09em; text-transform: uppercase; }
        .nav-menu { display: flex; align-items: center; gap: 4px; }
        .nav-link { padding: 10px 12px; border-radius: 10px; color: #514843; text-decoration: none; font-size: 13px; font-weight: 750; transition: color .16s ease, background .16s ease; }
        .nav-link:hover, .nav-link.is-active { color: var(--brand-dark); background: var(--brand-soft); }
        .nav-session { display: flex; align-items: center; gap: 8px; margin-left: 8px; }
        .button { min-height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 17px; border: 1px solid transparent; border-radius: 12px; background: var(--brand); color: #fff; text-decoration: none; font-weight: 800; cursor: pointer; transition: transform .16s ease, box-shadow .16s ease, background .16s ease; }
        .button:hover { background: var(--brand-dark); box-shadow: 0 10px 24px rgba(115, 48, 29, .2); transform: translateY(-2px); }
        .button.is-secondary { border-color: var(--line); background: #fff; color: var(--ink); box-shadow: none; }
        .button.is-secondary:hover { border-color: #d8c6ba; background: var(--soft); color: var(--brand-dark); }
        .button.is-small { min-height: 42px; padding: 9px 14px; font-size: 13px; }
        .logout-form { margin: 0; }
        .menu-toggle { width: 44px; height: 44px; display: none; place-items: center; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: var(--ink); cursor: pointer; }
        .menu-toggle span, .menu-toggle::before, .menu-toggle::after { content: ""; width: 19px; height: 2px; display: block; border-radius: 999px; background: currentColor; transition: transform .18s ease, opacity .18s ease; }
        .menu-toggle span { margin: 4px 0; }
        .menu-toggle[aria-expanded="true"] span { opacity: 0; }
        .menu-toggle[aria-expanded="true"]::before { transform: translateY(6px) rotate(45deg); }
        .menu-toggle[aria-expanded="true"]::after { transform: translateY(-6px) rotate(-45deg); }

        .hero { position: relative; overflow: hidden; padding: 152px 0 82px; background: radial-gradient(circle at 88% 20%, rgba(242, 162, 74, .17), transparent 30%), radial-gradient(circle at 2% 90%, rgba(38, 115, 91, .11), transparent 30%), linear-gradient(135deg, #fffaf6 0%, #fffdfb 48%, #f9f4f0 100%); }
        .hero::before { content: ""; position: absolute; inset: 0; opacity: .34; background-image: radial-gradient(rgba(168, 70, 39, .18) .8px, transparent .8px); background-size: 22px 22px; pointer-events: none; }
        .hero-grid { position: relative; display: grid; grid-template-columns: minmax(0, 1.03fr) minmax(420px, .97fr); gap: 70px; align-items: center; }
        .hero-copy, .hero-visual, .section-head > *, .support-photo, .support-content { min-width: 0; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; margin-bottom: 17px; color: var(--brand-dark); font-size: 12px; font-weight: 850; letter-spacing: .12em; text-transform: uppercase; }
        .eyebrow::before { content: ""; width: 28px; height: 2px; border-radius: 999px; background: var(--accent); }
        .hero h1 { max-width: 710px; margin: 0; color: #241812; font-family: Georgia, "Times New Roman", serif; font-size: clamp(46px, 6.5vw, 78px); font-weight: 600; letter-spacing: -.055em; line-height: .99; }
        .hero h1 span { color: var(--brand); }
        .hero-lead { max-width: 660px; margin: 24px 0 0; color: var(--muted); font-size: clamp(17px, 2vw, 20px); line-height: 1.7; }
        .hero-actions { display: flex; gap: 11px; flex-wrap: wrap; margin-top: 30px; }
        .hero-points { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 27px; padding: 0; list-style: none; }
        .hero-points li { display: inline-flex; align-items: center; gap: 7px; color: #514843; font-size: 13px; font-weight: 750; }
        .check { width: 21px; height: 21px; display: inline-grid; place-items: center; border-radius: 50%; background: var(--green-soft); color: var(--green); font-size: 12px; font-weight: 900; }
        .hero-visual { position: relative; min-height: 520px; }
        .hero-photo { position: absolute; inset: 0 0 28px 32px; overflow: hidden; border: 10px solid rgba(255, 255, 255, .96); border-radius: 36px 90px 36px 36px; background: #eee5df; box-shadow: var(--shadow-lg); }
        .hero-photo img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
        .hero-photo::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 50%, rgba(31, 19, 14, .36)); }
        .photo-caption { position: absolute; right: 25px; bottom: 54px; z-index: 2; max-width: 260px; color: #fff; text-align: right; }
        .photo-caption strong { display: block; font-size: 18px; }
        .photo-caption span { display: block; margin-top: 4px; font-size: 12px; line-height: 1.5; opacity: .88; }
        .floating-card { position: absolute; left: 0; bottom: 0; z-index: 3; width: min(320px, 76%); padding: 18px; border: 1px solid #ead8cc; border-radius: 19px; background: rgba(255, 255, 255, .96); box-shadow: 0 20px 46px rgba(68, 39, 25, .18); backdrop-filter: blur(12px); }
        .floating-card-label { color: var(--brand); font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        .floating-card strong { display: block; margin-top: 6px; font-size: 16px; line-height: 1.35; }
        .floating-card p { margin: 7px 0 0; color: var(--muted); font-size: 12px; line-height: 1.55; }

        .trust-bar { position: relative; z-index: 3; margin-top: -30px; }
        .trust-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); overflow: hidden; border: 1px solid var(--line); border-radius: 20px; background: #fff; box-shadow: var(--shadow-sm); }
        .trust-item { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 12px; align-items: center; padding: 20px 22px; }
        .trust-item + .trust-item { border-left: 1px solid var(--line); }
        .trust-number { color: var(--brand); font-family: Georgia, "Times New Roman", serif; font-size: 34px; font-weight: 700; line-height: 1; }
        .trust-copy strong { display: block; font-size: 13px; }
        .trust-copy span { display: block; margin-top: 3px; color: var(--muted); font-size: 11px; line-height: 1.4; }

        .section { padding: 96px 0; }
        .section.is-soft { background: var(--soft); }
        .section.is-dark { color: #fff; background: radial-gradient(circle at 10% 10%, rgba(242, 162, 74, .13), transparent 26%), linear-gradient(135deg, #2b201b, #1f1916); }
        .section-head { display: grid; grid-template-columns: minmax(0, .9fr) minmax(360px, 1.1fr); gap: 48px; align-items: end; margin-bottom: 42px; }
        .section-head.is-centered { display: block; max-width: 790px; margin: 0 auto 46px; text-align: center; }
        .section-head.is-centered .eyebrow { justify-content: center; }
        .section-title { margin: 0; color: #2a1e18; font-family: Georgia, "Times New Roman", serif; font-size: clamp(36px, 4.8vw, 56px); font-weight: 600; letter-spacing: -.045em; line-height: 1.05; }
        .is-dark .section-title { color: #fff; }
        .section-copy { margin: 0; color: var(--muted); font-size: 16px; line-height: 1.75; }
        .is-dark .section-copy { color: #cfc4bd; }

        .focus-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .focus-card { min-height: 310px; display: flex; flex-direction: column; padding: 26px; border: 1px solid var(--line); border-radius: var(--radius); background: #fff; box-shadow: 0 12px 35px rgba(74, 44, 29, .05); }
        .focus-card:nth-child(2) { background: var(--green-soft); }
        .focus-card:nth-child(3) { background: var(--blue-soft); }
        .focus-icon, .role-icon { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 15px; background: var(--brand-soft); color: var(--brand); font-size: 20px; font-weight: 900; }
        .focus-card:nth-child(2) .focus-icon { background: #d7eee5; color: var(--green); }
        .focus-card:nth-child(3) .focus-icon { background: #dcebf8; color: var(--blue); }
        .focus-card h3 { margin: 25px 0 10px; font-family: Georgia, "Times New Roman", serif; font-size: 25px; letter-spacing: -.025em; }
        .focus-card p { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.7; }
        .focus-note { margin-top: auto; padding-top: 20px; color: #4c433e; font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }

        .module-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .module-card { position: relative; min-height: 240px; overflow: hidden; padding: 23px; border: 1px solid var(--line); border-radius: 20px; background: #fff; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
        .module-card:hover { border-color: #ddc5b7; box-shadow: var(--shadow-sm); transform: translateY(-4px); }
        .module-number { width: 36px; height: 36px; display: grid; place-items: center; border-radius: 12px; background: var(--brand-soft); color: var(--brand); font-size: 12px; font-weight: 900; }
        .module-card h3 { margin: 20px 0 9px; color: #2c211c; font-size: 18px; letter-spacing: -.02em; line-height: 1.3; }
        .module-card p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.65; }
        .module-tag { position: absolute; top: 24px; right: 22px; padding: 5px 8px; border-radius: 999px; background: #f4f1ef; color: #756b65; font-size: 9px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .module-card.is-wide { grid-column: span 2; }

        .roles-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .role-card { position: relative; padding: 25px; border: 1px solid rgba(255, 255, 255, .12); border-radius: 22px; background: rgba(255, 255, 255, .06); }
        .role-icon { background: rgba(242, 162, 74, .16); color: #ffc17d; }
        .role-card h3 { margin: 20px 0 9px; color: #fff; font-size: 20px; }
        .role-card p { margin: 0; color: #cfc4bd; font-size: 13px; line-height: 1.7; }
        .role-list { display: grid; gap: 9px; margin: 20px 0 0; padding: 0; list-style: none; }
        .role-list li { display: flex; gap: 8px; color: #eee6e1; font-size: 12px; line-height: 1.45; }
        .role-list li::before { content: "•"; color: #ffc17d; font-weight: 900; }

        .support-grid { display: grid; grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); gap: 54px; align-items: center; }
        .support-photo { position: relative; }
        .support-photo img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 28px; box-shadow: var(--shadow-lg); }
        .support-photo::before { content: ""; position: absolute; inset: 22px -18px -18px 22px; z-index: -1; border-radius: 28px; background: var(--brand-soft); }
        .support-content .section-title { margin-bottom: 18px; }
        .support-list { display: grid; gap: 12px; margin: 25px 0 0; padding: 0; list-style: none; }
        .support-list li { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 12px; align-items: start; padding: 15px; border: 1px solid var(--line); border-radius: 14px; background: #fff; }
        .support-list strong { display: block; font-size: 13px; }
        .support-list span:last-child { display: block; margin-top: 3px; color: var(--muted); font-size: 12px; line-height: 1.5; }

        .faq-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .faq-item { overflow: hidden; border: 1px solid var(--line); border-radius: 16px; background: #fff; }
        .faq-item summary { display: flex; justify-content: space-between; gap: 14px; align-items: center; padding: 19px 20px; color: #342923; font-size: 14px; font-weight: 850; list-style: none; cursor: pointer; }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after { content: "+"; color: var(--brand); font-size: 22px; font-weight: 500; }
        .faq-item[open] summary::after { content: "−"; }
        .faq-item p { margin: 0; padding: 0 20px 20px; color: var(--muted); font-size: 13px; line-height: 1.7; }

        .site-footer { padding: 54px 0 24px; color: #d8cec8; background: #1d1714; }
        .footer-grid { display: grid; grid-template-columns: 1.25fr .75fr .75fr; gap: 40px; padding-bottom: 38px; }
        .footer-brand .brand-copy strong { color: #fff; }
        .footer-brand .brand-copy small { color: #b7aaa2; }
        .footer-brand p { max-width: 480px; margin: 17px 0 0; color: #bfb3ac; font-size: 13px; line-height: 1.7; }
        .footer-column h3 { margin: 0 0 14px; color: #fff; font-size: 12px; letter-spacing: .1em; text-transform: uppercase; }
        .footer-links { display: grid; gap: 10px; }
        .footer-links a { color: #bfb3ac; text-decoration: none; font-size: 13px; }
        .footer-links a:hover { color: #fff; }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; gap: 18px; padding-top: 22px; border-top: 1px solid rgba(255, 255, 255, .1); color: #94867e; font-size: 11px; }
        .footer-bottom .legal-links { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .footer-bottom .legal-links a { color: #d8cec8; text-decoration: none; font-weight: 750; }
        .footer-bottom .legal-links a:hover { color: #fff; text-decoration: underline; }

        @media (max-width: 1020px) {
            .nav-menu { position: fixed; top: 94px; right: 20px; left: 20px; display: none; align-items: stretch; padding: 14px; border: 1px solid var(--line); border-radius: 18px; background: #fff; box-shadow: var(--shadow-lg); }
            .nav-menu.is-open { display: grid; }
            .nav-link { padding: 12px; }
            .nav-session { display: grid; margin: 5px 0 0; }
            .nav-session .button, .logout-form .button { width: 100%; }
            .menu-toggle { display: grid; }
            .hero-grid, .support-grid { grid-template-columns: 1fr; }
            .hero-grid { gap: 48px; }
            .hero-copy { max-width: 760px; }
            .hero-visual { width: min(720px, 100%); min-height: 480px; }
            .section-head { grid-template-columns: 1fr; gap: 18px; }
            .module-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .module-card.is-wide { grid-column: auto; }
        }

        @media (max-width: 760px) {
            .container { width: calc(100% - 28px); max-width: 1160px; }
            .brand-copy small { display: none; }
            .hero { padding: 128px 0 67px; }
            .hero h1 { font-size: clamp(42px, 13vw, 62px); }
            .hero-visual { min-height: 410px; }
            .hero-photo { inset: 0 0 42px 0; border-width: 7px; border-radius: 25px 60px 25px 25px; }
            .photo-caption { right: 17px; bottom: 65px; }
            .floating-card { width: min(330px, 90%); }
            .trust-grid, .focus-grid, .roles-grid, .module-grid, .faq-grid, .footer-grid { grid-template-columns: 1fr; }
            .trust-item + .trust-item { border-top: 1px solid var(--line); border-left: 0; }
            .section { padding: 74px 0; }
            .focus-card { min-height: 260px; }
            .footer-grid { gap: 30px; }
            .footer-bottom { flex-direction: column; }
        }

        @media (max-width: 480px) {
            .nav-shell { min-height: 62px; padding-left: 11px; }
            .brand img { width: 44px; height: 44px; }
            .brand-copy strong { font-size: 20px; }
            .eyebrow { gap: 7px; font-size: 10px; letter-spacing: .08em; }
            .eyebrow::before { width: 20px; }
            .hero-actions .button { width: 100%; }
            .hero-points { display: grid; }
            .hero-visual { min-height: 360px; }
            .photo-caption { display: none; }
            .nav-menu { right: 14px; left: 14px; }
            .floating-card { width: 94%; }
            .footer-bottom { align-items: flex-start; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
@php
    $availableAuthGuards = array_values(array_unique([
        ...array_values(config('auth.role_guards', [])),
        'web',
    ]));
    $preferredAuthGuard = session('active_auth_guard');
    $activeLandingGuard = is_string($preferredAuthGuard)
        && in_array($preferredAuthGuard, $availableAuthGuards, true)
        && auth($preferredAuthGuard)->check()
            ? $preferredAuthGuard
            : collect($availableAuthGuards)
                ->first(fn (string $guard): bool => auth($guard)->check());
    $hasAuthenticatedPortal = is_string($activeLandingGuard);
@endphp
<body>
    <header class="site-header" id="siteHeader">
        <div class="container">
            <div class="nav-shell">
                <a href="#home" class="brand" aria-label="AmoraCare home">
                    <img src="{{ asset('images/amora.png') }}" alt="">
                    <span class="brand-copy">
                        <strong>AmoraCare</strong>
                        <small>Guidance • Care • Accountability</small>
                    </span>
                </a>

                <button class="menu-toggle" id="menuToggle" type="button" aria-expanded="false" aria-controls="mainNavigation" aria-label="Open navigation menu">
                    <span></span>
                </button>

                <nav class="nav-menu" id="mainNavigation" aria-label="Main navigation">
                    <a href="#home" class="nav-link is-active">Home</a>
                    <a href="#about" class="nav-link">About</a>
                    <a href="#modules" class="nav-link">System Modules</a>
                    <a href="#roles" class="nav-link">User Portals</a>
                    <a href="#support" class="nav-link">Donation Support</a>

                    <div class="nav-session">
                        @if($hasAuthenticatedPortal)
                            <a href="{{ route('dashboard') }}" class="button is-small">Open Dashboard</a>
                            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                                @csrf
                                <input type="hidden" name="guard" value="{{ $activeLandingGuard }}">
                                <button type="submit" class="button is-secondary is-small">Log out</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="button is-small">Secure Login</a>
                        @endif
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <section class="hero" id="home">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <div class="eyebrow">AMOR Village Orphanage Support System</div>
                    <h1>Care guided by people, <span>supported by technology.</span></h1>
                    <p class="hero-lead">AmoraCare brings adoption guidance, confidential case workflows, parent documents, reviewer decisions, and accountable donation records into one secure platform.</p>

                    <div class="hero-actions">
                        @if($hasAuthenticatedPortal)
                            <a href="{{ route('dashboard') }}" class="button">Go to your dashboard <span aria-hidden="true">→</span></a>
                        @else
                            <a href="{{ route('login') }}" class="button">Access AmoraCare <span aria-hidden="true">→</span></a>
                        @endif
                        <a href="#modules" class="button is-secondary">Explore all modules</a>
                    </div>

                    <ul class="hero-points" aria-label="System principles">
                        <li><span class="check">✓</span> Role-based access</li>
                        <li><span class="check">✓</span> Human-reviewed matching</li>
                        <li><span class="check">✓</span> Accountable records</li>
                    </ul>
                </div>

                <div class="hero-visual" aria-label="Community support at AMOR Village">
                    <div class="hero-photo">
                        <img src="{{ asset('images/amor-village-hero.jpg') }}" alt="Community donations delivered to AMOR Village">
                    </div>
                    <div class="photo-caption">
                        <strong>Community support in action</strong>
                        <span>Organized giving helps care teams respond to real needs.</span>
                    </div>
                    <div class="floating-card">
                        <span class="floating-card-label">Purpose-built workflow</span>
                        <strong>One trusted record from application to review.</strong>
                        <p>Authorized users see only the information and actions appropriate to their role.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="trust-bar">
            <div class="container trust-grid">
                <div class="trust-item"><span class="trust-number">11</span><span class="trust-copy"><strong>Core system modules</strong><span>Covering the implemented AmoraCare workflow.</span></span></div>
                <div class="trust-item"><span class="trust-number">3</span><span class="trust-copy"><strong>Role-based portals</strong><span>Admin, prospective parent, and external reviewer.</span></span></div>
                <div class="trust-item"><span class="trust-number">1</span><span class="trust-copy"><strong>Centralized platform</strong><span>For adoption guidance and donation accountability.</span></span></div>
            </div>
        </div>

        <section class="section" id="about">
            <div class="container">
                <div class="section-head">
                    <div><div class="eyebrow">About AmoraCare</div><h2 class="section-title">Designed around responsible care workflows.</h2></div>
                    <p class="section-copy">AmoraCare supports AMOR Village staff and authorized partners as they organize adoption-related records, guide prospective parents, review submitted requirements, and maintain transparent donation information. Sensitive decisions remain with qualified people and authorized agencies.</p>
                </div>

                <div class="focus-grid">
                    <article class="focus-card">
                        <span class="focus-icon" aria-hidden="true">A</span>
                        <h3>Adoption guidance</h3>
                        <p>Connect parent applications, child profiles, document requirements, case progress, notes, and reports in one controlled workflow.</p>
                        <span class="focus-note">Structured • Confidential • Traceable</span>
                    </article>
                    <article class="focus-card">
                        <span class="focus-icon" aria-hidden="true">D</span>
                        <h3>Donation accountability</h3>
                        <p>Record donor information, cash and in-kind support, specific purposes, values, acknowledgment status, and reportable activity.</p>
                        <span class="focus-note">Cash • In-kind • Reporting</span>
                    </article>
                    <article class="focus-card">
                        <span class="focus-icon" aria-hidden="true">AI</span>
                        <h3>AI-assisted guidance</h3>
                        <p>Provide informational legal guidance and clearer match explanations while keeping every adoption recommendation subject to human review.</p>
                        <span class="focus-note">Informational • Explainable • Human-led</span>
                    </article>
                </div>
            </div>
        </section>

        <section class="section is-soft" id="modules">
            <div class="container">
                <div class="section-head is-centered">
                    <div class="eyebrow">Complete system scope</div>
                    <h2 class="section-title">All AmoraCare modules</h2>
                    <p class="section-copy" style="margin-top: 16px;">The public overview now reflects the actual implemented Admin, Parent, and External Reviewer functionality.</p>
                </div>

                <div class="module-grid">
                    <article class="module-card"><span class="module-number">01</span><span class="module-tag">All users</span><h3>Authentication, Role-Based Access, and Dashboards</h3><p>Secure login, role routing, session controls, live dashboard summaries, and task-focused quick actions.</p></article>
                    <article class="module-card"><span class="module-number">02</span><span class="module-tag">Admin</span><h3>User and Role Management</h3><p>Create, view, update, filter, and manage accounts for administrators, parents, and external reviewers.</p></article>
                    <article class="module-card"><span class="module-number">03</span><span class="module-tag">Admin + Parent</span><h3>Parent Profile and Application Management</h3><p>Maintain applicant information, household preferences, readiness assessments, and application progress.</p></article>
                    <article class="module-card"><span class="module-number">04</span><span class="module-tag">Parent workflow</span><h3>Document Upload, Download, and Verification</h3><p>Manage confidential requirement checklists, secure files, statuses, reviewer remarks, and resubmissions.</p></article>
                    <article class="module-card"><span class="module-number">05</span><span class="module-tag">Parent</span><h3>AI-Assisted Adoption Legal Guidance</h3><p>Offer informational, source-grounded guidance about adoption procedures without replacing professional legal advice.</p></article>
                    <article class="module-card"><span class="module-number">06</span><span class="module-tag">Admin</span><h3>Child Profile Management</h3><p>Maintain authorized child records, eligibility information, case status, care details, and matching readiness.</p></article>
                    <article class="module-card"><span class="module-number">07</span><span class="module-tag">Decision support</span><h3>Stable Adoption Matching and AI Explanations</h3><p>Generate stable-match recommendations, scores, rankings, filters, and readable explanations for staff review.</p></article>
                    <article class="module-card"><span class="module-number">08</span><span class="module-tag">Admin</span><h3>Adoption Case and Workflow Management</h3><p>Track cases, milestones, priorities, documents, confidential notes, status changes, and matching conversions.</p></article>
                    <article class="module-card"><span class="module-number">09</span><span class="module-tag">Reviewer</span><h3>External Reviewer / RACCO Case Review</h3><p>Provide controlled case access, document acceptance or rejection, reviewer notes, approvals, and change requests.</p></article>
                    <article class="module-card"><span class="module-number">10</span><span class="module-tag">Admin</span><h3>Donor and Donation Management</h3><p>Record donor profiles, cash and in-kind donations, purposes, item values, acknowledgment status, and remarks.</p></article>
                    <article class="module-card is-wide"><span class="module-number">11</span><span class="module-tag">Accountability</span><h3>Reports, CSV Export, and Audit Activity</h3><p>Filter and export child, adoption-case, and donation reports while reviewing recent activity across major system records.</p></article>
                </div>
            </div>
        </section>

        <section class="section is-dark" id="roles">
            <div class="container">
                <div class="section-head">
                    <div><div class="eyebrow" style="color:#ffc17d;">Role-based experience</div><h2 class="section-title">The right tools for each authorized user.</h2></div>
                    <p class="section-copy">AmoraCare separates responsibilities across three portals so sensitive records and actions are available only to the people who need them.</p>
                </div>

                <div class="roles-grid">
                    <article class="role-card"><span class="role-icon" aria-hidden="true">01</span><h3>Administrator portal</h3><p>Central oversight for operational records, adoption workflows, matching, donations, and reporting.</p><ul class="role-list"><li>Manage users, parents, and child profiles</li><li>Run matching and manage adoption cases</li><li>Record donations, reports, and activity</li></ul></article>
                    <article class="role-card"><span class="role-icon" aria-hidden="true">02</span><h3>Prospective parent portal</h3><p>A focused space for applicants to understand progress and complete authorized requirements.</p><ul class="role-list"><li>Review application and case progress</li><li>Upload and download required documents</li><li>Use informational AI legal guidance</li></ul></article>
                    <article class="role-card"><span class="role-icon" aria-hidden="true">03</span><h3>External reviewer portal</h3><p>Controlled access for authorized DSWD/RACCO-related review and documented feedback.</p><ul class="role-list"><li>View authorized case summaries</li><li>Review, accept, or return documents</li><li>Record notes and case-level decisions</li></ul></article>
                </div>
            </div>
        </section>

        <section class="section" id="support">
            <div class="container support-grid">
                <div class="support-photo"><img src="{{ asset('images/amor-village-hero.jpg') }}" alt="Donated supplies at AMOR Village"></div>
                <div class="support-content">
                    <div class="eyebrow">Donation support</div>
                    <h2 class="section-title">Accountable support for real care needs.</h2>
                    <p class="section-copy">Donations can help support food, education, healthcare, clothing, shelter, operations, and adoption-related programs. AmoraCare gives authorized staff a consistent way to document that support.</p>
                    <ul class="support-list">
                        <li><span class="check">✓</span><span><strong>Cash and in-kind records</strong><span>Capture amounts, material items, quantities, estimated values, and payment details.</span></span></li>
                        <li><span class="check">✓</span><span><strong>Clear purpose tracking</strong><span>Record general or specific donation purposes and operational remarks.</span></span></li>
                        <li><span class="check">✓</span><span><strong>Transparent reporting</strong><span>Filter donation history and export records for authorized review.</span></span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section is-soft" id="faq">
            <div class="container">
                <div class="section-head is-centered"><div class="eyebrow">Frequently asked questions</div><h2 class="section-title">A few important clarifications</h2></div>
                <div class="faq-grid">
                    <details class="faq-item"><summary>Does the matching module decide who may adopt a child?</summary><p>No. It produces staff-review recommendations from authorized profile data. Qualified staff and applicable authorized reviewers remain responsible for every decision.</p></details>
                    <details class="faq-item"><summary>Does the AI guidance replace a lawyer or social worker?</summary><p>No. It provides informational adoption-related guidance. Users should still rely on authorized professionals and official government procedures.</p></details>
                    <details class="faq-item"><summary>Who can access confidential adoption records?</summary><p>Only authenticated users with the appropriate role and authorized case access. Parent, admin, and reviewer portals expose different information and actions.</p></details>
                    <details class="faq-item"><summary>What types of donations can the system record?</summary><p>Authorized staff can record cash, in-kind, or mixed donations, including donor details, purposes, item values, payment information, and acknowledgment status.</p></details>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="#home" class="brand"><img src="{{ asset('images/amora.png') }}" alt=""><span class="brand-copy"><strong>AmoraCare</strong><small>Child Adoption Guidance and Donation Management</small></span></a>
                    <p>A secure, role-based support system for AMOR Village adoption guidance, case collaboration, document workflows, and accountable donation records.</p>
                </div>
                <div class="footer-column"><h3>Explore</h3><div class="footer-links"><a href="#about">About AmoraCare</a><a href="#modules">System Modules</a><a href="#roles">User Portals</a><a href="#support">Donation Support</a></div></div>
                <div class="footer-column"><h3>System access</h3><div class="footer-links">@if($hasAuthenticatedPortal)<a href="{{ route('dashboard') }}">Open Dashboard</a>@else<a href="{{ route('login') }}">Secure Login</a>@endif<a href="#faq">Important Clarifications</a></div></div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} AmoraCare. All rights reserved.</span>
                @include('partials.legal-links')
                <span>Supporting care through responsible technology.</span>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const header = document.getElementById('siteHeader');
            const menuToggle = document.getElementById('menuToggle');
            const navigation = document.getElementById('mainNavigation');
            const navLinks = Array.from(document.querySelectorAll('.nav-link'));
            const sections = Array.from(document.querySelectorAll('main section[id]'));

            function updateHeader() { header?.classList.toggle('is-scrolled', window.scrollY > 20); }
            function closeMenu() { navigation?.classList.remove('is-open'); menuToggle?.setAttribute('aria-expanded', 'false'); document.body.classList.remove('menu-open'); }

            menuToggle?.addEventListener('click', function () {
                const isOpen = navigation?.classList.toggle('is-open') ?? false;
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                document.body.classList.toggle('menu-open', isOpen);
            });

            navLinks.forEach(function (link) { link.addEventListener('click', closeMenu); });
            window.addEventListener('resize', function () { if (window.innerWidth > 1020) closeMenu(); });
            window.addEventListener('scroll', updateHeader, { passive: true });
            updateHeader();

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        navLinks.forEach(function (link) { link.classList.toggle('is-active', link.getAttribute('href') === `#${entry.target.id}`); });
                    });
                }, { rootMargin: '-35% 0px -55% 0px', threshold: 0 });
                sections.forEach(function (section) { observer.observe(section); });
            }
        });
    </script>
</body>
</html>
