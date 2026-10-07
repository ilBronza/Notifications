@extends(config('notifications.layout', 'uikittemplate::app'))

@section('content')
<div class="uk-container uk-margin-top">
    <h1>@lang('notifications::notifications.newNotification')</h1>
    @if($errors->any())
        <div class="uk-alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="GET" class="uk-margin-bottom">
        <input class="uk-input" name="q" value="{{ request('q') }}" placeholder="{{ __('notifications::notifications.searchUsers') }}">
        <button class="uk-button uk-button-default">@lang('notifications::notifications.search')</button>
    </form>
    <form class="uk-form-stacked" method="POST" action="{{ route('notifications.store') }}">
        @csrf
        <label class="uk-form-label" for="user_id">@lang('notifications::notifications.recipient')</label>
        <select class="uk-select" id="user_id" name="user_id" required>
            <option value="">@lang('notifications::notifications.selectRecipient')</option>
            @foreach($users as $user)<option value="{{ $user->getKey() }}" @selected((string) old('user_id') === (string) $user->getKey())>{{ $user->name }}</option>@endforeach
        </select>
        <label class="uk-form-label" for="message">@lang('notifications::notifications.message')</label>
        <textarea class="uk-textarea" id="message" name="message" maxlength="10000" required>{{ old('message') }}</textarea>
        <label class="uk-form-label" for="managed">@lang('notifications::notifications.managementNote')</label>
        <input class="uk-input" id="managed" name="managed" maxlength="255" value="{{ old('managed') }}">
        <button class="uk-button uk-button-primary uk-margin-top" type="submit">@lang('notifications::notifications.send')</button>
    </form>
</div>
@endsection
