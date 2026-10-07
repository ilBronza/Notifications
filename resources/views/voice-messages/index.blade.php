@extends(config('notifications.layout', 'uikittemplate::app'))

@section('content')
<div class="uk-container uk-margin-top">
    <h1 class="uk-heading-medium">@lang('notifications::notifications.voiceMessages')</h1>
    <p class="uk-text-muted">@lang('notifications::notifications.usersList')</p>

    @if(session('success'))
        <div class="uk-alert-success" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="uk-alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="GET" class="uk-margin-bottom">
        <input class="uk-input" name="q" maxlength="100" value="{{ request('q') }}" placeholder="{{ __('notifications::notifications.searchUsers') }}">
        <button class="uk-button uk-button-default">@lang('notifications::notifications.search')</button>
    </form>
    @if($users->isEmpty())<p>@lang('notifications::notifications.noUsers')</p>@endif
    <div class="uk-grid uk-grid-small uk-child-width-1-2 uk-child-width-1-4@s uk-child-width-1-6@m" uk-grid>
        @foreach($users as $user)
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-card-small uk-card-hover voice-user-card" style="cursor: pointer;"
                     data-user-id="{{ $user->getKey() }}"
                     data-user-name="{{ $user->name ?? $user->email ?? 'Utente #' . $user->id }}">
                    <div class="uk-text-center">
                        <span class="uk-icon-button uk-margin-small-bottom" uk-icon="user"></span>
                        <p class="uk-margin-remove uk-text-small">{{ $user->name ?? $user->email ?? 'Utente #' . $user->id }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <nav class="uk-margin" aria-label="{{ __('notifications::notifications.pagination') }}">
        @if($users->previousPageUrl())<a class="uk-button uk-button-default" href="{{ $users->previousPageUrl() }}">@lang('notifications::notifications.previous')</a>@endif
        @if($users->nextPageUrl())<a class="uk-button uk-button-default" href="{{ $users->nextPageUrl() }}">@lang('notifications::notifications.next')</a>@endif
    </nav>
</div>

<div id="voice-message-modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <button class="uk-modal-close-default" type="button" uk-close></button>
        <h2 class="uk-modal-title">
            <span id="voice-message-target-name"></span>
        </h2>

        <form id="voice-message-form" action="{{ route('notifications.voice-messages.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="user_id" id="voice-message-user-id">

            <div class="uk-margin">
                <p id="voice-error" role="alert" class="uk-text-danger" hidden></p>
            <div id="recording-controls" class="uk-button-group">
                    <button type="button" id="record-btn" class="uk-button uk-button-primary">
                        <span uk-icon="mic"></span>
                        @lang('notifications::notifications.startRecording')
                    </button>
                    <button type="button" id="stop-btn" class="uk-button uk-button-danger" disabled>
                        <span uk-icon="ban"></span>
                        @lang('notifications::notifications.stopRecording')
                    </button>
                </div>
                <p id="recording-status" class="uk-text-small uk-text-muted uk-margin-small-top" style="display: none;">
                    @lang('notifications::notifications.recording')
                </p>
            </div>

            <div id="audio-preview" class="uk-margin" style="display: none;">
                <audio id="audio-playback" controls class="uk-width-1-1"></audio>
            </div>

            <input type="file" name="audio" id="audio-file-input" accept="audio/*" style="display: none;">

            <div class="uk-margin uk-margin-top">
                <button type="submit" id="send-btn" class="uk-button uk-button-primary" disabled>
                    @lang('notifications::notifications.send')
                </button>
                <button type="button" class="uk-button uk-button-default uk-modal-close">
                    @lang('notifications::notifications.cancel')
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('voice-message-modal');
    const form = document.getElementById('voice-message-form');
    const record = document.getElementById('record-btn');
    const stop = document.getElementById('stop-btn');
    const send = document.getElementById('send-btn');
    const status = document.getElementById('recording-status');
    const preview = document.getElementById('audio-preview');
    const playback = document.getElementById('audio-playback');
    const fileInput = document.getElementById('audio-file-input');
    const error = document.getElementById('voice-error');
    const messages = {{ Illuminate\Support\Js::from([
        'sendTo' => __('notifications::notifications.sendTo'),
        'microphone' => __('notifications::notifications.microphoneError'),
        'unsupported' => __('notifications::notifications.recordingUnsupported'),
        'tooLarge' => __('notifications::notifications.audioTooLarge'),
    ]) }};
    const maxBytes = {{ (int) config('notifications.voiceMessages.max_kilobytes', 5120) }} * 1024;
    const maxSeconds = {{ (int) config('notifications.voiceMessages.max_seconds', 120) }};
    let recorder, stream, chunks = [], url, timer, discard = false, bytes = 0;
    function fail(message) { error.textContent = message; error.hidden = false; }
    function release() {
        clearTimeout(timer);
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    function finish() {
        clearTimeout(timer);
        if (recorder && recorder.state !== 'inactive') recorder.stop();
        else release();
        record.disabled = false; stop.disabled = true; status.style.display = 'none';
    }
    function reset() {
        discard = true;
        finish(); release();
        playback.pause(); playback.removeAttribute('src');
        if (url) URL.revokeObjectURL(url);
        url = null; fileInput.value = ''; send.disabled = true; preview.style.display = 'none';
    }
    document.querySelectorAll('.voice-user-card').forEach(card => {
        card.setAttribute('tabindex', '0'); card.setAttribute('role', 'button');
        function open() {
            reset(); error.hidden = true;
            document.getElementById('voice-message-user-id').value = card.dataset.userId;
            document.getElementById('voice-message-target-name').textContent = messages.sendTo.replace(':name', card.dataset.userName);
            UIkit.modal(modal).show();
        }
        card.addEventListener('click', open);
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(); }
        });
    });
    record.addEventListener('click', async function () {
        if (!navigator.mediaDevices || !window.MediaRecorder) return fail(messages.unsupported);
        record.disabled = true; error.hidden = true; discard = false;
        try {
            stream = await navigator.mediaDevices.getUserMedia({audio: true});
            if (discard) { release(); record.disabled = false; return; }
            const mime = ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4'].find(type => MediaRecorder.isTypeSupported(type));
            recorder = mime ? new MediaRecorder(stream, {mimeType: mime}) : new MediaRecorder(stream);
            chunks = []; bytes = 0; send.disabled = true;
            recorder.ondataavailable = event => {
                if (discard || !event.data.size) return;
                bytes += event.data.size;
                if (bytes > maxBytes) { discard = true; finish(); fail(messages.tooLarge); return; }
                chunks.push(event.data);
            };
            recorder.onstop = function () {
                release();
                if (discard || !chunks.length) return;
                const type = recorder.mimeType || chunks[0].type;
                const extension = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
                const blob = new Blob(chunks, {type});
                if (url) URL.revokeObjectURL(url);
                url = URL.createObjectURL(blob); playback.src = url; preview.style.display = 'block';
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'recording.' + extension, {type}));
                fileInput.files = transfer.files; send.disabled = false;
            };
            recorder.onerror = function () { discard = true; finish(); release(); fail(messages.microphone); };
            recorder.start(250); stop.disabled = false; status.style.display = 'block';
            timer = setTimeout(finish, Math.max(1, maxSeconds) * 1000);
        } catch (exception) { release(); record.disabled = false; fail(messages.microphone); }
    });
    stop.addEventListener('click', finish);
    modal.addEventListener('beforehide', reset);
    window.addEventListener('pagehide', reset);
    form.addEventListener('submit', event => {
        if (!fileInput.files.length || send.disabled) { event.preventDefault(); return; }
        send.disabled = true;
    });
});
</script>
@endsection
