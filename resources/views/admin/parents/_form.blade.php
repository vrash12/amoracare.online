@php
    $isEdit = $parent->exists;
@endphp

<div class="parents-form-grid">
    <div class="parents-form-section">
        <h3>Account Details</h3>

        <div class="parents-field">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $parent->name) }}"
                required
            >
            @error('name')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div class="parents-field">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $parent->email) }}"
                required
            >
            @error('email')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div class="parents-field">
            <label for="phone_number">Phone Number</label>
            <input
                type="text"
                id="phone_number"
                name="phone_number"
                value="{{ old('phone_number', $parent->phone_number) }}"
                placeholder="Example: 09171234567"
            >
            @error('phone_number')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div class="parents-field">
            <label for="status">Account Status</label>
            <select id="status" name="status" required>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $parent->status ?? 'active') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div class="parents-field">
            <label for="password">
                Password
                @if($isEdit)
                    <span class="muted">(Leave blank to keep current password)</span>
                @endif
            </label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                maxlength="255"
                autocomplete="new-password"
                aria-describedby="password_help password_status"
                @if(!$isEdit) required @endif
            >
            <div class="parents-password-help" id="password_help">
                Use at least 8 characters, or generate a secure 16-character password.
            </div>
            <div class="parents-password-actions">
                <button type="button" class="btn light" id="generate_parent_password">
                    <i class="bi bi-stars"></i>
                    Generate Password
                </button>
                <button type="button" class="btn light" id="toggle_parent_password" aria-pressed="false">
                    <i class="bi bi-eye"></i>
                    <span>Show Password</span>
                </button>
            </div>
            @error('password')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div class="parents-field">
            <label for="password_confirmation">Confirm Password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                minlength="8"
                maxlength="255"
                autocomplete="new-password"
                aria-describedby="password_status"
                @if(!$isEdit) required @endif
            >
            <div class="parents-password-status" id="password_status" aria-live="polite"></div>
        </div>
    </div>

    <div class="parents-form-section">
        <h3>Matching Preferences</h3>

        <div class="parents-field">
            <label for="preferred_child_sex">Preferred Child Sex</label>
            <select id="preferred_child_sex" name="preferred_child_sex" required>
                <option value="any" @selected(old('preferred_child_sex', $profile->preferred_child_sex ?? 'any') === 'any')>Any</option>
                <option value="male" @selected(old('preferred_child_sex', $profile->preferred_child_sex ?? 'any') === 'male')>Male</option>
                <option value="female" @selected(old('preferred_child_sex', $profile->preferred_child_sex ?? 'any') === 'female')>Female</option>
            </select>
            @error('preferred_child_sex')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <br>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="parents-field">
                <label for="min_child_age">Minimum Child Age</label>
                <input
                    type="number"
                    id="min_child_age"
                    name="min_child_age"
                    min="0"
                    max="18"
                    value="{{ old('min_child_age', $profile->min_child_age ?? 0) }}"
                >
                @error('min_child_age')
                    <div class="parents-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="parents-field">
                <label for="max_child_age">Maximum Child Age</label>
                <input
                    type="number"
                    id="max_child_age"
                    name="max_child_age"
                    min="0"
                    max="18"
                    value="{{ old('max_child_age', $profile->max_child_age ?? 10) }}"
                >
                @error('max_child_age')
                    <div class="parents-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <br>

        <div class="parents-switch-row">
            <div>
                <strong>Open to Special Needs</strong>
                <div class="muted">Allow this parent to match with children marked as special needs.</div>
            </div>

            <input type="hidden" name="open_to_special_needs" value="0">
            <input
                type="checkbox"
                name="open_to_special_needs"
                value="1"
                @checked((bool) old('open_to_special_needs', $profile->open_to_special_needs ?? false))
            >
        </div>

        <br>

        <div class="parents-switch-row">
            <div>
                <strong>Home Study Verified</strong>
                <div class="muted">Mark if the parent has verified home study readiness.</div>
            </div>

            <input type="hidden" name="home_study_verified" value="0">
            <input
                type="checkbox"
                name="home_study_verified"
                value="1"
                @checked((bool) old('home_study_verified', $profile->home_study_verified ?? false))
            >
        </div>
    </div>
</div>

<br>

