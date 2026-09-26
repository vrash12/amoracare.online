@if(isset($user) && ! $user->first_name)
    <p class="form-group full">Existing name: <strong>{{ $user->name }}</strong>. Please confirm its parts below. Existing names are not split automatically.</p>
@endif
<div class="form-group">
    <label for="first_name">First Name <span class="required-marker" aria-hidden="true">*</span></label>
    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" maxlength="70" autocomplete="given-name" autocapitalize="words" data-auto-capitalize="words" required>
    @error('first_name')<small class="form-error">{{ $message }}</small>@enderror
</div>
<div class="form-group">
    <label for="middle_name">Middle Name <span class="field-optional">(optional)</span></label>
    <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $user->middle_name ?? '') }}" maxlength="70" autocomplete="additional-name" autocapitalize="words" data-auto-capitalize="words">
    @error('middle_name')<small class="form-error">{{ $message }}</small>@enderror
</div>
<div class="form-group">
    <label for="last_name">Last Name <span class="required-marker" aria-hidden="true">*</span></label>
    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" maxlength="70" autocomplete="family-name" autocapitalize="words" data-auto-capitalize="words" required>
    @error('last_name')<small class="form-error">{{ $message }}</small>@enderror
</div>
<div class="form-group">
    <label for="name_extension">Name Extension <span class="field-optional">(optional)</span></label>
    <select name="name_extension" id="name_extension" autocomplete="honorific-suffix">
        <option value="">None</option>
        @foreach(\App\Support\PersonName::EXTENSIONS as $extension)
            <option value="{{ $extension }}" @selected(old('name_extension', $user->name_extension ?? '') === $extension)>{{ $extension }}</option>
        @endforeach
    </select>
    @error('name_extension')<small class="form-error">{{ $message }}</small>@enderror
</div>
@error('name')<small class="form-error form-group full">{{ $message }}</small>@enderror
