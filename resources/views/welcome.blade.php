<!DOCTYPE html>
<!-- laravel-app/resources/views/welcome.blade.php -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AmoraCare | Amor Village Orphanage</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Neo-Brutalist Welcome Page CSS --}}
    <style>
        :root {
            --ink: #1f120b;
            --paper: #fff8ef;
            --cream: #fff1dc;
            --orange: #f97316;
            --burnt: #8b2d16;
            --yellow: #ffd166;
            --blue: #7dd3fc;
            --green: #86efac;
            --pink: #f9a8d4;
            --purple: #c4b5fd;
            --red: #fca5a5;
            --white: #ffffff;
            --shadow: 8px 8px 0 var(--ink);
            --shadow-sm: 4px 4px 0 var(--ink);
            --radius: 24px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Inter, Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at 18px 18px, rgba(31, 18, 11, .10) 2px, transparent 2px),
                var(--paper);
            background-size: 28px 28px;
            color: var(--ink);
        }

        a {
            color: inherit;
        }

        .hero {
            min-height: 100vh;
            padding: 24px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            right: -110px;
            top: 120px;
            background: var(--yellow);
            border: 4px solid var(--ink);
            border-radius: 50%;
            box-shadow: var(--shadow);
            z-index: 0;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 190px;
            height: 190px;
            left: -70px;
            bottom: 70px;
            background: var(--blue);
            border: 4px solid var(--ink);
            transform: rotate(12deg);
            box-shadow: var(--shadow);
            z-index: 0;
        }

        .navbar {
            position: sticky;
            top: 18px;
            z-index: 20;
            width: min(1180px, 100%);
            margin: 0 auto;
            background: var(--white);
            border: 4px solid var(--ink);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .navbar.scrolled {
            transform: translate(3px, 3px);
            box-shadow: 5px 5px 0 var(--ink);
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: max-content;
        }

        .logo-box a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-box img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border: 3px solid var(--ink);
            border-radius: 18px;
            background: var(--cream);
            box-shadow: var(--shadow-sm);
            padding: 6px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-menu a,
        .nav-logout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 13px;
            border: 3px solid var(--ink);
            border-radius: 999px;
            background: var(--white);
            color: var(--ink);
            text-decoration: none;
            font-size: 13px;
            font-weight: 1000;
            box-shadow: 3px 3px 0 var(--ink);
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease, background .12s ease;
        }

        .nav-menu a:hover,
        .nav-logout-button:hover,
        .nav-menu a.active {
            background: var(--yellow);
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--ink);
        }

        .login-nav-link {
            background: var(--orange) !important;
            color: #ffffff !important;
        }

        .nav-logout-form {
            margin: 0;
        }

        .nav-logout-button {
            font-family: inherit;
            background: var(--red);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            width: min(1180px, 100%);
            margin: auto auto 70px;
            padding: 48px 0 20px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--green);
            border: 4px solid var(--ink);
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 1000;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 22px;
        }

        .hero-content h1 {
            margin: 0;
            max-width: 980px;
            font-size: clamp(48px, 9vw, 118px);
            line-height: .86;
            letter-spacing: -0.08em;
            text-transform: uppercase;
            font-weight: 1000;
            color: var(--ink);
            text-shadow: 5px 5px 0 var(--yellow);
        }

        .hero-subtitle {
            max-width: 720px;
            margin: 26px 0 0;
            background: var(--white);
            border: 4px solid var(--ink);
            border-radius: 22px;
            box-shadow: var(--shadow);
            padding: 20px;
            color: var(--ink);
            font-size: clamp(16px, 2vw, 22px);
            font-weight: 800;
            line-height: 1.5;
        }

        .hero-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .neo-btn,
        .donate-btn,
        .hero-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 56px;
            padding: 15px 24px;
            border: 4px solid var(--ink);
            border-radius: 16px;
            color: var(--ink);
            text-decoration: none;
            font-weight: 1000;
            text-transform: uppercase;
            letter-spacing: .04em;
            box-shadow: var(--shadow);
            transition: transform .12s ease, box-shadow .12s ease;
        }

        .donate-btn {
            background: var(--orange);
            color: #ffffff;
        }

        .hero-login-btn {
            background: var(--blue);
        }

        .neo-btn:hover,
        .donate-btn:hover,
        .hero-login-btn:hover {
            transform: translate(4px, 4px);
            box-shadow: 4px 4px 0 var(--ink);
        }

        .info-section,
        .modules-section {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto 34px;
            position: relative;
            z-index: 2;
        }

        .info-section {
            display: grid;
            grid-template-columns: .85fr 1.15fr;
            gap: 24px;
            align-items: stretch;
        }

        .intro-text,
        .photo-week,
        .section-heading,
        .module-card,
        .faq-item,
        .footer {
            border: 4px solid var(--ink);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .intro-text {
            background: var(--yellow);
            padding: 28px;
            display: flex;
            align-items: center;
            transform: rotate(-1deg);
        }

        .intro-text p {
            margin: 0;
            font-size: clamp(22px, 3vw, 38px);
            line-height: 1.08;
            letter-spacing: -0.04em;
            font-weight: 1000;
            text-transform: uppercase;
        }

        .photo-week {
            background: var(--white);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .photo-week::after {
            content: "";
            position: absolute;
            right: -40px;
            bottom: -50px;
            width: 160px;
            height: 160px;
            border: 4px solid var(--ink);
            background: var(--pink);
            border-radius: 40px;
            transform: rotate(15deg);
            opacity: .85;
        }

        .photo-week h2,
        .section-heading h2 {
            margin: 0 0 16px;
            font-size: clamp(34px, 5vw, 64px);
            line-height: .92;
            letter-spacing: -0.06em;
            text-transform: uppercase;
            font-weight: 1000;
        }

        .info-paragraph,
        .section-heading p,
        .module-card p,
        .faq-item p {
            color: #4b2c1f;
            font-size: 16px;
            line-height: 1.65;
            font-weight: 750;
        }

        .section-heading {
            background:
                linear-gradient(135deg, rgba(255,255,255,.92), rgba(255,241,220,.94)),
                repeating-linear-gradient(45deg, transparent 0 12px, rgba(31, 18, 11, .08) 12px 16px);
            padding: 30px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .section-heading::before {
            content: "AMORA";
            position: absolute;
            right: 20px;
            bottom: -10px;
            color: rgba(31, 18, 11, .08);
            font-size: 92px;
            font-weight: 1000;
            letter-spacing: -0.08em;
        }

        .section-heading p {
            max-width: 850px;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .module-card {
            background: var(--white);
            padding: 24px;
            min-height: 260px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform .12s ease, box-shadow .12s ease;
            position: relative;
            overflow: hidden;
        }

        .module-card:nth-child(6n + 1) {
            background: var(--blue);
        }

        .module-card:nth-child(6n + 2) {
            background: var(--green);
        }

        .module-card:nth-child(6n + 3) {
            background: var(--yellow);
        }

        .module-card:nth-child(6n + 4) {
            background: var(--pink);
        }

        .module-card:nth-child(6n + 5) {
            background: var(--purple);
        }

        .module-card:nth-child(6n + 6) {
            background: var(--cream);
        }

        .module-card::after {
            content: "";
            position: absolute;
            width: 78px;
            height: 78px;
            right: -20px;
            top: -20px;
            background: rgba(255, 255, 255, .55);
            border: 4px solid var(--ink);
            border-radius: 20px;
            transform: rotate(12deg);
        }

        .module-card:hover {
            transform: translate(4px, 4px);
            box-shadow: 4px 4px 0 var(--ink);
        }

        .module-card h3 {
            margin: 0 0 16px;
            font-size: 24px;
            line-height: 1;
            letter-spacing: -0.04em;
            text-transform: uppercase;
            font-weight: 1000;
            position: relative;
            z-index: 1;
        }

        .module-card p {
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .light-section .section-heading {
            background:
                linear-gradient(135deg, rgba(255,209,102,.88), rgba(255,255,255,.94)),
                repeating-linear-gradient(-45deg, transparent 0 12px, rgba(31, 18, 11, .08) 12px 16px);
        }

        .faq-list {
            display: grid;
            gap: 18px;
        }

        .faq-item {
            background: var(--white);
            padding: 24px;
            display: grid;
            grid-template-columns: .7fr 1.3fr;
            gap: 20px;
            align-items: start;
        }

        .faq-item:nth-child(even) {
            background: var(--cream);
        }

        .faq-item h3 {
            margin: 0;
            font-size: 24px;
            line-height: 1.05;
            letter-spacing: -0.04em;
            text-transform: uppercase;
            font-weight: 1000;
        }

        .faq-item p {
            margin: 0;
        }

        .footer {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto 24px;
            padding: 22px;
            text-align: center;
            background: var(--ink);
            color: #ffffff;
            font-weight: 1000;
            text-transform: uppercase;
            letter-spacing: .04em;
            box-shadow: 8px 8px 0 var(--orange);
        }

        @media (max-width: 980px) {
            .navbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-menu {
                justify-content: flex-start;
            }

            .info-section {
                grid-template-columns: 1fr;
            }

            .module-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .faq-item {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .hero {
                padding: 14px;
            }

            .navbar {
                top: 10px;
                border-radius: 18px;
            }

            .logo-box img {
                width: 54px;
                height: 54px;
            }

            .nav-menu a,
            .nav-logout-button {
                font-size: 12px;
                padding: 8px 10px;
            }

            .hero-content {
                padding-top: 34px;
                margin-bottom: 42px;
            }

            .hero-content h1 {
                font-size: 52px;
            }

            .hero-subtitle {
                padding: 16px;
            }

            .hero-actions a {
                width: 100%;
            }

            .module-grid {
                grid-template-columns: 1fr;
            }

            .photo-week h2,
            .section-heading h2 {
                font-size: 36px;
            }

            .intro-text,
            .photo-week,
            .section-heading,
            .module-card,
            .faq-item {
                border-radius: 18px;
                box-shadow: 5px 5px 0 var(--ink);
            }
        }
    </style>
</head>
<body>

{{-- Hero Section --}}
<section class="hero hero-amor-village">
    <header class="navbar">
        <div class="logo-box">
            <a href="{{ url('/') }}">
                <img src="{{ asset('images/amora.png') }}" alt="AmoraCare Logo">
            </a>
        </div>

        <nav class="nav-menu">
            <a href="{{ url('/') }}" class="active">Home</a>
            <a href="#about">About Us</a>
            <a href="#services">Services</a>
            <a href="#facilities">Facilities</a>
            <a href="#donate">Donate</a>

            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>

                <form method="POST" action="{{ route('logout') }}" class="nav-logout-form">
                    @csrf
                    <button type="submit" class="nav-logout-button">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="login-nav-link">Login</a>
            @endauth
        </nav>
    </header>

    <div class="hero-content">
        <div class="hero-badge">
            Amor Village Orphanage Support System
        </div>

        <h1>
            Partner with us to<br>
            transform lives
        </h1>

        <p class="hero-subtitle">
            Supporting care, protection, adoption guidance, and donation management
            for vulnerable children under Amor Village Orphanage.
        </p>

        <div class="hero-actions">
            <a href="#donate" class="donate-btn">Donate</a>

            @guest
                <a href="{{ route('login') }}" class="hero-login-btn">Login</a>
            @endguest
        </div>
    </div>
</section>

{{-- About Section --}}
<section class="info-section" id="about">
    <div class="intro-text">
        <p>
            Your partnership helps provide care, protection, recovery support, and hope for children who need safety.
        </p>
    </div>

    <div class="photo-week">
        <h2>About AMOR Village</h2>

        <p class="info-paragraph">
            AMOR Village, also known as Accelerating Minors Opportunity for Recovery,
            is a residential care facility located in San Francisco East, Anao, Tarlac.
            It provides a safe and caring environment for vulnerable children who need
            protection, recovery, and support.
        </p>

        <p class="info-paragraph">
            The facility helps children affected by abuse, neglect, exploitation,
            trafficking, abandonment, disability, special needs, or difficult family
            situations. Through proper care, counseling, education, medical support,
            and social work intervention, AMOR Village helps children move toward
            healing, reintegration, and a better future.
        </p>
    </div>
</section>

{{-- Services Section --}}
<section class="modules-section" id="services">
    <div class="section-heading">
        <h2>Care and Support Services</h2>

        <p>
            AMOR Village provides services focused on protection, recovery,
            education, health, emotional healing, and skills development for children
            and residents who need structured care.
        </p>
    </div>

    <div class="module-grid">
        <div class="module-card">
            <h3>Temporary Shelter and Protection</h3>

            <p>
                The facility provides a safe residential environment for children
                while their cases, family conditions, and reintegration needs are
                handled by authorized professionals.
            </p>
        </div>

        <div class="module-card">
            <h3>Counseling and Case Work</h3>

            <p>
                Social workers and care professionals provide counseling, case
                management, and psychosocial interventions to support emotional
                recovery and development.
            </p>
        </div>

        <div class="module-card">
            <h3>Medical and Nutritional Support</h3>

            <p>
                Residents receive assistance for healthcare, nutrition, hygiene,
                safety, and other basic daily needs essential to child welfare.
            </p>
        </div>

        <div class="module-card">
            <h3>Education Support</h3>

            <p>
                Children may receive educational assistance through nearby schools,
                alternative learning support, special education services, and partner
                organizations.
            </p>
        </div>

        <div class="module-card">
            <h3>Livelihood and Skills Training</h3>

            <p>
                Skills development and livelihood activities help residents prepare
                for more independent living and productive community reintegration.
            </p>
        </div>

        <div class="module-card">
            <h3>Therapeutic Home-Life Care</h3>

            <p>
                House parents, staff, nurses, teachers, psychologists, and care
                workers help create a therapeutic home-like environment for residents.
            </p>
        </div>
    </div>
</section>

{{-- Facilities Section --}}
<section class="modules-section light-section" id="facilities">
    <div class="section-heading">
        <h2>Facilities and Capacity</h2>

        <p>
            AMOR Village provides structured residential facilities that support
            safety, learning, therapy, recreation, and daily care for children
            and residents.
        </p>
    </div>

    <div class="module-grid">
        <div class="module-card">
            <h3>Residential Dormitories</h3>

            <p>
                The facility includes separate dormitory spaces for residents,
                allowing organized supervision, daily care, and a safer living
                environment.
            </p>
        </div>

        <div class="module-card">
            <h3>Learning Spaces</h3>

            <p>
                Learning areas such as classrooms, a library, and activity spaces
                help support education, creativity, and child development.
            </p>
        </div>

        <div class="module-card">
            <h3>Therapy and Psychology Rooms</h3>

            <p>
                Dedicated spaces for therapy, counseling, assessment, and treatment
                planning support children’s mental and emotional recovery.
            </p>
        </div>
    </div>
</section>

{{-- Donation Section --}}
<section class="modules-section" id="donate">
    <div class="section-heading">
        <h2>Support Amor Village Orphanage</h2>

        <p>
            Donations and sponsorships help support food, daily needs,
            rehabilitation activities, education, hygiene, healthcare, shelter
            maintenance, and child development programs.
        </p>
    </div>

    <div class="module-grid">
        <div class="module-card">
            <h3>Cash Donations</h3>

            <p>
                Monetary donations can support food supplies, education, healthcare,
                therapy activities, shelter operations, and emergency needs.
            </p>
        </div>

        <div class="module-card">
            <h3>In-Kind Donations</h3>

            <p>
                Donors may provide food, clothes, school supplies, hygiene kits,
                medicine, toys, learning materials, and other essential goods.
            </p>
        </div>

        <div class="module-card">
            <h3>Transparent Donation Records</h3>

            <p>
                AmoraCare helps staff encode, organize, monitor, and report donation
                records for transparency, accountability, and better resource tracking.
            </p>
        </div>
    </div>
</section>

{{-- AmoraCare System Section --}}
<section class="modules-section light-section" id="system">
    <div class="section-heading">
        <h2>AmoraCare System Modules</h2>

        <p>
            AmoraCare is a secure web-based system designed to support adoption
            guidance, donation records, AI legal support, and staff-reviewed
            matching recommendations.
        </p>
    </div>

    <div class="module-grid">
        <div class="module-card">
            <h3>Adoption Guidance</h3>

            <p>
                Manage parent applications, document checklists, adoption case
                updates, workflow progress, and adoption-related reports in one
                centralized platform.
            </p>
        </div>

        <div class="module-card">
            <h3>Donation Management</h3>

            <p>
                Record cash and in-kind donations, manage donor details, generate
                acknowledgments, and monitor donation allocation history.
            </p>
        </div>

        <div class="module-card">
            <h3>AI Legal Support</h3>

            <p>
                Provide informational adoption-related guidance using curated legal
                documents, official procedures, FAQs, and source-grounded AI responses.
            </p>
        </div>
    </div>
</section>

{{-- FAQ Section --}}
<section class="modules-section" id="faq">
    <div class="section-heading">
        <h2>AMOR Village Information Summary</h2>

        <p>
            The information below summarizes key details about AMOR Village and
            how AmoraCare supports its adoption and donation management processes.
        </p>
    </div>

    <div class="faq-list">
        <div class="faq-item">
            <h3>Where is AMOR Village located?</h3>

            <p>
                AMOR Village is located in San Francisco East, Anao, Tarlac.
            </p>
        </div>

        <div class="faq-item">
            <h3>Who does AMOR Village serve?</h3>

            <p>
                It serves vulnerable children and residents, including those affected
                by abuse, neglect, exploitation, trafficking, abandonment, disability,
                special needs, or conflict with the law.
            </p>
        </div>

        <div class="faq-item">
            <h3>What support does AMOR Village provide?</h3>

            <p>
                It provides shelter, counseling, medical assistance, education
                support, nutrition, livelihood training, psychosocial services,
                and therapeutic home-life care.
            </p>
        </div>

        <div class="faq-item">
            <h3>How does AmoraCare help?</h3>

            <p>
                AmoraCare helps organize adoption cases, parent applications,
                donation records, document checklists, AI legal guidance, and
                staff-reviewed matching recommendations.
            </p>
        </div>
    </div>
</section>

<footer class="footer">
    &copy; {{ date('Y') }} AmoraCare. Child Adoption Guidance and Donation Management System.
</footer>

<script>
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function () {
        if (window.scrollY > 40) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>

</body>
</html>
