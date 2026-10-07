<?php
namespace IlBronza\Notifications\Tests\Fixtures;
use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Illuminate\Database\Eloquent\Model;
class Role extends Model
{
    use ExtendedNotifiable;
    protected $table = 'roles';
    protected $guarded = [];
}
