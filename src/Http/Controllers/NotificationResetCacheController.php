<?php

namespace IlBronza\Notifications\Http\Controllers;

use Illuminate\Routing\Controller;
use IlBronza\Notifications\Http\Helpers\NotificationGetterHelper;


class NotificationResetCacheController extends Controller
{
    public function header()
    {
        NotificationGetterHelper::resetHeaderSessionCache();

        session()->flash('success', __('notifications::notifications.cacheReset'));

        return back();
    }
}

