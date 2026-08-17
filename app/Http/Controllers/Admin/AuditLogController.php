<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionMatchingRun;
use App\Models\Child;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $type = (string) $request->input('type', '');
        $events = collect();

        User::with('role')->latest('updated_at')->take(30)->get()->each(function (User $user) use ($events) {
            $events->push($this->event(
                'user',
                $this->recordAction($user->created_at, $user->updated_at, 'User created', 'User updated'),
                "{$user->name} ({$user->email})",
                $user->name,
                $user->updated_at ?? $user->created_at,
                route('admin.users.show', $user),
                'bi-person-gear'
            ));
        });

        Child::with(['creator', 'updater'])->latest('updated_at')->take(30)->get()->each(function (Child $child) use ($events) {
            $events->push($this->event(
                'child',
                $this->recordAction($child->created_at, $child->updated_at, 'Child profile created', 'Child profile updated'),
                "{$child->child_code} - {$child->full_name}",
                $child->updater?->name ?? $child->creator?->name ?? 'System',
                $child->updated_at ?? $child->created_at,
                route('admin.children.show', $child),
                'bi-person-hearts'
            ));
        });

        AdoptionCase::with(['creator', 'updater'])->latest('updated_at')->take(30)->get()->each(function (AdoptionCase $case) use ($events) {
            $events->push($this->event(
                'case',
                $this->recordAction($case->created_at, $case->updated_at, 'Adoption case created', 'Adoption case updated'),
                "{$case->case_code} - {$case->status_label}",
                $case->updater?->name ?? $case->creator?->name ?? 'System',
                $case->updated_at ?? $case->created_at,
                route('admin.adoption-cases.show', $case),
                'bi-folder2-open'
            ));
        });

        Donation::with(['encoder', 'updater'])->latest('updated_at')->take(30)->get()->each(function (Donation $donation) use ($events) {
            $events->push($this->event(
                'donation',
                $this->recordAction($donation->created_at, $donation->updated_at, 'Donation recorded', 'Donation updated'),
                "{$donation->donation_code} - {$donation->donation_type_label}",
                $donation->updater?->name ?? $donation->encoder?->name ?? 'System',
                $donation->updated_at ?? $donation->created_at,
                route('admin.donations.show', $donation),
                'bi-gift'
            ));
        });

        AdoptionMatchingRun::with('generator')->latest('generated_at')->take(30)->get()->each(function (AdoptionMatchingRun $run) use ($events) {
            $events->push($this->event(
                'matching',
                'Matching run generated',
                "{$run->run_code} - " . ucwords(str_replace('_', ' ', $run->status)),
                $run->generator?->name ?? 'System',
                $run->generated_at ?? $run->created_at,
                route('admin.matching.show', $run),
                'bi-diagram-3'
            ));
        });

        $events = $events
            ->filter(fn (array $event) => $event['occurred_at'] !== null)
            ->when($type !== '', fn ($items) => $items->where('type', $type))
            ->when($search !== '', function ($items) use ($search) {
                $needle = mb_strtolower($search);

                return $items->filter(function (array $event) use ($needle) {
                    return str_contains(mb_strtolower($event['action']), $needle)
                        || str_contains(mb_strtolower($event['description']), $needle)
                        || str_contains(mb_strtolower($event['actor']), $needle);
                });
            })
            ->sortByDesc('occurred_at')
            ->values();

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $paginatedEvents = new LengthAwarePaginator(
            $events->forPage($page, $perPage)->values(),
            $events->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.audit_logs.index', [
            'events' => $paginatedEvents,
            'search' => $search,
            'type' => $type,
            'types' => [
                'user' => 'Users',
                'child' => 'Child Profiles',
                'case' => 'Adoption Cases',
                'donation' => 'Donations',
                'matching' => 'Matching Runs',
            ],
        ]);
    }

    private function event(
        string $type,
        string $action,
        string $description,
        string $actor,
        $occurredAt,
        string $url,
        string $icon
    ): array {
        return compact('type', 'action', 'description', 'actor', 'url', 'icon') + [
            'occurred_at' => $occurredAt,
        ];
    }

    private function recordAction($createdAt, $updatedAt, string $createdLabel, string $updatedLabel): string
    {
        if ($createdAt && $updatedAt && $createdAt->equalTo($updatedAt)) {
            return $createdLabel;
        }

        return $updatedLabel;
    }
}
