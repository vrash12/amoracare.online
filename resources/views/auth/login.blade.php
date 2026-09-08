<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Securely sign in to the AmoraCare adoption guidance and donation management system."
    >

    <title>Sign In | AmoraCare</title>

    <link
        rel="icon"
        type="image/png"
        href="{{ asset('images/amora.png') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/login.css') }}"
    >
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
                        <a
                            href="{{ url('/') }}"
                            class="auth-logo-link"
                            aria-label="Go to AmoraCare home page"
                        >
                            <span class="auth-logo-frame">
                                <img
                                    src="{{ asset('images/amora.png') }}"
                                    alt="AmoraCare logo"
                                    class="auth-logo"
                                >
                            </span>

                            <span class="auth-brand-copy">
                                <strong>AmoraCare</strong>
                                <small>Care. Guidance. Hope.</small>
                            </span>
                        </a>
                    </header>

                    <div class="auth-welcome">
                  

                        <h1>
                            Supporting every adoption journey with care.
                        </h1>

                        <p>
                            Access child adoption guidance, case records,
                            donations, application updates, and authorized
                            system activities from one secure platform.
                        </p>
                    </div>

                    <div class="auth-features">
                        <article class="auth-feature">
                            <span class="auth-feature-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M12 3 5 6v5c0 4.7 2.8 8.4 7 10 4.2-1.6 7-5.3 7-10V6l-7-3Z"
                                    />
                                    <path d="m9.5 12 1.7 1.7 3.6-4" />
                                </svg>
                            </span>

                            <div>
                                <strong>Protected access</strong>
                                <p>
                                    Role-based access helps keep sensitive
                                    records private and secure.
                                </p>
                            </div>
                        </article>

                        <article class="auth-feature">
                            <span class="auth-feature-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M7 21h10a2 2 0 0 0 2-2V8l-5-5H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z"
                                    />
                                    <path d="M14 3v5h5" />
                                    <path d="M9 13h6M9 17h4" />
                                </svg>
                            </span>

                            <div>
                                <strong>Organized case records</strong>
                                <p>
                                    Manage documents, case progress, and
                                    important updates efficiently.
                                </p>
                            </div>
                        </article>
                    </div>

                    <footer class="auth-introduction-footer">
                        <span>
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10Z"
                                />
                            </svg>

                            Built to support children, families, and
                            authorized care professionals.
                        </span>
                    </footer>
                </div>
            </aside>

            <section class="auth-form-section">
                <div class="auth-mobile-brand">
                    <a
                        href="{{ url('/') }}"
                        aria-label="Go to AmoraCare home page"
                    >
                        <img
                            src="{{ asset('images/amora.png') }}"
                            alt="AmoraCare logo"
                        >

                        <span>AmoraCare</span>
                    </a>
                </div>

                <div class="auth-form-container">
                    <div class="auth-form-heading">
                        <span class="auth-form-icon">
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="M15 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"
                                />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M16 11h5M18.5 8.5v5" />
                            </svg>
                        </span>

                        <span class="auth-form-label">
                            Account access
                        </span>

                        <h2>Welcome back</h2>

                        <p>
                            Enter your account credentials to continue to
                            your dashboard.
                        </p>
                    </div>

                    @if(session('success'))
                        <div
                            class="auth-alert auth-alert-success"
                            role="alert"
                        >
                            <span class="auth-alert-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="m8 12 2.5 2.5L16 9" />
                                </svg>
                            </span>

                            <div>
                                <strong>Success</strong>
                                <p>{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div
                            class="auth-alert auth-alert-error"
                            role="alert"
                        >
                            <span class="auth-alert-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 8v5M12 17h.01" />
                                </svg>
                            </span>

                            <div>
                                <strong>Unable to sign in</strong>
                                <p>{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div
                            class="auth-alert auth-alert-error"
                            role="alert"
                        >
                            <span class="auth-alert-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 8v5M12 17h.01" />
                                </svg>
                            </span>

                            <div>
                                <strong>Please review your details</strong>
                                <p>
                                    The email address or password you entered
                                    could not be verified.
                                </p>
                            </div>
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('login.store') }}"
                        class="auth-form"
                        id="loginForm"
                        novalidate
                    >
                        @csrf

                        <div class="auth-field">
                            <label for="email">
                                Email address
                            </label>

                            <div
                                class="auth-input-wrapper
                                    @error('email') has-error @enderror"
                            >
                                <span class="auth-input-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <rect
                                            x="3"
                                            y="5"
                                            width="18"
                                            height="14"
                                            rx="2"
                                        />
                                        <path d="m3 7 9 6 9-6" />
                                    </svg>
                                </span>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    placeholder="name@example.com"
                                    aria-describedby="emailHelp"
                                    @error('email')
                                        aria-invalid="true"
                                    @enderror
                                >
                            </div>

                            @error('email')
                                <p class="auth-error-text" id="emailHelp">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 8v5M12 17h.01" />
                                    </svg>

                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <div class="auth-label-row">
                                <label for="password">
                                    Password
                                </label>
                            </div>

                            <div
                                class="auth-input-wrapper
                                    @error('password') has-error @enderror"
                            >
                                <span class="auth-input-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <rect
                                            x="4"
                                            y="10"
                                            width="16"
                                            height="11"
                                            rx="2"
                                        />
                                        <path
                                            d="M8 10V7a4 4 0 0 1 8 0v3"
                                        />
                                    </svg>
                                </span>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                    aria-describedby="passwordHelp"
                                    @error('password')
                                        aria-invalid="true"
                                    @enderror
                                >

                                <button
                                    type="button"
                                    class="auth-password-toggle"
                                    id="passwordToggle"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <svg
                                        class="password-eye-show"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"
                                        />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>

                                    <svg
                                        class="password-eye-hide"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="m3 3 18 18M10.6 6.2A9.7 9.7 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.3 3"
                                        />
                                        <path
                                            d="M6.5 6.5C3.9 8.3 2.5 12 2.5 12s3.5 6 9.5 6a9 9 0 0 0 3.2-.6"
                                        />
                                        <path
                                            d="M9.8 9.8A3 3 0 0 0 14.2 14.2"
                                        />
                                    </svg>
                                </button>
                            </div>

                            @error('password')
                                <p
                                    class="auth-error-text"
                                    id="passwordHelp"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 8v5M12 17h.01" />
                                    </svg>

                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="auth-form-options">
                            <label class="auth-checkbox">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    @checked(old('remember'))
                                >

                                <span class="auth-checkbox-control">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path d="m5 12 4 4L19 6" />
                                    </svg>
                                </span>

                                <span>Keep me signed in</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="auth-submit"
                            id="loginButton"
                        >
                            <span class="auth-submit-content">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M10 17l5-5-5-5M15 12H3"
                                    />
                                    <path
                                        d="M14 4h4a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-4"
                                    />
                                </svg>

                                Sign in securely
                            </span>

                            <span class="auth-submit-loading">
                                <span class="auth-spinner"></span>
                                Signing in...
                            </span>
                        </button>
                    </form>

               

                    <div class="auth-divider">
                        <span>AmoraCare Portal</span>
                    </div>

                    <div class="auth-bottom-links">
                        <a href="{{ url('/') }}">
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path d="m15 18-6-6 6-6" />
                            </svg>

                            Return to home
                        </a>

                        <span>
                            Need assistance? Contact your administrator.
                        </span>
                    </div>

                </div>

                <footer class="auth-mobile-footer">
                    <span>&copy; {{ date('Y') }} AmoraCare. All rights reserved.</span>
                    @include('partials.legal-links')
                </footer>
            </section>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const passwordInput =
                document.getElementById("password");

            const passwordToggle =
                document.getElementById("passwordToggle");

            const loginForm =
                document.getElementById("loginForm");

            const loginButton =
                document.getElementById("loginButton");

            passwordToggle?.addEventListener("click", function () {
                const isPassword =
                    passwordInput.type === "password";

                passwordInput.type =
                    isPassword ? "text" : "password";

                passwordToggle.classList.toggle(
                    "is-visible",
                    isPassword
                );

                passwordToggle.setAttribute(
                    "aria-pressed",
                    isPassword ? "true" : "false"
                );

                passwordToggle.setAttribute(
                    "aria-label",
                    isPassword
                        ? "Hide password"
                        : "Show password"
                );
            });

            loginForm?.addEventListener("submit", function () {
                if (!loginForm.checkValidity()) {
                    return;
                }

                loginButton.disabled = true;
                loginButton.classList.add("is-loading");
            });
        });
    </script>
</body>
</html>
