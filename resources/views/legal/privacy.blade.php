@extends('layouts.legal')

@section('title', 'Privacy Notice')
@section('description', 'Privacy notice describing how AmoraCare collects, uses, protects, and shares personal information.')
@section('heading', 'Privacy Notice')
@section('summary', 'This notice explains what information AmoraCare handles, why it is needed, who may access it, and the choices and rights available to data subjects.')

@section('content')
    <div class="legal-callout is-green">
        <strong>Privacy and child protection are shared responsibilities.</strong>
        <p>AmoraCare handles personal and sensitive information for authorized adoption-assistance and donation-management activities. Users must only enter, view, or share information that their role permits.</p>
    </div>

    <section id="scope">
        <h2>1. Scope of this notice</h2>
        <p>This Privacy Notice applies to visitors and users of AmoraCare, including prospective adoptive parents, administrators, authorized staff or social workers, external reviewers, donors whose information is recorded, and individuals represented in authorized adoption records.</p>
        <p>For information processed through AmoraCare, AMOR Village Orphanage acts as the responsible organization or personal information controller, subject to its official policies and applicable Philippine law.</p>
    </section>

    <section id="data-collected">
        <h2>2. Information the system may collect</h2>
        <ul>
            <li><strong>Account and identity information:</strong> name, email address, phone number, role, account status, password in protected form, email-verification status, and login activity.</li>
            <li><strong>Prospective parent information:</strong> application details, household and matching preferences, assessments, required documents, reviewer remarks, case progress, and related communications.</li>
            <li><strong>Child and case information:</strong> identity, care, eligibility, welfare, health-related, social-work, placement, documentary, and case information entered by authorized personnel.</li>
            <li><strong>Reviewer and staff information:</strong> role assignments, authorized case access, notes, document decisions, case decisions, and audit activity.</li>
            <li><strong>Donation information:</strong> donor contact details, donation type, amount or estimated value, purpose, payment or acknowledgment details, items, and remarks.</li>
            <li><strong>Technical and security information:</strong> session data, timestamps, device or browser information when available, error information, and logs used for security, troubleshooting, and accountability.</li>
            <li><strong>AI guidance information:</strong> questions submitted to the legal-guidance assistant, generated responses, cited sources, and limited context needed to provide the feature.</li>
        </ul>
    </section>

    <section id="purpose">
        <h2>3. Why information is processed</h2>
        <p>Information may be processed to:</p>
        <ul>
            <li>create and verify accounts, authenticate users, assign permissions, and protect system access;</li>
            <li>receive and review preliminary applications and required documents;</li>
            <li>manage authorized profiles, adoption cases, workflows, notes, and status updates;</li>
            <li>provide informational legal guidance and human-reviewed matching recommendations;</li>
            <li>record donations, prepare acknowledgments, produce reports, and support accountability;</li>
            <li>communicate necessary account, verification, document, and case information;</li>
            <li>maintain audit records, investigate misuse, correct errors, and improve reliability; and</li>
            <li>meet applicable legal, regulatory, child-protection, and official reporting obligations.</li>
        </ul>
    </section>

    <section id="basis">
        <h2>4. Basis for processing</h2>
        <p>Depending on the information and activity involved, processing may be based on informed consent, steps requested by the data subject, the organization&rsquo;s legitimate and authorized functions, protection of vital interests, compliance with a legal obligation, or another basis permitted by the Data Privacy Act of 2012 and applicable regulations.</p>
        <p>Consent may be withdrawn where consent is the applicable basis, but withdrawal does not affect processing already lawfully performed and may limit the availability of functions that require the information.</p>
    </section>

    <section id="access-sharing">
        <h2>5. Access and disclosure</h2>
        <p>Access is limited according to assigned roles and authorized case responsibilities. Information may be available to authorized AMOR Village administrators, staff, social workers, and external reviewers such as RACCO personnel only when their work requires it.</p>
        <p>Minimum necessary information may also be handled by contracted technology providers that support functions such as email verification, hosting, storage, security, or AI-assisted guidance. Information may be disclosed to NACC, RACCO, DSWD, law-enforcement bodies, courts, regulators, or other authorities when authorized, requested through proper process, or required by law.</p>
        <p>AmoraCare does not permit users to sell personal information or use it for unrelated advertising.</p>
    </section>

    <section id="ai-processing">
        <h2>6. AI-assisted features</h2>
        <p>When an external AI service is enabled, a user&rsquo;s legal-guidance question and the minimum context needed for a response may be transmitted to the configured service provider. Users should avoid placing names, document numbers, medical details, case identifiers, or other sensitive information in free-text questions.</p>
        <p>AI guidance and matching recommendations do not make final adoption decisions. Authorized professionals remain responsible for reviewing the available information and making or referring decisions through the proper official process.</p>
    </section>

    <section id="retention">
        <h2>7. Retention and disposal</h2>
        <p>Records are retained only for as long as reasonably necessary for the stated purpose, authorized orphanage operations, case continuity, accountability, dispute resolution, security, and applicable legal or official requirements. When retention is no longer justified, records should be securely deleted, anonymized, archived, or otherwise disposed of under an approved retention policy.</p>
    </section>

    <section id="security">
        <h2>8. Security measures</h2>
        <p>AmoraCare uses measures such as authenticated access, role-based permissions, email verification, protected credentials, limited reviewer access, session controls, and activity records. Authorized users must also protect devices and credentials, sign out from shared devices, and promptly report loss, misuse, or suspected unauthorized access.</p>
        <p>No information system is completely risk-free. If a privacy or security concern is confirmed, the organization will respond in accordance with its incident procedures and applicable requirements.</p>
    </section>

    <section id="rights">
        <h2>9. Your data-privacy rights</h2>
        <p>Subject to the conditions and exceptions provided by law, a data subject may have the right to be informed, object, access information, correct inaccurate data, request erasure or blocking, obtain data portability where applicable, claim damages, and lodge a complaint with the National Privacy Commission.</p>
        <p>Requests should be submitted to AMOR Village Orphanage or its designated privacy contact through an official channel. Identity and authority may need to be verified before a request is fulfilled, particularly when child or adoption records are involved.</p>
        <p>More information is available from the <a href="https://privacy.gov.ph/data-subject-rights/" target="_blank" rel="noopener noreferrer">National Privacy Commission&rsquo;s guide to data-subject rights</a>.</p>
    </section>

    <section id="sessions">
        <h2>10. Sessions and essential browser storage</h2>
        <p>AmoraCare uses session cookies or similar essential browser storage to keep users signed in, protect forms, preserve limited workflow state, and support security. Disabling essential storage may prevent login or other protected functions from working correctly. AmoraCare does not use this essential storage for unrelated behavioral advertising.</p>
    </section>

    <section id="children">
        <h2>11. Children&rsquo;s information</h2>
        <p>The public application form is intended for prospective adoptive parents, not for children to complete. Child information must be entered and handled only by authorized personnel through protected workflows. The child&rsquo;s dignity, safety, welfare, confidentiality, and best interests must remain the primary considerations.</p>
    </section>

    <section id="updates-contact">
        <h2>12. Updates, questions, and complaints</h2>
        <p>This notice may be updated to reflect changes in system features, organizational practices, or applicable requirements. The effective date will be revised when an update is published.</p>
        <p>For a privacy request, question, or complaint, contact AMOR Village Orphanage or the designated AmoraCare privacy contact using the official contact channel provided to you. You may also contact the <a href="https://privacy.gov.ph/" target="_blank" rel="noopener noreferrer">National Privacy Commission</a>.</p>
        <p class="legal-source-note">This notice should be completed with the organization&rsquo;s official privacy contact, retention schedule, hosting details, and approved service-provider disclosures before production deployment.</p>
    </section>
@endsection
