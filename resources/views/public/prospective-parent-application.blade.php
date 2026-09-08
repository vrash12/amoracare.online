<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Submit a preliminary prospective adoptive parent application to AmoraCare.">
    <title>Prospective Parent Application | AmoraCare</title>
    <link rel="stylesheet" href="{{ asset('css/parent-application.css') }}">
</head>
<body>
    <header class="application-header">
        <a href="{{ route('home') }}" class="brand" aria-label="Return to AmoraCare home">
            <img src="{{ asset('images/amora.png') }}" alt="AmoraCare logo">
            <span><strong>AmoraCare</strong><small>AMOR Village Orphanage</small></span>
        </a>
        <a href="{{ route('login') }}" class="header-link">Already registered? Log in</a>
    </header>

    <main class="application-shell">
        <section class="application-intro">
            <span class="eyebrow">Preliminary application</span>
            <h1>Begin your adoption journey with care.</h1>
            <p>Complete this secure form to create a prospective adoptive parent application. Authorized AmoraCare staff will review your information before activating your account.</p>
            <div class="privacy-note"><strong>Your information is confidential.</strong> Only authorized personnel may review application records. Submitting this form does not guarantee approval or matching.</div>
        </section>

        <section class="form-card">
            <div class="form-heading">
                <span>Prospective Adoptive Parent</span>
                <h2>Application Form</h2>
                <p>Fields marked with * are required.</p>
            </div>

            @if (session('error'))
                <div class="error-summary" role="alert">
                    <strong>Unable to continue</strong>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="error-summary" role="alert">
                    <strong>Please review the highlighted information.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('parent.application.store') }}" class="application-form">
                @csrf

                <fieldset>
                    <legend>Contact and account information</legend>
                    <div class="field-grid">
                        <div class="field field-wide">
                            <label for="name">Full name *</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="255" autocomplete="name" autocapitalize="words" data-auto-capitalize required @class(['is-invalid' => $errors->has('name')])>
                            @error('name')<small class="field-error">{{ $message }}</small>@enderror
                        </div>
                        <div class="field">
                            <label for="email">Email address *</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required @class(['is-invalid' => $errors->has('email')])>
                            @error('email')<small class="field-error">{{ $message }}</small>@enderror
                        </div>
                        <div class="field">
                            <label for="phone_number">Phone number *</label>
                            <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" maxlength="50" autocomplete="tel" required @class(['is-invalid' => $errors->has('phone_number')])>
                            @error('phone_number')<small class="field-error">{{ $message }}</small>@enderror
                        </div>
                        <div class="field">
                            <label for="password">Create password *</label>
                            <input id="password" name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required @class(['is-invalid' => $errors->has('password')])>
                            <small>Use at least 8 characters.</small>
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm password *</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
                            @error('password')<small class="field-error">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Initial matching preferences</legend>
                    <p class="section-help">These preferences are preliminary and may be discussed with your assigned social worker.</p>
                    <div class="field-grid field-grid-three">
                        <div class="field">
                            <label for="preferred_child_sex">Preferred child sex *</label>
                            <select id="preferred_child_sex" name="preferred_child_sex" required>
                                <option value="any" @selected(old('preferred_child_sex', 'any') === 'any')>No preference</option>
                                <option value="male" @selected(old('preferred_child_sex') === 'male')>Male</option>
                                <option value="female" @selected(old('preferred_child_sex') === 'female')>Female</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="min_child_age">Minimum child age *</label>
                            <input id="min_child_age" name="min_child_age" type="number" value="{{ old('min_child_age', 0) }}" min="0" max="18" required>
                        </div>
                        <div class="field">
                            <label for="max_child_age">Maximum child age *</label>
                            <input id="max_child_age" name="max_child_age" type="number" value="{{ old('max_child_age', 10) }}" min="0" max="18" required>
                        </div>
                    </div>
                    <label class="check-field">
                        <input type="checkbox" name="open_to_special_needs" value="1" @checked(old('open_to_special_needs'))>
                        <span><strong>Open to considering a child with special needs</strong><small>This preference can be discussed and updated during assessment.</small></span>
                    </label>
                </fieldset>

                <label class="check-field consent-field">
                    <input type="checkbox" name="consent" value="1" required aria-describedby="applicationConsentHelp" @checked(old('consent'))>
                    <span id="applicationConsentHelp">
                        <strong>Application consent *</strong>
                        <small>
                            I confirm that the information provided is accurate and consent to its review by authorized AmoraCare personnel for preliminary adoption assistance.
                        </small>
                    </span>
                </label>
                @error('consent')<small class="field-error consent-error">{{ $message }}</small>@enderror

                <label class="check-field consent-field">
                    <input type="checkbox" name="terms_accepted" value="1" required aria-describedby="legalAgreementHelp" @checked(old('terms_accepted'))>
                    <span id="legalAgreementHelp">
                        <strong>Terms and privacy acknowledgment *</strong>
                        <small>
                            I agree to the <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer">Terms and Conditions</a>
                            and confirm that I have read the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Notice</a>.
                        </small>
                    </span>
                </label>
                @error('terms_accepted')<small class="field-error consent-error">{{ $message }}</small>@enderror

                <button type="submit" class="submit-button">Submit Application</button>
                <p class="submit-help">Your account will remain <strong>Pending</strong> until it is reviewed and activated by authorized staff.</p>
            </form>
        </section>
    </main>

    <footer class="application-legal-footer">
        <span>&copy; {{ date('Y') }} AmoraCare</span>
        @include('partials.legal-links')
    </footer>

    <script>
        document.querySelectorAll('[data-auto-capitalize]').forEach((input) => {
            input.addEventListener('blur', () => {
                input.value = input.value.trim().replace(/\s+/g, ' ').toLowerCase().replace(/\b\p{L}/gu, (letter) => letter.toUpperCase());
            });
        });
    </script>
</body>
</html>
