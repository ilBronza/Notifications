<?php

namespace IlBronza\Notifications\Http\Models;

use IlBronza\Notifications\ExtendedDatabaseNotification;

class BaseNotificationModel extends ExtendedDatabaseNotification
{
    public $viewName = 'notifications::notifications._notification';

    public function getViewName() { return $this->viewName; }
    /** @deprecated Use getViewName(). */
    public function gertViewName() { return $this->getViewName(); }
    public function render() { return view($this->getViewName(), ['notification' => $this])->render(); }
    public function scopeValid($query) { return $this->scopeValidByDate($query); }
}
