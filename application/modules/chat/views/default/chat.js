$(document).ready(function () {
	
	var block = 0;

	function send_message() {
		var message_text = $('#chat_text_input').val();
		var server_id = $('#server_id').val();
		if (message_text != "")
		{
			$.ajax(
			{
				url: '{site_url}chat/ajax/send',
				type: 'POST',
				data:
				{
					'message_text': message_text,
					'server_id': server_id,
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
				},
				dataType: 'html',
				success: function (result)
				{
					$('#chat_text_input').val('');
					//~ get_chat_messages();
				}
			});
		}
	}

	function get_chat_messages() {
			
		if (block == 0) {

			block = 1;
			
			var last_act = $('#last_act').val();
			var server_id = $('#server_id').val();
			$.ajax(
			{
				url: '{site_url}chat/ajax/get',
				type: 'POST',
				data:
				{
					'last_act': last_act,
					'server_id': server_id,
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
				},
				dataType: 'json',
				success: function (result)
				{
					if(result.messages != '') {
						$('#chat_text_field').html(result.messages);
					} else {
						$('#chat_text_field').html('No messages');
					}
					
					$('#chat_text_field').scrollTop($('#chat_text_field').scrollTop()+100*$('.chat_post_my, .chat_post_other').size()); 

					block = 0;
				}
			});
		}
	}

	if ($("#chat_text_input").size()>0)
	{
		$("#chat_text_input").focus();
	}
	
	$('#chat_text_input').keyup(function(event)
	{
		if (event.which == 13)
		{
			send_message();
		}
	});
	
	$('#chat_button').click(function()
	{
		send_message();
	});
	
	setInterval(function(){get_chat_messages();}, 20000);
	window.onload = get_chat_messages();
	
	$('#chat_text_field').scrollTop($('#chat_text_field').scrollTop()+100*$('.chat_post_other').size()); 
});
