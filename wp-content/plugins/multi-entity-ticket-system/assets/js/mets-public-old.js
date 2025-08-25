/**
 * Multi-Entity Ticket System Public JavaScript
 *
 * @package MultiEntityTicketSystem
 * @since   1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize public functionality
	 */
	$(document).ready(function() {
		// Entity search functionality
		var searchTimeout;
		var selectedEntityIndex = -1;
		
		$('.mets-entity-search').on('input', function() {
			var searchInput = $(this);
			var searchTerm = searchInput.val().trim();
			var resultsContainer = $('.mets-entity-results');
			var resultsContent = $('.mets-entity-results-content');
			
			// Clear previous timeout
			clearTimeout(searchTimeout);
			
			// Reset selection
			selectedEntityIndex = -1;
			
			if (searchTerm.length < 2) {
				resultsContainer.hide();
				$('#entity_id').val('');
				searchInput.removeClass('has-value');
				return;
			}
			
			// Show loading
			resultsContent.html('<div class="mets-loading-entities">Searching...</div>');
			resultsContainer.show();
			
			// Debounce search
			searchTimeout = setTimeout(function() {
				performEntitySearch(searchTerm);
			}, 300);
		});
		
		// Handle keyboard navigation
		$('.mets-entity-search').on('keydown', function(e) {
			var resultsContainer = $('.mets-entity-results');
			var items = resultsContainer.find('.mets-entity-item');
			
			if (!resultsContainer.is(':visible') || items.length === 0) {
				return;
			}
			
			switch(e.keyCode) {
				case 38: // Up arrow
					e.preventDefault();
					selectedEntityIndex = Math.max(0, selectedEntityIndex - 1);
					updateSelection(items);
					break;
				case 40: // Down arrow
					e.preventDefault();
					selectedEntityIndex = Math.min(items.length - 1, selectedEntityIndex + 1);
					updateSelection(items);
					break;
				case 13: // Enter
					e.preventDefault();
					if (selectedEntityIndex >= 0) {
						selectEntity(items.eq(selectedEntityIndex));
					}
					break;
				case 27: // Escape
					resultsContainer.hide();
					selectedEntityIndex = -1;
					break;
			}
		});
		
		// Handle clicking outside to close results
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.mets-entity-search-wrapper').length) {
				$('.mets-entity-results').hide();
				selectedEntityIndex = -1;
			}
		});
		
		// Entity selection
		$(document).on('click', '.mets-entity-item', function() {
			selectEntity($(this));
		});
		
		function performEntitySearch(searchTerm) {
			$.ajax({
				url: mets_public_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'mets_search_entities_public',
					nonce: mets_public_ajax.nonce,
					search: searchTerm
				},
				success: function(response) {
					if (response.success) {
						displaySearchResults(response.data.entities);
					} else {
						$('.mets-entity-results-content').html('<div class="mets-no-results">Search failed: ' + (response.data.message || 'Please try again.') + '</div>');
					}
				},
				error: function(xhr, status, error) {
					$('.mets-entity-results-content').html('<div class="mets-no-results">Network error. Please try again.</div>');
				}
			});
		}
		
		function displaySearchResults(entities) {
			var resultsContent = $('.mets-entity-results-content');
			
			if (entities.length === 0) {
				resultsContent.html('<div class="mets-no-results">No departments found matching your search.</div>');
				return;
			}
			
			var html = '';
			entities.forEach(function(entity) {
				html += '<div class="mets-entity-item" data-entity-id="' + entity.id + '">';
				html += '<div class="mets-entity-name">' + escapeHtml(entity.name) + '</div>';
				if (entity.description) {
					html += '<div class="mets-entity-description">' + escapeHtml(entity.description) + '</div>';
				}
				if (entity.parent_name) {
					html += '<div class="mets-entity-parent">Part of: ' + escapeHtml(entity.parent_name) + '</div>';
				}
				html += '</div>';
			});
			
			resultsContent.html(html);
		}
		
		function updateSelection(items) {
			items.removeClass('selected');
			if (selectedEntityIndex >= 0) {
				items.eq(selectedEntityIndex).addClass('selected');
			}
		}
		
		function selectEntity(item) {
			var entityId = item.data('entity-id');
			var entityName = item.find('.mets-entity-name').text();
			
			$('#entity_id').val(entityId);
			$('.mets-entity-search').val(entityName).addClass('has-value');
			$('.mets-entity-results').hide();
			selectedEntityIndex = -1;
		}
		
		function escapeHtml(text) {
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, function(m) { return map[m]; });
		}

		// Ticket form submission
		$('#mets-ticket-form').on('submit', function(e) {
			e.preventDefault();
			
			var form = $(this);
			var submitButton = form.find('.mets-submit-button');
			var loadingSpinner = form.find('.mets-loading');
			var successMessage = form.find('.mets-success-message');
			var errorMessage = form.find('.mets-error-message');
			
			// Clear previous messages
			successMessage.hide().empty();
			errorMessage.hide().empty();
			
			// Validate form
			var isValid = true;
			form.find('[required]').each(function() {
				var field = $(this);
				if (!field.val().trim()) {
					// Special handling for entity search
					if (field.attr('id') === 'entity_id') {
						$('.mets-entity-search').addClass('mets-field-error');
					} else {
						field.addClass('mets-field-error');
					}
					isValid = false;
				} else {
					// Clear error state
					if (field.attr('id') === 'entity_id') {
						$('.mets-entity-search').removeClass('mets-field-error');
					} else {
						field.removeClass('mets-field-error');
					}
				}
			});
			
			// Email validation
			var emailField = form.find('input[type="email"]');
			if (emailField.length > 0) {
				var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if (!emailPattern.test(emailField.val())) {
					emailField.addClass('mets-field-error');
					isValid = false;
				}
			}
			
			if (!isValid) {
				errorMessage.text(mets_public_ajax.error_messages.required_fields || 'Please fill in all required fields correctly.').show();
				return false;
			}
			
			// Show loading state
			submitButton.prop('disabled', true);
			loadingSpinner.show();
			
			// Prepare form data (including files)
			var formData = new FormData(form[0]);
			formData.append('action', 'mets_submit_ticket');
			// The nonce is already in the form as mets_ticket_nonce
			
			// Submit via AJAX
			
			$.ajax({
				url: mets_public_ajax.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				beforeSend: function() {
					console.log('AJAX request starting...');
				},
				success: function(response) {
					console.log('AJAX success response:', response);
					loadingSpinner.hide();
					submitButton.prop('disabled', false);
					
					if (response.success) {
						// Show success message
						var message = response.data.message;
						if (response.data.ticket_number) {
							message += ' ' + (mets_public_ajax.ticket_number_text || 'Ticket number:') + ' <strong>' + response.data.ticket_number + '</strong>';
						}
						
						// Add file upload information
						if (response.data.files_uploaded) {
							message += '<br>' + response.data.upload_message;
						}
						
						// Add upload warnings if any
						if (response.data.upload_warnings && response.data.upload_warnings.length > 0) {
							message += '<div class="mets-upload-warnings" style="margin-top: 10px; color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px;">';
							message += '<strong>File upload warnings:</strong><ul>';
							response.data.upload_warnings.forEach(function(warning) {
								message += '<li>' + warning + '</li>';
							});
							message += '</ul></div>';
						}
						
						successMessage.html(message).show();
						
						// Reset form and file preview
						form[0].reset();
						$('.mets-file-preview').hide();
						$('.mets-file-list').empty();
						
						// Redirect if specified
						if (response.data.redirect) {
							setTimeout(function() {
								window.location.href = response.data.redirect;
							}, 2000);
						}
					} else {
						errorMessage.text(response.data.message || 'An error occurred. Please try again.').show();
					}
				},
				error: function(xhr, status, error) {
					console.log('AJAX error:', xhr, status, error);
					console.log('Response text:', xhr.responseText);
					loadingSpinner.hide();
					submitButton.prop('disabled', false);
					errorMessage.text('An error occurred. Please try again later.').show();
					console.error('AJAX Error:', error);
				}
			});
		});

		// File upload handling (placeholder for Phase 3)
		$('.mets-file-upload').on('change', function() {
			var files = this.files;
			console.log('Files selected:', files.length);
			// Implement file upload logic in Phase 3
		});
	});

	/**
	 * AJAX helper function
	 */
	function makeAjaxCall(action, data, callback) {
		$.ajax({
			url: mets_public_ajax.ajax_url,
			type: 'POST',
			data: $.extend({
				action: action,
				nonce: mets_public_ajax.nonce
			}, data),
			success: function(response) {
				if (response.success) {
					callback(response.data);
				} else {
					console.error('AJAX Error:', response.data);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Request Failed:', error);
			}
		});
	}

	// File Upload Preview Functionality
	$(document).on('change', '#ticket_attachments', function() {
		var files = this.files;
		var fileList = $('.mets-file-list');
		var filePreview = $('.mets-file-preview');
		
		// Clear previous files
		fileList.empty();
		
		if (files.length > 0) {
			filePreview.show();
			
			Array.from(files).forEach(function(file, index) {
				var fileSize = formatFileSize(file.size);
				var fileExtension = getFileExtension(file.name);
				var fileIconClass = getFileIconClass(fileExtension);
				
				var fileItem = $('<div class="mets-file-item">' +
					'<div class="mets-file-icon ' + fileIconClass + '"></div>' +
					'<div class="mets-file-info">' +
						'<div class="mets-file-name">' + file.name + '</div>' +
						'<div class="mets-file-size">' + fileSize + '</div>' +
					'</div>' +
					'<button type="button" class="mets-file-remove" data-index="' + index + '">Remove</button>' +
				'</div>');
				
				fileList.append(fileItem);
			});
		} else {
			filePreview.hide();
		}
	});
	
	// Remove file functionality
	$(document).on('click', '.mets-file-remove', function() {
		var index = $(this).data('index');
		var fileInput = $('#ticket_attachments')[0];
		var files = Array.from(fileInput.files);
		
		// Remove the file at the specified index
		files.splice(index, 1);
		
		// Create new FileList and update input
		var dt = new DataTransfer();
		files.forEach(function(file) {
			dt.items.add(file);
		});
		fileInput.files = dt.files;
		
		// Trigger change event to update preview
		$(fileInput).trigger('change');
	});
	
	// Helper functions for file handling
	function formatFileSize(bytes) {
		if (bytes === 0) return '0 B';
		var sizes = ['B', 'KB', 'MB', 'GB'];
		var i = Math.floor(Math.log(bytes) / Math.log(1024));
		return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
	}
	
	function getFileExtension(filename) {
		return filename.split('.').pop().toLowerCase();
	}
	
	function getFileIconClass(extension) {
		var icons = {
			'pdf': 'mets-icon-pdf',
			'doc': 'mets-icon-doc',
			'docx': 'mets-icon-doc',
			'xls': 'mets-icon-excel',
			'xlsx': 'mets-icon-excel',
			'ppt': 'mets-icon-ppt',
			'pptx': 'mets-icon-ppt',
			'txt': 'mets-icon-text',
			'zip': 'mets-icon-zip',
			'rar': 'mets-icon-zip',
			'jpg': 'mets-icon-image',
			'jpeg': 'mets-icon-image',
			'png': 'mets-icon-image',
			'gif': 'mets-icon-image',
			'svg': 'mets-icon-image'
		};
		
		return icons[extension] || 'mets-icon-file';
	}

})(jQuery);