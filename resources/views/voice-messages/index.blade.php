@extends('uikittemplate::app')

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

    <div class="uk-grid uk-grid-small uk-child-width-1-2 uk-child-width-1-4@s uk-child-width-1-6@m" uk-grid>
        @foreach($users as $user)
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-card-small uk-card-hover voice-user-card" style="cursor: pointer;"
                     data-user-id="{{ $user->id }}"
                     data-user-name="{{ $user->name ?? $user->email ?? 'Utente #' . $user->id }}">
                    <div class="uk-text-center">
                        <span class="uk-icon-button uk-margin-small-bottom" uk-icon="user"></span>
                        <p class="uk-margin-remove uk-text-small">{{ $user->name ?? $user->email ?? 'Utente #' . $user->id }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
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

            <input type="file" name="audio" id="audio-file-input" accept="audio/*" style="display: none;" required>

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
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('voice-message-modal');
    const form = document.getElementById('voice-message-form');
    const recordBtn = document.getElementById('record-btn');
    const stopBtn = document.getElementById('stop-btn');
    const sendBtn = document.getElementById('send-btn');
    const recordingStatus = document.getElementById('recording-status');
    const audioPreview = document.getElementById('audio-preview');
    const audioPlayback = document.getElementById('audio-playback');
    const audioFileInput = document.getElementById('audio-file-input');
    const targetNameEl = document.getElementById('voice-message-target-name');
    const userIdInput = document.getElementById('voice-message-user-id');

    let mediaRecorder = null;
    let audioChunks = [];

    document.querySelectorAll('.voice-user-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            userIdInput.value = userId;
            targetNameEl.textContent = '{{ __("notifications::notifications.sendTo") }}'.replace(':name', userName);

            audioChunks = [];
            audioPreview.style.display = 'none';
            audioFileInput.value = '';
            sendBtn.disabled = true;

            if (typeof UIkit !== 'undefined') {
                UIkit.modal(document.getElementById('voice-message-modal')).show();
            } else {
                document.getElementById('voice-message-modal').style.display = 'block';
            }
        });
    });

    recordBtn.addEventListener('click', async function() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = function(e) {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = function() {
                stream.getTracks().forEach(track => track.stop());
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const url = URL.createObjectURL(audioBlob);
                audioPlayback.src = url;
                audioPreview.style.display = 'block';

                const file = new File([audioBlob], 'recording.webm', { type: 'audio/webm' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                audioFileInput.files = dataTransfer.files;
                sendBtn.disabled = false;
            };

            mediaRecorder.start();
            recordBtn.disabled = true;
            stopBtn.disabled = false;
            recordingStatus.style.display = 'block';
        } catch (err) {
            console.error('Errore accesso microfono:', err);
            alert('Impossibile accedere al microfono. Verifica i permessi del browser.');
        }
    });

    stopBtn.addEventListener('click', function() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        recordBtn.disabled = false;
        stopBtn.disabled = true;
        recordingStatus.style.display = 'none';
    });
});
</script>
@endsection
