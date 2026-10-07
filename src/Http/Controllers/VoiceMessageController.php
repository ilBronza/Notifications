<?php

namespace IlBronza\Notifications\Http\Controllers;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Notification;
use IlBronza\Notifications\Notifications\AudioNotification;
use IlBronza\Notifications\Support\RecipientDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VoiceMessageController
{
    public function index(Request $request, RecipientDirectory $directory)
    {
        $directory->authorizeSending();
        $validated = $request->validate(['q' => 'nullable|string|max:100']);
        $query = $directory->query();
        if ($search = $validated['q'] ?? null) {
            $query->where('name', 'like', '%'.$search.'%');
        }
        $users = $query->orderBy($query->getModel()->getKeyName())->paginate(max(1, (int) config('notifications.voiceMessages.per_page', 24)))->withQueryString();
        return view('notifications::voice-messages.index', compact('users'));
    }

    public function store(Request $request, RecipientDirectory $directory)
    {
        $directory->authorizeSending();
        $validated = $request->validate([
            'user_id' => ['required', 'regex:/\A[a-zA-Z0-9_-]{1,64}\z/'],
            'audio' => 'required|file|mimes:webm,mp3,ogg,wav,mpeg,mp4,m4a,aac|max:'.config('notifications.voiceMessages.max_kilobytes', 5120),
        ]);
        $user = $directory->find($validated['user_id']);
        $disk = config('notifications.voiceMessages.storage_disk');
        $path = trim(config('notifications.voiceMessages.storage_path', ''), '/');
        $filename = Str::uuid().'.'.$request->file('audio')->extension();
        $stored = $request->file('audio')->storeAs($path, $filename, ['disk' => $disk, 'visibility' => 'private']);
        if (! $stored) {
            throw new RuntimeException('Unable to store the audio message.');
        }
        try {
            $id = Notification::users($user)->notification(AudioNotification::fromStorage($disk, $stored))->send();
            if (! ExtendedDatabaseNotification::whereKey($id)->exists()) {
                throw new RuntimeException('The audio notification was rejected.');
            }
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($stored);
            throw $exception;
        }
        $message = __('notifications::notifications.voiceMessageSent', ['name' => $user->name ?? $user->getKey()]);
        return $request->expectsJson()
            ? response()->json(['success' => true, 'id' => $id, 'message' => $message], 201)
            : redirect()->route('notifications.voice-messages.index')->with('success', $message);
    }

    public function audio(Request $request, ExtendedDatabaseNotification $notification)
    {
        abort_unless($notification->canBeAccessedBy($request->user()), 403);
        $data = $notification->getData();
        abort_unless(isset($data['audio_disk'], $data['audio_path']), 404);
        $disk = Storage::disk($data['audio_disk']);
        abort_unless($disk->exists($data['audio_path']), 404);
        return $disk->response($data['audio_path'], basename($data['audio_path']), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
