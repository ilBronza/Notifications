@extends(config('notifications.layout', 'uikittemplate::app'))

@section('content')
<div class="uk-container uk-margin-top">
    <h1>@lang('notifications::notifications.notifications')</h1>
    <div class="uk-flex uk-flex-wrap uk-flex-middle uk-margin-bottom" style="gap: 8px">
        <a class="uk-button {{ !$archived ? 'uk-button-primary' : 'uk-button-default' }}" href="{{ route('notifications.index') }}">@lang('notifications::notifications.toManage')</a>
        <a class="uk-button {{ $archived ? 'uk-button-primary' : 'uk-button-default' }}" href="{{ route('notifications.archived') }}">@lang('notifications::notifications.archived')</a>
        <a class="uk-button uk-button-default" href="{{ route('notifications.create') }}">@lang('notifications::notifications.newNotification')</a>
        <a class="uk-button uk-button-default" href="{{ route('notifications.voice-messages.index') }}">@lang('notifications::notifications.voiceMessages')</a>
        <button class="uk-button uk-button-text" id="restore-header" type="button">@lang('notifications::notifications.restoreHeader')</button>
    </div>
    <form method="GET" class="uk-margin-small-bottom">
        <label><input class="uk-checkbox" type="checkbox" name="unread" value="1" @checked(request()->boolean('unread')) onchange="this.form.submit()"> @lang('notifications::notifications.onlyUnread')</label>
    </form>
    <p id="inbox-status" role="status" aria-live="polite"></p>
    <form id="inbox-bulk">
        @csrf
        <div class="uk-flex uk-flex-wrap uk-margin-bottom" style="gap: 8px">
            <select class="uk-select uk-width-auto" name="action" aria-label="{{ __('notifications::notifications.action') }}">
                <option value="read">@lang('notifications::notifications.markRead')</option>
                <option value="unread">@lang('notifications::notifications.markUnread')</option>
                <option value="manage">@lang('notifications::notifications.markManaged')</option>
                <option value="unmanage">@lang('notifications::notifications.markUnmanaged')</option>
            </select>
            <button class="uk-button uk-button-default" type="submit">@lang('notifications::notifications.applySelected')</button>
        </div>
        <div class="uk-overflow-auto">
            <table class="uk-table uk-table-divider uk-table-middle">
                <thead><tr>
                    <th><input class="uk-checkbox" id="select-notifications" type="checkbox" aria-label="{{ __('notifications::notifications.selectAll') }}"></th>
                    <th>@lang('notifications::notifications.message')</th><th>@lang('notifications::notifications.date')</th><th>@lang('notifications::notifications.state')</th>
                </tr></thead>
                <tbody>
                @forelse($notifications as $notification)
                    <tr>
                        <td><input class="uk-checkbox" type="checkbox" name="ids[]" value="{{ $notification->id }}" aria-label="{{ __('notifications::notifications.selectNotification') }}"></td>
                        <td>
                            @include('notifications::notifications._notification')
                            <a href="{{ route('notifications.show', $notification) }}">@lang('notifications::notifications.details')</a>
                        </td>
                        <td>{{ $notification->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $notification->read_at ? __('notifications::notifications.read') : __('notifications::notifications.unread') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">@lang('notifications::notifications.noNotifications')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>
    <nav aria-label="{{ __('notifications::notifications.pagination') }}" class="uk-margin">
        @if($notifications->previousPageUrl())<a class="uk-button uk-button-default" href="{{ $notifications->previousPageUrl() }}">@lang('notifications::notifications.previous')</a>@endif
        @if($notifications->nextPageUrl())<a class="uk-button uk-button-default" href="{{ $notifications->nextPageUrl() }}">@lang('notifications::notifications.next')</a>@endif
    </nav>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('inbox-bulk');
    const status = document.getElementById('inbox-status');
    const errorMessage = {{ Illuminate\Support\Js::from(__('notifications::notifications.operationFailed')) }};
    document.getElementById('select-notifications').addEventListener('change', function () {
        form.querySelectorAll('[name="ids[]"]').forEach(box => box.checked = this.checked);
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const ids = Array.from(form.querySelectorAll('[name="ids[]"]:checked')).map(box => box.value);
        if (!ids.length) return;
        const button = form.querySelector('[type="submit"]'); button.disabled = true;
        try {
            const response = await fetch({{ Illuminate\Support\Js::from(route('notifications.bulk')) }}, {
                method: 'PATCH', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: JSON.stringify({ids, action: form.elements.action.value})
            });
            if (!response.ok) throw new Error();
            window.location.reload();
        } catch (error) { status.textContent = errorMessage; button.disabled = false; }
    });
    document.getElementById('restore-header').addEventListener('click', async function () {
        try {
            const response = await fetch({{ Illuminate\Support\Js::from(route('notifications.header.show')) }}, {
                method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}
            });
            if (!response.ok) throw new Error();
            window.location.reload();
        } catch (error) { status.textContent = errorMessage; }
    });
});
</script>
@endsection
