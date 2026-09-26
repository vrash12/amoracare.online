@php
    $adoptionCase = $adoptionCase ?? null;
@endphp

<style>
    .case-form {
        display: grid;
        gap: 24px;
    }

    .case-form-card {
        background: #ffffff;
        border: 1px solid #eadfd8;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 14px 35px rgba(120, 53, 15, 0.08);
    }

    .case-form-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding: 24px 28px;
        background:
            linear-gradient(135deg, rgba(153, 70, 26, 0.10), rgba(255, 247, 237, 0.95)),
            #fff7ed;
        border-bottom: 1px solid #f1dfd4;
    }

    .case-form-title {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .case-form-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: #8f3518;
        color: #ffffff;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 10px 22px rgba(143, 53, 24, 0.22);
    }

    .case-form-header h3 {
        margin: 0;
        font-size: 20px;
        color: #3b2418;
        font-weight: 800;
    }

    .case-form-header p {
        margin: 6px 0 0;
        color: #7b6255;
        font-size: 14px;
        line-height: 1.5;
    }

    .case-form-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 13px;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid #ead6c8;
        color: #7c2d12;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .case-form-body {
        padding: 28px;
    }

    .case-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-field.full {
        grid-column: 1 / -1;
    }

    .form-field label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        color: #3b2418;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.01em;
    }

    .required {
        color: #b42318;
        font-weight: 900;
    }

    .optional-label {
        color: #9a8174;
        font-size: 11px;
        font-weight: 700;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
        width: 100%;
        border: 1px solid #decfc5;
        border-radius: 14px;
        background: #fffdfb;
        color: #352016;
        padding: 13px 14px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .form-field textarea {
        resize: vertical;
        min-height: 130px;
        line-height: 1.6;
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: #9a3f1f;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(154, 63, 31, 0.13);
    }

    .form-field input::placeholder,
    .form-field textarea::placeholder {
        color: #a99589;
    }

    .field-help {
        color: #8b7468;
        font-size: 12px;
        line-height: 1.5;
    }

    .error-text {
        color: #b42318;
        background: #fff1f1;
        border: 1px solid #ffd5d5;
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .case-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px 16px;
        border-radius: 16px;
        background: #fff8eb;
        border: 1px solid #f4dfb9;
        color: #6f4b1f;
        margin-bottom: 22px;
        font-size: 13px;
        line-height: 1.55;
    }

    .case-alert i {
        color: #9a5b13;
        font-size: 18px;
        margin-top: 1px;
    }

    .case-divider {
        height: 1px;
        background: #f0e2da;
        margin: 26px 0;
    }

    .case-subsection-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        color: #4a2b1d;
        font-size: 15px;
        font-weight: 900;
    }

    .case-subsection-title span {
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #f8eadf;
        color: #8f3518;
    }

    .case-form-actions-note {
        margin-top: 22px;
        padding: 14px 16px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        color: #64748b;
        font-size: 13px;
        line-height: 1.55;
    }

    @media (max-width: 900px) {
        .case-form-header {
            flex-direction: column;
        }

        .case-form-grid {
            grid-template-columns: 1fr;
        }

        .case-form-body,
        .case-form-header {
            padding: 22px;
        }

        .case-form-badge {
            white-space: normal;
        }
    }
</style>

<div class="case-form">
    @if ($errors->any())
        <div class="case-alert" style="background: #fff1f1; border-color: #ffd5d5; color: #991b1b;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Please check the form.</strong>
                <br>
                Some required information is missing or invalid.
            </div>
        </div>
    @endif

    <div class="case-form-card">
        <div class="case-form-header">
            <div class="case-form-title">
                <div class="case-form-icon">
                    <i class="bi bi-folder2-open"></i>
                </div>

                <div>
                    <h3>Adoption Case Information</h3>
                    <p>
                        Manage the case workflow, assigned parent, responsible staff, timeline, and internal case details.
                    </p>
                </div>
            </div>

            <div class="case-form-badge">
                <i class="bi bi-shield-check"></i>
                Authorized Staff Only
            </div>
        </div>

        <div class="case-form-body">
            <div class="case-alert">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    Select the correct prospective parent here. This connection allows the parent dashboard and AI guidance
                    module to read that parent’s own application status and document progress.
                </div>
            </div>

            <div class="case-subsection-title">
                <span><i class="bi bi-journal-text"></i></span>
                Basic Case Details
            </div>

            <div class="case-form-grid">
<div class="form-field">
    <label for="case_code_display">
        Case Code
        <span class="optional-label">Auto-generated</span>
    </label>

    <input
        type="text"
        id="case_code_display"
        value="{{ $adoptionCase?->case_code ?? 'Automatically generated after saving' }}"
        disabled
    >

    <div class="field-help">
        The system will automatically generate the case code when the adoption case is saved.
    </div>
