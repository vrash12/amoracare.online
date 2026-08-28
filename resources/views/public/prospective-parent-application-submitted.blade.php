<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted | AmoraCare</title>
    <link rel="stylesheet" href="{{ asset('css/parent-application.css') }}">
</head>
<body class="success-page">
    <main class="success-card">
        <img src="{{ asset('images/amora.png') }}" alt="AmoraCare logo" class="success-logo">
        <div class="success-icon" aria-hidden="true">✓</div>
        <span class="eyebrow">Application received</span>
        <h1>Thank you for applying.</h1>
        <p>Your email <strong>{{ $email }}</strong> was verified, your preliminary application was submitted successfully, and the account is now <strong>Pending</strong>.</p>
        <div class="success-next"><strong>What happens next?</strong> Authorized staff will review your information. Once the account is activated, each login will require your password and a new email OTP.</div>
        <div class="success-actions">
            <a href="{{ route('home') }}" class="secondary-button">Return Home</a>
            <a href="{{ route('login') }}" class="submit-button">Go to Login</a>
        </div>
    </main>
</body>
</html>
