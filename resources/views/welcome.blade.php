<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#075e59">
    <meta name="description" content="Discover AMOR Village, a residential care community supporting children with disabilities in Anao, Tarlac. Connect with the center or access the AmoraCare portal.">
    <title>AMOR Village | A place to belong. A chance to thrive.</title>
    <link rel="canonical" href="{{ route('home') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="AMOR Village | A place to belong. A chance to thrive.">
    <meta property="og:description" content="Care, connection and possibility in Anao, Tarlac. Discover AMOR Village and connect through AmoraCare.">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image" content="{{ asset('amor-village/photos/amor-shared-moments.webp') }}">
    <link rel="preload" href="{{ asset('amor-village/nunito-sans.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('amor-village/welcome.css') }}?v=20260914">
    <link rel="stylesheet" href="{{ asset('amor-village/accessibility.css') }}?v=20260914">
    <script src="{{ asset('amor-village/accessibility.js') }}?v=20260914" defer></script>
    <script src="{{ asset('amor-village/welcome.js') }}?v=20260914" defer></script>
</head>
@php
    // Keep the existing independent portal sessions and preferred-guard behavior.
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
<body class="amor-site" id="home">
    <a class="amor-skip" href="#main-content">Skip to content</a>
    <header class="amor-header">
        <div class="amor-header-inner">
            <a href="{{ route('home') }}" class="amor-brand" aria-label="AMOR Village home">@include('partials.amor-brand')</a>
            <button class="amor-menu-toggle" type="button" aria-expanded="false" aria-controls="amor-navigation" hidden>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg><span>Menu</span>
            </button>
            <nav class="amor-navigation" id="amor-navigation" aria-label="Main navigation">
                <a href="#about">About AMOR</a><a href="#care">Our Care</a><a href="#life">Life at AMOR</a><a href="#get-involved">Get Involved</a><a href="#amoracare">AmoraCare</a><a href="#contact" class="amor-button amor-nav-cta">Get in Touch <span aria-hidden="true">→</span></a>
            </nav>
        </div>
    </header>

    <main id="main-content" tabindex="-1">
        <section class="amor-section amor-hero amor-wave" aria-labelledby="hero-title">
            <div class="amor-shell amor-hero-row">
                <div class="amor-hero-copy">
                    <p class="amor-kicker">Rooted in care. Growing together.</p>
                    <h1 id="hero-title">A place to<br><span>belong.</span><br>A chance to <em>thrive.</em></h1>
                    <p class="amor-hero-lead">Care, connection and possibility. AMOR Village supports children with disabilities in a nurturing residential community in Anao, Tarlac.</p>
                    <div class="amor-actions"><a class="amor-button" href="#care">Discover Our Care <span aria-hidden="true">→</span></a><a class="amor-button amor-button-outline" href="#contact">Get in Touch</a></div>
                </div>
                <figure class="amor-hero-visual">
                    <div class="amor-hero-image"><img src="{{ asset('amor-village/photos/amor-shared-moments.webp') }}" width="1080" height="1080" fetchpriority="high" alt="A smiling participant makes a peace sign during a community gathering at AMOR Village."></div>
                    <div class="amor-hero-badge"><strong>Every person.</strong>Every possibility.</div>
                    <figcaption>Shared moments from the AMOR community.</figcaption>
                </figure>
            </div>
        </section>

        <section class="amor-section amor-intro" id="about" aria-labelledby="about-title">
            <div class="amor-shell">
                <div class="amor-intro-row">
                    <div><p class="amor-kicker">Welcome to AMOR Village</p><h2 id="about-title">Care that sees the person.<br>A community that makes room.</h2></div>
                    <div class="amor-intro-copy"><p>AMOR Village is a residential care facility of DSWD Field Office III in Barangay San Francisco East, Anao, Tarlac. Its work brings care, protection and individual support together for children with disabilities.</p><a class="amor-text-link" href="#care">Get to know our care <span aria-hidden="true">→</span></a></div>
                </div>
                <figure class="amor-community-photo">
                    <div class="amor-community-image"><img src="{{ asset('amor-village/photos/children-playing-manila.webp') }}" width="1400" height="788" loading="lazy" decoding="async" alt="Children playing together on a street in Manila, including a child holding a yellow ball."></div>
                    <figcaption><strong>Childhood, community and belonging.</strong>Children playing in Manila. The children shown are not identified as AMOR residents.<br>Photo: <a href="https://www.pexels.com/photo/asia-fujifilm-street-photo-street-photography-27848701/">Zachary Angeles / Pexels</a> <span aria-hidden="true">·</span> <a href="https://www.pexels.com/license/">Photo license</a></figcaption>
                </figure>
            </div>
        </section>

        <section class="amor-section amor-care" id="care" aria-labelledby="care-title">
            <div class="amor-shell">
                <div class="amor-section-head"><div><p class="amor-kicker">Support that starts with the person</p><h2 id="care-title">Thoughtful care.<br>Everyday possibilities.</h2></div><a href="#contact" class="amor-text-link">Talk with the center <span aria-hidden="true">→</span></a></div>
                <div class="amor-cards">
                    <article class="amor-care-card">
                        <div class="amor-service-image"><img src="{{ asset('amor-village/photos/amor-community-gathering.webp') }}" width="1080" height="1080" loading="lazy" decoding="async" alt="A group poses together in the gathering space at AMOR Village."></div>
                        <h3>A supportive place to live</h3><p>Residential care and a nurturing setting for children who need protection and support.</p><a class="amor-text-link" href="#contact">Ask about residential care <span aria-hidden="true">→</span></a>
                    </article>
                    <article class="amor-care-card">
                        <div class="amor-service-image"><img src="{{ asset('amor-village/photos/amor-shared-activity.webp') }}" width="1080" height="1080" loading="lazy" decoding="async" alt="Two participants hold microphones in front of a seated group in the covered hall."></div>
                        <h3>Support for the individual</h3><p>Case management that recognizes each person’s circumstances and individual support needs.</p><a class="amor-text-link" href="#contact">Ask about individual support <span aria-hidden="true">→</span></a>
                    </article>
                    <article class="amor-care-card">
                        <div class="amor-service-image"><img src="{{ asset('amor-village/photos/amor-shared-moments.webp') }}" width="1080" height="1080" loading="lazy" decoding="async" alt="A participant smiles during a shared community activity."></div>
                        <h3>Care with connection</h3><p>Personal emotional support within a caring, child-focused residential environment.</p><a class="amor-text-link" href="#contact">Connect with the team <span aria-hidden="true">→</span></a>
                    </article>
                </div>
                <p class="amor-source-note">Based on official DSWD descriptions. Contact the center for current services and referrals. Community photographs show shared activities and do not identify anyone’s care needs.</p>
            </div>
        </section>

        <section class="amor-section amor-life amor-wave" id="life" aria-labelledby="life-title">
            <div class="amor-shell">
                <div class="amor-section-head"><div><p class="amor-kicker">The beauty in everyday moments</p><h2 id="life-title">Little moments.<br>Meaningful connections.</h2></div><p class="amor-section-lead">A glimpse of the people, shared activities and community support around AMOR Village.</p></div>
                <div class="amor-life-grid">
                    <figure class="amor-life-card"><div class="amor-life-image"><img src="{{ asset('amor-village/photos/amor-shared-activity.webp') }}" width="1080" height="1080" loading="lazy" decoding="async" alt="Participants share an activity with microphones in the covered hall."></div><figcaption><strong>Moments to share.</strong>A shared activity in the covered hall.</figcaption></figure>
                    <figure class="amor-life-card"><div class="amor-life-image"><img src="{{ asset('amor-village/photos/amor-community-gathering.webp') }}" width="1080" height="1080" loading="lazy" decoding="async" alt="A large group poses for a photograph at AMOR Village."></div><figcaption><strong>Better, together.</strong>A group photograph in the gathering space.</figcaption></figure>
                    <figure class="amor-life-card"><div class="amor-life-image"><img src="{{ asset('amor-village/photos/amor-community-support.webp') }}" width="1400" height="1050" loading="lazy" decoding="async" alt="Community supporters stand behind supplies and AMOR Village banners."></div><figcaption><strong>A community that shows up.</strong>People and supplies gathered at AMOR Village.</figcaption></figure>
                </div>
            </div>
        </section>

        <section class="amor-section amor-values amor-wave" aria-labelledby="values-title">
            <div class="amor-shell amor-values-row">
                <div class="amor-values-intro"><p class="amor-kicker">Room to be yourself</p><h2 id="values-title">People first.<br>Always.</h2><p>Every person deserves to be seen, respected and included. Care begins with the person, and grows through connection.</p></div>
                <div class="amor-values-grid">
                    <article><span class="amor-value-mark" aria-hidden="true">♡</span><h3>Dignity</h3><p>Respect for the person, their voice and their privacy.</p></article>
                    <article><span class="amor-value-mark" aria-hidden="true">⌂</span><h3>Belonging</h3><p>A welcoming sense of connection and community.</p></article>
                    <article><span class="amor-value-mark" aria-hidden="true">✳</span><h3>Individual support</h3><p>Space for different needs, strengths and ways of growing.</p></article>
                    <article><span class="amor-value-mark" aria-hidden="true">☼</span><h3>Inclusion</h3><p>Everyday opportunities to take part and feel valued.</p></article>
                </div>
            </div>
        </section>

        <section class="amor-section" id="get-involved" aria-labelledby="involve-title">
            <div class="amor-shell amor-involve-row">
                <div><p class="amor-kicker">Good things grow with community</p><h2 id="involve-title">There’s a place<br>for your kindness.</h2><p>Interested in supporting AMOR? Start a conversation about the center’s current needs and how you might help.</p><a class="amor-button" href="#contact">Find Your Way to Help <span aria-hidden="true">→</span></a></div>
                <div class="amor-involve-aside"><ol><li><span>01</span>Ask about current needs</li><li><span>02</span>Explore a partnership</li><li><span>03</span>Discuss a proposed visit</li></ol><p>Please coordinate donations, visits and offers of help directly with the center before making arrangements.</p></div>
            </div>
        </section>

        <section class="amor-section amor-portal-section" id="amoracare" aria-labelledby="portal-title">
            <div class="amor-shell">
                <div class="amor-section-head"><div><p class="amor-kicker">Connected through AmoraCare</p><h2 id="portal-title">The next step,<br>with guidance.</h2></div><p class="amor-section-lead">A secure space for prospective parents, care teams and authorized reviewers to work together.</p></div>
                <div class="amor-portal-grid">
                    <article class="amor-portal-card"><span class="amor-card-label">For prospective parents</span><h3>Begin with an application.</h3><p>Submit your prospective adoptive parent application through AmoraCare to start the review process.</p><a class="amor-text-link" href="{{ route('parent.application.create') }}">Start an application <span aria-hidden="true">→</span></a></article>
                    <article class="amor-portal-card"><span class="amor-card-label">Your secure portal</span><h3>{{ $hasAuthenticatedPortal ? 'Welcome back.' : 'Keep everything connected.' }}</h3><p>Access your authorized workspace for application progress, documents and case collaboration.</p>
                        @if($hasAuthenticatedPortal)
                            <a class="amor-text-link" href="{{ route('dashboard') }}">Open Dashboard <span aria-hidden="true">→</span></a>
                            <form method="POST" action="{{ route('logout') }}" class="amor-logout-form">@csrf<input type="hidden" name="guard" value="{{ $activeLandingGuard }}"><button type="submit" class="amor-logout">Log out</button></form>
                        @else
                            <a class="amor-text-link" href="{{ route('login') }}">Secure Login <span aria-hidden="true">→</span></a>
                        @endif
                    </article>
                    <article class="amor-portal-card"><span class="amor-card-label">For staff and partners</span><h3>Care through collaboration.</h3><p>Authorized staff and reviewers can coordinate case records, document reviews and donation reporting.</p><a class="amor-text-link" href="{{ route($hasAuthenticatedPortal ? 'dashboard' : 'login') }}">{{ $hasAuthenticatedPortal ? 'Go to your workspace' : 'Access your workspace' }} <span aria-hidden="true">→</span></a></article>
                </div>
                <div class="amor-faq" aria-label="About AmoraCare">
                    <details><summary>Who can access confidential records?</summary><p>Authenticated users with the appropriate role and authorized case access. The parent, admin and external reviewer portals provide different information and actions.</p></details>
                    <details><summary>How are adoption decisions made?</summary><p>AmoraCare supports applications and staff review. Matching recommendations and informational AI guidance support the work of qualified people; decisions remain with authorized professionals and agencies.</p></details>
                </div>
            </div>
        </section>

        <section class="amor-section amor-contact-invite amor-wave" id="contact" aria-labelledby="contact-title">
            <div class="amor-shell amor-contact-row"><div><p class="amor-kicker">A conversation is a good place to start</p><h2 id="contact-title">Let’s make a connection.</h2><p>Have a question about care or getting involved?<br>Reach out to the team at AMOR Village.</p></div><div class="amor-contact-cta"><a class="amor-button" href="mailto:amorv.fo3@dswd.gov.ph">Email the Center <span aria-hidden="true">↗</span></a><a href="mailto:amorv.fo3@dswd.gov.ph">amorv.fo3@dswd.gov.ph</a></div></div>
        </section>
    </main>

    <footer class="amor-footer">
        <div class="amor-shell">
            <div class="amor-footer-main">
                <div class="amor-footer-brand"><a class="amor-brand" href="{{ route('home') }}" aria-label="AMOR Village home">@include('partials.amor-brand')</a><p>A place to belong.<br>A chance to thrive.</p><small>DSWD Field Office III<br>Anao, Tarlac, Philippines</small></div>
                <div><h2>Explore AMOR</h2><a href="#about">About AMOR</a><a href="#care">Our Care</a><a href="#life">Life at AMOR</a></div>
                <div><h2>Be part of the story</h2><a href="#get-involved">Get Involved</a><a href="{{ route('parent.application.create') }}">Parent Application</a><a href="{{ route($hasAuthenticatedPortal ? 'dashboard' : 'login') }}">{{ $hasAuthenticatedPortal ? 'Open Dashboard' : 'AmoraCare Login' }}</a></div>
                <div><h2>Let’s connect</h2><a href="mailto:amorv.fo3@dswd.gov.ph">amorv.fo3@dswd.gov.ph</a><p>Barangay San Francisco East<br>Anao, Tarlac, Philippines</p><a class="amor-official" href="https://fo3.dswd.gov.ph/dir/">Official DSWD directory <span aria-hidden="true">↗</span></a></div>
            </div>
            <div class="amor-footer-bottom"><span>&copy; {{ date('Y') }} AMOR Village · AmoraCare</span>@include('partials.legal-links')<a href="#accessibility">Accessibility</a></div>
            <details class="amor-accessibility-statement" id="accessibility"><summary>Accessibility on this website</summary><p>Use the Accessibility button to adjust text size, spacing, contrast, link underlines, motion and a reading guide. Preferences are saved in this browser when storage is available. You can also use your browser’s zoom and navigate with a keyboard. Contact <a href="mailto:amorv.fo3@dswd.gov.ph">amorv.fo3@dswd.gov.ph</a> to share an access difficulty with the center.</p></details>
        </div>
    </footer>
    @include('partials.amor-accessibility')
</body>
</html>
