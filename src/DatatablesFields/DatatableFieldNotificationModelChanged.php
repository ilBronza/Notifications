<?php

namespace IlBronza\Notifications\DatatablesFields;

use IlBronza\Datatables\DatatablesFields\DatatableField;

class DatatableFieldNotificationModelChanged extends DatatableField
{
	public $requireElement = true;

	static function _transformValue($value)
	{
		if(! $value)
			return ;

		return json_encode($value->getData());
	}
}