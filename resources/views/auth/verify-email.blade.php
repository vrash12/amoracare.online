<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Verify your AmoraCare account email address.">
    <title>Verify Email | AmoraCare</title>
    <link rel="icon" type="image/png" href="{{ asset('images/amora.png') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <main class="auth-page">
        <div class="auth-background" aria-hidden="true">
            <span class="auth-shape auth-shape-one"></span>
            <span class="auth-shape auth-shape-two"></span>
            <span class="auth-shape auth-shape-three"></span>
        </div>

        <section class="auth-shell">
            <aside class="auth-introduction">
                <div class="auth-introduction-overlay"></div>
                <div class="auth-introduction-content">
                    <header class="auth-brand">
                        <a href="{{ url('/') }}" class="auth-logo-link" aria-label="Go to AmoraCare home page">
                            <span class="auth-logo-frame">
                                <img src="{{ asset('images/amora.png') }}" alt="AmoraCare logo" class="auth-logo">
                            </span>
                            <span class="auth-brand-copy">
                                <strong>AmoraCare</strong>
                                <small>Care. Guidance. Hope.</small>
                            </span>
                        </a>
                    </header>

                    <div class="auth-welcome">
                        <h1>One more step to protect your account.</h1>
                        <p>Email verification helps ensure that account access and sensitive application updates reach the correct person.</p>
                    </div>

                    <footer class="auth-introduction-footer">
                        <span>Your six-digit code is private and expires automatically.</span>
                    </footer>
                </div>
            </aside>

            <section class="auth-form-section">
                <div class="auth-mobile-brand">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('images/amora.png') }}" alt="AmoraCare logo">
                        <span>AmoraCare</span>
                    </a>
                </div>

                <div class="auth-form-container">
                    <div class="auth-form-heading">
                        <span class="auth-form-label">Email verification</span>
                        <h2>Enter your verification code</h2>
                        <p>We sent a six-digit code to <strong>{{ $maskedEmail }}</strong>. It expires in {{ $expiresMinutes }} minutes.</p>
                    </div>

                    @if(session('success'))
                        <div class="auth-alert auth-alert-success" role="alert">
                            <div>
                                <strong>Verification code</strong>
                                <p>{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="auth-alert auth-alert-error" role="alert">
                            <div>
                                <strong>Unable to verify</strong>
                                <p>{{ $errors->first() }}</p>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('email.verification.verify') }}" class="auth-form" id="verificationForm">
                        @csrf

                        <div class="auth-field">
                            <label for="code">Six-digit verification code</label>
                            <div class="auth-input-wrapper @error('code') has-error @enderror">
                                <input
                                    type="text"
                                    id="code"
                                    name="code"
                                    value="{{ old('code') }}"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    pattern="[0-9]{6}"
                                    maxlength="6"
                                    placeholder="000000"
                                    style="padding-left:18px;text-align:center;font-size:26px;font-weight:800;letter-spacing:10px;"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <button type="submit" class="auth-submit" id="verifyButton">
                            <span class="auth-submit-content">Verify email and continue</span>
                            <span class="auth-submit-loading"><span class="auth-spinner"></span> Verifying...</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('email.verification.resend') }}" style="margin-top:16px;text-align:center;">
                        @csrf
                        <button type="submit" style="border:0;background:transparent;color:#9d3f20;font-weight:700;cursor:pointer;">
                            Send another code
                        </button>
                    </form>

                    <div class="auth-bottom-links" style="margin-top:20px;">
                        <a href="{{ route('login') }}">Return to sign in</a>
                        <span>Need assistance? Contact your administrator.</span>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const codeInput = document.getElementById('code');
            const form = document.getElementById('verificationForm');
            const button = document.getElementById('verifyButton');

            codeInput?.addEventListener('input', function () {
                codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
            });

            form?.addEventListener('submit', function () {
                if (!form.checkValidity()) return;
                button.disabled = true;
                button.classList.add('is-loading');
            });
        });
    </script>
</body>
</html>
