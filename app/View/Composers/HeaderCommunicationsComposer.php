<?php

namespace App\View\Composers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HeaderCommunicationsComposer
{
    public function compose(View $view): void
    {
        $defaults = [
            'headerUnreadAnnouncements' => 0,
            'headerUnreadDms' => 0,
            'headerAnnouncementPreviews' => collect(),
            'headerDmPreviews' => collect(),
        ];

        $user = auth()->user();
        if (! $user || ! $user->canUseTenantCommunications()) {
            $view->with($defaults);

            return;
        }

        if (! Schema::hasTable('direct_messages')
            || ! Schema::hasTable('announcements')
            || ! Schema::hasTable('announcement_reads')) {
            $view->with($defaults);

            return;
        }

        $companyId = (int) $user->company_id;
        $uid = (int) $user->id;

        $announcementsOn = $user->notificationPreference('announcements_enabled', true);
        $dmsOn = $user->notificationPreference('direct_messages_enabled', true);

        if ($announcementsOn) {
            $unreadAnnouncements = Announcement::query()
                ->visibleTo($user)
                ->unreadFor($user)
                ->count();

            $readIds = AnnouncementRead::query()
                ->where('user_id', $uid)
                ->pluck('announcement_id');

            $announcementPreviews = Announcement::query()
                ->visibleTo($user)
                ->with(['site:id,name'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function (Announcement $a) use ($readIds) {
                    $a->setAttribute('unread', ! $readIds->contains($a->id));

                    return $a;
                });
        } else {
            $unreadAnnouncements = 0;
            $announcementPreviews = collect();
        }

        if ($dmsOn) {
            $unreadDms = DirectMessage::query()
                ->where('company_id', $companyId)
                ->where('recipient_id', $uid)
                ->whereNull('read_at')
                ->count();

            $dmPreviews = $this->dmThreadPreviews($companyId, $uid, 5);
        } else {
            $unreadDms = 0;
            $dmPreviews = collect();
        }

        $view->with([
            'headerUnreadAnnouncements' => $unreadAnnouncements,
            'headerUnreadDms' => $unreadDms,
            'headerAnnouncementPreviews' => $announcementPreviews,
            'headerDmPreviews' => $dmPreviews,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{user: User, lastMessage: DirectMessage, unread: int}>
     */
    private function dmThreadPreviews(int $companyId, int $uid, int $limit)
    {
        $messages = DirectMessage::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($uid) {
                $q->where('sender_id', $uid)->orWhere('recipient_id', $uid);
            })
            ->with(['sender:id,name,email', 'recipient:id,name,email'])
            ->orderByDesc('created_at')
            ->get();

        $seen = [];
        $threads = [];
        foreach ($messages as $msg) {
            $otherId = (int) ($msg->sender_id === $uid ? $msg->recipient_id : $msg->sender_id);
            if (isset($seen[$otherId])) {
                continue;
            }
            $seen[$otherId] = true;
            $other = $msg->sender_id === $uid ? $msg->recipient : $msg->sender;
            $unread = DirectMessage::query()
                ->where('company_id', $companyId)
                ->where('recipient_id', $uid)
                ->where('sender_id', $otherId)
                ->whereNull('read_at')
                ->count();
            $threads[] = [
                'user' => $other,
                'lastMessage' => $msg,
                'unread' => $unread,
            ];
            if (count($threads) >= $limit) {
                break;
            }
        }

        return collect($threads);
    }
}
