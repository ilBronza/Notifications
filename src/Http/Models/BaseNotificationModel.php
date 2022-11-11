<?php

namespace IlBronza\Notifications\Http\Models;

use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDUseUuidTrait;
use IlBronza\CRUD\Traits\Model\CRUDValidityDatesTrait;
use Illuminate\Notifications\DatabaseNotification;

class BaseNotificationModel extends DatabaseNotification
{
	use CRUDCacheTrait;
	use CRUDModelTrait;
    use CRUDValidityDatesTrait;

	use CRUDUseUuidTrait;

    public $viewName = 'notifications::notifications._notification';

    public function gertViewName()
    {
        return $this->viewName;
    }

    public function getMessage() : ? string
    {
        if(isset($this->data['message']))
            return $this->data['message'];

        return null;
    }

    public function render()
    {
        $viewName = $this->gertViewName();

        return view($viewName, ['notification' => $this])->render();
    }

    public function scopeValid($query)
    {

    }
}