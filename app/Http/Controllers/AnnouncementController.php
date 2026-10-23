<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Announcement\StoreRequest;
use App\Models\Announcement;
use App\Models\Certification;
use App\Models\User;
use App\UseCases\Announcement\IndexAction;
use App\UseCases\Announcement\ShowAction;
use App\UseCases\Announcement\StoreAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(IndexAction $action): View
    {
        $this->authorize('viewAny', Announcement::class);

        $announcements = $action();

        return view('announcement.management.index', [
            'announcements' => $announcements,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        $certifications = Certification::query()
            ->published()
            ->orderBy('name')
            ->get();

        $students = User::query()
            ->where('role', UserRole::Student)
            ->where('status', UserStatus::InProgress)
            ->orderBy('name')
            ->get();

        return view('announcement.management.create', [
            'certifications' => $certifications,
            'students' => $students,
        ]);
    }

    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $validated = $request->validated();

        $announcement = $action(
            admin: $request->user(),
            validated: $validated,
        );

        return redirect()
            ->route('admin.announcements.show', $announcement)
            ->with('success', "お知らせを配信しました。（{$announcement->dispatched_count}件）");
    }

    public function show(Announcement $announcement, ShowAction $action): View
    {
        $this->authorize('view', $announcement);

        $announcement = $action($announcement);

        return view('announcement.management.show', [
            'announcement' => $announcement,
        ]);
    }
}