</div>
                <div class="form-field">
                    <label for="child_id">
                        Child <span class="required">*</span>
                    </label>
                    <div id="childSearchControl" hidden>
                        <label for="childSearch">Search child by name or code</label>
                        <input type="search" id="childSearch" placeholder="Type a name or child code" autocomplete="off" aria-controls="child_id" aria-describedby="childSearchStatus">
                        <small id="childSearchStatus" role="status" aria-live="polite"></small>
                    </div>
                    <select id="child_id" name="child_id" required>
                        <option value="">Select child profile</option>
                        @foreach($children as $child)
                            <option
                                value="{{ $child->id }}"
                                @selected((string) old('child_id', $adoptionCase?->child_id) === (string) $child->id)
                            >
                                {{ $child->child_code }} — {{ $child->full_name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="field-help">Only authorized staff should manage child-linked case records.</div>
                    @error('child_id')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="case_type">
                        Case Type <span class="required">*</span>
                    </label>
                    <select id="case_type" name="case_type" required>
                        @foreach($caseTypes as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('case_type', $adoptionCase?->case_type ?? 'domestic_adoption') === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('case_type')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="priority">
                        Priority <span class="required">*</span>
                    </label>
                    <select id="priority" name="priority" required>
                        @foreach($priorities as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('priority', $adoptionCase?->priority ?? 'normal') === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="field-help">Use urgent only for cases that require immediate administrative attention.</div>
                    @error('priority')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="case-divider"></div>

            <div class="case-subsection-title">
                <span><i class="bi bi-people"></i></span>
                Parent and Staff Assignment
            </div>

            <div class="case-form-grid">
                <div class="form-field">
                    <label for="prospective_parent_id">
                        Prospective Adoptive Parent
                        <span class="optional-label">Optional</span>
                    </label>
                    <select id="prospective_parent_id" name="prospective_parent_id">
                        <option value="">Not assigned</option>
                        @foreach($prospectiveParents as $parent)
                            <option
                                value="{{ $parent->id }}"
                                @selected((string) old('prospective_parent_id', $adoptionCase?->prospective_parent_id) === (string) $parent->id)
                            >
                                {{ $parent->name }} — {{ $parent->email }}
                            </option>
                        @endforeach
                    </select>
                    <div class="field-help">
                        Assigning a parent here links this case to their parent dashboard and AI guidance.
                    </div>
                    @error('prospective_parent_id')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="assigned_social_worker_id">
                        Assigned Staff / Social Worker
                        <span class="optional-label">Optional</span>
                    </label>
                    <select id="assigned_social_worker_id" name="assigned_social_worker_id">
                        <option value="">Not assigned</option>
                        @foreach($staffUsers as $staff)
                            <option
                                value="{{ $staff->id }}"
                                @selected((string) old('assigned_social_worker_id', $adoptionCase?->assigned_social_worker_id) === (string) $staff->id)
                            >
                                {{ $staff->name }}{{ $staff->email ? ' — ' . $staff->email : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="field-help">
                        This staff member will appear as the assigned contact in parent-facing guidance.
                    </div>
                    @error('assigned_social_worker_id')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="case-divider"></div>

            <div class="case-subsection-title">
                <span><i class="bi bi-bar-chart-steps"></i></span>
                Workflow Status and Timeline
            </div>

            <div class="case-form-grid">
                <div class="form-field">
                    <label for="status">
                        Status <span class="required">*</span>
                    </label>
                    <select id="status" name="status" required>
                        @foreach($statuses as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('status', $adoptionCase?->status ?? 'draft') === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="field-help">
                        This controls the parent’s application timeline and progress view.
                    </div>
                    @error('status')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="opened_at">
                        Opened Date
                        <span class="optional-label">Optional</span>
                    </label>
                    <input
                        type="date"
                        id="opened_at"
                        name="opened_at"
                        value="{{ old('opened_at', $adoptionCase?->opened_at?->format('Y-m-d')) }}"
                    >
                    @error('opened_at')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="target_completion_date">
                        Target Completion Date
                        <span class="optional-label">Optional</span>
                    </label>
                    <input
                        type="date"
                        id="target_completion_date"
                        name="target_completion_date"
                        value="{{ old('target_completion_date', $adoptionCase?->target_completion_date?->format('Y-m-d')) }}"
                    >
                    <div class="field-help">
                        Must be the same as or later than the opened date.
                    </div>
                    @error('target_completion_date')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="closed_at">
                        Closed Date
                        <span class="optional-label">Optional</span>
                    </label>
                    <input
                        type="date"
                        id="closed_at"
                        name="closed_at"
                        value="{{ old('closed_at', $adoptionCase?->closed_at?->format('Y-m-d')) }}"
                    >
                    <div class="field-help">
                        Fill this only when the case has been closed, finalized, cancelled, or completed.
                    </div>
                    @error('closed_at')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="case-divider"></div>

            <div class="case-subsection-title">
                <span><i class="bi bi-file-earmark-text"></i></span>
                Case Notes
            </div>

            <div class="case-form-grid">
                <div class="form-field full">
                    <label for="summary">
                        Case Summary
                        <span class="optional-label">Visible to internal workflow</span>
                    </label>
                    <textarea
                        id="summary"
                        name="summary"
                        rows="5"
                        placeholder="Summarize the adoption case status, progress, and relevant workflow context."
                    >{{ old('summary', $adoptionCase?->summary) }}</textarea>
                    <div class="field-help">
                        Keep this factual and concise. Use parent updates separately when the parent should see an update.
                    </div>
                    @error('summary')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-field full">
                    <label for="confidential_notes">
                        Confidential Internal Notes
                        <span class="optional-label">Private</span>
                    </label>
                    <textarea
                        id="confidential_notes"
                        name="confidential_notes"
                        rows="5"
                        placeholder="Internal-only confidential notes. Do not expose this to parents or external reviewers."
                    >{{ old('confidential_notes', $adoptionCase?->confidential_notes) }}</textarea>
                    <div class="field-help">
                        These notes should stay internal and must not be used in parent-facing AI guidance.
                    </div>
                    @error('confidential_notes')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="case-form-actions-note">
                <strong>Reminder:</strong> Updating the case status may affect what parents see in their application timeline.
                Assigning the correct prospective parent is required for the AI guidance module to answer personal document
                and application-status questions.
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/child-search.js') }}" defer></script>
