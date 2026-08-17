@php
    $donation = $donation ?? null;
    $donor = $donation?->donor;

    $selectedDonationType = old('donation.donation_type', $donation?->donation_type ?? 'cash');

    $items = old(
        'items',
        $donation?->items?->values()->map(fn ($item) => $item->toArray())->toArray() ?? [[]]
    );

    if (empty($items)) {
        $items = [[]];
    }
@endphp

<style>
    .simple-donation-form {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    .form-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        padding: 24px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .form-card-header {
        margin-bottom: 20px;
    }

    .form-card-header h3 {
        margin: 0;
        font-size: 20px;
        color: #111827;
        font-weight: 900;
    }

    .form-card-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .donation-type-options {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .type-option {
        position: relative;
        cursor: pointer;
    }

    .type-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .type-box {
        height: 100%;
        border: 2px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .type-box:hover {
        border-color: #f9a8d4;
        transform: translateY(-2px);
    }

    .type-option input:checked + .type-box {
        border-color: #ec4899;
        background: #fdf2f8;
        box-shadow: 0 12px 26px rgba(236, 72, 153, 0.16);
    }

    .type-box i {
        font-size: 28px;
        color: #be185d;
        display: block;
        margin-bottom: 10px;
    }

    .type-box strong {
        display: block;
        color: #111827;
        font-size: 15px;
    }

    .type-box span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-top: 4px;
        line-height: 1.4;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .form-field.full {
        grid-column: 1 / -1;
    }

    .form-field[hidden] {
        display: none !important;
    }

    .form-field label {
        font-size: 13px;
        color: #374151;
        font-weight: 800;
    }

    .required {
        color: #dc2626;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 13px;
        padding: 12px 14px;
        font-size: 14px;
        color: #111827;
        background: #ffffff;
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        outline: none;
        border-color: #ec4899;
        box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.12);
    }

    .form-field textarea {
        min-height: 84px;
        resize: vertical;
    }

    .error-text {
        color: #dc2626;
        font-size: 12px;
        font-weight: 700;
    }

    .conditional-section[hidden] {
        display: none !important;
    }

    .item-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .item-card {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        background: #f9fafb;
    }

    .item-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        gap: 10px;
    }

    .item-card-top strong {
        color: #111827;
    }

    .remove-item-btn {
        border: none;
        background: #fee2e2;
        color: #b91c1c;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
    }

    .remove-item-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .add-item-btn {
        margin-top: 14px;
        border: none;
        background: #111827;
        color: #ffffff;
        border-radius: 999px;
        padding: 11px 16px;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .add-item-btn:hover {
        background: #374151;
    }

    .summary-box {
        background: #111827;
        color: #ffffff;
        border-radius: 20px;
        padding: 20px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .summary-item span {
        display: block;
        color: #d1d5db;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .summary-item strong {
        display: block;
        font-size: 18px;
    }

    @media (max-width: 900px) {
        .donation-type-options,
        .summary-box {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .form-card {
            padding: 20px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .item-card-top {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="simple-donation-form">
    <div class="summary-box">
        <div class="summary-item">
            <span>Selected Type</span>
            <strong id="summaryType">Cash</strong>
        </div>

        <div class="summary-item">
            <span>Cash Amount</span>
            <strong>₱<span id="summaryCash">0.00</span></strong>
        </div>

        <div class="summary-item">
            <span>Material Items</span>
            <strong><span id="summaryItems">0</span> item/s</strong>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <h3>Donation Type</h3>
            <p>Choose the type of donation. The needed fields will appear automatically.</p>
        </div>

        <div class="donation-type-options">
            <label class="type-option">
                <input
                    type="radio"
                    name="donation[donation_type]"
                    value="cash"
                    data-label="Cash"
                    @checked($selectedDonationType === 'cash')
                >
                <div class="type-box">
                    <i class="bi bi-cash-coin"></i>
                    <strong>Cash</strong>
                    <span>Money, GCash, bank transfer, or check.</span>
                </div>
            </label>

            <label class="type-option">
                <input
                    type="radio"
                    name="donation[donation_type]"
                    value="in_kind"
                    data-label="Material"
                    @checked($selectedDonationType === 'in_kind')
                >
                <div class="type-box">
                    <i class="bi bi-box-seam"></i>
                    <strong>Material</strong>
                    <span>Food, clothes, medicine, supplies, or items.</span>
                </div>
            </label>

            <label class="type-option">
                <input
                    type="radio"
                    name="donation[donation_type]"
                    value="mixed"
                    data-label="Mixed"
                    @checked($selectedDonationType === 'mixed')
                >
                <div class="type-box">
                    <i class="bi bi-gift"></i>
                    <strong>Mixed</strong>
                    <span>Cash and material items together.</span>
                </div>
            </label>
        </div>

        @error('donation.donation_type')
            <div class="error-text" style="margin-top: 12px;">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <h3>Donor Details</h3>
            <p>Only basic contact information is needed.</p>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label>Donor Name <span class="required">*</span></label>
                <input
                    type="text"
                    name="donor[name]"
                    value="{{ old('donor.name', $donor?->name) }}"
                    placeholder="Enter donor name"
                    required
                >
                @error('donor.name')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label>Phone Number</label>
                <input
                    type="text"
                    name="donor[phone_number]"
                    value="{{ old('donor.phone_number', $donor?->phone_number) }}"
                    placeholder="Numbers only (optional)"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="30"
                    title="Enter numbers only."
                    data-numeric-only
                >
                @error('donor.phone_number')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field full">
                <label>Email</label>
                <input
                    type="email"
                    name="donor[email]"
                    value="{{ old('donor.email', $donor?->email) }}"
                    placeholder="Optional"
                >
                @error('donor.email')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <h3>Donation Details</h3>
            <p>Basic information for recordkeeping and reports.</p>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label>Donation Date <span class="required">*</span></label>
                <input
                    type="date"
                    name="donation[donation_date]"
                    value="{{ old('donation.donation_date', $donation?->donation_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                    required
                >
                @error('donation.donation_date')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            @php($selectedPurpose = old('donation.purpose', $donation?->purpose ?? 'general_support'))

            <div class="form-field">
                <label>Purpose <span class="required">*</span></label>
                <select name="donation[purpose]" id="donationPurpose" required>
                    @foreach($purposes as $value => $label)
                        <option value="{{ $value }}" @selected($selectedPurpose === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('donation.purpose')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field full" id="otherPurposeField" @if($selectedPurpose !== 'other') hidden @endif>
                <label>Specific Purpose <span class="required">*</span></label>
                <input
                    type="text"
                    name="donation[allocation_notes]"
                    value="{{ old('donation.allocation_notes', $donation?->allocation_notes) }}"
                    maxlength="255"
                    placeholder="Please specify the donation purpose"
                    @if($selectedPurpose !== 'other') disabled @else required @endif
                >
                @error('donation.allocation_notes')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field full">
                <label>Remarks</label>
                <textarea
                    name="donation[remarks]"
                    placeholder="Optional notes"
                >{{ old('donation.remarks', $donation?->remarks) }}</textarea>
                @error('donation.remarks')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="form-card conditional-section" data-section="cash">
        <div class="form-card-header">
            <h3>Cash Information</h3>
            <p>Fill this out for cash or mixed donations.</p>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label>Cash Amount <span class="required">*</span></label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="donation[cash_amount]"
                    value="{{ old('donation.cash_amount', $donation?->cash_amount) }}"
                    placeholder="0.00"
                    data-cash-field
                    data-cash-amount
                >
                @error('donation.cash_amount')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label>Payment Method</label>
                <select name="donation[payment_method]" data-cash-field>
                    <option value="">Select method</option>
                    @foreach($paymentMethods as $value => $label)
                        <option value="{{ $value }}" @selected(old('donation.payment_method', $donation?->payment_method) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('donation.payment_method')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="form-card conditional-section" data-section="material">
        <div class="form-card-header">
            <h3>Material Items</h3>
            <p>Add only the essential item details.</p>
        </div>

        <div class="item-list" id="itemList">
            @foreach($items as $i => $item)
                <div class="item-card" data-item-card>
                    <div class="item-card-top">
                        <strong>Item <span data-item-number>{{ $i + 1 }}</span></strong>

                        <button type="button" class="remove-item-btn" data-remove-item>
                            Remove
                        </button>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label>Item Name <span class="required">*</span></label>
                            <input
                                type="text"
                                name="items[{{ $i }}][item_name]"
                                value="{{ old("items.$i.item_name", $item['item_name'] ?? '') }}"
                                placeholder="Example: Rice"
                                data-material-field
                                data-item-name
                            >
                            @error("items.$i.item_name")
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label>Category</label>
                            <select name="items[{{ $i }}][item_category]" data-material-field>
                                @foreach($itemCategories as $value => $label)
                                    <option value="{{ $value }}" @selected(old("items.$i.item_category", $item['item_category'] ?? 'other') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error("items.$i.item_category")
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label>Quantity</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="items[{{ $i }}][quantity]"
                                value="{{ old("items.$i.quantity", $item['quantity'] ?? 1) }}"
                                data-material-field
                            >
                            @error("items.$i.quantity")
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label>Unit</label>
                            <input
                                type="text"
                                name="items[{{ $i }}][unit]"
                                value="{{ old("items.$i.unit", $item['unit'] ?? 'pcs') }}"
                                placeholder="pcs, box, kg"
                                data-material-field
                            >
                            @error("items.$i.unit")
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-field full">
                            <label>Estimated Unit Value</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="items[{{ $i }}][estimated_unit_value]"
                                value="{{ old("items.$i.estimated_unit_value", $item['estimated_unit_value'] ?? '') }}"
                                placeholder="Optional"
                                data-material-field
                            >
                            @error("items.$i.estimated_unit_value")
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button" class="add-item-btn" id="addItemBtn">
            <i class="bi bi-plus-circle"></i>
            Add Item
        </button>
    </div>
</div>

<template id="itemTemplate">
    <div class="item-card" data-item-card>
        <div class="item-card-top">
            <strong>Item <span data-item-number>__NUMBER__</span></strong>

            <button type="button" class="remove-item-btn" data-remove-item>
                Remove
            </button>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label>Item Name <span class="required">*</span></label>
                <input
                    type="text"
                    name="items[__INDEX__][item_name]"
                    placeholder="Example: Rice"
                    data-material-field
                    data-item-name
                >
            </div>

            <div class="form-field">
                <label>Category</label>
                <select name="items[__INDEX__][item_category]" data-material-field>
                    @foreach($itemCategories as $value => $label)
                        <option value="{{ $value }}" @selected($value === 'other')>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label>Quantity</label>
                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="items[__INDEX__][quantity]"
                    value="1"
                    data-material-field
                >
            </div>

            <div class="form-field">
                <label>Unit</label>
                <input
                    type="text"
                    name="items[__INDEX__][unit]"
                    value="pcs"
                    placeholder="pcs, box, kg"
                    data-material-field
                >
            </div>

            <div class="form-field full">
                <label>Estimated Unit Value</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="items[__INDEX__][estimated_unit_value]"
                    placeholder="Optional"
                    data-material-field
                >
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeRadios = document.querySelectorAll('input[name="donation[donation_type]"]');
        const cashSection = document.querySelector('[data-section="cash"]');
        const materialSection = document.querySelector('[data-section="material"]');
        const itemList = document.getElementById('itemList');
        const addItemBtn = document.getElementById('addItemBtn');
        const itemTemplate = document.getElementById('itemTemplate');
        const phoneInput = document.querySelector('[data-numeric-only]');
        const purposeSelect = document.getElementById('donationPurpose');
        const otherPurposeField = document.getElementById('otherPurposeField');

        const summaryType = document.getElementById('summaryType');
        const summaryCash = document.getElementById('summaryCash');
        const summaryItems = document.getElementById('summaryItems');

        function selectedType() {
            return document.querySelector('input[name="donation[donation_type]"]:checked')?.value || 'cash';
        }

        function selectedLabel() {
            return document.querySelector('input[name="donation[donation_type]"]:checked')?.dataset.label || 'Cash';
        }

        function updatePurposeField() {
            if (!purposeSelect || !otherPurposeField) {
                return;
            }

            const isOther = purposeSelect.value === 'other';
            const input = otherPurposeField.querySelector('input');

            otherPurposeField.hidden = !isOther;

            if (input) {
                input.disabled = !isOther;
                input.required = isOther;

                if (isOther && document.activeElement === purposeSelect) {
                    input.focus();
                }
            }
        }

        function enableSection(section, enabled) {
            if (!section) {
                return;
            }

            section.hidden = !enabled;

            section.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !enabled;
            });
        }

        function updateView() {
            const type = selectedType();
            const showCash = type === 'cash' || type === 'mixed';
            const showMaterial = type === 'in_kind' || type === 'mixed';

            enableSection(cashSection, showCash);
            enableSection(materialSection, showMaterial);

            document.querySelectorAll('[data-cash-amount]').forEach(function (field) {
                field.required = showCash;
            });

            document.querySelectorAll('[data-item-name]').forEach(function (field, index) {
                field.required = showMaterial && index === 0;
            });

            if (summaryType) {
                summaryType.textContent = selectedLabel();
            }

            updateSummary();
            updateItemButtons();
        }

        function updateSummary() {
            const type = selectedType();
            const cashInput = document.querySelector('[data-cash-amount]');
            const cashAmount = type === 'cash' || type === 'mixed'
                ? Number(cashInput?.value || 0)
                : 0;

            const itemCount = type === 'in_kind' || type === 'mixed'
                ? Array.from(document.querySelectorAll('[data-item-name]'))
                    .filter(input => input.value.trim() !== '')
                    .length
                : 0;

            if (summaryCash) {
                summaryCash.textContent = cashAmount.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            if (summaryItems) {
                summaryItems.textContent = itemCount;
            }
        }

        function updateItemNumbers() {
            document.querySelectorAll('[data-item-card]').forEach(function (card, index) {
                const number = card.querySelector('[data-item-number]');

                if (number) {
                    number.textContent = index + 1;
                }
            });
        }

        function updateItemButtons() {
            const cards = document.querySelectorAll('[data-item-card]');
            const onlyOne = cards.length <= 1;

            cards.forEach(function (card) {
                const button = card.querySelector('[data-remove-item]');

                if (button) {
                    button.disabled = onlyOne;
                    button.textContent = onlyOne ? 'Required' : 'Remove';
                }
            });
        }

        function addItem() {
            const index = Date.now();
            const number = document.querySelectorAll('[data-item-card]').length + 1;

            const html = itemTemplate.innerHTML
                .replaceAll('__INDEX__', index)
                .replaceAll('__NUMBER__', number);

            itemList.insertAdjacentHTML('beforeend', html);

            updateView();
            updateItemNumbers();
        }

        typeRadios.forEach(function (radio) {
            radio.addEventListener('change', updateView);
        });

        purposeSelect?.addEventListener('change', updatePurposeField);

        document.addEventListener('input', function (event) {
            if (
                event.target.matches('[data-cash-amount]') ||
                event.target.matches('[data-item-name]')
            ) {
                updateSummary();
            }
        });

        addItemBtn?.addEventListener('click', addItem);

        phoneInput?.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        itemList?.addEventListener('click', function (event) {
            const removeButton = event.target.closest('[data-remove-item]');

            if (!removeButton || removeButton.disabled) {
                return;
            }

            removeButton.closest('[data-item-card]')?.remove();

            updateItemNumbers();
            updateItemButtons();
            updateSummary();
        });

        updateView();
        updatePurposeField();
        updateItemNumbers();
        updateItemButtons();
    });
</script>
