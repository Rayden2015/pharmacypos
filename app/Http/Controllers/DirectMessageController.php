<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDirectMessageRequest;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DirectMessageController extends Controller
{
    public function index(Request $request): View
    {
        Log::info('DirectMessageController@index');
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
        Log::info('DirectMessageController@show', ['user_id' => $userId]);

        $other = User::query()->find($userId);
        if ($other === null) {
            Log::warning('DirectMessageController@show user not found', ['user_id' => $userId]);

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

        $messages = DirectMessage::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($uid, $otherId) {
                $q->where(function ($q2) use ($uid, $otherId) {
                    $q2->where('sender_id', $uid)->where('recipient_id', $otherId);
                })->orWhere(function ($q2) use ($uid, $otherId) {
                    $q2->where('sender_id', $otherId)->where('recipient_id', $uid);
                });
            })
            ->with(['sender:id,name', 'recipient:id,name'])
            ->orderBy('created_at')
            ->get();

        return view('messages.thread', [
            'other' => $other,
            'messages' => $messages,
        ]);
    }

    public function store(StoreDirectMessageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Log::info('DirectMessageController@store', ['recipient_id' => $data['recipient_id'] ?? null]);
        $me = $request->user();
        $recipient = User::query()->findOrFail($data['recipient_id']);

        DirectMessage::create([
            'company_id' => $me->company_id,
            'sender_id' => $me->id,
            'recipient_id' => $recipient->id,
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('messages.show', $recipient)
            ->with('success', 'Message sent.');
    }

    /**
     * Mark all inbound DMs as read for the current user (header dropdown).
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Log::info('DirectMessageController@markAllRead');
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
