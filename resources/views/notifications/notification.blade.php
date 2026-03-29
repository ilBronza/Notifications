@php
    $notification = $element ?? $notification ?? null;
@endphp
@if($notification)
    {!! $notification->render() !!}
@endif
