@extends('layouts.legal')

@section('title', 'Terms and Conditions')
@section('description', 'Terms and conditions governing access to and use of the AmoraCare system.')
@section('heading', 'Terms and Conditions')
@section('summary', 'These terms explain the permitted use of AmoraCare, the responsibilities of each user, and the limits of the platform.')

@section('content')
    <div class="legal-callout">
        <strong>Please read these terms before creating or using an account.</strong>
        <p>By creating an account, submitting an application, or continuing to use AmoraCare, you confirm that you understand and agree to these Terms and Conditions and acknowledge the Privacy Notice.</p>
    </div>

    <section id="purpose">
        <h2>1. Purpose and scope</h2>
        <p>AmoraCare is a web-based support system used by AMOR Village Orphanage for adoption-related assistance, document and case workflows, prospective parent applications, authorized external review, donation records, and informational legal guidance.</p>
        <p>AmoraCare is not the National Authority for Child Care (NACC), a Regional Alternative Child Care Office (RACCO), a court, or another government decision-making body. Using the platform, submitting information, or receiving a system recommendation does not guarantee application approval, child placement, or an adoption order.</p>
    </section>

    <section id="accounts">
        <h2>2. Account eligibility and security</h2>
        <ul>
            <li>You must provide accurate, current, and complete information and use an email address that you are authorized to access.</li>
            <li>You must complete the required email verification before registration or login is completed.</li>
            <li>You are responsible for protecting your password and one-time verification codes and for all authorized activity performed through your account.</li>
            <li>You must not share credentials, impersonate another person, or attempt to use a role or case access that has not been assigned to you.</li>
            <li>You should sign out after using a shared device and immediately report suspected unauthorized access to an AmoraCare administrator.</li>
        </ul>
    </section>

    <section id="acceptable-use">
        <h2>3. Acceptable use and confidentiality</h2>
        <p>You may use AmoraCare only for legitimate adoption-assistance, case-review, administrative, or donation-record purposes permitted by your assigned role. You must protect confidential child, family, applicant, donor, and case information.</p>
        <p>You must not:</p>
        <ul>
            <li>access, copy, download, photograph, disclose, or distribute records without authorization;</li>
            <li>upload malicious files, interfere with security controls, probe the system for vulnerabilities, or disrupt its operation;</li>
            <li>submit information that is knowingly false, misleading, unlawful, infringing, or unrelated to an authorized workflow; or</li>
            <li>use AI guidance, matching information, or other system output to harass, discriminate against, exploit, or endanger any person.</li>
        </ul>
    </section>

    <section id="adoption-support">
        <h2>4. Adoption assistance and official decisions</h2>
        <p>The system helps organize preliminary applications, required documents, status updates, notes, and authorized reviews. It does not replace counseling, a home study, professional assessment, statutory documentary requirements, or the official processes of NACC, RACCO, and other competent authorities.</p>
        <p>Users remain responsible for following instructions issued by authorized social workers and government agencies. Where system information differs from an official instruction, the official instruction controls.</p>
    </section>

    <section id="ai-guidance">
        <h2>5. AI legal guidance</h2>
        <p>The AI legal-guidance feature provides general, informational support based on the references available to the system. It may be incomplete, outdated, or inaccurate and does not create a lawyer-client relationship or replace advice from a qualified lawyer, social worker, NACC, RACCO, or another authorized professional.</p>
        <p>Do not include a child&rsquo;s name, case number, medical information, or other identifying or sensitive information in an AI question unless AmoraCare expressly requests it through an authorized workflow. Important decisions must be verified with an appropriate professional or official source.</p>
    </section>

    <section id="matching">
        <h2>6. Parent&ndash;child matching recommendations</h2>
        <p>Matching results are decision-support recommendations generated from available profile data and defined criteria. They are not approvals or final placements. Qualified personnel must independently review every recommendation, apply professional judgment, and give primary consideration to the child&rsquo;s rights, safety, welfare, and best interests.</p>
    </section>

    <section id="documents">
        <h2>7. Submitted information and documents</h2>
        <p>You confirm that you are authorized to submit the information and documents you provide and that they are accurate to the best of your knowledge. Files remain subject to staff or reviewer assessment and may be returned, rejected, or require replacement. A submission shown as received or verified in AmoraCare does not by itself establish legal validity or government acceptance.</p>
    </section>

    <section id="donations">
        <h2>8. Donation records</h2>
        <p>The donation module records information entered by authorized personnel. It does not independently verify bank records, external accounting statements, ownership, valuation, or tax treatment. Official acknowledgments, refunds, and accounting corrections remain subject to AMOR Village policies and applicable requirements.</p>
    </section>

    <section id="availability">
        <h2>9. Availability and security limitations</h2>
        <p>AmoraCare uses reasonable safeguards appropriate to the system, but no online service can guarantee uninterrupted availability, error-free operation, or absolute security. Access may be temporarily limited for maintenance, security response, connectivity problems, or circumstances beyond the organization&rsquo;s control.</p>
    </section>

    <section id="account-action">
        <h2>10. Account restriction and deactivation</h2>
        <p>AmoraCare may restrict, suspend, or deactivate an account when required to protect users or records, respond to unauthorized activity, enforce these terms, comply with an official instruction, or manage accounts that have been inactive for the configured period. An authorized administrator may review reactivation requests.</p>
    </section>

    <section id="changes">
        <h2>11. Changes to these terms</h2>
        <p>These terms may be updated when system functions, organizational procedures, or applicable requirements change. The effective date will be revised, and users may be asked to review and accept a material update before continuing to use affected services.</p>
    </section>

    <section id="contact">
        <h2>12. Questions and concerns</h2>
        <p>For questions about these terms, your account, or an authorized case workflow, contact AMOR Village Orphanage or the designated AmoraCare administrator using the official contact channel provided to you.</p>
        <p class="legal-source-note">These terms should be reviewed by the organization and qualified legal or privacy personnel before production deployment.</p>
    </section>
@endsection
