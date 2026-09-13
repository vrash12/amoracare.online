@extends('layouts.dashboard', ['title' => 'Parent Application QR Code'])

@section('content')
    @include('admin.parents._styles')

    <style>
        .qr-layout { display: grid; grid-template-columns: minmax(300px, 430px) minmax(0, 1fr); gap: 22px; align-items: start; }
        .qr-card { padding: 28px; border: 1px solid #eaded8; border-radius: 24px; background: #fff; box-shadow: 0 18px 48px rgba(69, 37, 24, .08); }
        .qr-frame { display: grid; place-items: center; padding: 18px; border: 1px solid #eaded8; border-radius: 20px; background: #fffdfb; }
        .qr-frame img { width: min(100%, 390px); height: auto; }
        .qr-url { margin: 18px 0 0; padding: 13px 15px; overflow-wrap: anywhere; border: 1px solid #e5e7eb; border-radius: 13px; background: #f8fafc; color: #475467; font-size: 13px; }
        .qr-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
        .qr-guide { display: grid; gap: 14px; margin-top: 22px; }
        .qr-step { display: grid; grid-template-columns: 36px 1fr; gap: 12px; align-items: start; }
        .qr-step-number { width: 36px; height: 36px; display: grid; place-items: center; border-radius: 11px; background: #fff0e9; color: #8e371f; font-weight: 900; }
        .qr-step strong { display: block; margin-bottom: 3px; color: #172033; }
        .qr-step p { margin: 0; color: #667085; line-height: 1.6; }
        .qr-note { margin-top: 22px; padding: 15px; border: 1px solid #fde68a; border-radius: 15px; background: #fffbeb; color: #92400e; line-height: 1.6; }
        @media print { .sidebar, .topbar, .parents-actions, .qr-actions, .qr-guide, .qr-note, .parents-eyebrow, .parents-title p { display: none !important; } .main-content { margin: 0 !important; padding: 0 !important; } .qr-layout { display: block; } .qr-card { max-width: 560px; margin: 30px auto; box-shadow: none; text-align: center; } }
        @media (max-width: 850px) { .qr-layout { grid-template-columns: 1fr; } }
    </style>

    <div class="parents-page">
        <section class="parents-hero">
            <div class="parents-header">
                <div class="parents-title">
                    <div class="parents-eyebrow"><i class="bi bi-qr-code"></i> Public application access</div>
                    <h2>Prospective Parent Application QR Code</h2>
                    <p>Print or display this code so prospective adoptive parents can open and complete the application form on their phone.</p>
                </div>
                <div class="parents-actions">
                    <a href="{{ route('admin.parents.index') }}" class="btn light"><i class="bi bi-arrow-left"></i> Back to Parents</a>
                </div>
            </div>
        </section>

        <div class="qr-layout">
            <section class="qr-card">
                <div class="qr-frame">
                    <img src="{{ route('admin.parents.application-qr.image') }}" alt="QR code for the AmoraCare prospective adoptive parent application form">
                </div>
                <div class="qr-url">{{ $applicationUrl }}</div>
                <div class="qr-actions">
                    <a href="{{ route('admin.parents.application-qr.download') }}" class="btn secondary"><i class="bi bi-download"></i> Download QR</a>
                    <button type="button" class="btn light" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                    <a href="{{ $applicationUrl }}" target="_blank" rel="noopener" class="btn light"><i class="bi bi-box-arrow-up-right"></i> Open Form</a>
                </div>
            </section>

            <section class="qr-card">
                <h3 style="margin: 0; color: #172033;">How it works</h3>
                <div class="qr-guide">
                    <div class="qr-step"><div class="qr-step-number">1</div><div><strong>Scan the QR code</strong><p>The applicant scans the code using a phone camera and opens the secure AmoraCare form.</p></div></div>
                    <div class="qr-step"><div class="qr-step-number">2</div><div><strong>Complete the preliminary form</strong><p>The applicant provides contact details, creates a password, and records basic matching preferences.</p></div></div>
                    <div class="qr-step"><div class="qr-step-number">3</div><div><strong>Verify the email address</strong><p>AmoraCare sends a one-time code to the applicant. The account is created only after the code is entered successfully.</p></div></div>
                    <div class="qr-step"><div class="qr-step-number">4</div><div><strong>Review the Pending account</strong><p>The verified submission appears in Parent Profiles with a Pending status until authorized staff activate it.</p></div></div>
                </div>
                <div class="qr-note"><strong>Staff-controlled assessment:</strong> Home-study verification, readiness scores, internal notes, and final adoption decisions are not included in the public form.</div>
            </section>
        </div>
    </div>
@endsection
