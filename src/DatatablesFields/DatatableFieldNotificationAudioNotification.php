<?php

namespace IlBronza\Notifications\DatatablesFields;

use IlBronza\Datatables\DatatablesFields\DatatableField;

class DatatableFieldNotificationAudioNotification extends DatatableField
{
    public $requireElement = true;

    public static function _transformValue($value)
    {
        if (! $value) {
            return '';
        }

        return $value->render();
    }
}
