<?php

namespace IlBronza\Notifications\Http\Controllers;

use IlBronza\Notifications\Notification as NotificationBuilder;
use IlBronza\Notifications\Notifications\AudioNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceMessageController
{
    public function index()
    {
        $userModel = config('notifications.voiceMessages.user_model');
        $users = $userModel::query()->orderBy((new $userModel)->getKeyName())->get();

        return view('notifications::voice-messages.index', compact('users'));
    }

    public function store(Request $request)
    {
        $userModel = config('notifications.voiceMessages.user_model');
        $usersTable = (new $userModel)->getTable();

        $request->validate([
            'user_id' => 'required|exists:' . $usersTable . ',id',
            'audio' => 'required|file|mimes:webm,mp3,ogg,wav,mpeg|max:5120',
        ]);

        $user = $userModel::findOrFail($request->user_id);

        $disk = config('notifications.voiceMessages.storage_disk');
        $path = config('notifications.voiceMessages.storage_path');
        $filename = Str::uuid() . '.' . $request->file('audio')->getClientOriginalExtension();
        $fullPath = $path ? $path . '/' . $filename : $filename;

        $request->file('audio')->storeAs($path ?: '', $filename, $disk);
        $audioUrl = Storage::disk($disk)->url($fullPath);

        $notification = new AudioNotification($audioUrl);
        NotificationBuilder::build()
            ->users(collect([$user]))
            ->notification($notification)
            ->send();

        $userName = $user->name ?? $user->email ?? 'Utente #' . $user->id;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('notifications::notifications.voiceMessage') . ' inviato a ' . $userName,
            ]);
        }

        return redirect()
            ->route('notifications.voice-messages.index')
            ->with('success', __('notifications::notifications.voiceMessage') . ' inviato a ' . $userName);
    }
}
