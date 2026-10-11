@props(['status' => 'info'])
@php
    $kind = match ($status) {
        'pending', 'rejected', 'expired' => 'action',
        'submitted', 'under_review' => 'review',
        'verified' => 'verified',
        default => 'info',
    };
@endphp
<svg {{ $attributes->class(['document-status-icon', 'is-'.$kind]) }} viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @if($kind === 'action')
        <circle cx="12" cy="12" r="9" />
        <path d="M12 7v6" />
        <circle cx="12" cy="17" r="1" fill="currentColor" stroke="none" />
    @elseif($kind === 'review')
        <circle cx="12" cy="12" r="9" />
        <path d="M12 7v5l3 2" />
    @elseif($kind === 'verified')
        <circle cx="12" cy="12" r="9" />
        <path d="m8 12 3 3 5-6" />
    @else
        <circle cx="12" cy="12" r="9" />
        <path d="M12 11v6" />
        <circle cx="12" cy="7" r="1" fill="currentColor" stroke="none" />
    @endif
</svg>
