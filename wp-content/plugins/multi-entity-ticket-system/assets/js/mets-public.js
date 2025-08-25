(function($) {
	'use strict';

	$(document).ready(function() {
		
		// Entity search functionality
		var searchTimeout;
		var kbSearchTimeout;
		
		$(document).on('input', '.mets-entity-search', function() {
			var searchInput = $(this);
			var searchTerm = searchInput.val().trim();
			var dropdown = searchInput.siblings('.mets-entity-results');
			var hiddenInput = searchInput.siblings('input[name="entity_id"]');
			
			// Clear previous timeout
			clearTimeout(searchTimeout);
			
			if (searchTerm.length < 2) {
				dropdown.hide().find('.mets-entity-results-content').empty();
				hiddenInput.val('');
				return;
			}
			
			// Debounce search
			searchTimeout = setTimeout(function() {
				performEntitySearch(searchTerm);
			}, 300);
		});
		
		// Handle entity selection
		$(document).on('click', '.mets-entity-item', function() {
			var result = $(this);
			var entityId = result.data('entity-id');
			var entityName = result.text();
			var searchInput = $('.mets-entity-search');
			var dropdown = $('.mets-entity-results');
			var hiddenInput = $('input[name="entity_id"]');
			
			searchInput.val(entityName);
			hiddenInput.val(entityId);
			dropdown.hide().find('.mets-entity-results-content').empty();
		});
		
		// Hide dropdown when clicking outside
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.mets-entity-search-wrapper').length) {
				$('.mets-entity-results').hide();
			}
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
					}
				},
				error: function() {
					console.error('Entity search failed');
				}
			});
		}
		
		function displaySearchResults(entities) {
			var dropdown = $('.mets-entity-results');
			var dropdownContent = dropdown.find('.mets-entity-results-content');
			dropdownContent.empty();
			
			if (entities.length === 0) {
				dropdownContent.append('<div class="mets-no-results">No departments found</div>');
			} else {
				entities.forEach(function(entity) {
					var prefix = entity.parent_name ? '— ' : '';
					var displayName = prefix + entity.name;
					if (entity.parent_name && entity.name !== entity.parent_name) {
						displayName = entity.parent_name + ' → ' + entity.name;
					}
					dropdownContent.append(
						'<div class="mets-entity-item" data-entity-id="' + entity.id + '">' + displayName + '</div>'
					);
				});
			}
			
			dropdown.show();
		}

		// Mandatory KB Search Gate functionality (only if element exists)
		var kbGateSearchTimeout;
		var hasSearchedKB = false;
		
		// Check if KB gate exists on the page
		if ($('#mets-kb-search-gate').length > 0) {
			// KB Gate search functionality
			$('#kb-gate-search-btn, #kb-gate-search').on('click keypress', function(e) {
			if (e.type === 'click' || e.which === 13) {
				e.preventDefault();
				performMandatoryKBSearch();
			}
		});
		
		// Real-time search with debounce for KB gate
		$('#kb-gate-search').on('input', function() {
			clearTimeout(kbGateSearchTimeout);
			var searchTerm = $(this).val().trim();
			
			if (searchTerm.length < 3) {
				$('#kb-gate-results').hide();
				return;
			}
			
			kbGateSearchTimeout = setTimeout(function() {
				performMandatoryKBSearch();
			}, 800); // Longer delay for mandatory search
		});
		
		// Handle "I found my answer" button
		$('#kb-gate-found-answer').on('click', function() {
			$('#kb-gate-results').hide();
			$('#kb-gate-success').show();
			hasSearchedKB = true;
			
			// Track successful self-service (optional analytics)
			if (typeof gtag !== 'undefined') {
				gtag('event', 'kb_self_service_success', {
					'event_category': 'knowledge_base',
					'event_label': $('#kb-gate-search').val()
				});
			}
		});
		
		// Handle "I still need help" button
		$('#kb-gate-need-help').on('click', function() {
			$('#mets-kb-search-gate').hide();
			$('#mets-ticket-form').show();
			hasSearchedKB = true;
			
			// Pre-populate ticket subject with search term
			var searchTerm = $('#kb-gate-search').val();
			if (searchTerm) {
				$('#ticket_subject').val(searchTerm);
				// Trigger existing KB suggestions
				triggerKbSearch();
			}
			
			// Track ticket creation after KB search
			if (typeof gtag !== 'undefined') {
				gtag('event', 'kb_search_then_ticket', {
					'event_category': 'knowledge_base',
					'event_label': searchTerm
				});
			}
		});
		
		// Handle "Search Again" button
		$('#kb-gate-search-again').on('click', function() {
			$('#kb-gate-success').hide();
			$('#kb-gate-results').hide();
			$('#kb-gate-search').val('').focus();
			hasSearchedKB = false;
		});
		
		function performMandatoryKBSearch() {
			var searchTerm = $('#kb-gate-search').val().trim();
			
			if (searchTerm.length < 3) {
				$('#kb-gate-results').hide();
				return;
			}
			
			$('#kb-gate-results-list').html('<div class="kb-gate-loading"><p>🔍 ' + 'Searching our knowledge base...' + '</p></div>');
			$('#kb-gate-results').show();
			
			$.ajax({
				url: mets_public_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'mets_search_kb_articles',
					nonce: mets_public_ajax.nonce,
					search: searchTerm,
					entity_id: $('input[name="entity_id"]').val() || 0
				},
				success: function(response) {
					if (response.success) {
						displayKBGateResults(response.data.articles);
					} else {
						$('#kb-gate-results-list').html('<div class="kb-gate-error"><p>Search failed. Please try a different search term.</p></div>');
					}
				},
				error: function() {
					$('#kb-gate-results-list').html('<div class="kb-gate-error"><p>Search request failed. Please try again.</p></div>');
				}
			});
		}
		
		function displayKBGateResults(articles) {
			var resultsHtml = '';
			
			if (articles.length === 0) {
				resultsHtml = '<div class="kb-gate-no-results">';
				resultsHtml += '<p><strong>No articles found for your search.</strong></p>';
				resultsHtml += '<p>Don\'t worry! You can still create a support ticket and our team will help you.</p>';
				resultsHtml += '<button type="button" id="kb-gate-no-results-ticket" class="mets-button">Create Support Ticket</button>';
				resultsHtml += '</div>';
			} else {
				resultsHtml = '<div class="kb-gate-articles-list">';
				$.each(articles, function(i, article) {
					var helpfulScore = article.helpful_yes > 0 || article.helpful_no > 0 
						? Math.round((article.helpful_yes / (article.helpful_yes + article.helpful_no)) * 100)
						: null;
					
					resultsHtml += '<div class="kb-gate-article-item">';
					resultsHtml += '<div class="kb-gate-article-header">';
					resultsHtml += '<h5><a href="' + article.url + '" target="_blank" class="kb-gate-article-title">' + article.title + '</a></h5>';
					resultsHtml += '<span class="kb-gate-article-entity">' + article.entity_name + '</span>';
					resultsHtml += '</div>';
					resultsHtml += '<div class="kb-gate-article-excerpt">' + article.excerpt + '</div>';
					if (helpfulScore !== null) {
						resultsHtml += '<div class="kb-gate-article-helpful">👍 ' + helpfulScore + '% found this helpful</div>';
					}
					resultsHtml += '<div class="kb-gate-article-actions">';
					resultsHtml += '<a href="' + article.url + '" target="_blank" class="button button-small">Read Full Article</a>';
					resultsHtml += '</div>';
					resultsHtml += '</div>';
				});
				resultsHtml += '</div>';
			}
			
			$('#kb-gate-results-list').html(resultsHtml);
			
			// Handle no results ticket creation
			$('#kb-gate-no-results-ticket').on('click', function() {
				$('#kb-gate-need-help').trigger('click');
			});
		}
		} // End of KB gate conditional check

		// KB Article Suggestions functionality
		function triggerKbSearch() {
			var subject = $('#ticket_subject').val().trim();
			var description = $('#ticket_description').val().trim();
			var entityId = $('input[name="entity_id"]').val();
			
			// Combine subject and description for search
			var searchTerm = subject;
			if (description) {
				searchTerm += ' ' + description;
			}
			
			// Clear previous timeout
			clearTimeout(kbSearchTimeout);
			
			if (searchTerm.length < 3) {
				$('#mets-kb-suggestions').hide();
				return;
			}
			
			// Debounce search
			kbSearchTimeout = setTimeout(function() {
				performKbSearch(searchTerm, entityId);
			}, 500);
		}
		
		// Listen for subject and description changes
		$(document).on('input', '#ticket_subject, #ticket_description', function() {
			triggerKbSearch();
		});
		
		// Listen for entity changes
		$(document).on('change', 'input[name="entity_id"]', function() {
			triggerKbSearch();
		});
		
		function performKbSearch(searchTerm, entityId) {
			$.ajax({
				url: mets_public_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'mets_search_kb_articles',
					nonce: mets_public_ajax.nonce,
					search: searchTerm,
					entity_id: entityId || 0
				},
				success: function(response) {
					if (response.success) {
						displayKbSuggestions(response.data.articles);
					}
				},
				error: function() {
					console.error('KB article search failed');
				}
			});
		}
		
		function displayKbSuggestions(articles) {
			var suggestionsContainer = $('#mets-kb-suggestions');
			var suggestionsList = $('#mets-kb-suggestions-list');
			
			suggestionsList.empty();
			
			if (articles.length === 0) {
				suggestionsContainer.hide();
				return;
			}
			
			articles.forEach(function(article) {
				var helpfulScore = article.helpful_yes > 0 || article.helpful_no > 0 
					? Math.round((article.helpful_yes / (article.helpful_yes + article.helpful_no)) * 100)
					: null;
				
				var helpfulBadge = helpfulScore !== null 
					? '<span class="mets-kb-helpful-score">' + helpfulScore + '% helpful</span>'
					: '';
				
				var articleHtml = 
					'<div class="mets-kb-suggestion-item">' +
						'<div class="mets-kb-suggestion-header">' +
							'<a href="' + article.url + '" target="_blank" class="mets-kb-suggestion-title">' +
								article.title +
							'</a>' +
							'<small class="mets-kb-suggestion-entity">' + article.entity_name + '</small>' +
						'</div>' +
						'<div class="mets-kb-suggestion-excerpt">' + article.excerpt + '</div>' +
						'<div class="mets-kb-suggestion-footer">' +
							helpfulBadge +
							'<a href="' + article.url + '" target="_blank" class="mets-kb-read-more">Read more →</a>' +
						'</div>' +
					'</div>';
				
				suggestionsList.append(articleHtml);
			});
			
			suggestionsContainer.show();
		}

		// Ticket form submission with progressive file upload
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
			
			// Reset field error states
			form.find('.mets-field-error').removeClass('mets-field-error');
			
			// Validate required fields
			var isValid = true;
			form.find('[required]').each(function() {
				if (!$(this).val().trim()) {
					$(this).addClass('mets-field-error');
					isValid = false;
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
			
			// File size validation using actual PHP limits
			var fileInput = $('#ticket_attachments')[0];
			var maxFileSize = mets_public_ajax.upload_limits ? mets_public_ajax.upload_limits.max_file_size : (20 * 1024 * 1024); // Use PHP limit or fallback to 20MB
			var maxFileSizeMB = mets_public_ajax.upload_limits ? mets_public_ajax.upload_limits.max_file_size_mb : 20;
			var maxTotalSize = maxFileSize * 10; // Allow up to 10 files at max size
			var totalSize = 0;
			
			if (fileInput && fileInput.files) {
				for (var i = 0; i < fileInput.files.length; i++) {
					var file = fileInput.files[i];
					totalSize += file.size;
					
					if (file.size > maxFileSize) {
						errorMessage.text('File "' + file.name + '" is too large. Maximum file size is ' + maxFileSizeMB + 'MB (server limit).').show();
						return false;
					}
				}
				
				if (totalSize > maxTotalSize) {
					errorMessage.text('Total file size too large (' + formatFileSize(totalSize) + '). Please reduce the number or size of files (max ' + Math.round(maxTotalSize/1024/1024) + 'MB total).').show();
					return false;
				}
			}
			
			if (!isValid) {
				errorMessage.text(mets_public_ajax.error_messages.required_fields || 'Please fill in all required fields correctly.').show();
				return false;
			}
			
			// Show loading state
			submitButton.prop('disabled', true);
			loadingSpinner.show();
			
			// Step 1: Submit ticket without files
			submitTicketWithProgressiveUpload(form, fileInput, submitButton, loadingSpinner, successMessage, errorMessage);
		});
		
		function submitTicketWithProgressiveUpload(form, fileInput, submitButton, loadingSpinner, successMessage, errorMessage) {
			// Prepare form data without files
			var formData = new FormData();
			
			// Add form fields except files
			$(form[0].elements).each(function() {
				if (this.type !== 'file' && this.name) {
					formData.append(this.name, this.value);
				}
			});
			
			formData.append('action', 'mets_submit_ticket');
			
			// Submit ticket first
			$.ajax({
				url: mets_public_ajax.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						var ticketId = response.data.ticket_id;
						var ticketNumber = response.data.ticket_number;
						
						// If there are files, upload them progressively
						if (fileInput && fileInput.files && fileInput.files.length > 0) {
							uploadFilesProgressively(ticketId, fileInput.files, 0, [], function(uploadResults) {
								// All files processed
								displayFinalResults(response.data.message, ticketNumber, uploadResults, submitButton, loadingSpinner, successMessage, errorMessage, form);
							});
						} else {
							// No files, show success immediately
							displayFinalResults(response.data.message, ticketNumber, [], submitButton, loadingSpinner, successMessage, errorMessage, form);
						}
					} else {
						loadingSpinner.hide();
						submitButton.prop('disabled', false);
						errorMessage.text(response.data.message || 'Failed to create ticket. Please try again.').show();
					}
				},
				error: function(xhr, status, error) {
					loadingSpinner.hide();
					submitButton.prop('disabled', false);
					errorMessage.text('An error occurred. Please try again later.').show();
					console.error('Ticket submission error:', error);
				}
			});
		}
		
		function uploadFilesProgressively(ticketId, files, currentIndex, results, callback) {
			if (currentIndex >= files.length) {
				callback(results);
				return;
			}
			
			var file = files[currentIndex];
			var fileData = new FormData();
			fileData.append('action', 'mets_upload_file');
			fileData.append('nonce', mets_public_ajax.nonce);
			fileData.append('ticket_id', ticketId);
			fileData.append('file', file);
			
			// Update loading message
			$('.mets-loading').text('Uploading file ' + (currentIndex + 1) + ' of ' + files.length + '...');
			
			$.ajax({
				url: mets_public_ajax.ajax_url,
				type: 'POST',
				data: fileData,
				processData: false,
				contentType: false,
				success: function(response) {
					results.push({
						fileName: file.name,
						success: response.success,
						message: response.success ? 'Uploaded successfully' : (response.data ? response.data.message : 'Upload failed')
					});
					
					// Continue with next file
					uploadFilesProgressively(ticketId, files, currentIndex + 1, results, callback);
				},
				error: function() {
					results.push({
						fileName: file.name,
						success: false,
						message: 'Upload failed due to network error'
					});
					
					// Continue with next file even if this one failed
					uploadFilesProgressively(ticketId, files, currentIndex + 1, results, callback);
				}
			});
		}
		
		function displayFinalResults(ticketMessage, ticketNumber, uploadResults, submitButton, loadingSpinner, successMessage, errorMessage, form) {
			loadingSpinner.hide();
			submitButton.prop('disabled', false);
			
			var message = ticketMessage;
			if (ticketNumber) {
				message += ' ' + (mets_public_ajax.ticket_number_text || 'Ticket number:') + ' <strong>' + ticketNumber + '</strong>';
			}
			
			// Add file upload results
			if (uploadResults.length > 0) {
				var successCount = uploadResults.filter(function(r) { return r.success; }).length;
				var failCount = uploadResults.length - successCount;
				
				if (successCount > 0) {
					message += '<br><span style="color: #00a32a;">✓ ' + successCount + ' file(s) uploaded successfully</span>';
				}
				
				if (failCount > 0) {
					message += '<br><span style="color: #d63638;">✗ ' + failCount + ' file(s) failed to upload</span>';
					message += '<div style="margin-top: 10px; font-size: 14px;">';
					uploadResults.forEach(function(result) {
						if (!result.success) {
							message += '<div style="color: #856404;">• ' + result.fileName + ': ' + result.message + '</div>';
						}
					});
					message += '</div>';
				}
			}
			
			successMessage.html(message).show();
			
			// Reset form
			form[0].reset();
			$('.mets-file-preview').hide();
			$('.mets-file-list').empty();
			
			// Reset loading text
			loadingSpinner.text('Submitting...');
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
					
					// Check file size and show warning using actual PHP limits
					var maxFileSize = mets_public_ajax.upload_limits ? mets_public_ajax.upload_limits.max_file_size : (20 * 1024 * 1024);
					var maxFileSizeMB = mets_public_ajax.upload_limits ? mets_public_ajax.upload_limits.max_file_size_mb : 20;
					var sizeWarning = '';
					if (file.size > maxFileSize) {
						sizeWarning = ' <span style="color: #d63638;">(Too large - max ' + maxFileSizeMB + 'MB)</span>';
					}
					
					var fileItem = $('<div class="mets-file-item">' +
						'<div class="mets-file-icon ' + fileIconClass + '"></div>' +
						'<div class="mets-file-info">' +
							'<div class="mets-file-name">' + file.name + sizeWarning + '</div>' +
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

		// Customer reply form handling with proper event delegation
		// Using single document-level event listener to prevent multiple handlers
		$(document).off('submit.mets-reply', '.mets-reply-form form').on('submit.mets-reply', '.mets-reply-form form', function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			
			var form = $(this);
			
			// Check if form is already being processed using data attribute
			if (form.data('mets-processing') === true) {
				console.log('METS: Reply submission blocked - form already processing');
				return false;
			}
			
			var submitButton = form.find('button[type="submit"]');
			var loadingSpinner = form.find('.mets-loading');
			var successMessage = form.find('.mets-success-message');
			var errorMessage = form.find('.mets-error-message');
			var replyContent = form.find('textarea[name="reply_content"]').val().trim();
			
			// Validate reply content
			if (!replyContent) {
				errorMessage.text('Please enter your reply.').show();
				return false;
			}
			
			// Clear previous messages
			successMessage.hide();
			errorMessage.hide();
			
			// Mark form as processing and disable submit button immediately
			form.data('mets-processing', true);
			submitButton.prop('disabled', true).text('Submitting...');
			loadingSpinner.show();
			
			// Prepare form data
			var formData = form.serialize();
			formData += '&action=mets_customer_reply&nonce=' + mets_public_ajax.nonce;
			
			// Submit the reply
			$.ajax({
				url: mets_public_ajax.ajax_url,
				type: 'POST',
				data: formData,
				timeout: 30000, // 30 second timeout
				success: function(response) {
					loadingSpinner.hide();
					
					if (response.success) {
						successMessage.text(response.data.message || 'Reply submitted successfully').show();
						form.find('textarea[name="reply_content"]').val('');
						
						// Keep form disabled and show success state
						submitButton.text('Reply Sent!');
						
						// Reload page to show new reply after short delay
						setTimeout(function() {
							location.reload();
						}, 1500);
					} else {
						// On error, reset form state to allow retry
						form.data('mets-processing', false);
						submitButton.prop('disabled', false).text('Submit Reply');
						errorMessage.text(response.data ? response.data.message : 'Failed to submit reply. Please try again.').show();
					}
				},
				error: function(xhr, status, error) {
					// On error, reset form state to allow retry
					form.data('mets-processing', false);
					loadingSpinner.hide();
					submitButton.prop('disabled', false).text('Submit Reply');
					
					var errorMsg = 'An error occurred. Please try again.';
					if (status === 'timeout') {
						errorMsg = 'Request timed out. Please check your connection and try again.';
					}
					errorMessage.text(errorMsg).show();
				}
			});
		});
	});

	// Helper AJAX function
	function makeAjaxRequest(action, data, callback) {
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