(function( $ ) {
	'use strict';

	$(document).ready(function() {
		let modelMessage = $('#model_message');
		let spanCounterModelMessage = $('#description-description');

		if(modelMessage.length > 0) {
			modelMessage.keyup(updateCounterModelMessage);
			updateCounterModelMessage();
		}

		function updateCounterModelMessage() {
			let modelMessageVal = modelMessage.val();
			let messageLength = modelMessageVal.length;
			let smsUsed = 1;

			if(messageLength > 160) {
				smsUsed = Math.ceil(messageLength / 153);
			}

			if(messageLength > 160 && messageLength < 306) {
				smsUsed = 2;
			}

			spanCounterModelMessage.text(messageLength + ' caractères, soit ' + smsUsed + ' SMS utilisé' + (smsUsed > 1 ? 's' : ''));
		}

		function showSuccessMessage(message) {
			$('#conexteo_message').html('<div class="notice notice-success"><p>' + message + '</p><button type="button" class="notice-dismiss"></div>');
		}

		function showErrorMessage(message) {
			$('#conexteo_message').html('<div class="notice notice-error"><p>' + message + '</p><button type="button" class="notice-dismiss"></div>');
		}

		let url_cron = $('#sync_now').data('url');
		$('#sync_now').click(function() {
			$('#sync_now').addClass('active disabled');
			$.ajax({
				url: url_cron,
				type: 'GET',
				dataType: 'json',
				success: function(data) {
					if (data.status === 'success') {
						showSuccessMessage('Synchronisation effectuée avec succés');
					} else {
						showErrorMessage('Une erreur est survenue lors de la synchronisation');
					}
					$('#sync_now').removeClass('active disabled');
				}
			});
		});

		$('#send-msg-panel').click(function() {
			if($('#conexteo_send_message').is(':visible')) {
				$('#conexteo_send_message').hide();
			}
			else {
				$('#conexteo_send_message').show();
			}

		});

		let spanCounterMessage = $('#conexteo_message-counter-sms');
		let messageToSend = $('#message-to-send');
		let smsUsed = 1;
		messageToSend.keyup(function() {
			let messageToSendVal = messageToSend.val();
			let messageLength = messageToSendVal.length;

			if(messageLength > 160) {
				smsUsed = Math.ceil(messageLength / 153);
			}

			if(messageLength > 160 && messageLength < 306) {
				smsUsed = 2;
			}

			spanCounterMessage.text(messageLength + ' caractères, soit ' + smsUsed + ' SMS utilisé' + (smsUsed > 1 ? 's' : '') + ' par destinataire');
			smsUsed = 1;
		});

		let strStop = ' #STOP_XXX#';
		let checkboxStopSendMessage = $('#message-to-send-stop');
		checkboxStopSendMessage.change(function() {
			if(checkboxStopSendMessage.is(':checked')) {
				messageToSend.val(messageToSend.val() + strStop);
			} else {
				messageToSend.val(messageToSend.val().replace(strStop, ''));
			}
			// simulate key up
			messageToSend.trigger('keyup');
		});

		let btn_send = $('#send-message');
		btn_send.click(function(event) {
			//prevent default
			event.preventDefault();
			let url = btn_send.data('url');
			let sender = $('#sender');
			let contact_list = $('#contactlist-to-send');

			if (messageToSend.val()) {
				$.ajax({
					url: url,
					type: 'POST',
					data: {
						message: messageToSend.val(),
						sender: sender.val(),
						contact_list: contact_list.val()
					},
					dataType: 'json',
					success: function(data) {
						console.log(data);
						if (data.status === 'success') {
							let creditsUsed = data.message * smsUsed;
							showSuccessMessage('Message envoyé avec succés, ' + creditsUsed + ' crédits ont été débités');
							messageToSend.val('');
						} else {
							showErrorMessage('Une erreur est survenue lors de l\'envoi du message');
						}
					}
				});
			} else {
				showErrorMessage('Veuillez saisir un message');
			}
		});

		let formModels = $('#conexteo_models_form');
		let formModelsMessage = formModels.find('#message');
		let messageHelpP = formModelsMessage.parent().find('p');

		let checkboxStopSendModelsMessage = $('select[name="model_stop"]');
		checkboxStopSendModelsMessage.change(function() {
			if(checkboxStopSendModelsMessage.val() == 1) {
				modelMessage.val(modelMessage.val() + strStop);
			} else {
				modelMessage.val(modelMessage.val().replace(strStop, ''));
			}
			// simulate key up
			modelMessage.trigger('keyup');
		});

		formModelsMessage.keyup(function() {
			let message = formModelsMessage.val();
			let messageLength = message.length;
			let smsUsed = 1;

			if(messageLength > 160) {
				smsUsed = Math.ceil(messageLength / 153);
			}

			if(messageLength > 160 && messageLength < 306) {
				smsUsed = 2;
			}

			messageHelpP.text(messageLength + ' caractères, soit ' + smsUsed + ' SMS utilisé' + (smsUsed > 1 ? 's' : ''));
		});


		let status_dynamic_display = $('#status_dynamic_display');
		if(status_dynamic_display.length > 0) {
			// hide it if select with name has value "cart"
			if($('select[name="model_event_type"]').val() === 'cart') {
				status_dynamic_display.hide();
			}
			else {
				status_dynamic_display.show();
			}

			$('select[name="model_event_type"]').change(function() {
				if($(this).val() === 'cart') {
					status_dynamic_display.hide();
				}
				else {
					status_dynamic_display.show();
				}
			});
		}


		let formConexteoOrder = $('#post');
		let formConexteoOrderMessage = formConexteoOrder.find('#conexteo_order-order-message');
		let formConexteoOrderSender = formConexteoOrder.find('#conexteo_order-order-sender');
		let messageOrderHelpSpan = formConexteoOrder.find('.conexteo_order-count-sms');
		let sendButton = formConexteoOrder.find('#conexteo_order-send');

		let smsUsedOrderMessage = 1;
		formConexteoOrderMessage.keyup(function() {
			let message = formConexteoOrderMessage.val();
			let messageLength = message.length;

			if(messageLength > 160) {
				smsUsedOrderMessage = Math.ceil(messageLength / 153);
			}

			if(messageLength > 160 && messageLength < 306) {
				smsUsedOrderMessage = 2;
			}

			messageOrderHelpSpan.text(messageLength + ' carac. = ' + smsUsedOrderMessage + ' SMS');
		});

		sendButton.click(function(event) {
			//prevent default
			event.preventDefault();
			let url = sendButton.data('url');
			let sender = formConexteoOrderSender.val();
			let message = formConexteoOrderMessage.val();

			if (message) {
				$.ajax({
					url: url,
					type: 'POST',
					data: {
						message: message,
						sender: sender,
					},
					dataType: 'json',
					success: function(data) {
						if (data.status === 'success') {
							showSuccessMessage('Message envoyé avec succés, ' + smsUsedOrderMessage + ' crédit(s) débité(s)');
							formConexteoOrderMessage.val('');
							smsUsedOrderMessage = 1;
						} else {
							showErrorMessage('Une erreur est survenue lors de l\'envoi du message');
						}
					}
				});
			} else {
				showErrorMessage('Veuillez saisir un message');
			}
		});

		let checkboxStopSendOrderMessage = $('#conexteo_order-order-stop');
		checkboxStopSendOrderMessage.change(function() {
			if(checkboxStopSendOrderMessage.is(':checked')) {
				formConexteoOrderMessage.val(formConexteoOrderMessage.val() + strStop);
			} else {
				formConexteoOrderMessage.val(formConexteoOrderMessage.val().replace(strStop, ''));
			}
			// simulate key up
			formConexteoOrderMessage.trigger('keyup');
		});
	});

})( jQuery );
