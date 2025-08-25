/**
 * WooCommerce Integration JavaScript
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public/js
 * @since      1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize WooCommerce integration
		initWooCommerceIntegration();
	});

	function initWooCommerceIntegration() {
		// Order ticket creation
		initOrderTicketCreation();
		
		// Product support
		initProductSupport();
		
		// My Account support tickets
		initMyAccountSupport();
		
		// Admin quick ticket creation
		initAdminQuickTicket();
	}

	function initOrderTicketCreation() {
		$(document).on('click', '.mets-create-order-ticket', function(e) {
			e.preventDefault();
			
			var orderId = $(this).data('order-id');
			showOrderTicketModal(orderId);
		});
	}

	function showOrderTicketModal(orderId) {
		var modal = $('<div class="mets-modal mets-order-ticket-modal">');
		var modalContent = $('<div class="mets-modal-content">');
		
		var modalHeader = $('<div class="mets-modal-header">');
		modalHeader.append('<h3>' + mets_wc_ajax.strings.create_ticket + '</h3>');
		modalHeader.append('<button type="button" class="mets-modal-close">&times;</button>');
		
		var form = $('<form id="mets-order-ticket-form">');
		form.append('<input type="hidden" name="order_id" value="' + orderId + '">');
		
		form.append(
			'<div class="mets-form-group">' +
			'<label for="mets-order-ticket-subject">' + mets_wc_ajax.strings.subject + ' <span class="required">*</span></label>' +
			'<input type="text" id="mets-order-ticket-subject" name="subject" required maxlength="255">' +
			'</div>'
		);
		
		form.append(
			'<div class="mets-form-group">' +
			'<label for="mets-order-ticket-description">' + mets_wc_ajax.strings.description + ' <span class="required">*</span></label>' +
			'<textarea id="mets-order-ticket-description" name="description" required rows="5"></textarea>' +
			'</div>'
		);
		
		form.append(
			'<div class="mets-form-group">' +
			'<label for="mets-order-ticket-priority">' + mets_wc_ajax.strings.priority + '</label>' +
			'<select id="mets-order-ticket-priority" name="priority">' +
			'<option value="low">Low</option>' +
			'<option value="medium" selected>Medium</option>' +
			'<option value="high">High</option>' +
			'<option value="critical">Critical</option>' +
			'</select>' +
			'</div>'
		);
		
		var actions = $('<div class="mets-form-actions">');
		actions.append('<button type="button" class="button mets-modal-close">Cancel</button>');
		actions.append('<button type="submit" class="button button-primary">Create Ticket</button>');
		form.append(actions);
		
		modalContent.append(modalHeader);
		modalContent.append(form);
		modal.append(modalContent);
		
		$('body').append(modal);
		modal.show();
		
		// Close modal handlers
		modal.on('click', '.mets-modal-close', function() {
			modal.remove();
		});
		
		modal.on('click', function(e) {
			if (e.target === modal[0]) {
				modal.remove();
			}
		});
		
		// Form submission
		form.on('submit', function(e) {
			e.preventDefault();
			submitOrderTicket(form, modal);
		});
	}

	function submitOrderTicket(form, modal) {
		var formData = {
			action: 'mets_create_order_ticket',
			nonce: mets_wc_ajax.nonce,
			order_id: form.find('[name="order_id"]').val(),
			subject: form.find('[name="subject"]').val(),
			description: form.find('[name="description"]').val(),
			priority: form.find('[name="priority"]').val()
		};
		
		// Validation
		if (!formData.subject.trim()) {
			showError(mets_wc_ajax.strings.subject_required);
			return;
		}
		
		if (!formData.description.trim()) {
			showError(mets_wc_ajax.strings.description_required);
			return;
		}
		
		var submitBtn = form.find('[type="submit"]');
		var originalText = submitBtn.text();
		submitBtn.prop('disabled', true).text(mets_wc_ajax.strings.creating_ticket);
		
		$.post(mets_wc_ajax.ajax_url, formData)
			.done(function(response) {
				if (response.success) {
					showSuccess(response.data.message);
					modal.remove();
					
					// Redirect if specified
					if (response.data.redirect_url) {
						setTimeout(function() {
							window.location.href = response.data.redirect_url;
						}, 1500);
					} else {
						// Refresh page to show new ticket button state
						setTimeout(function() {
							location.reload();
						}, 1500);
					}
				} else {
					showError(response.data || mets_wc_ajax.strings.error_occurred);
				}
			})
			.fail(function() {
				showError(mets_wc_ajax.strings.error_occurred);
			})
			.always(function() {
				submitBtn.prop('disabled', false).text(originalText);
			});
	}

	function initProductSupport() {
		$(document).on('click', '.mets-product-support-btn, .mets-create-product-ticket', function(e) {
			e.preventDefault();
			
			var productId = $(this).data('product-id');
			showProductSupportModal(productId);
		});
	}

	function showProductSupportModal(productId) {
		// Similar to order ticket modal but for products
		var modal = $('<div class="mets-modal mets-product-ticket-modal">');
		var modalContent = $('<div class="mets-modal-content">');
		
		var modalHeader = $('<div class="mets-modal-header">');
		modalHeader.append('<h3>Product Support</h3>');
		modalHeader.append('<button type="button" class="mets-modal-close">&times;</button>');
		
		var form = $('<form id="mets-product-ticket-form">');
		form.append('<input type="hidden" name="product_id" value="' + productId + '">');
		
		// Add form fields similar to order ticket form
		form.append(
			'<div class="mets-form-group">' +
			'<label for="mets-product-ticket-subject">Subject <span class="required">*</span></label>' +
			'<input type="text" id="mets-product-ticket-subject" name="subject" required maxlength="255">' +
			'</div>'
		);
		
		form.append(
			'<div class="mets-form-group">' +
			'<label for="mets-product-ticket-description">Description <span class="required">*</span></label>' +
			'<textarea id="mets-product-ticket-description" name="description" required rows="5"></textarea>' +
			'</div>'
		);
		
		form.append(
			'<div class="mets-form-group">' +
			'<label for="mets-product-ticket-priority">Priority</label>' +
			'<select id="mets-product-ticket-priority" name="priority">' +
			'<option value="low">Low</option>' +
			'<option value="medium" selected>Medium</option>' +
			'<option value="high">High</option>' +
			'<option value="critical">Critical</option>' +
			'</select>' +
			'</div>'
		);
		
		var actions = $('<div class="mets-form-actions">');
		actions.append('<button type="button" class="button mets-modal-close">Cancel</button>');
		actions.append('<button type="submit" class="button button-primary">Create Ticket</button>');
		form.append(actions);
		
		modalContent.append(modalHeader);
		modalContent.append(form);
		modal.append(modalContent);
		
		$('body').append(modal);
		modal.show();
		
		// Modal close handlers
		modal.on('click', '.mets-modal-close', function() {
			modal.remove();
		});
		
		modal.on('click', function(e) {
			if (e.target === modal[0]) {
				modal.remove();
			}
		});
		
		// Form submission - similar to order ticket but with product data
		form.on('submit', function(e) {
			e.preventDefault();
			// Implement product ticket creation
			modal.remove();
			showSuccess('Product support ticket functionality coming soon!');
		});
	}

	function initMyAccountSupport() {
		// New ticket modal
		$(document).on('click', '.mets-new-ticket-btn', function(e) {
			e.preventDefault();
			$('#mets-new-ticket-modal').show();
		});
		
		// Close modal
		$(document).on('click', '.mets-modal-close', function(e) {
			$(this).closest('.mets-modal').hide();
		});
		
		// Close modal on background click
		$(document).on('click', '.mets-modal', function(e) {
			if (e.target === this) {
				$(this).hide();
			}
		});
		
		// New ticket form submission
		$(document).on('submit', '#mets-new-ticket-form', function(e) {
			e.preventDefault();
			
			var form = $(this);
			var formData = {
				action: 'mets_create_order_ticket',
				nonce: mets_wc_ajax.nonce,
				order_id: form.find('[name="order_id"]').val(),
				subject: form.find('[name="subject"]').val(),
				description: form.find('[name="description"]').val(),
				priority: form.find('[name="priority"]').val()
			};
			
			// Validation
			if (!formData.subject.trim()) {
				showError(mets_wc_ajax.strings.subject_required);
				return;
			}
			
			if (!formData.description.trim()) {
				showError(mets_wc_ajax.strings.description_required);
				return;
			}
			
			var submitBtn = form.find('[type="submit"]');
			var originalText = submitBtn.text();
			submitBtn.prop('disabled', true).text(mets_wc_ajax.strings.creating_ticket);
			
			$.post(mets_wc_ajax.ajax_url, formData)
				.done(function(response) {
					if (response.success) {
						showSuccess(response.data.message);
						$('#mets-new-ticket-modal').hide();
						form[0].reset();
						
						// Refresh page to show new ticket
						setTimeout(function() {
							location.reload();
						}, 1500);
					} else {
						showError(response.data || mets_wc_ajax.strings.error_occurred);
					}
				})
				.fail(function() {
					showError(mets_wc_ajax.strings.error_occurred);
				})
				.always(function() {
					submitBtn.prop('disabled', false).text(originalText);
				});
		});
		
		// Ticket reply form
		$(document).on('submit', '#mets-ticket-reply-form', function(e) {
			e.preventDefault();
			
			var form = $(this);
			var ticketId = form.data('ticket-id');
			var content = form.find('[name="reply_content"]').val();
			
			if (!content.trim()) {
				showError('Reply content is required.');
				return;
			}
			
			var submitBtn = form.find('[type="submit"]');
			var originalText = submitBtn.text();
			submitBtn.prop('disabled', true).text('Sending...');
			
			var formData = {
				action: 'mets_customer_reply',
				nonce: mets_wc_ajax.nonce,
				ticket_id: ticketId,
				content: content
			};
			
			$.post(mets_wc_ajax.ajax_url, formData)
				.done(function(response) {
					if (response.success) {
						showSuccess('Reply sent successfully!');
						form[0].reset();
						
						// Refresh page to show new reply
						setTimeout(function() {
							location.reload();
						}, 1500);
					} else {
						showError(response.data || 'Failed to send reply.');
					}
				})
				.fail(function() {
					showError('An error occurred. Please try again.');
				})
				.always(function() {
					submitBtn.prop('disabled', false).text(originalText);
				});
		});
	}

	function initAdminQuickTicket() {
		$(document).on('click', '#mets-create-order-ticket-admin', function(e) {
			e.preventDefault();
			
			var button = $(this);
			var orderId = button.data('order-id');
			var subject = $('#mets-quick-subject').val();
			var description = $('#mets-quick-description').val();
			var priority = $('#mets-quick-priority').val();
			
			if (!subject.trim() || !description.trim()) {
				alert('Subject and description are required.');
				return;
			}
			
			var originalText = button.text();
			button.prop('disabled', true).text('Creating...');
			
			var formData = {
				action: 'mets_create_order_ticket',
				nonce: mets_wc_ajax.nonce,
				order_id: orderId,
				subject: subject,
				description: description,
				priority: priority
			};
			
			$.post(mets_wc_ajax.ajax_url, formData)
				.done(function(response) {
					if (response.success) {
						alert(response.data.message);
						
						// Clear form
						$('#mets-quick-subject, #mets-quick-description').val('');
						$('#mets-quick-priority').val('medium');
						
						// Refresh metabox content
						location.reload();
					} else {
						alert(response.data || 'Failed to create ticket.');
					}
				})
				.fail(function() {
					alert('An error occurred. Please try again.');
				})
				.always(function() {
					button.prop('disabled', false).text(originalText);
				});
		});
	}

	function showSuccess(message) {
		showNotice(message, 'success');
	}

	function showError(message) {
		showNotice(message, 'error');
	}

	function showNotice(message, type) {
		var notice = $('<div class="mets-notice mets-notice-' + type + '">');
		notice.text(message);
		
		// Add to top of page or modal
		var target = $('.mets-modal:visible .mets-modal-content');
		if (target.length === 0) {
			target = $('body');
		}
		
		target.prepend(notice);
		
		// Auto-hide after 5 seconds
		setTimeout(function() {
			notice.fadeOut(function() {
				notice.remove();
			});
		}, 5000);
		
		// Allow manual close
		notice.on('click', function() {
			notice.remove();
		});
	}

	// Add CSS for notices
	$('<style>')
		.prop('type', 'text/css')
		.html(
			'.mets-notice { ' +
			'padding: 10px 15px; ' +
			'margin: 10px 0; ' +
			'border-radius: 3px; ' +
			'cursor: pointer; ' +
			'position: relative; ' +
			'z-index: 1001; ' +
			'} ' +
			'.mets-notice-success { ' +
			'background: #d4edda; ' +
			'color: #155724; ' +
			'border: 1px solid #c3e6cb; ' +
			'} ' +
			'.mets-notice-error { ' +
			'background: #f8d7da; ' +
			'color: #721c24; ' +
			'border: 1px solid #f5c6cb; ' +
			'} ' +
			'.mets-modal { ' +
			'position: fixed; ' +
			'z-index: 1000; ' +
			'left: 0; ' +
			'top: 0; ' +
			'width: 100%; ' +
			'height: 100%; ' +
			'background-color: rgba(0,0,0,0.5); ' +
			'} ' +
			'.mets-modal-content { ' +
			'background-color: #fefefe; ' +
			'margin: 5% auto; ' +
			'padding: 0; ' +
			'border-radius: 5px; ' +
			'width: 90%; ' +
			'max-width: 600px; ' +
			'max-height: 90vh; ' +
			'overflow-y: auto; ' +
			'} ' +
			'.mets-modal-header { ' +
			'display: flex; ' +
			'justify-content: space-between; ' +
			'align-items: center; ' +
			'padding: 20px; ' +
			'background: #f1f1f1; ' +
			'border-bottom: 1px solid #ddd; ' +
			'border-radius: 5px 5px 0 0; ' +
			'} ' +
			'.mets-modal-header h3 { margin: 0; } ' +
			'.mets-modal-close { ' +
			'background: none; ' +
			'border: none; ' +
			'font-size: 28px; ' +
			'font-weight: bold; ' +
			'cursor: pointer; ' +
			'color: #999; ' +
			'} ' +
			'.mets-modal-close:hover { color: #000; } ' +
			'#mets-order-ticket-form, #mets-product-ticket-form { padding: 20px; } ' +
			'.mets-form-group { margin-bottom: 15px; } ' +
			'.mets-form-group label { ' +
			'display: block; ' +
			'margin-bottom: 5px; ' +
			'font-weight: bold; ' +
			'} ' +
			'.mets-form-group input, ' +
			'.mets-form-group select, ' +
			'.mets-form-group textarea { ' +
			'width: 100%; ' +
			'padding: 8px; ' +
			'border: 1px solid #ddd; ' +
			'border-radius: 3px; ' +
			'font-size: 14px; ' +
			'box-sizing: border-box; ' +
			'} ' +
			'.mets-form-actions { ' +
			'display: flex; ' +
			'justify-content: flex-end; ' +
			'gap: 10px; ' +
			'padding-top: 15px; ' +
			'border-top: 1px solid #ddd; ' +
			'} ' +
			'.required { color: #d63638; }'
		)
		.appendTo('head');

})(jQuery);