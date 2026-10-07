<?php

namespace IlBronza\Notifications;

use Carbon\Carbon;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDParentingTrait;
use IlBronza\Notifications\Http\Controllers\Providers\Fieldsets\NotificationShowFieldsetsParameters;
use Illuminate\Notifications\DatabaseNotification;

class ExtendedDatabaseNotification extends DatabaseNotification
{
    use CRUDModelTrait;
    use CRUDParentingTrait;

    public $routeClassname = 'notification';

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime',
        'managed_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'priority' => 'integer',
        'headerbar' => 'boolean'
    ];

    public function getShowParametersFile() : string
    {
        return NotificationShowFieldsetsParameters::class;
    }

    public function getPluralTranslatedClassname() : string
    {
        return trans('notifications::notifications.notifications');
    }

    public function getTranslatedClassname() : string
    {
        return trans('notifications::notifications.notification');
    }

    public function scopeManaged($query)
    {
        return $query->whereNotNull('managed_at');
    }

    public function scopeToManage($query)
    {
        return $query->whereNull('managed_at');
    }

    public function getLink()
    {
        return $this->link ?? $this->parent?->link;
    }

    public function getLinkText()
    {
        return $this->link_text ?? $this->parent?->link_text;
    }

    public function getType()
    {
        $parts = explode("\\", $this->parent?->type ?? $this->type);

        return array_pop($parts);
    }

    public function getData()
    {
        return $this->data ?: ($this->parent?->data ?? []);
    }

    public function render()
    {
        return view('notifications::notifications._notification', ['notification' => $this])->render();
    }

    public function getMessage()
    {
        if (! $data = $this->getData()) {
            return null;
        }

        return $data['message'] ?? null;
    }

    public function setReadAtAttribute($value)
    {
        if(! is_bool($value))
            return $this->attributes['read_at'] = $value;

        if($value)
            return $this->attributes['read_at'] = Carbon::now();

        $this->attributes['read_at'] = null;
    }

    public function setManagedAtAttribute($value)
    {
        if(! is_bool($value))
            return $this->attributes['managed_at'] = $value;

        if($value)
            return $this->attributes['managed_at'] = Carbon::now();

        $this->attributes['managed_at'] = null;
    }

    protected static function booted()
    {
        static::observe(\IlBronza\Notifications\Observers\ExtendedDatabaseNotificationObserver::class);
    }

    public function scopeVisibleTo($query, ?\Illuminate\Database\Eloquent\Model $user)
    {
        if (! $user || $user->getKey() === null) {
            return $query->whereRaw('1 = 0');
        }
        $recipients = collect([$user])->concat(\IlBronza\Notifications\Support\Recipients::roles($user));
        return $query->where(function ($query) use ($recipients) {
            foreach ($recipients as $recipient) {
                $query->orWhere(function ($query) use ($recipient) {
                    $query->whereIn('notifiable_type', array_unique([$recipient->getMorphClass(), get_class($recipient)]))
                        ->where('notifiable_id', $recipient->getKey());
                });
            }
        });
    }

    public function scopeInboxFor($query, \Illuminate\Database\Eloquent\Model $user)
    {
        // One receipt per group, preferring the direct user receipt over a role receipt.
        $ids = static::query()->visibleTo($user)
            ->selectRaw('COALESCE(MIN(CASE WHEN notifiable_type IN (?, ?) AND notifiable_id = ? THEN id END), MIN(id))',
                [$user->getMorphClass(), get_class($user), $user->getKey()])
            ->groupByRaw('COALESCE(parent_id, id)');
        return $query->whereIn('id', $ids);
    }

    public function scopeValidByDate($query, ?Carbon $date = null)
    {
        $date = $date ?? Carbon::now();
        return $query->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', $date));
    }

    public function canBeAccessedBy($user): bool
    {
        if (! $user instanceof \Illuminate\Database\Eloquent\Model) {
            return false;
        }
        if (\Illuminate\Support\Facades\Gate::has('notifications.manageAll')
            && \Illuminate\Support\Facades\Gate::forUser($user)->allows('notifications.manageAll')) {
            return true;
        }
        return static::query()->visibleTo($user)->whereKey($this->getKey())->exists();
    }

    public function userCanSee($user = null) { return $this->canBeAccessedBy($user ?? auth()->user()); }
    public function userCanUpdate($user = null) { return $this->canBeAccessedBy($user ?? auth()->user()); }
    public function userCanDelete($user = null) { return $this->canBeAccessedBy($user ?? auth()->user()); }

    public function getAudioUrl(): ?string
    {
        $data = $this->getData();
        if (isset($data['audio_disk'], $data['audio_path'])) {
            return route('notifications.audio', $this->getKey());
        }
        $url = $data['audio_url'] ?? null;
        return is_string($url) && (preg_match('#^https?://#i', $url) || (str_starts_with($url, '/') && ! str_starts_with($url, '//'))) ? $url : null;
    }

    public function delete()
    {
        return $this->getConnection()->transaction(function () {
            $audio = $this->getData();
            // Removing a receipt must not remove another recipient's shared message.
            if (! $this->parent_id && ($replacement = $this->children()->orderBy('id')->lockForUpdate()->first())) {
                if (! $replacement->data) {
                    $replacement->forceFill($this->only(['type', 'data', 'link', 'link_text', 'created_by']));
                }
                $replacement->parent_id = null;
                $replacement->saveQuietly();
                $this->children()->update(['parent_id' => $replacement->getKey()]);
            }
            $result = parent::delete();
            if ($result && isset($audio['audio_disk'], $audio['audio_path'])) {
                $this->getConnection()->afterCommit(function () use ($audio) {
                    if (! static::query()->where('data->audio_disk', $audio['audio_disk'])->where('data->audio_path', $audio['audio_path'])->exists()) {
                        \Illuminate\Support\Facades\Storage::disk($audio['audio_disk'])->delete($audio['audio_path']);
                    }
                });
            }
            return $result;
        });
    }
}
