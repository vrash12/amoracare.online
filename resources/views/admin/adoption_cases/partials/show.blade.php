@extends('layouts.dashboard', ['title' => 'Adoption Case Details'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>{{ $adoptionCase->case_code }}</h2>
                <p>{{ $adoptionCase->case_type_label }} • {{ $adoptionCase->status_label }}</p>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ route('admin.adoption-cases.edit', $adoptionCase) }}" class="btn secondary">Edit</a>
                <a href="{{ route('admin.adoption-cases.index') }}" class="btn light">Back</a>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>Case Overview</h2>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
            <p><strong>Child:</strong><br>{{ $adoptionCase->child?->full_name ?? 'N/A' }}</p>
            <p><strong>Child Code:</strong><br>{{ $adoptionCase->child?->child_code ?? 'N/A' }}</p>
            <p><strong>Prospective Parent:</strong><br>{{ $adoptionCase->prospectiveParent?->name ?? 'Not assigned' }}</p>
            <p><strong>Assigned Staff:</strong><br>{{ $adoptionCase->assignedSocialWorker?->name ?? 'Not assigned' }}</p>
            <p><strong>Status:</strong><br>{{ $adoptionCase->status_label }}</p>
            <p><strong>Priority:</strong><br>{{ $adoptionCase->priority_label }}</p>
            <p><strong>Opened Date:</strong><br>{{ $adoptionCase->opened_at?->format('F d, Y') ?? 'N/A' }}</p>
            <p><strong>Target Completion:</strong><br>{{ $adoptionCase->target_completion_date?->format('F d, Y') ?? 'N/A' }}</p>
            <p><strong>Closed Date:</strong><br>{{ $adoptionCase->closed_at?->format('F d, Y') ?? 'N/A' }}</p>
            <p><strong>Document Progress:</strong><br>{{ $adoptionCase->document_progress }}</p>
        </div>
    </div>

    <div class="panel">
        <h2>Case Summary</h2>
        <p>{{ $adoptionCase->summary ?? 'No case summary recorded.' }}</p>
    </div>

    <div class="panel">
        <h2>Confidential Internal Notes</h2>
        <p>{{ $adoptionCase->confidential_notes ?? 'No confidential notes recorded.' }}</p>
    </div>

    <div class="panel">
        <h2>Document Checklist</h2>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 12px;">Document</th>
                        <th style="text-align: left; padding: 12px;">Scope</th>
                        <th style="text-align: left; padding: 12px;">Status</th>
                        <th style="text-align: left; padding: 12px;">File</th>
                        <th style="text-align: left; padding: 12px;">Update</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($adoptionCase->documents as $document)
                        <tr>
                            <td style="padding: 12px;">
                                <strong>{{ $document->document_name }}</strong>
                                <br>
                                <small>{{ $document->document_type ?? 'N/A' }}</small>
                            </td>

                            <td style="padding: 12px;">
                                {{ $document->scope_label }}
                            </td>

                            <td style="padding: 12px;">
                                {{ $document->status_label }}
                            </td>

                            <td style="padding: 12px;">
                                @if($document->original_filename)
                                    {{ $document->original_filename }}
                                    <br>
                                    <small>
                                        Uploaded by {{ $document->uploader?->name ?? 'N/A' }}
                                    </small>
                                @else
                                    No file uploaded
                                @endif
                            </td>

                            <td style="padding: 12px;">
                                <form
                                    method="POST"
                                    action="{{ route('admin.adoption-cases.documents.update', [$adoptionCase, $document]) }}"
                                    enctype="multipart/form-data"
                                    style="display: grid; gap: 8px; min-width: 260px;"
                                >
                                    @csrf
                                    @method('PUT')

                                    <select name="status" required>
                                        @foreach($documentStatuses as $value => $label)
                                            <option value="{{ $value }}" @selected($document->status === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <input type="date" name="expiry_date" value="{{ $document->expiry_date?->format('Y-m-d') }}">

                                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">

                                    <textarea name="remarks" rows="2" placeholder="Remarks">{{ $document->remarks }}</textarea>

                                    <button type="submit" class="btn secondary">
                                        Save Document
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h2>Add Case Note</h2>

        <form method="POST" action="{{ route('admin.adoption-cases.notes.store', $adoptionCase) }}">
            @csrf

            <div style="display: grid; grid-template-columns: 220px 220px 1fr; gap: 12px;">
                <div>
                    <label for="note_type">Note Type</label>
                    <select id="note_type" name="note_type" required style="width: 100%;">
                        @foreach($noteTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="visibility">Visibility</label>
                    <select id="visibility" name="visibility" required style="width: 100%;">
                        @foreach($noteVisibilities as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" style="width: 100%;" placeholder="Optional title">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label for="body">Note</label>
                    <textarea id="body" name="body" rows="4" required style="width: 100%;" placeholder="Write a factual case note."></textarea>
                </div>
            </div>

            <div style="margin-top: 12px;">
                <button type="submit" class="btn">
                    Add Note
                </button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>Case Notes</h2>

        @forelse($adoptionCase->notes->sortByDesc('created_at') as $note)
            <div style="padding: 14px; border: 1px solid #e5e7eb; border-radius: 14px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <strong>{{ $note->title ?? $note->note_type_label }}</strong>
                        <p style="margin: 4px 0;">
                            {{ $note->note_type_label }} • {{ $note->visibility_label }}
                        </p>
                    </div>

                    <small>
                        {{ $note->created_at?->format('M d, Y h:i A') }}
                        by {{ $note->creator?->name ?? 'N/A' }}
                    </small>
                </div>

                <p style="margin-top: 10px;">{{ $note->body }}</p>
            </div>
        @empty
            <p>No case notes recorded.</p>
        @endforelse
    </div>
@endsection