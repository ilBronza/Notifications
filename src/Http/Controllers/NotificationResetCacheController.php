<?php

namespace IlBronza\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use IlBronza\Notifications\Http\Helpers\NotificationGetterHelper;
use IlBronza\Ukn\Ukn;

class NotificationResetCacheController extends Controller
{
    public function header()
    {
        NotificationGetterHelper::resetHeaderSessionCache();

        Ukn::s('Header notifications cache reset');

        return back();
    }
}

