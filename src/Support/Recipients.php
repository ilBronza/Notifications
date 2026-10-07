<?php

namespace IlBronza\Notifications\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Recipients
{
    public static function resolve($value, string $model): Collection
    {
        if ($value instanceof Model) {
            return collect([$value]);
        }
        if ($value instanceof Collection) {
            $value = $value->all();
        }
        if (is_array($value)) {
            if ($value === []) {
                return collect();
            }
            if (collect($value)->every(fn ($item) => $item instanceof Model)) {
                return collect($value)->values();
            }
            if (! collect($value)->every(fn ($item) => is_int($item) || is_string($item))) {
                throw new InvalidArgumentException('Recipients must be models, IDs or names.');
            }
            $field = collect($value)->every(fn ($item) => is_int($item)) ? (new $model)->getKeyName() : 'name';
            return $model::query()->whereIn($field, $value)->get();
        }
        if (is_int($value)) {
            return $model::query()->whereKey($value)->get();
        }
        if (is_string($value)) {
            return $model::query()->where('name', $value)->get();
        }
        throw new InvalidArgumentException('Recipients must be models, IDs or names.');
    }

    public static function roles(Model $recipient): Collection
    {
        if ($recipient->relationLoaded('roles')) {
            return collect($recipient->getRelation('roles'));
        }
        return method_exists($recipient, 'roles') ? $recipient->roles()->get() : collect();
    }

    public static function key(Model $recipient): string
    {
        return $recipient->getMorphClass().':'.$recipient->getKey();
    }
}
