<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use App\Models\Donation;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'registeredUsersCount' => User::count(),
            'adoptionCasesCount' => AdoptionCase::count(),
            'pendingDocumentsCount' => AdoptionCaseDocument::where('status', 'pending')->count(),
            'donationRecordsCount' => Donation::count(),
        ]);
    }
}
