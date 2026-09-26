@extends('layouts.dashboard', ['title' => 'Terms and Conditions'])

@section('content')
<section class="panel account-panel">
    <h1>Terms and Conditions</h1>
    <p>Please review and accept the current terms before using your account. If you do not agree, you can sign out using the navigation menu.</p>
    <p>Effective date: {{ config('legal.effective_date') }}. <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Open full terms (new tab)</a></p>
    <div class="account-terms-box" role="region" aria-label="Terms and Conditions content" tabindex="0">
        @include('partials.terms-content')
    </div>
    <form method="POST" action="{{ route(auth()->user()->accountRoute('terms.accept')) }}" class="account-form">
        @csrf
        <input type="hidden" name="terms_version" value="{{ config('legal.terms_version') }}">
        <label class="account-consent"><input type="checkbox" name="terms_accepted" value="1" required> <span>I have read and agree to the Terms and Conditions and have read the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Privacy Notice (new tab)</a>.</span></label>
        <button class="btn" type="submit">Accept and continue</button>
    </form>
</section>
@endsection
