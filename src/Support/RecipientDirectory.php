<?php

namespace IlBronza\Notifications\Support;

use Illuminate\Support\Facades\Gate;

class RecipientDirectory
{
    public function query()
    {
        $model = config('notifications.voiceMessages.user_model') ?: config('notifications.user_model') ?: config('auth.providers.users.model');
        $query = $model::query();
        if ($scope = config('notifications.voiceMessages.recipient_scope')) {
            $query = app($scope)($query, auth()->user());
        }
        return $query;
    }

    public function authorizeSending(): void
    {
        abort_unless(auth()->check(), 403);
        if (Gate::has('notifications.send')) {
            Gate::authorize('notifications.send');
        }
    }

    public function find($id)
    {
        $this->authorizeSending();
        $recipient = $this->query()->findOrFail($id);
        if (Gate::has('notifications.sendTo')) {
            Gate::authorize('notifications.sendTo', $recipient);
        }
        return $recipient;
    }
}
