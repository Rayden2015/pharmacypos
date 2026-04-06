<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDirectMessageRequest;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DirectMessageController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user();
        $companyId = (int) $me->company_id;
        $uid = (int) $me->id;

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
        }

        $colleagues = User::query()
            ->where('company_id', $companyId)
            ->where('id', '!=', $uid)
            ->where('is_super_admin', false)
            ->whereNotNull('company_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('messages.index', [
            'threads' => $threads,
            'colleagues' => $colleagues,
        ]);
    }

    public function show(Request $request, string $user): View
    {
        $userId = (int) $user;

        $other = User::query()->find($userId);
        if ($other === null) {
            Log::warning('DirectMessageController@show user not found', ['user_id' => $userId]);

            abort(404);
        }

        $me = $request->user()->loadMissing(['company:id,company_name']);
        $this->assertDmPartner($me, $other);

        $companyId = (int) $me->company_id;
        $uid = (int) $me->id;
        $otherId = (int) $other->id;

        DirectMessage::query()
            ->where('company_id', $companyId)
            ->where('recipient_id', $uid)
            ->where('sender_id', $otherId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $this->threadBetween($request, $other)
            ->with(['sender:id,name,user_img', 'recipient:id,name,user_img'])
            ->orderBy('created_at')
            ->get();

        return view('messages.thread', [
            'other' => $other,
            'me' => $me,
            'messages' => $messages,
        ]);
    }

    /**
     * Poll for new messages in a thread (used for near real-time updates without WebSockets).
     */
    public function poll(Request $request, string $user): JsonResponse
    {
        $userId = (int) $user;
        $other = User::query()->find($userId);
        if ($other === null) {
            abort(404);
        }

        $me = $request->user();
        $this->assertDmPartner($me, $other);

        $companyId = (int) $me->company_id;
        $uid = (int) $me->id;
        $otherId = (int) $other->id;

        DirectMessage::query()
            ->where('company_id', $companyId)
            ->where('recipient_id', $uid)
            ->where('sender_id', $otherId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $afterId = max(0, (int) $request->query('after_id', 0));

        $newMessages = $this->threadBetween($request, $other)
            ->where('id', '>', $afterId)
            ->with(['sender:id,name,user_img'])
            ->orderBy('id')
            ->get();

        return response()->json([
            'ok' => true,
            'messages' => $newMessages->map(fn (DirectMessage $m) => $this->messagePayload($m))->all(),
        ]);
    }

    public function store(StoreDirectMessageRequest $request)
    {
        $data = $request->validated();
        $me = $request->user();
        $recipient = User::query()->findOrFail($data['recipient_id']);

        $dm = DirectMessage::create([
            'company_id' => $me->company_id,
            'sender_id' => $me->id,
            'recipient_id' => $recipient->id,
            'body' => $data['body'],
        ]);
        $dm->load(['sender:id,name,user_img']);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $this->messagePayload($dm),
            ]);
        }

        return redirect()
            ->route('messages.show', $recipient)
            ->with('success', 'Message sent.');
    }

    private function threadBetween(Request $request, User $other): Builder
    {
        $me = $request->user();
        $companyId = (int) $me->company_id;
        $uid = (int) $me->id;
        $otherId = (int) $other->id;

        return DirectMessage::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($uid, $otherId) {
                $q->where(function ($q2) use ($uid, $otherId) {
                    $q2->where('sender_id', $uid)->where('recipient_id', $otherId);
                })->orWhere(function ($q2) use ($uid, $otherId) {
                    $q2->where('sender_id', $otherId)->where('recipient_id', $uid);
                });
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function messagePayload(DirectMessage $m): array
    {
        $m->loadMissing('sender:id,name,user_img');

        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'body' => $m->body,
            'created_at' => $m->created_at?->toIso8601String(),
            'created_label' => $m->created_at?->format('M j, g:i a'),
            'sender' => [
                'id' => $m->sender->id,
                'name' => $m->sender->name,
                'has_photo' => $m->sender->hasProfilePhoto(),
                'photo_url' => $m->sender->profilePhotoUrl(),
            ],
        ];
    }

    /**
     * Mark all inbound DMs as read for the current user (header dropdown).
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;

        DirectMessage::query()
            ->where('company_id', $companyId)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function assertDmPartner(?User $me, User $other): void
    {
        if (! $me || ! $me->canUseTenantCommunications()) {
            abort(403);
        }
        if ($other->isSuperAdmin() || (int) $other->company_id !== (int) $me->company_id) {
            abort(403);
        }
    }
}
