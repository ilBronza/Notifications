<?php

namespace IlBronza\Notifications;

use App\Traits\ParentingTrait;
use Carbon\Carbon;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDParentingTrait;
use Illuminate\Notifications\DatabaseNotification;

class ExtendedDatabaseNotification extends DatabaseNotification
{
	use CRUDModelTrait;
	use CRUDParentingTrait;

	public $routeClassname = 'notification';

	protected $dates = [
		'cread_at',
		'updated_at',
		'read_at',
		'managed_at'
	];

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
        return $this->link;
    }

    public function getLinkText()
    {
        return $this->link_text;
    }

	public function getType()
	{
		$parts = explode("\\", $this->type);

		return array_pop($parts);
	}

	public function getData()
	{
		return $this->data;
	}

	public function getMessage()
	{
		if(! $data = $this->getData())
			return ;

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
}