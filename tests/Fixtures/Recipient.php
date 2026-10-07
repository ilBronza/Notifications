<?php
namespace IlBronza\Notifications\Tests\Fixtures;
use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Illuminate\Foundation\Auth\User;
class Recipient extends User
{
    use ExtendedNotifiable;
    protected $table = 'users';
    protected $guarded = [];
    public function roles() { return $this->belongsToMany(Role::class, 'role_user', 'recipient_id', 'role_id'); }
}
