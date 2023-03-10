@if(($notifications ?? false)&&($count = count($notifications))&&(! session('header.notifications.hidePermanently', false)))

<script type="text/javascript">
jQuery(document).ready(function($)
{
	$('.notificationsajaxbutton').on('click', function()
	{
		let that = this;

		$.ajax({
			url: $(that).data('url'),
			type: 'POST',
			success: function(result)
			{
				window.addSuccessNotification(result.message, 1, 200);
			},
			error: function(result)
			{
				window.addErrorNotification('Error');
			}
		});
	});
});
</script>

<div class="uk-alert-danger" uk-alert>
	<a
		href="javascript:void(0)"
		data-url="{{ route('notifications.header.hidePermanently') }}"
		class="notificationsajaxbutton uk-alert-close"
		uk-close>
	</a>

	<a
	@if(session('header.notifications.visible', true))
		hidden
	@endif
		href="javascript:void(0)"
		data-url="{{ route('notifications.header.show') }}"
		data-type="POST"

		class="notificationsajaxbutton uk-button uk-button-default header-notifications-toggler"
		uk-toggle="target: .header-notifications-toggler; animation: uk-animation-fade"
		>
		@lang('notifications::notifications.showCount', [
			'count' => $count
		])
	</a>

	<a
		@if(! session('header.notifications.visible', true))
			hidden
		@endif

		href="javascript:void(0)"
		data-url="{{ route('notifications.header.hide') }}"
		data-type="POST"

		class="notificationsajaxbutton uk-button uk-button-default header-notifications-toggler" 
		uk-toggle="target: .header-notifications-toggler; animation: uk-animation-fade"
		>
		@lang('notifications::notifications.hide')
	</a>

	<div
		@if(! session('header.notifications.visible', true))
			hidden
		@endif

		class="header-notifications-toggler uk-margin-top">
		<ul class="uk-list uk-list-bullet ">
			@foreach($notifications as $notification)
			<li>{!! $notification->render() !!}</li>
			@endforeach
		</ul>
	</div>

</div>

<div>
	<div class="uk-align-right">
		<a href="{{ route('notifications.header.resetSessionCache') }}">RESET</a>
	</div>
</div>

@endif