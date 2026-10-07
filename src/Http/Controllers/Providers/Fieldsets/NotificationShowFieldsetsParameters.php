<?php

namespace IlBronza\Notifications\Http\Controllers\Providers\Fieldsets;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class NotificationShowFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters(): array
    {
        return [
            'notification' => [
                'fields' => [
                    'id' => ['text' => 'string'],
                    'type' => ['text' => 'string'],
                    'message' => ['textarea' => 'string|nullable'],
                    'link' => ['text' => 'string|nullable'],
                    'link_text' => ['text' => 'string|nullable'],
                    'created_at' => ['datetime' => 'date|nullable'],
                    'read_at' => ['datetime' => 'date|nullable'],
                    'managed_at' => ['datetime' => 'date|nullable'],
                    'managed' => ['textarea' => 'string|nullable'],
                ],
                'width' => ['1-1'],
            ],
        ];
    }
}