<div class="parents-form-section">
    <h3>Assessment Scores</h3>
    <p class="muted">Scores range from 0 to 100; a higher score means better assessment performance and results.</p>

    <div class="parents-score-grid">
        <div class="parents-field">
            <label for="financial_capacity_score">Financial Capacity Score</label>
            <input
                type="number"
                id="financial_capacity_score"
                name="financial_capacity_score"
                min="0"
                max="100"
                value="{{ old('financial_capacity_score', $profile->financial_capacity_score ?? 80) }}"
                required
            >
            @error('financial_capacity_score')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="parents-field">
            <label for="housing_score">Housing Readiness Score</label>
            <input
                type="number"
                id="housing_score"
                name="housing_score"
                min="0"
                max="100"
                value="{{ old('housing_score', $profile->housing_score ?? 80) }}"
                required
            >
            @error('housing_score')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="parents-field">
            <label for="parenting_capacity_score">Parenting Capacity Score</label>
            <input
                type="number"
                id="parenting_capacity_score"
                name="parenting_capacity_score"
                min="0"
                max="100"
                value="{{ old('parenting_capacity_score', $profile->parenting_capacity_score ?? 80) }}"
                required
            >
            @error('parenting_capacity_score')
                <div class="parents-error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <br>

    <div class="parents-field">
        <label for="matching_notes">Matching Notes</label>
        <textarea
            id="matching_notes"
            name="matching_notes"
            placeholder="Add notes about parent readiness, preferences, or matching considerations."
        >{{ old('matching_notes', $profile->matching_notes ?? '') }}</textarea>
        @error('matching_notes')
            <div class="parents-error">{{ $message }}</div>
        @enderror
    </div>
</div>

<br>

<div class="parents-actions">
    <button type="submit" class="btn secondary">
        <i class="bi bi-save"></i>
        {{ $buttonLabel ?? 'Save Parent' }}
    </button>

    <a href="{{ route('admin.parents.index') }}" class="btn light">
        Cancel
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        const generateButton = document.getElementById('generate_parent_password');
        const toggleButton = document.getElementById('toggle_parent_password');
        const status = document.getElementById('password_status');

        if (!password || !confirmation || !generateButton || !toggleButton || !status) {
            return;
        }

        function randomIndex(limit) {
            const maximum = Math.floor(0x100000000 / limit) * limit;
            const value = new Uint32Array(1);

            do {
                window.crypto.getRandomValues(value);
            } while (value[0] >= maximum);

            return value[0] % limit;
        }

        function randomCharacter(characters) {
            return characters[randomIndex(characters.length)];
        }

        function generatePassword() {
            const groups = [
                'ABCDEFGHJKLMNPQRSTUVWXYZ',
                'abcdefghijkmnopqrstuvwxyz',
                '23456789',
                '!@#$%^&*_-+=?'
            ];
            const allCharacters = groups.join('');
            const characters = groups.map(randomCharacter);

            while (characters.length < 16) {
                characters.push(randomCharacter(allCharacters));
            }

            for (let index = characters.length - 1; index > 0; index -= 1) {
                const swapIndex = randomIndex(index + 1);
                [characters[index], characters[swapIndex]] = [characters[swapIndex], characters[index]];
            }

            return characters.join('');
        }

        function setPasswordVisibility(isVisible) {
            const inputType = isVisible ? 'text' : 'password';
            password.type = inputType;
            confirmation.type = inputType;
            toggleButton.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
            toggleButton.querySelector('i').className = isVisible ? 'bi bi-eye-slash' : 'bi bi-eye';
            toggleButton.querySelector('span').textContent = isVisible ? 'Hide Password' : 'Show Password';
        }

        function validateConfirmation() {
            status.classList.remove('is-success', 'is-error');
            confirmation.setCustomValidity('');

            if (!password.value && !confirmation.value) {
                status.textContent = '';
                return;
            }

            if (password.value !== confirmation.value) {
                confirmation.setCustomValidity('Passwords do not match.');
                status.textContent = 'Passwords do not match.';
                status.classList.add('is-error');
                return;
            }

            status.textContent = 'Passwords match.';
            status.classList.add('is-success');
        }

        generateButton.addEventListener('click', function () {
            if (!window.crypto || typeof window.crypto.getRandomValues !== 'function') {
                status.textContent = 'Secure password generation is not supported by this browser.';
                status.classList.add('is-error');
                return;
            }

            const generatedPassword = generatePassword();
            password.value = generatedPassword;
            confirmation.value = generatedPassword;
            setPasswordVisibility(true);
            validateConfirmation();
            password.focus();
            password.select();
        });

        toggleButton.addEventListener('click', function () {
            setPasswordVisibility(password.type === 'password');
        });

        password.addEventListener('input', validateConfirmation);
        confirmation.addEventListener('input', validateConfirmation);
    });
</script>
