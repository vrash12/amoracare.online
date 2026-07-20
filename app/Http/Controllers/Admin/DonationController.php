<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\DonationItem;
use App\Models\Donor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonationController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can access donation management.');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $search = $request->input('search');
        $type = $request->input('donation_type');
        $purpose = $request->input('purpose');
        $status = $request->input('status');

        $donations = Donation::with(['donor', 'items', 'encoder'])
            ->when($search, function ($query) use ($search) {
                $query->where('donation_code', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('donor', function ($donorQuery) use ($search) {
                        $donorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('donor_code', 'like', "%{$search}%");
                    });
            })
            ->when($type, fn ($query) => $query->where('donation_type', $type))
            ->when($purpose, fn ($query) => $query->where('purpose', $purpose))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.donations.index', [
            'donations' => $donations,
            'search' => $search,
            'type' => $type,
            'purpose' => $purpose,
            'status' => $status,
            'types' => Donation::TYPES,
            'purposes' => Donation::PURPOSES,
            'statuses' => Donation::STATUSES,
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.donations.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validateDonation($request);

        DB::transaction(function () use ($validated) {
            $donorData = $validated['donor'];
            $donorData['donor_code'] = $this->generateDonorCode();
            $donorData['donor_type'] = 'individual';

            $donor = Donor::create($donorData);

            $donationData = $validated['donation'];
            $donationData['donation_code'] = $this->generateDonationCode();
            $donationData['donor_id'] = $donor->id;
            $donationData['acknowledgment_status'] = 'pending';
            $donationData['status'] = 'recorded';
            $donationData['encoded_by'] = Auth::id();
            $donationData['updated_by'] = Auth::id();

            $donation = Donation::create($donationData);

            foreach ($validated['items'] ?? [] as $item) {
                if (empty($item['item_name'])) {
                    continue;
                }

                $item['estimated_total_value'] = $this->computeEstimatedTotal($item);
                $donation->items()->create($item);
            }
        });

        return redirect()
            ->route('admin.donations.index')
            ->with('success', 'Donation record created successfully.');
    }

    public function show(Donation $donation): View
    {
        $this->authorizeAdmin();

        $donation->load(['donor', 'items', 'encoder', 'updater']);

        return view('admin.donations.show', compact('donation'));
    }

    public function edit(Donation $donation): View
    {
        $this->authorizeAdmin();

        $donation->load(['donor', 'items']);

        return view('admin.donations.edit', [
            'donation' => $donation,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Donation $donation): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validateDonation($request, $donation);

        DB::transaction(function () use ($validated, $donation) {
            $donation->donor->update($validated['donor']);

            $donationData = $validated['donation'];
            $donationData['updated_by'] = Auth::id();

            if ($donationData['donation_type'] === 'in_kind') {
                $donationData['cash_amount'] = null;
                $donationData['payment_method'] = null;
                $donationData['reference_number'] = null;
            }

            $donation->update($donationData);

            $donation->items()->delete();

            foreach ($validated['items'] ?? [] as $item) {
                if (empty($item['item_name'])) {
                    continue;
                }

                $item['estimated_total_value'] = $this->computeEstimatedTotal($item);
                $donation->items()->create($item);
            }
        });

        return redirect()
            ->route('admin.donations.show', $donation)
            ->with('success', 'Donation record updated successfully.');
    }

    public function destroy(Donation $donation): RedirectResponse
    {
        $this->authorizeAdmin();

        $donation->update([
            'updated_by' => Auth::id(),
        ]);

        $donation->delete();

        return redirect()
            ->route('admin.donations.index')
            ->with('success', 'Donation record deleted successfully.');
    }

    private function validateDonation(Request $request, ?Donation $donation = null): array
    {
        $validated = $request->validate([
            'donor.name' => ['required', 'string', 'max:150'],
            'donor.email' => ['nullable', 'email', 'max:150'],
            'donor.phone_number' => ['nullable', 'string', 'max:30'],

            'donation.donation_type' => ['required', Rule::in(array_keys(Donation::TYPES))],
            'donation.donation_date' => ['required', 'date'],
            'donation.purpose' => ['required', Rule::in(array_keys(Donation::PURPOSES))],
            'donation.cash_amount' => ['nullable', 'numeric', 'min:0'],
            'donation.payment_method' => ['nullable', Rule::in(array_keys(Donation::PAYMENT_METHODS))],
            'donation.remarks' => ['nullable', 'string'],

            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['nullable', 'string', 'max:150'],
            'items.*.item_category' => ['nullable', Rule::in(array_keys(DonationItem::CATEGORIES))],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_unit_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $type = $validated['donation']['donation_type'];

        if (in_array($type, ['cash', 'mixed'], true) && empty($validated['donation']['cash_amount'])) {
            back()
                ->withErrors(['donation.cash_amount' => 'Cash amount is required for cash or mixed donations.'])
                ->withInput()
                ->throwResponse();
        }

        if (in_array($type, ['in_kind', 'mixed'], true)) {
            $hasItem = collect($validated['items'] ?? [])
                ->contains(fn ($item) => !empty($item['item_name']));

            if (!$hasItem) {
                back()
                    ->withErrors(['items.0.item_name' => 'Please add at least one material item.'])
                    ->withInput()
                    ->throwResponse();
            }
        }

        if ($type === 'cash') {
            $validated['items'] = [];
        }

        if ($type === 'in_kind') {
            $validated['donation']['cash_amount'] = null;
            $validated['donation']['payment_method'] = null;
            $validated['donation']['reference_number'] = null;
        }

        return $validated;
    }

    private function computeEstimatedTotal(array $item): ?float
    {
        $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0;
        $unitValue = isset($item['estimated_unit_value']) ? (float) $item['estimated_unit_value'] : 0;

        if ($quantity <= 0 || $unitValue <= 0) {
            return null;
        }

        return $quantity * $unitValue;
    }

    private function generateDonorCode(): string
    {
        $nextId = (Donor::withTrashed()->max('id') ?? 0) + 1;

        return 'DN-' . now()->format('Y') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    private function generateDonationCode(): string
    {
        $nextId = (Donation::withTrashed()->max('id') ?? 0) + 1;

        return 'DO-' . now()->format('Y') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    private function formData(): array
    {
        return [
            'donorTypes' => Donor::TYPES,
            'donationTypes' => Donation::TYPES,
            'purposes' => Donation::PURPOSES,
            'paymentMethods' => Donation::PAYMENT_METHODS,
            'acknowledgmentStatuses' => Donation::ACKNOWLEDGMENT_STATUSES,
            'statuses' => Donation::STATUSES,
            'itemCategories' => DonationItem::CATEGORIES,
            'itemConditions' => DonationItem::CONDITIONS,
        ];
    }
}