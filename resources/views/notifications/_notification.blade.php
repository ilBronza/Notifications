<div class="notification">
	@if(isset($notification->data['audio_url']))
		@if($message = $notification->getMessage())
			<p class="uk-margin-small-bottom">{!! $message !!}</p>
		@endif
		<audio controls src="{{ $notification->data['audio_url'] }}" class="uk-width-1-1" style="max-width: 300px;"></audio>
	@elseif($message = $notification->getMessage())
		{!! $message !!}
	@else
		{!! is_array($notification->data) ? json_encode($notification->data) : $notification->data !!}
	@endif
</div>