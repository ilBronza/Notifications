<?php

namespace IlBronza\Notifications\Http\Controllers;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InboxController
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'state' => ['nullable', Rule::in(['all', 'unread', 'unmanaged', 'managed'])],
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);
        $query = ExtendedDatabaseNotification::query()->inboxFor($request->user())->with('parent')->latest();
        $state = $input['state'] ?? 'unmanaged';
        match ($state) {
            'unread' => $query->unread(),
            'unmanaged' => $query->toManage(),
            'managed' => $query->managed(),
            default => $query,
        };
        $page = $query->paginate($input['per_page'] ?? 25);
        $page->through(fn ($item) => [
            'id' => $item->id, 'type' => $item->getType(), 'message' => $item->getMessage(),
            'link' => $item->getLink(), 'link_text' => $item->getLinkText(), 'audio_url' => $item->getAudioUrl(),
            'read_at' => $item->read_at, 'managed_at' => $item->managed_at, 'created_at' => $item->created_at,
        ]);
        return response()->json($page);
    }

    public function count(Request $request)
    {
        $query = ExtendedDatabaseNotification::query()->inboxFor($request->user());
        return response()->json(['unmanaged' => (clone $query)->toManage()->count(), 'unread' => $query->unread()->count()]);
    }

    public function update(Request $request)
    {
        $input = $request->validate([
            'ids' => 'required|array|min:1|max:100', 'ids.*' => 'required|uuid|distinct',
            'action' => ['required', Rule::in(['read', 'unread', 'manage', 'unmanage'])],
        ]);
        return (new ExtendedDatabaseNotification)->getConnection()->transaction(function () use ($request, $input) {
            $notifications = ExtendedDatabaseNotification::query()->visibleTo($request->user())->whereKey($input['ids'])->lockForUpdate()->get();
            abort_unless($notifications->count() === count($input['ids']), 403);
            foreach ($notifications as $notification) {
                match ($input['action']) {
                    'read' => $notification->markAsRead(),
                    'unread' => $notification->markAsUnread(),
                    'manage' => $notification->update(['managed_at' => true]),
                    'unmanage' => $notification->update(['managed_at' => false]),
                };
            }
            return response()->json(['success' => true, 'updated' => $notifications->count()]);
        });
    }
}
