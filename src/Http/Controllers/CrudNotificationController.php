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

    public $modelClass = ExtendedDatabaseNotification::class;

    public $routeBaseNamePieces = ['notifications'];

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
    ];

    public function getIndexElements()
    {
        $user = Auth::user();

        return ExtendedDatabaseNotification::query()->inboxFor($user)->with('parent')->toManage()->latest()->get();
    }

    public function getArchivedElements()
    {
        $user = Auth::user();

        return ExtendedDatabaseNotification::query()->inboxFor($user)->with('parent')->managed()->latest()->get();
    }

    public $avoidCreateButton = true;

    public function show(ExtendedDatabaseNotification $notification)
    {
        abort_unless($notification->canBeAccessedBy(auth()->user()), 403);
        return $this->_show($notification);
    }

    public function edit(ExtendedDatabaseNotification $notification)
    {
        abort_unless($notification->canBeAccessedBy(auth()->user()), 403);
        return $this->_edit($notification);
    }

    public function update(Request $request, ExtendedDatabaseNotification $notification)
    {
        abort_unless($notification->canBeAccessedBy($request->user()), 403);
        $rules = ['read_at' => 'nullable|boolean', 'managed_at' => 'nullable|boolean', 'managed' => 'nullable|string|max:255'];
        if ($request->boolean('ib-editor')) {
            $input = $request->validate(['field' => ['required', \Illuminate\Validation\Rule::in(array_keys($rules))]]);
            $value = $request->validate(['value' => $rules[$input['field']]]);
            $data = [$input['field'] => $value['value'] ?? null];
        } else {
            $data = $request->validate($rules);
        }
        foreach (['read_at', 'managed_at'] as $field) {
            if (array_key_exists($field, $data)) { $data[$field] = (bool) $data[$field]; }
        }
        $notification->getConnection()->transaction(fn () => $notification->update($data));
        if ($request->expectsJson() || $request->boolean('ib-editor')) {
            return response()->json(['success' => true, 'update-editor' => true, 'model-id' => $notification->id,
                'field' => $request->input('field'), 'value' => $request->input('value')]);
        }
        return redirect()->route('notifications.show', $notification);
    }

    public function destroy(ExtendedDatabaseNotification $notification)
    {
        abort_unless($notification->canBeAccessedBy(auth()->user()), 403);
        $notification->delete();
        return request()->expectsJson() ? response()->json(['success' => true]) : redirect()->route('notifications.index');
    }

    public function create()
    {
        $directory = app(\IlBronza\Notifications\Support\RecipientDirectory::class);
        $directory->authorizeSending();
        $search = request()->validate(['q' => 'nullable|string|max:100'])['q'] ?? null;
        $users = $directory->query()->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))->limit(50)->get();
        return view('notifications::notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $input = $request->validate(['user_id' => ['required', 'regex:/\A[a-zA-Z0-9_-]{1,64}\z/'], 'message' => 'required|string|max:10000', 'managed' => 'nullable|string|max:255']);
        $recipient = app(\IlBronza\Notifications\Support\RecipientDirectory::class)->find($input['user_id']);
        $id = (new ExtendedDatabaseNotification)->getConnection()->transaction(function () use ($recipient, $input) {
            $id = \IlBronza\Notifications\Notification::users($recipient)
                ->notification(new \IlBronza\Notifications\Notifications\TextNotification($input['message']))->send();
            $notification = ExtendedDatabaseNotification::findOrFail($id);
            if (! empty($input['managed'])) { $notification->update(['managed' => $input['managed']]); }
            return $id;
        });
        return $request->expectsJson() ? response()->json(['success' => true, 'id' => $id], 201)
            : redirect()->route('notifications.index')->with('success', __('notifications::notifications.sent'));
    }

    public function index()
    {
        return $this->renderInbox(false);
    }

    public function archived()
    {
        return $this->renderInbox(true);
    }

    private function renderInbox(bool $archived)
    {
        $query = ExtendedDatabaseNotification::query()->inboxFor(auth()->user())->with('parent')->latest();
        $archived ? $query->managed() : $query->toManage();
        if (request()->boolean('unread')) { $query->unread(); }
        $notifications = $query->paginate(25)->withQueryString();
        return view('notifications::notifications.inbox', compact('notifications', 'archived'));
    }
}
