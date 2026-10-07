<div class="notification">
    @if($message = $notification->getMessage())
        @if(config('notifications.allow_html', false))
            <div>{!! $message !!}</div>
        @else
            <div style="white-space: pre-wrap">{{ $message }}</div>
        @endif
    @elseif(!$notification->getAudioUrl() && ($data = $notification->getData()))
        <pre>{{ is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $data }}</pre>
    @endif
    @if($audioUrl = $notification->getAudioUrl())
        <audio controls preload="none" src="{{ $audioUrl }}" class="uk-width-1-1" style="max-width: 300px;"></audio>
    @endif
</div>
