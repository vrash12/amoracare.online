<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description')">
    <title>@yield('title') | AmoraCare</title>
    <link rel="icon" type="image/png" href="{{ asset('images/amora.png') }}">
    <link rel="stylesheet" href="{{ asset('css/legal.css') }}">
</head>
<body>
    <a class="skip-link" href="#legalContent">Skip to content</a>

    <header class="legal-header">
        <div class="legal-container legal-header-inner">
            <a href="{{ url('/') }}" class="legal-brand" aria-label="Return to AmoraCare home">
                <img src="{{ asset('images/amora.png') }}" alt="">
                <span>
                    <strong>AmoraCare</strong>
                    <small>Care. Guidance. Hope.</small>
                </span>
            </a>

            <nav class="legal-navigation" aria-label="Legal pages">
                <a href="{{ route('legal.terms') }}" @class(['is-current' => request()->routeIs('legal.terms')])>Terms</a>
                <a href="{{ route('legal.privacy') }}" @class(['is-current' => request()->routeIs('legal.privacy')])>Privacy</a>
                <a href="{{ route('home') }}" class="legal-home-link">Return home</a>
            </nav>
        </div>
    </header>

    <main id="legalContent" class="legal-main">
        <div class="legal-container legal-layout">
            <aside class="legal-summary" aria-label="Document summary">
                <span class="legal-eyebrow">AmoraCare policies</span>
                <h1>@yield('heading')</h1>
                <p>@yield('summary')</p>
                <dl>
                    <div>
                        <dt>Effective date</dt>
                        <dd>{{ config('legal.effective_date') }}</dd>
                    </div>
                    <div>
                        <dt>Applies to</dt>
                        <dd>All AmoraCare users and visitors</dd>
                    </div>
                </dl>
            </aside>

            <article class="legal-document">
                @yield('content')
            </article>
        </div>
    </main>

    <footer class="legal-footer">
        <div class="legal-container legal-footer-inner">
            <span>&copy; {{ date('Y') }} AmoraCare. All rights reserved.</span>
            @include('partials.legal-links')
        </div>
    </footer>
</body>
</html>
