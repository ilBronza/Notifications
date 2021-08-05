<?php

namespace IlBronza\Notifications\Traits;

trait CRUDNotificationParametersTrait
{
    public static $tables = [

        'index' => [
            'fields' => 
            [
                'id' => 'flat',
                'mySelfSee' => 'links.see',
                'type' => 'notifications.type',
                'mySelf' => 'notifications.notification',
                'created_by' => 'users.name',
                'mySelfLink' => [
                    'type' => 'notifications.link',
                    'width' => '340px'
                ],
                'read_at' => [
                    'type' => 'editor.toggle',
                    'editorProperty' => 'read_at',
                ],
                'managed' => 'editor.text',
                'managed_at' => [
                    'type' => 'editor.toggle',
                    'editorProperty' => 'managed_at',
                ]
            ]
        ],

        'archived' => [
            'fields' => 
            [
                'id' => 'flat',
                'mySelfSee' => 'links.see',
                'type' => 'notifications.type',
                'mySelf' => 'notifications.notification',
                'created_by' => 'users.name',
                'mySelfLink' => [
                    'type' => 'notifications.link',
                    'width' => '340px'
                ],
                'read_at' => [
                    'type' => 'editor.toggle',
                    'editorProperty' => 'read_at',
                ]
            ]
        ]
    ];

    static $formFields = [
        'common' => [
            'default' => [
                'read_at' => ['boolean' => 'boolean|nullable'],
                'managed_at' => ['boolean' => 'boolean|nullable'],
                'managed' => ['text' => 'string|nullable|max:255'],
        /**
                'name' => ['text' => 'string|required|max:255'],
                'age' => ['number' => 'numeric|required'],
                'color' => ['color' => 'numeric|required'],
                'dated_at' => ['date' => 'date|nullable'],
                'time_at' => ['datetime' => 'date|nullable'],
                'permissions' => [
                    'type' => 'select',
                    'multiple' => true,
                    'rules' => 'array|nullable|exists:permissions,id',
                    'relation' => 'permissions'
                ],
                'city' => [
                    'type' => 'select',
                    'multiple' => false,
                    'rules' => 'integer|nullable|exists:cities,id',
                    'relation' => 'city'
                ],
            ]
        **/
        ],
        /**
        'edit' => [
            'default' => [
            ]
        ],
        'onlyEdit' => [
            'default' => [
            ]
        ],
        'create' => [
            'default' => [
            ]
        ],
        'onlyCreate' => [
            'default' => [
            ]
        **/
        ],
    ];    
}