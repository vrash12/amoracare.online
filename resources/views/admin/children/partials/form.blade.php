@php
    $child = $child ?? null;
@endphp

<div class="child-form">

    {{-- Basic Information --}}
    <div class="child-form-section">
        <div class="child-form-section-header">
            <div class="section-icon">
                <i class="bi bi-person-vcard"></i>
            </div>

            <div>
                <h3>Basic Information</h3>
                <p>Enter the child’s identifying information for internal case records.</p>
            </div>
        </div>

        <div class="child-form-grid">
            <div class="form-field">
                <label for="child_code">
                    Child Code <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="child_code"
                    name="child_code"
                    value="{{ old('child_code', $child?->child_code) }}"
                    required
                    placeholder="Example: CH-2026-0001"
                >

                @error('child_code')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="nickname">Nickname</label>

                <input
                    type="text"
                    id="nickname"
                    name="nickname"
                    value="{{ old('nickname', $child?->nickname) }}"
                    placeholder="Optional nickname"
                >

                @error('nickname')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="first_name">
                    First Name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="{{ old('first_name', $child?->first_name) }}"
                    required
                    placeholder="Enter first name"
                >

                @error('first_name')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="middle_name">Middle Name</label>

                <input
                    type="text"
                    id="middle_name"
                    name="middle_name"
                    value="{{ old('middle_name', $child?->middle_name) }}"
                    placeholder="Enter middle name"
                >

                @error('middle_name')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="last_name">
                    Last Name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="{{ old('last_name', $child?->last_name) }}"
                    required
                    placeholder="Enter last name"
                >

                @error('last_name')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="sex">
                    Sex <span class="required">*</span>
                </label>

                <select id="sex" name="sex" required>
                    <option value="">Select Sex</option>
                    <option value="male" @selected(old('sex', $child?->sex) === 'male')>
                        Male
                    </option>
                    <option value="female" @selected(old('sex', $child?->sex) === 'female')>
                        Female
                    </option>
                </select>

                @error('sex')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="date_of_birth">Date of Birth</label>

                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth', $child?->date_of_birth?->format('Y-m-d')) }}"
                >

                @error('date_of_birth')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="place_of_birth">Place of Birth</label>

                <input
                    type="text"
                    id="place_of_birth"
                    name="place_of_birth"
                    value="{{ old('place_of_birth', $child?->place_of_birth) }}"
                    placeholder="Enter place of birth"
                >

                @error('place_of_birth')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Profile Photo --}}
    <div class="child-form-section">
        <div class="child-form-section-header">
            <div class="section-icon">
                <i class="bi bi-image"></i>
            </div>

            <div>
                <h3>Profile Photo</h3>
                <p>Upload an internal profile photo. Accepted formats: JPG, PNG, WEBP.</p>
            </div>
        </div>

        <div class="photo-upload-card">
            <div class="photo-preview">
                @if($child?->photo_path)
                    <img src="{{ asset('storage/' . $child->photo_path) }}" alt="Child photo">
                @else
                    <div class="photo-placeholder">
                        <i class="bi bi-person"></i>
                    </div>
                @endif
            </div>

            <div class="photo-upload-content">
                <label for="photo" class="photo-upload-button">
                    <i class="bi bi-cloud-arrow-up"></i>
                    Choose Photo
                </label>

                <input
                    type="file"
                    id="photo"
                    name="photo"
                    accept="image/jpeg,image/png,image/webp"
                    class="file-input-hidden"
                >

                <p class="helper-text">
                    Maximum file size: 2MB. Use a clear photo for staff identification only.
                </p>

                @if($child?->photo_path)
                    <label class="checkbox-row danger-checkbox">
                        <input type="checkbox" name="remove_photo" value="1">
                        <span>Remove current photo</span>
                    </label>
                @endif

                @error('photo')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Admission and Case Information --}}
    <div class="child-form-section">
        <div class="child-form-section-header">
            <div class="section-icon">
                <i class="bi bi-folder2-open"></i>
            </div>

            <div>
                <h3>Admission and Case Information</h3>
                <p>Track the child’s current case stage, location, and adoption eligibility.</p>
            </div>
        </div>

        <div class="child-form-grid">
            <div class="form-field">
                <label for="current_location">Current Location</label>

                <input
                    type="text"
                    id="current_location"
                    name="current_location"
                    value="{{ old('current_location', $child?->current_location) }}"
                    placeholder="Example: Amor Village Orphanage"
                >

                @error('current_location')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="admission_date">Admission Date</label>

                <input
                    type="date"
                    id="admission_date"
                    name="admission_date"
                    value="{{ old('admission_date', $child?->admission_date?->format('Y-m-d')) }}"
                >

                @error('admission_date')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="admission_reason">Admission Reason</label>

                <input
                    type="text"
                    id="admission_reason"
                    name="admission_reason"
                    value="{{ old('admission_reason', $child?->admission_reason) }}"
                    placeholder="Reason for admission"
                >

                @error('admission_reason')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="case_status">
                    Case Status <span class="required">*</span>
                </label>

                <select id="case_status" name="case_status" required>
                    @foreach($caseStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('case_status', $child?->case_status ?? 'in_care') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('case_status')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="adoption_eligibility_status">
                    Adoption Eligibility <span class="required">*</span>
                </label>

                <select id="adoption_eligibility_status" name="adoption_eligibility_status" required>
                    @foreach($eligibilityStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('adoption_eligibility_status', $child?->adoption_eligibility_status ?? 'not_assessed') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('adoption_eligibility_status')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field checkbox-field">
                <input type="hidden" name="is_special_needs" value="0">

                <label class="checkbox-row">
                    <input
                        type="checkbox"
                        name="is_special_needs"
                        value="1"
                        @checked(old('is_special_needs', $child?->is_special_needs))
                    >

                    <span>Child has special needs</span>
                </label>

                @error('is_special_needs')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Education --}}
    <div class="child-form-section">
        <div class="child-form-section-header">
            <div class="section-icon">
                <i class="bi bi-mortarboard"></i>
            </div>

            <div>
                <h3>Education</h3>
                <p>Record the child’s current education level and school information.</p>
            </div>
        </div>

        <div class="child-form-grid">
            <div class="form-field">
                <label for="educational_level">Educational Level</label>

                <input
                    type="text"
                    id="educational_level"
                    name="educational_level"
                    value="{{ old('educational_level', $child?->educational_level) }}"
                    placeholder="Example: Grade 3, Kinder, Not yet enrolled"
                >

                @error('educational_level')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label for="school_name">School Name</label>

                <input
                    type="text"
                    id="school_name"
                    name="school_name"
                    value="{{ old('school_name', $child?->school_name) }}"
                    placeholder="Enter school name"
                >

                @error('school_name')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Notes and Background --}}
    <div class="child-form-section">
        <div class="child-form-section-header">
            <div class="section-icon">
                <i class="bi bi-journal-text"></i>
            </div>

            <div>
                <h3>Health, Background, and Remarks</h3>
                <p>Keep this information factual, respectful, and limited to authorized case use.</p>
            </div>
        </div>

        <div class="child-form-grid">
            <div class="form-field full">
                <label for="health_status">Health Status</label>

                <textarea
                    id="health_status"
                    name="health_status"
                    rows="4"
                    placeholder="Record relevant health notes, medical observations, or care requirements."
                >{{ old('health_status', $child?->health_status) }}</textarea>

                @error('health_status')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field full">
                <label for="background_summary">Background Summary</label>

                <textarea
                    id="background_summary"
                    name="background_summary"
                    rows="5"
                    placeholder="Summarize background information relevant to authorized case management."
                >{{ old('background_summary', $child?->background_summary) }}</textarea>

                @error('background_summary')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field full">
                <label for="remarks">Remarks</label>

                <textarea
                    id="remarks"
                    name="remarks"
                    rows="4"
                    placeholder="Add internal remarks for authorized staff."
                >{{ old('remarks', $child?->remarks) }}</textarea>

                @error('remarks')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>