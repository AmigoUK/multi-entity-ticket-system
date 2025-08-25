/**
 * Multi-Entity Ticket System Admin JavaScript
 *
 * @package MultiEntityTicketSystem
 * @since   1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize admin functionality
	 */
	$(document).ready(function() {
		// Auto-generate slug from name only if slug field is empty
		$('#entity_name').on('input', function() {
			var $slugField = $('#entity_slug');
			
			// Only generate slug if it's empty or user hasn't manually edited it
			if (!$slugField.val() || !$slugField.data('manually-edited')) {
				var name = $(this).val();
				var slug = name.toLowerCase()
					.replace(/[^a-z0-9\s-]/g, '')
					.replace(/\s+/g, '-')
					.replace(/-+/g, '-')
					.replace(/^-+|-+$/g, '');
				
				$slugField.val(slug);
			}
		});
		
		// Track if user manually edits the slug
		$('#entity_slug').on('input', function() {
			$(this).data('manually-edited', true);
		});
		
		// Sync ticket properties form fields with hidden inputs
		$('#sidebar_ticket_status').on('change', function() {
			$('#properties_ticket_status').val($(this).val());
		});
		
		$('#sidebar_ticket_priority').on('change', function() {
			$('#properties_ticket_priority').val($(this).val());
		});
		
		$('#sidebar_ticket_category').on('change', function() {
			$('#properties_ticket_category').val($(this).val());
		});
		
		$('#sidebar_assigned_to').on('change', function() {
			$('#properties_assigned_to').val($(this).val());
		});

		// Confirm delete actions
		$('.delete a').on('click', function(e) {
			if (!confirm(mets_admin_strings.confirm_delete)) {
				e.preventDefault();
				return false;
			}
		});

		// AJAX entity search (placeholder for Phase 3)
		$('#entity-search-input').on('input', function() {
			var searchTerm = $(this).val();
			if (searchTerm.length >= 3) {
				// Implement AJAX search in Phase 3
				console.log('Searching for: ' + searchTerm);
			}
		});

		// Initialize real-time dashboard updates
		initDashboardUpdates();

		// Copy shortcode functionality
		$('.copy-shortcode').on('click', function(e) {
			e.preventDefault();
			var shortcode = $(this).data('shortcode');
			
			// Create temporary text area
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(shortcode).select();
			document.execCommand('copy');
			$temp.remove();
			
			// Show feedback
			var $button = $(this);
			var originalText = $button.text();
			$button.text('Copied!').prop('disabled', true);
			
			setTimeout(function() {
				$button.text(originalText).prop('disabled', false);
			}, 2000);
		});

		// Gmail provider selection handling
		$('#smtp_provider').on('change', function() {
			var provider = $(this).val();
			var $gmailInstructions = $('.gmail-setup-info');
			
			if (provider === 'gmail') {
				$gmailInstructions.removeClass('gmail-hidden').show();
			} else {
				$gmailInstructions.addClass('gmail-hidden').hide();
			}
		});

		// Initialize Gmail instructions visibility
		if ($('#smtp_provider').val() === 'gmail') {
			$('.gmail-setup-info').removeClass('gmail-hidden').show();
		}
	});

	/**
	 * Initialize real-time dashboard updates
	 */
	function initDashboardUpdates() {
		// Only run on dashboard page
		if (pagenow !== 'dashboard') {
			return;
		}

		var $slaWidget = $('#mets_sla_dashboard');
		var $ticketsWidget = $('#mets_tickets_overview');
		
		if ($slaWidget.length === 0 && $ticketsWidget.length === 0) {
			return;
		}

		// Get refresh interval from widget config (default 5 minutes)
		var refreshInterval = 300000; // 5 minutes in milliseconds
		
		// Set up periodic updates
		setInterval(function() {
			refreshDashboardWidgets();
		}, refreshInterval);

		// Add manual refresh buttons
		addRefreshButtons();
	}

	/**
	 * Refresh dashboard widgets
	 */
	function refreshDashboardWidgets() {
		var $slaWidget = $('#mets_sla_dashboard .inside');
		var $ticketsWidget = $('#mets_tickets_overview .inside');

		if ($slaWidget.length > 0) {
			refreshSLAWidget($slaWidget);
		}

		if ($ticketsWidget.length > 0) {
			refreshTicketsWidget($ticketsWidget);
		}
	}

	/**
	 * Refresh SLA dashboard widget
	 */
	function refreshSLAWidget($container) {
		makeAjaxCall('mets_refresh_sla_widget', {}, function(data) {
			// Update metrics
			if (data.metrics) {
				updateMetricItem('breached', data.metrics.breached_count, data.metrics.breached_count > 0);
				updateMetricItem('approaching', data.metrics.approaching_count, data.metrics.approaching_count > 0);
				updateMetricItem('sla-tickets', data.metrics.sla_tickets);
				updateMetricItem('active-tickets', data.metrics.active_tickets);
			}

			// Update last check time
			if (data.last_check) {
				$container.find('.mets-last-check span').attr('title', data.last_check).text(data.last_check_relative);
			}

			// Update alerts if needed
			if (data.alerts_html) {
				var $alerts = $container.find('.mets-sla-alerts');
				if ($alerts.length > 0) {
					$alerts.replaceWith(data.alerts_html);
				} else if (data.alerts_html.trim() !== '') {
					$container.find('.mets-sla-metrics').after(data.alerts_html);
				}
			}

			// Show refresh indicator
			showRefreshIndicator($container);
		});
	}

	/**
	 * Refresh tickets overview widget
	 */
	function refreshTicketsWidget($container) {
		makeAjaxCall('mets_refresh_tickets_widget', {}, function(data) {
			if (data.status_counts) {
				// Update status counts
				$.each(data.status_counts, function(status, count) {
					$container.find('.status-' + status + ' .mets-status-count').text(count);
				});
			}

			if (data.priority_counts) {
				// Update priority counts
				$.each(data.priority_counts, function(priority, count) {
					$container.find('.priority-' + priority + ' .mets-priority-count').text(count);
				});
			}

			if (data.recent_tickets !== undefined) {
				// Update recent tickets count
				$container.find('.mets-recent-stat strong').text(data.recent_tickets);
			}

			// Show refresh indicator
			showRefreshIndicator($container);
		});
	}

	/**
	 * Update individual metric item
	 */
	function updateMetricItem(type, value, isAlert) {
		var $metric = $('.mets-metric-item').filter(function() {
			return $(this).find('.mets-metric-label').text().toLowerCase().includes(type);
		});

		if ($metric.length > 0) {
			$metric.find('.mets-metric-number').text(value);
			
			// Update alert state
			$metric.removeClass('critical warning good');
			if (isAlert === true) {
				$metric.addClass(type === 'breached' ? 'critical' : 'warning');
			} else {
				$metric.addClass('good');
			}
		}
	}

	/**
	 * Show refresh indicator
	 */
	function showRefreshIndicator($container) {
		var $indicator = $('<span class="mets-refresh-indicator">↻</span>');
		$indicator.css({
			position: 'absolute',
			top: '10px',
			right: '10px',
			color: '#666',
			fontSize: '16px',
			animation: 'spin 1s linear'
		});

		$container.css('position', 'relative').append($indicator);

		setTimeout(function() {
			$indicator.fadeOut(500, function() {
				$(this).remove();
			});
		}, 2000);
	}

	/**
	 * Add manual refresh buttons to widgets
	 */
	function addRefreshButtons() {
		// Add CSS for refresh button
		if (!$('#mets-refresh-styles').length) {
			$('head').append('<style id="mets-refresh-styles">' +
				'.mets-refresh-btn { font-size: 12px; padding: 2px 6px; margin-left: 8px; }' +
				'@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }' +
				'.mets-refresh-indicator { animation: spin 1s linear infinite; }' +
			'</style>');
		}

		// Add refresh button to SLA widget
		var $slaWidget = $('#mets_sla_dashboard');
		if ($slaWidget.length > 0) {
			var $slaHeader = $slaWidget.find('.hndle');
			var $refreshBtn = $('<button type="button" class="button mets-refresh-btn">↻ Refresh</button>');
			$refreshBtn.on('click', function() {
				refreshSLAWidget($slaWidget.find('.inside'));
			});
			$slaHeader.append($refreshBtn);
		}

		// Add refresh button to tickets widget
		var $ticketsWidget = $('#mets_tickets_overview');
		if ($ticketsWidget.length > 0) {
			var $ticketsHeader = $ticketsWidget.find('.hndle');
			var $refreshBtn = $('<button type="button" class="button mets-refresh-btn">↻ Refresh</button>');
			$refreshBtn.on('click', function() {
				refreshTicketsWidget($ticketsWidget.find('.inside'));
			});
			$ticketsHeader.append($refreshBtn);
		}
	}

	/**
	 * AJAX helper function
	 */
	function makeAjaxCall(action, data, callback) {
		$.ajax({
			url: mets_admin_ajax.ajax_url,
			type: 'POST',
			data: $.extend({
				action: action,
				nonce: mets_admin_ajax.nonce
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

})(jQuery);