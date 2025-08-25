<?php
/**
 * Security Dashboard
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

class METS_Security_Dashboard {

	/**
	 * Security manager instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Security_Manager    $security_manager
	 */
	private $security_manager;

	/**
	 * Security audit instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Security_Audit    $security_audit
	 */
	private $security_audit;

	/**
	 * Translation helper method
	 *
	 * @since    1.0.0
	 * @param    string    $text        Text to translate
	 * @param    string    $domain      Text domain
	 * @return   string                 Translated text or original if too early
	 */
	private function safe_translate( $text, $domain = METS_TEXT_DOMAIN ) {
		if ( ! did_action( 'init' ) ) {
			return $text;
		}
		return __( $text, $domain );
	}

	/**
	 * Safe echo translation helper method
	 *
	 * @since    1.0.0
	 * @param    string    $text        Text to translate and echo
	 * @param    string    $domain      Text domain
	 */
	private function safe_echo_translate( $text, $domain = METS_TEXT_DOMAIN ) {
		if ( ! did_action( 'init' ) ) {
			echo esc_html( $text );
			return;
		}
		_e( $text, $domain );
	}

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->security_manager = METS_Security_Manager::get_instance();
		$this->security_audit = METS_Security_Audit::get_instance();
	}

	/**
	 * Display the security dashboard
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( $this->safe_translate( 'You do not have sufficient permissions to access this page.' ) );
		}

		$audit_report = $this->security_audit->get_latest_audit_report();
		$security_config = $this->security_manager->get_security_config();
		$recent_security_logs = $this->security_manager->get_security_log( array( 'limit' => 10 ) );

		?>
		<div class="wrap">
			<h1><?php $this->safe_echo_translate( 'Security Dashboard' ); ?></h1>
			
			<div class="security-dashboard">
				<!-- Security Overview -->
				<div class="security-section">
					<h2><?php $this->safe_echo_translate( 'Security Overview' ); ?></h2>
					
					<?php if ( $audit_report ) : ?>
						<div class="security-score-card">
							<div class="score-circle score-<?php echo $this->get_score_class( $audit_report['security_score'] ); ?>">
								<span class="score-number"><?php echo $audit_report['security_score']; ?></span>
								<span class="score-label"><?php $this->safe_echo_translate( 'Security Score' ); ?></span>
							</div>
							
							<div class="score-details">
								<div class="score-item">
									<span class="score-value"><?php echo $audit_report['passed_checks']; ?></span>
									<span class="score-desc"><?php $this->safe_echo_translate( 'Passed' ); ?></span>
								</div>
								<div class="score-item">
									<span class="score-value"><?php echo $audit_report['failed_checks']; ?></span>
									<span class="score-desc"><?php $this->safe_echo_translate( 'Failed' ); ?></span>
								</div>
								<div class="score-item">
									<span class="score-value"><?php echo $audit_report['warnings']; ?></span>
									<span class="score-desc"><?php $this->safe_echo_translate( 'Warnings' ); ?></span>
								</div>
							</div>
						</div>
						
						<p class="audit-date">
							<?php printf( 
								$this->safe_translate( 'Last audit: %s' ), 
								date( 'Y-m-d H:i:s', strtotime( $audit_report['timestamp'] ) ) 
							); ?>
						</p>
					<?php else : ?>
						<div class="security-alert">
							<p><?php $this->safe_echo_translate( 'No security audit has been performed yet.' ); ?></p>
							<button class="button button-primary" id="run-security-audit">
								<?php $this->safe_echo_translate( 'Run Security Audit' ); ?>
							</button>
						</div>
					<?php endif; ?>
				</div>

				<!-- Security Configuration -->
				<div class="security-section">
					<h2><?php $this->safe_echo_translate( 'Security Configuration' ); ?></h2>
					
					<form id="security-config-form" method="post">
						<?php wp_nonce_field( 'mets_security_config', 'security_config_nonce' ); ?>
						
						<table class="form-table">
							<tr>
								<th scope="row"><?php $this->safe_echo_translate( 'Rate Limiting' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="enable_rate_limiting" value="1" 
											<?php checked( $security_config['enable_rate_limiting'] ); ?>>
										<?php $this->safe_echo_translate( 'Enable rate limiting for API requests and form submissions' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php $this->safe_echo_translate( 'Input Validation' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="enable_input_validation" value="1" 
											<?php checked( $security_config['enable_input_validation'] ); ?>>
										<?php $this->safe_echo_translate( 'Enable comprehensive input validation and sanitization' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php $this->safe_echo_translate( 'File Upload Security' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="enable_file_upload_security" value="1" 
											<?php checked( $security_config['enable_file_upload_security'] ); ?>>
										<?php $this->safe_echo_translate( 'Enable file upload validation and malware scanning' ); ?>
									</label>
									<p class="description">
										<?php printf( 
											$this->safe_translate( 'Max upload size: %s' ), 
											size_format( $security_config['max_upload_size'] ) 
										); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php $this->safe_echo_translate( 'Login Attempts' ); ?></th>
								<td>
									<input type="number" name="max_login_attempts" 
										value="<?php echo esc_attr( $security_config['max_login_attempts'] ); ?>" 
										min="1" max="20" class="small-text">
									<p class="description">
										<?php $this->safe_echo_translate( 'Maximum failed login attempts before lockout' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php $this->safe_echo_translate( 'Lockout Duration' ); ?></th>
								<td>
									<input type="number" name="lockout_duration" 
										value="<?php echo esc_attr( $security_config['lockout_duration'] / 60 ); ?>" 
										min="5" max="1440" class="small-text">
									<span><?php $this->safe_echo_translate( 'minutes' ); ?></span>
									<p class="description">
										<?php $this->safe_echo_translate( 'How long to lock out users after failed attempts' ); ?>
									</p>
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<input type="submit" name="save_security_config" class="button-primary" 
								value="<?php $this->safe_echo_translate( 'Save Configuration' ); ?>">
						</p>
					</form>
				</div>

				<!-- Recent Security Events -->
				<div class="security-section">
					<h2><?php $this->safe_echo_translate( 'Recent Security Events' ); ?></h2>
					
					<?php if ( ! empty( $recent_security_logs ) ) : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php $this->safe_echo_translate( 'Date/Time' ); ?></th>
									<th><?php $this->safe_echo_translate( 'Event Type' ); ?></th>
									<th><?php $this->safe_echo_translate( 'IP Address' ); ?></th>
									<th><?php $this->safe_echo_translate( 'User' ); ?></th>
									<th><?php $this->safe_echo_translate( 'Details' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_security_logs as $log ) : ?>
									<?php $event_data = json_decode( $log->event_data, true ); ?>
									<tr class="security-event-<?php echo esc_attr( $log->event_type ); ?>">
										<td><?php echo esc_html( $log->created_at ); ?></td>
										<td>
											<span class="event-type-badge event-<?php echo esc_attr( $log->event_type ); ?>">
												<?php echo esc_html( ucwords( str_replace( '_', ' ', $log->event_type ) ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $log->ip_address ); ?></td>
										<td>
											<?php if ( $log->user_id ) : ?>
												<?php $user = get_userdata( $log->user_id ); ?>
												<?php echo $user ? esc_html( $user->user_login ) : $this->safe_translate( 'Unknown' ); ?>
											<?php else : ?>
												<?php $this->safe_echo_translate( 'Guest' ); ?>
											<?php endif; ?>
										</td>
										<td>
											<button class="button button-small view-event-details" 
												data-event='<?php echo esc_attr( wp_json_encode( $event_data ) ); ?>'>
												<?php $this->safe_echo_translate( 'View Details' ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						
						<p>
							<a href="<?php echo admin_url( 'admin.php?page=mets-security-logs' ); ?>" class="button">
								<?php $this->safe_echo_translate( 'View All Security Logs' ); ?>
							</a>
						</p>
					<?php else : ?>
						<p><?php $this->safe_echo_translate( 'No recent security events.' ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Security Recommendations -->
				<?php if ( $audit_report && ! empty( $audit_report['recommendations'] ) ) : ?>
				<div class="security-section">
					<h2><?php $this->safe_echo_translate( 'Security Recommendations' ); ?></h2>
					
					<div class="recommendations-list">
						<?php foreach ( $audit_report['recommendations'] as $recommendation ) : ?>
							<div class="recommendation recommendation-<?php echo esc_attr( $recommendation['priority'] ); ?>">
								<div class="recommendation-priority">
									<span class="priority-badge priority-<?php echo esc_attr( $recommendation['priority'] ); ?>">
										<?php echo esc_html( ucfirst( $recommendation['priority'] ) ); ?>
									</span>
								</div>
								<div class="recommendation-content">
									<h4><?php echo esc_html( $recommendation['title'] ); ?></h4>
									<p><?php echo esc_html( $recommendation['recommendation'] ); ?></p>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<!-- Security Actions -->
				<div class="security-section">
					<h2><?php $this->safe_echo_translate( 'Security Actions' ); ?></h2>
					
					<div class="security-actions-grid">
						<div class="action-card">
							<h3><?php $this->safe_echo_translate( 'Run Security Audit' ); ?></h3>
							<p><?php $this->safe_echo_translate( 'Perform a comprehensive security audit of the system.' ); ?></p>
							<button class="button button-primary" id="run-security-audit">
								<?php $this->safe_echo_translate( 'Run Audit' ); ?>
							</button>
						</div>
						
						<div class="action-card">
							<h3><?php $this->safe_echo_translate( 'Clear Security Logs' ); ?></h3>
							<p><?php $this->safe_echo_translate( 'Clear old security log entries (older than 90 days).' ); ?></p>
							<button class="button" id="clear-security-logs">
								<?php $this->safe_echo_translate( 'Clear Logs' ); ?>
							</button>
						</div>
						
						<div class="action-card">
							<h3><?php $this->safe_echo_translate( 'Export Security Report' ); ?></h3>
							<p><?php $this->safe_echo_translate( 'Export the latest security audit report as PDF.' ); ?></p>
							<button class="button" id="export-security-report">
								<?php $this->safe_echo_translate( 'Export Report' ); ?>
							</button>
						</div>
						
						<div class="action-card">
							<h3><?php $this->safe_echo_translate( 'Reset Rate Limits' ); ?></h3>
							<p><?php $this->safe_echo_translate( 'Reset all active rate limiting counters.' ); ?></p>
							<button class="button" id="reset-rate-limits">
								<?php $this->safe_echo_translate( 'Reset Limits' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Event Details Modal -->
		<div id="event-details-modal" class="mets-modal" style="display: none;">
			<div class="mets-modal-content">
				<div class="mets-modal-header">
					<h3><?php $this->safe_echo_translate( 'Security Event Details' ); ?></h3>
					<button type="button" class="mets-modal-close">&times;</button>
				</div>
				<div class="mets-modal-body">
					<div id="event-details-content"></div>
				</div>
			</div>
		</div>

		<style>
		.security-dashboard {
			max-width: 1200px;
		}

		.security-section {
			background: #fff;
			margin: 20px 0;
			padding: 20px;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}

		.security-section h2 {
			margin-top: 0;
			border-bottom: 1px solid #eee;
			padding-bottom: 10px;
		}

		.security-score-card {
			display: flex;
			align-items: center;
			gap: 30px;
			margin: 20px 0;
		}

		.score-circle {
			width: 120px;
			height: 120px;
			border-radius: 50%;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			position: relative;
			border: 8px solid;
		}

		.score-circle.score-high { border-color: #00a32a; }
		.score-circle.score-medium { border-color: #dba617; }
		.score-circle.score-low { border-color: #d63638; }

		.score-number {
			font-size: 32px;
			font-weight: bold;
			line-height: 1;
		}

		.score-label {
			font-size: 12px;
			text-transform: uppercase;
			margin-top: 5px;
		}

		.score-details {
			display: flex;
			gap: 30px;
		}

		.score-item {
			text-align: center;
		}

		.score-value {
			display: block;
			font-size: 24px;
			font-weight: bold;
			color: #2271b1;
		}

		.score-desc {
			display: block;
			font-size: 12px;
			color: #666;
			text-transform: uppercase;
		}

		.security-alert {
			background: #fcf9e8;
			border: 1px solid #dba617;
			border-radius: 4px;
			padding: 20px;
			text-align: center;
		}

		.event-type-badge {
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
		}

		.event-type-badge.event-failed_login { background: #fef7f1; color: #d63638; }
		.event-type-badge.event-rate_limit_exceeded { background: #fcf9e8; color: #dba617; }
		.event-type-badge.event-sql_injection_attempt { background: #fef7f1; color: #d63638; }
		.event-type-badge.event-account_locked { background: #fef7f1; color: #d63638; }

		.recommendations-list {
			margin: 20px 0;
		}

		.recommendation {
			display: flex;
			align-items: flex-start;
			padding: 15px;
			margin-bottom: 15px;
			border-radius: 4px;
			border-left: 4px solid;
		}

		.recommendation-high {
			background: #fef7f1;
			border-left-color: #d63638;
		}

		.recommendation-medium {
			background: #fcf9e8;
			border-left-color: #dba617;
		}

		.recommendation-low {
			background: #f0f6fc;
			border-left-color: #2271b1;
		}

		.recommendation-priority {
			margin-right: 15px;
		}

		.priority-badge {
			padding: 4px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
		}

		.priority-badge.priority-high { background: #d63638; color: #fff; }
		.priority-badge.priority-medium { background: #dba617; color: #fff; }
		.priority-badge.priority-low { background: #2271b1; color: #fff; }

		.recommendation-content h4 {
			margin: 0 0 8px 0;
		}

		.recommendation-content p {
			margin: 0;
			color: #666;
		}

		.security-actions-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin: 20px 0;
		}

		.action-card {
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 4px;
			background: #f9f9f9;
			text-align: center;
		}

		.action-card h3 {
			margin-top: 0;
			margin-bottom: 10px;
		}

		.action-card p {
			margin-bottom: 15px;
			color: #666;
		}

		.audit-date {
			color: #666;
			font-style: italic;
			margin-top: 10px;
		}

		.mets-modal {
			position: fixed;
			z-index: 100000;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0,0,0,0.5);
		}

		.mets-modal-content {
			background-color: #fefefe;
			margin: 5% auto;
			padding: 0;
			border-radius: 5px;
			width: 90%;
			max-width: 600px;
			max-height: 80vh;
			overflow-y: auto;
		}

		.mets-modal-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 20px;
			background: #f1f1f1;
			border-bottom: 1px solid #ddd;
		}

		.mets-modal-body {
			padding: 20px;
		}

		.mets-modal-close {
			background: none;
			border: none;
			font-size: 24px;
			cursor: pointer;
			color: #999;
		}

		@media (max-width: 768px) {
			.security-score-card {
				flex-direction: column;
				text-align: center;
			}
			
			.score-details {
				justify-content: center;
			}
			
			.security-actions-grid {
				grid-template-columns: 1fr;
			}
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Run security audit
			$('#run-security-audit').on('click', function() {
				var button = $(this);
				button.prop('disabled', true).text('<?php $this->safe_echo_translate( 'Running...' ); ?>');
				
				$.post(ajaxurl, {
					action: 'mets_run_security_audit',
					nonce: '<?php echo wp_create_nonce( 'mets_security_action' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php $this->safe_echo_translate( 'Security audit completed successfully!' ); ?>');
						location.reload();
					} else {
						alert('<?php $this->safe_echo_translate( 'Error: ' ); ?>' + response.data);
					}
				}).always(function() {
					button.prop('disabled', false).text('<?php $this->safe_echo_translate( 'Run Audit' ); ?>');
				});
			});

			// View event details
			$('.view-event-details').on('click', function() {
				var eventData = $(this).data('event');
				var content = '<table class="form-table">';
				
				$.each(eventData, function(key, value) {
					if (typeof value === 'object') {
						value = JSON.stringify(value, null, 2);
					}
					content += '<tr><th>' + key + '</th><td><pre>' + value + '</pre></td></tr>';
				});
				
				content += '</table>';
				$('#event-details-content').html(content);
				$('#event-details-modal').show();
			});

			// Close modal
			$('.mets-modal-close, .mets-modal').on('click', function(e) {
				if (e.target === this) {
					$('.mets-modal').hide();
				}
			});

			// Save security configuration
			$('#security-config-form').on('submit', function(e) {
				e.preventDefault();
				
				$.post(ajaxurl, {
					action: 'mets_save_security_config',
					nonce: '<?php echo wp_create_nonce( 'mets_security_action' ); ?>',
					config: $(this).serialize()
				}, function(response) {
					if (response.success) {
						alert('<?php $this->safe_echo_translate( 'Security configuration saved successfully!' ); ?>');
					} else {
						alert('<?php $this->safe_echo_translate( 'Error: ' ); ?>' + response.data);
					}
				});
			});

			// Clear security logs
			$('#clear-security-logs').on('click', function() {
				if (!confirm('<?php $this->safe_echo_translate( 'Are you sure you want to clear old security logs?' ); ?>')) {
					return;
				}
				
				var button = $(this);
				button.prop('disabled', true).text('<?php $this->safe_echo_translate( 'Clearing...' ); ?>');
				
				$.post(ajaxurl, {
					action: 'mets_clear_security_logs',
					nonce: '<?php echo wp_create_nonce( 'mets_security_action' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php $this->safe_echo_translate( 'Security logs cleared successfully!' ); ?>');
						location.reload();
					} else {
						alert('<?php $this->safe_echo_translate( 'Error: ' ); ?>' + response.data);
					}
				}).always(function() {
					button.prop('disabled', false).text('<?php $this->safe_echo_translate( 'Clear Logs' ); ?>');
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Get security score CSS class
	 *
	 * @since    1.0.0
	 * @param    int    $score    Security score
	 * @return   string           CSS class
	 */
	private function get_score_class( $score ) {
		if ( $score >= 80 ) {
			return 'high';
		} elseif ( $score >= 60 ) {
			return 'medium';
		} else {
			return 'low';
		}
	}
}