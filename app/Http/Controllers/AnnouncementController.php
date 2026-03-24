<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with(['author:id,name', 'site:id,name'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $readIds = AnnouncementRead::query()
            ->where('user_id', $user->id)
            ->pluck('announcement_id')
            ->all();

        return view('announcements.index', [
            'announcements' => $announcements,
            'readIds' => $readIds,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->canPublishAnnouncements()) {
            abort(403);
        }

        $sites = Site::query()
            ->forUserTenant($user)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $defaultSiteId = (int) ($user->site_id ?? Site::defaultId());

        if ($user->isBranchAnnouncementAuthor() && ! $user->isTenantAdmin()) {
            $sites = $sites->where('id', $defaultSiteId)->values();
        }

        return view('announcements.create', [
            'sites' => $sites,
            'defaultSiteId' => $defaultSiteId,
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $scope = $data['scope'];

        $siteId = $scope === 'tenant' ? null : (int) $data['site_id'];

        Announcement::create([
            'company_id' => $user->company_id,
            'site_id' => $siteId,
            'author_id' => $user->id,
            'title' => $data['title'],
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Announcement published.');
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $user = $request->user();

        if (! Announcement::query()->visibleTo($user)->whereKey($announcement->id)->exists()) {
            Log::warning('AnnouncementController@show not visible', [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ]);
            abort(404);
        }

        AnnouncementRead::query()->firstOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            ['read_at' => now()]
        );

        $announcement->load(['author:id,name', 'site:id,name']);

        return view('announcements.show', [
            'announcement' => $announcement,
        ]);
    }

    /**
     * Mark every visible announcement as read for the current user (header dropdown).
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $ids = Announcement::query()
            ->visibleTo($user)
            ->unreadFor($user)
            ->pluck('id');

        $now = now();
        foreach ($ids as $announcementId) {
            AnnouncementRead::query()->firstOrCreate(
                [
                    'announcement_id' => $announcementId,
                    'user_id' => $user->id,
                ],
                ['read_at' => $now]
            );
        }

        return response()->json(['ok' => true]);
    }
}
