<div class="notification">
	@if($message = $notification->getMessage())
		{!! $message !!}
	@else
		{!! $notification->data !!}
	@endif
</div>