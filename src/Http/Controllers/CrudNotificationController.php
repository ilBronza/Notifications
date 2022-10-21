<?php

namespace IlBronza\Notifications\Http\Controllers;

use Auth;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Traits\CRUDArchiveTrait;
use IlBronza\CRUD\Traits\CRUDBelongsToManyTrait;
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;
use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDNestableTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;
use IlBronza\CRUD\Traits\CRUDUpdateEditorTrait;
use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Traits\CRUDNotificationParametersTrait;
use Illuminate\Http\Request;

class CrudNotificationController extends CRUD
{
    use CRUDNotificationParametersTrait;

    use CRUDShowTrait;
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;
    use CRUDArchiveTrait;
    use CRUDEditUpdateTrait;
    use CRUDUpdateEditorTrait;
    use CRUDCreateStoreTrait;

    use CRUDDeleteTrait;
    use CRUDDestroyTrait;

    use CRUDRelationshipTrait;
    use CRUDBelongsToManyTrait;

    use CRUDNestableTrait;

    /**
     * subject model class full path
     **/
    public $modelClass = ExtendedDatabaseNotification::class;

    /**
     * http methods allowed. remove non existing methods to get a 403
     **/
    public $allowedMethods = [
        'index',
        'show',
        'edit',
        'update',
        'create',
        'store',
        'destroy',
        'deleted',
        'archived',
        'reorder',
        'storeReorder'
    ];

    /**
     * to override show view use full view name
     **/
    //public $showView = 'products.showPartial';

    // public $guardedEditDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
    // public $guardedCreateDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
    // public $guardedShowDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * relations called to be automatically shown on 'show' method
     **/
    //public $showMethodRelationships = ['posts', 'users', 'operations'];

    /**
        protected $relationshipsControllers = [
        'permissions' => '\IlBronza\AccountManager\Http\Controllers\PermissionController'
    ];
    **/


    /**
     * getter method for 'index' method.
     *
     * is declared here to force the developer to rationally choose which elements to be shown
     *
     * @return Collection
     **/
     
    public function getIndexElements()
    {
        $user = Auth::user();

        cache()->forget('notificationsCount' . $user->getKey());

        return $user->getUnmanagedNotifications();
    }

    public function getArchivedElements()
    {
        $user = Auth::user();

        return $user->getArchivedNotifications();        
    }
    /**
     * parameter that decides which fields to use inside index table
     **/
    //  public $indexFieldsGroups = ['index'];

    /**
     * parameter that decides if create button is available
     **/
     public $avoidCreateButton = true;


    /**
     * START base methods declared in extended controller to correctly perform dependency injection
     *
     * these methods are compulsorily needed to execute CRUD base functions
     **/
    public function show(ExtendedDatabaseNotification $notification)
    {
        //$this->addExtraView('top', 'folder.subFolder.viewName', ['some' => $thing]);

        return $this->_show($notification);
    }

    public function edit(ExtendedDatabaseNotification $notification)
    {
        return $this->_edit($notification);
    }

    public function update(Request $request, ExtendedDatabaseNotification $notification)
    {
        return $this->_update($request, $notification);
    }

    public function destroy(ExtendedDatabaseNotification $notification)
    {
        return $this->_destroy($notification);
    }

    /**
     * END base methods
     **/




     /**
      * START CREATE PARAMETERS AND METHODS
      **/

    // public function beforeRenderCreate()
    // {
    //     $this->modelInstance->agent_id = session('agent')->getKey();
    // }

     /**
      * STOP CREATE PARAMETERS AND METHODS
      **/

}

