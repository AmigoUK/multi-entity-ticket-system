<?php
/**
 * Manager Dashboard Class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * Manager Dashboard functionality
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Manager_Dashboard {

	/**
	 * KPI settings cache
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $kpi_settings
	 */
	private $kpi_settings;

	/**
	 * Initialize dashboard hooks
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// AJAX action is now registered in the admin class constructor
		// This ensures the action is available immediately when WordPress loads
		
		// Load KPI settings
		$this->kpi_settings = $this->get_kpi_settings();
	}

	/**
	 * Display manager dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard() {
		// Enqueue dashboard assets
		wp_enqueue_style( 'mets-team-dashboard', METS_PLUGIN_URL . 'assets/css/mets-team-dashboard.css', array(), METS_VERSION );
		wp_enqueue_script( 'mets-team-dashboard', METS_PLUGIN_URL . 'assets/js/mets-team-dashboard.js', array( 'jquery' ), METS_VERSION, true );
		
		// Localize script
		wp_localize_script( 'mets-team-dashboard', 'metsTeamDashboard', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'mets_team_dashboard' ),
			'strings' => array(
				'refreshing' => __( 'Refreshing...', METS_TEXT_DOMAIN ),
				'refresh' => __( 'Refresh Data', METS_TEXT_DOMAIN ),
				'error' => __( 'Error loading data', METS_TEXT_DOMAIN ),
				'noData' => __( 'No data available', METS_TEXT_DOMAIN )
			)
		) );
		
		?>
		<div class="wrap">
			<h1><?php _e( '📊 Team Performance Dashboard', METS_TEXT_DOMAIN ); ?></h1>
			
			<div class="mets-manager-dashboard">
				<div class="mets-dashboard-header">
					<p><?php _e( 'Monitor team performance, agent workloads, and support metrics.', METS_TEXT_DOMAIN ); ?></p>
				</div>

				<!-- Period Selector -->
				<div class="mets-period-selector">
					<label for="mets-period-selector"><?php _e( 'Time Period:', METS_TEXT_DOMAIN ); ?></label>
					<select id="mets-period-selector" name="period">
						<option value="today"><?php _e( 'Today', METS_TEXT_DOMAIN ); ?></option>
						<option value="yesterday"><?php _e( 'Yesterday', METS_TEXT_DOMAIN ); ?></option>
						<option value="this_week"><?php _e( 'This Week', METS_TEXT_DOMAIN ); ?></option>
						<option value="last_week"><?php _e( 'Last Week', METS_TEXT_DOMAIN ); ?></option>
						<option value="this_month"><?php _e( 'This Month', METS_TEXT_DOMAIN ); ?></option>
						<option value="last_month"><?php _e( 'Last Month', METS_TEXT_DOMAIN ); ?></option>
						<option value="custom"><?php _e( 'Custom Range', METS_TEXT_DOMAIN ); ?></option>
					</select>
					
					<div class="mets-period-custom">
						<label for="mets-date-from"><?php _e( 'From:', METS_TEXT_DOMAIN ); ?></label>
						<input type="date" id="mets-date-from" name="date_from" />
						<label for="mets-date-to"><?php _e( 'To:', METS_TEXT_DOMAIN ); ?></label>
						<input type="date" id="mets-date-to" name="date_to" />
					</div>
					
					<button type="button" class="button mets-refresh-data">
						<span class="dashicons dashicons-update"></span>
						<?php _e( 'Refresh Data', METS_TEXT_DOMAIN ); ?>
					</button>
				</div>

				<!-- Performance Overview -->
				<div class="mets-performance-overview">
					<h2><?php _e( 'Performance Overview', METS_TEXT_DOMAIN ); ?></h2>
					<div class="mets-metrics-grid">
						<?php $this->display_team_metrics(); ?>
					</div>
				</div>

				<!-- Agent Performance -->
				<div class="mets-agent-performance">
					<h2><?php _e( 'Agent Performance', METS_TEXT_DOMAIN ); ?></h2>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Active Tickets', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Resolved Today', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Avg Response Time', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Customer Rating', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Workload', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php $this->display_agent_performance(); ?>
						</tbody>
					</table>
				</div>

				<!-- Recent Activity -->
				<div class="mets-recent-activity">
					<h2><?php _e( 'Recent Team Activity', METS_TEXT_DOMAIN ); ?></h2>
					<div class="mets-activity-feed">
						<?php $this->display_recent_activity(); ?>
					</div>
				</div>

				<!-- SLA Performance -->
				<div class="mets-sla-performance">
					<h2><?php _e( 'SLA Performance', METS_TEXT_DOMAIN ); ?></h2>
					<div class="mets-sla-grid">
						<?php $this->display_sla_performance(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for dashboard refresh
	 *
	 * @since    1.0.0
	 */
	public function ajax_refresh_dashboard() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_team_dashboard' ) ) {
			wp_send_json_error( __( 'Security check failed', METS_TEXT_DOMAIN ) );
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_tickets' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions', METS_TEXT_DOMAIN ) );
			return;
		}

		$period = sanitize_text_field( $_POST['period'] ?? 'today' );
		$date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
		$date_to = sanitize_text_field( $_POST['date_to'] ?? '' );
		$filters = isset( $_POST['filters'] ) ? (array) $_POST['filters'] : array();

		try {
			$data = array(
				'metrics' => $this->get_metrics_data( $period, $date_from, $date_to ),
				'activities' => $this->get_activities_data( $period, $date_from, $date_to, $filters ),
				'agents' => $this->get_agent_performance_data( $period, $date_from, $date_to ),
				'sla' => $this->get_sla_data( $period, $date_from, $date_to )
			);

			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Get metrics data for specified period
	 *
	 * @since    1.0.0
	 * @param    string $period    Period type
	 * @param    string $date_from Custom date from
	 * @param    string $date_to   Custom date to
	 * @return   array            Metrics data
	 */
	private function get_metrics_data( $period, $date_from = '', $date_to = '' ) {
		global $wpdb;

		// Check if required tables exist
		$required_tables = array(
			$wpdb->prefix . 'mets_tickets',
			$wpdb->prefix . 'mets_ticket_ratings',
			$wpdb->prefix . 'mets_sla_tracking',
			$wpdb->prefix . 'mets_entities',
			$wpdb->prefix . 'mets_ticket_replies'
		);

		$missing_tables = array();
		foreach ( $required_tables as $table ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
				$missing_tables[] = str_replace( $wpdb->prefix, '', $table );
			}
		}

		if ( ! empty( $missing_tables ) ) {
			throw new Exception( sprintf( 
				__( 'Required database tables missing: %s. Please deactivate and reactivate the plugin to create missing tables.', METS_TEXT_DOMAIN ), 
				implode( ', ', $missing_tables ) 
			) );
		}

		$date_conditions = $this->get_date_conditions( $period, $date_from, $date_to );
		$comparison_conditions = $this->get_comparison_date_conditions( $period, $date_from, $date_to );

		// Current period metrics
		$current_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE {$date_conditions['current']}"
		);

		$current_resolved = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			 WHERE status = 'resolved' AND {$date_conditions['current']}"
		);

		$current_response = $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) 
			 FROM {$wpdb->prefix}mets_tickets 
			 WHERE first_response_at IS NOT NULL AND {$date_conditions['current']}"
		);

		$current_satisfaction = $wpdb->get_var(
			"SELECT AVG(rating) FROM {$wpdb->prefix}mets_ticket_ratings 
			 WHERE {$date_conditions['current']}"
		);

		// Previous period metrics for comparison
		$previous_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE {$comparison_conditions}"
		);

		$previous_resolved = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			 WHERE status = 'resolved' AND {$comparison_conditions}"
		);

		$previous_response = $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) 
			 FROM {$wpdb->prefix}mets_tickets 
			 WHERE first_response_at IS NOT NULL AND {$comparison_conditions}"
		);

		$previous_satisfaction = $wpdb->get_var(
			"SELECT AVG(rating) FROM {$wpdb->prefix}mets_ticket_ratings 
			 WHERE {$comparison_conditions}"
		);

		// Calculate percentage changes
		$tickets_change = $previous_tickets > 0 ? round((($current_tickets - $previous_tickets) / $previous_tickets) * 100, 1) : 0;
		$resolved_change = $previous_resolved > 0 ? round((($current_resolved - $previous_resolved) / $previous_resolved) * 100, 1) : 0;
		$response_change = $previous_response > 0 ? round((($current_response - $previous_response) / $previous_response) * 100, 1) : 0;
		$satisfaction_change = $previous_satisfaction > 0 ? round((($current_satisfaction - $previous_satisfaction) / $previous_satisfaction) * 100, 1) : 0;

		return array(
			array(
				'key' => 'new_tickets',
				'number' => intval( $current_tickets ),
				'change' => $tickets_change,
				'is_negative' => $tickets_change < 0
			),
			array(
				'key' => 'resolved_tickets',
				'number' => intval( $current_resolved ),
				'change' => $resolved_change,
				'is_negative' => $resolved_change < 0
			),
			array(
				'key' => 'avg_response',
				'number' => round( floatval( $current_response ), 1 ) . 'h',
				'change' => $response_change,
				'is_negative' => $response_change > 0 // For response time, increase is negative
			),
			array(
				'key' => 'satisfaction',
				'number' => round( floatval( $current_satisfaction ), 1 ) . '/5',
				'change' => $satisfaction_change,
				'is_negative' => $satisfaction_change < 0
			)
		);
	}

	/**
	 * Display team metrics
	 *
	 * @since    1.0.0
	 */
	private function display_team_metrics() {
		global $wpdb;

		// Total tickets today
		$tickets_today = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			 WHERE DATE(created_at) = CURDATE()"
		);

		// Total tickets yesterday
		$tickets_yesterday = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			 WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"
		);

		// Resolved tickets today
		$resolved_today = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			 WHERE status = 'resolved' AND DATE(updated_at) = CURDATE()"
		);

		// Resolved tickets yesterday
		$resolved_yesterday = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			 WHERE status = 'resolved' AND DATE(updated_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"
		);

		// Average response time (in hours)
		$avg_response = $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) 
			 FROM {$wpdb->prefix}mets_tickets 
			 WHERE first_response_at IS NOT NULL 
			 AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
		);

		// Average response time last week
		$avg_response_last_week = $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) 
			 FROM {$wpdb->prefix}mets_tickets 
			 WHERE first_response_at IS NOT NULL 
			 AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
			 AND DATE(created_at) < DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
		);

		// Customer satisfaction
		$satisfaction = $wpdb->get_var(
			"SELECT AVG(rating) FROM {$wpdb->prefix}mets_ticket_ratings r
			 WHERE DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
		);

		// Customer satisfaction last month
		$satisfaction_last_month = $wpdb->get_var(
			"SELECT AVG(rating) FROM {$wpdb->prefix}mets_ticket_ratings r
			 WHERE DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
			 AND DATE(r.created_at) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
		);

		// Calculate percentage changes
		$tickets_change = $tickets_yesterday > 0 ? round((($tickets_today - $tickets_yesterday) / $tickets_yesterday) * 100, 1) : 0;
		$resolved_change = $resolved_yesterday > 0 ? round((($resolved_today - $resolved_yesterday) / $resolved_yesterday) * 100, 1) : 0;
		$response_change = $avg_response_last_week > 0 ? round((($avg_response - $avg_response_last_week) / $avg_response_last_week) * 100, 1) : 0;
		$satisfaction_change = $satisfaction_last_month > 0 ? round((($satisfaction - $satisfaction_last_month) / $satisfaction_last_month) * 100, 1) : 0;

		$metrics = array(
			array(
				'number' => intval( $tickets_today ),
				'label' => __( 'New Tickets Today', METS_TEXT_DOMAIN ),
				'change' => $tickets_change,
				'is_negative' => $tickets_change < 0,
				'status' => null // No status for ticket count
			),
			array(
				'number' => intval( $resolved_today ),
				'label' => __( 'Resolved Today', METS_TEXT_DOMAIN ),
				'change' => $resolved_change,
				'is_negative' => $resolved_change < 0,
				'status' => null // No status for resolved count
			),
			array(
				'number' => round( floatval( $avg_response ), 1 ) . 'h',
				'label' => __( 'Avg Response Time', METS_TEXT_DOMAIN ),
				'change' => $response_change,
				'is_negative' => $response_change > 0, // For response time, increase is negative
				'status' => $avg_response ? $this->get_response_time_status( floatval( $avg_response ) ) : null
			),
			array(
				'number' => round( floatval( $satisfaction ), 1 ) . '/5',
				'label' => __( 'Customer Satisfaction', METS_TEXT_DOMAIN ),
				'change' => $satisfaction_change,
				'is_negative' => $satisfaction_change < 0,
				'status' => $satisfaction ? $this->get_satisfaction_status( floatval( $satisfaction ) ) : null
			)
		);

		foreach ( $metrics as $metric ) {
			$change_text = ($metric['change'] >= 0 ? '+' : '') . $metric['change'] . '%';
			$change_class = $metric['is_negative'] ? 'mets-metric-down' : 'mets-metric-up';
			$status_class = $metric['status'] ? 'mets-status-' . $metric['status'] : '';
			
			echo '<div class="mets-metric-card ' . esc_attr( $status_class ) . '">';
			echo '<span class="mets-metric-number' . ($metric['change'] < 0 && is_numeric($metric['number']) ? ' negative' : '') . '">' . esc_html( $metric['number'] ) . '</span>';
			echo '<div class="mets-metric-label">' . esc_html( $metric['label'] ) . '</div>';
			echo '<div class="mets-metric-change ' . esc_attr( $change_class ) . '">' . esc_html( $change_text ) . '</div>';
			if ( $metric['status'] ) {
				$status_text = ucfirst( $metric['status'] );
				echo '<div class="mets-metric-status mets-status-' . esc_attr( $metric['status'] ) . '">' . esc_html( $status_text ) . '</div>';
			}
			echo '</div>';
		}
	}

	/**
	 * Display agent performance
	 *
	 * @since    1.0.0
	 */
	private function display_agent_performance() {
		global $wpdb;

		// Get agents with performance data
		$agents = get_users( array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'wp_capabilities',
					'value' => 'ticket_agent',
					'compare' => 'LIKE'
				),
				array(
					'key' => 'wp_capabilities',
					'value' => 'ticket_manager',
					'compare' => 'LIKE'
				)
			)
		) );

		if ( empty( $agents ) ) {
			echo '<tr><td colspan="6">' . __( 'No agent performance data available.', METS_TEXT_DOMAIN ) . '</td></tr>';
			return;
		}

		foreach ( $agents as $agent ) {
			// Active tickets
			$active_tickets = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				 WHERE assigned_to = %d AND status NOT IN ('closed', 'resolved')",
				$agent->ID
			) );

			// Resolved today
			$resolved_today = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				 WHERE assigned_to = %d AND status = 'resolved' AND DATE(updated_at) = CURDATE()",
				$agent->ID
			) );

			// Average response time
			$avg_response = $wpdb->get_var( $wpdb->prepare(
				"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) 
				 FROM {$wpdb->prefix}mets_tickets 
				 WHERE assigned_to = %d AND first_response_at IS NOT NULL 
				 AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
				$agent->ID
			) );

			// Customer rating
			$rating = $wpdb->get_var( $wpdb->prepare(
				"SELECT AVG(r.rating) FROM {$wpdb->prefix}mets_ticket_ratings r
				 INNER JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
				 WHERE t.assigned_to = %d AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
				$agent->ID
			) );

			// Workload calculation using KPI settings
			$workload = $this->get_workload_status( $active_tickets );

			// Get status indicators
			$response_status = $avg_response ? $this->get_response_time_status( floatval( $avg_response ) ) : null;
			$rating_status = $rating ? $this->get_satisfaction_status( floatval( $rating ) ) : null;

			echo '<tr>';
			echo '<td><strong>' . esc_html( $agent->display_name ) . '</strong></td>';
			echo '<td>' . intval( $active_tickets ) . '</td>';
			echo '<td>' . intval( $resolved_today ) . '</td>';
			echo '<td>';
			if ( $avg_response ) {
				echo '<span class="mets-response-' . esc_attr( $response_status ) . '">' . round( floatval( $avg_response ), 1 ) . 'h</span>';
			} else {
				echo 'N/A';
			}
			echo '</td>';
			echo '<td>';
			if ( $rating ) {
				echo '<span class="mets-rating-' . esc_attr( $rating_status ) . '">' . round( floatval( $rating ), 1 ) . '/5</span>';
			} else {
				echo 'N/A';
			}
			echo '</td>';
			echo '<td><span class="mets-workload-' . esc_attr( $workload ) . '">' . esc_html( ucfirst( $workload ) ) . '</span></td>';
			echo '</tr>';
		}
	}

	/**
	 * Display recent activity
	 *
	 * @since    1.0.0
	 */
	private function display_recent_activity() {
		global $wpdb;

		$activities = $wpdb->get_results(
			"SELECT t.ticket_number, t.subject, u.display_name, t.updated_at, t.status
			 FROM {$wpdb->prefix}mets_tickets t
			 LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
			 WHERE t.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
			 ORDER BY t.updated_at DESC
			 LIMIT 10"
		);

		if ( empty( $activities ) ) {
			echo '<p>' . __( 'No recent activity to display.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		foreach ( $activities as $activity ) {
			$time_ago = human_time_diff( strtotime( $activity->updated_at ) );
			$agent_name = $activity->display_name ?: __( 'Unassigned', METS_TEXT_DOMAIN );
			
			echo '<div class="mets-activity-item">';
			echo '<strong>' . esc_html( $activity->ticket_number ) . '</strong> - ' . esc_html( wp_trim_words( $activity->subject, 8 ) );
			echo '<br><small>' . sprintf( __( 'Updated by %s • %s ago • Status: %s', METS_TEXT_DOMAIN ), 
				esc_html( $agent_name ), 
				esc_html( $time_ago ), 
				esc_html( ucfirst( $activity->status ) ) 
			) . '</small>';
			echo '</div>';
		}
	}

	/**
	 * Display SLA performance
	 *
	 * @since    1.0.0
	 */
	private function display_sla_performance() {
		global $wpdb;

		// Response SLA compliance
		$response_compliance = $wpdb->get_var(
			"SELECT CASE 
				WHEN COUNT(*) > 0 
				THEN (COUNT(CASE WHEN s.response_sla_met = 1 THEN 1 END) * 100.0 / COUNT(*))
				ELSE 0 
			 END
			 FROM {$wpdb->prefix}mets_sla_tracking s
			 INNER JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
			 WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		// Resolution SLA compliance  
		$resolution_compliance = $wpdb->get_var(
			"SELECT CASE 
				WHEN COUNT(*) > 0 
				THEN (COUNT(CASE WHEN s.resolution_sla_met = 1 THEN 1 END) * 100.0 / COUNT(*))
				ELSE 0 
			 END
			 FROM {$wpdb->prefix}mets_sla_tracking s
			 INNER JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
			 WHERE t.status IN ('resolved', 'closed') 
			 AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		$sla_metrics = array(
			array(
				'title' => __( 'Response SLA Compliance', METS_TEXT_DOMAIN ),
				'percentage' => round( floatval( $response_compliance ), 1 ),
				'status' => $this->get_sla_status( floatval( $response_compliance ) )
			),
			array(
				'title' => __( 'Resolution SLA Compliance', METS_TEXT_DOMAIN ),
				'percentage' => round( floatval( $resolution_compliance ), 1 ),
				'status' => $this->get_sla_status( floatval( $resolution_compliance ) )
			)
		);

		foreach ( $sla_metrics as $metric ) {
			$percentage_class = 'mets-sla-' . esc_attr( $metric['status'] );
			if ( $metric['percentage'] < 0 ) {
				$percentage_class .= ' negative-value';
			}
			
			echo '<div class="mets-sla-card">';
			echo '<div class="mets-sla-title">' . esc_html( $metric['title'] ) . '</div>';
			echo '<div class="mets-sla-percentage ' . $percentage_class . '">' . esc_html( $metric['percentage'] ) . '%</div>';
			echo '<div>' . sprintf( __( 'Last 30 days', METS_TEXT_DOMAIN ) ) . '</div>';
			echo '</div>';
		}
	}

	/**
	 * Get date conditions for SQL queries
	 *
	 * @since    1.0.0
	 * @param    string $period    Period type
	 * @param    string $date_from Custom date from
	 * @param    string $date_to   Custom date to
	 * @return   array            Date conditions
	 */
	private function get_date_conditions( $period, $date_from = '', $date_to = '' ) {
		switch ( $period ) {
			case 'today':
				return array( 'current' => 'DATE(created_at) = CURDATE()' );
			case 'yesterday':
				return array( 'current' => 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)' );
			case 'this_week':
				return array( 'current' => 'YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)' );
			case 'last_week':
				return array( 'current' => 'YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)' );
			case 'this_month':
				return array( 'current' => 'YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())' );
			case 'last_month':
				return array( 'current' => 'YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))' );
			case 'custom':
				if ( $date_from && $date_to ) {
					return array( 'current' => "DATE(created_at) BETWEEN '" . esc_sql( $date_from ) . "' AND '" . esc_sql( $date_to ) . "'" );
				}
				break;
		}
		
		// Default to today
		return array( 'current' => 'DATE(created_at) = CURDATE()' );
	}

	/**
	 * Get comparison date conditions for SQL queries
	 *
	 * @since    1.0.0
	 * @param    string $period    Period type
	 * @param    string $date_from Custom date from
	 * @param    string $date_to   Custom date to
	 * @return   string           Comparison condition
	 */
	private function get_comparison_date_conditions( $period, $date_from = '', $date_to = '' ) {
		switch ( $period ) {
			case 'today':
				return 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
			case 'yesterday':
				return 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 2 DAY)';
			case 'this_week':
				return 'YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)';
			case 'last_week':
				return 'YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 2 WEEK), 1)';
			case 'this_month':
				return 'YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))';
			case 'last_month':
				return 'YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 2 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 2 MONTH))';
			case 'custom':
				if ( $date_from && $date_to ) {
					$from = new DateTime( $date_from );
					$to = new DateTime( $date_to );
					$diff = $to->diff( $from )->days;
					$comparison_from = $from->sub( new DateInterval( "P{$diff}D" ) )->format( 'Y-m-d' );
					$comparison_to = $date_from;
					return "DATE(created_at) BETWEEN '" . esc_sql( $comparison_from ) . "' AND '" . esc_sql( $comparison_to ) . "'";
				}
				break;
		}
		
		// Default to yesterday
		return 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
	}

	/**
	 * Get activities data for specified period
	 *
	 * @since    1.0.0
	 * @param    string $period    Period type
	 * @param    string $date_from Custom date from
	 * @param    string $date_to   Custom date to
	 * @param    array  $filters   Activity filters
	 * @return   array            Activities data
	 */
	private function get_activities_data( $period, $date_from = '', $date_to = '', $filters = array() ) {
		global $wpdb;

		$date_conditions = $this->get_date_conditions( $period, $date_from, $date_to );
		$where_clause = str_replace( 'created_at', 't.updated_at', $date_conditions['current'] );

		// Add status filter if specified
		if ( isset( $filters['activity'] ) && $filters['activity'] !== 'all' ) {
			$where_clause .= " AND t.status = '" . esc_sql( $filters['activity'] ) . "'";
		}

		// Add search filter if specified
		if ( isset( $filters['search'] ) && ! empty( $filters['search'] ) ) {
			$search_term = esc_sql( $filters['search'] );
			$where_clause .= " AND (t.ticket_number LIKE '%{$search_term}%' OR t.subject LIKE '%{$search_term}%')";
		}

		$activities = $wpdb->get_results(
			"SELECT t.id, t.ticket_number, t.subject, u.display_name, t.updated_at, t.status
			 FROM {$wpdb->prefix}mets_tickets t
			 LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
			 WHERE {$where_clause}
			 ORDER BY t.updated_at DESC
			 LIMIT 20"
		);

		$result = array();
		foreach ( $activities as $activity ) {
			$result[] = array(
				'ticket_id' => $activity->id,
				'ticket_number' => $activity->ticket_number,
				'subject' => wp_trim_words( $activity->subject, 8 ),
				'agent_name' => $activity->display_name ?: __( 'Unassigned', METS_TEXT_DOMAIN ),
				'time_ago' => human_time_diff( strtotime( $activity->updated_at ) ),
				'status' => ucfirst( $activity->status ),
				'action' => 'Updated',
				'ticket_url' => admin_url( 'admin.php?page=mets-tickets&action=view&ticket_id=' . $activity->id )
			);
		}

		return $result;
	}

	/**
	 * Get agent performance data for specified period
	 *
	 * @since    1.0.0
	 * @param    string $period    Period type
	 * @param    string $date_from Custom date from
	 * @param    string $date_to   Custom date to
	 * @return   array            Agent performance data
	 */
	private function get_agent_performance_data( $period, $date_from = '', $date_to = '' ) {
		global $wpdb;

		$date_conditions = $this->get_date_conditions( $period, $date_from, $date_to );

		// Get agents with performance data
		$agents = get_users( array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'wp_capabilities',
					'value' => 'ticket_agent',
					'compare' => 'LIKE'
				),
				array(
					'key' => 'wp_capabilities',
					'value' => 'ticket_manager',
					'compare' => 'LIKE'
				)
			)
		) );

		$result = array();
		foreach ( $agents as $agent ) {
			// Active tickets
			$active_tickets = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				 WHERE assigned_to = %d AND status NOT IN ('closed', 'resolved')",
				$agent->ID
			) );

			// Resolved today
			$resolved_today = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				 WHERE assigned_to = %d AND status = 'resolved' AND " . str_replace( 'created_at', 'updated_at', $date_conditions['current'] ),
				$agent->ID
			) );

			// Average response time
			$avg_response = $wpdb->get_var( $wpdb->prepare(
				"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) 
				 FROM {$wpdb->prefix}mets_tickets 
				 WHERE assigned_to = %d AND first_response_at IS NOT NULL 
				 AND " . $date_conditions['current'],
				$agent->ID
			) );

			// Customer rating
			$rating = $wpdb->get_var( $wpdb->prepare(
				"SELECT AVG(r.rating) FROM {$wpdb->prefix}mets_ticket_ratings r
				 INNER JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
				 WHERE t.assigned_to = %d AND " . str_replace( 'created_at', 'r.created_at', $date_conditions['current'] ),
				$agent->ID
			) );

			// Workload calculation
			$workload = 'low';
			$workload_label = __( 'Low', METS_TEXT_DOMAIN );
			if ( $active_tickets > 10 ) {
				$workload = 'high';
				$workload_label = __( 'High', METS_TEXT_DOMAIN );
			} elseif ( $active_tickets > 5 ) {
				$workload = 'medium';
				$workload_label = __( 'Medium', METS_TEXT_DOMAIN );
			}

			$result[] = array(
				'id' => $agent->ID,
				'name' => $agent->display_name,
				'active_tickets' => intval( $active_tickets ),
				'resolved_today' => intval( $resolved_today ),
				'avg_response' => $avg_response ? round( floatval( $avg_response ), 1 ) . 'h' : 'N/A',
				'rating' => $rating ? round( floatval( $rating ), 1 ) . '/5' : 'N/A',
				'workload' => $workload,
				'workload_label' => $workload_label
			);
		}

		return $result;
	}

	/**
	 * Get SLA data for specified period
	 *
	 * @since    1.0.0
	 * @param    string $period    Period type
	 * @param    string $date_from Custom date from
	 * @param    string $date_to   Custom date to
	 * @return   array            SLA data
	 */
	private function get_sla_data( $period, $date_from = '', $date_to = '' ) {
		global $wpdb;

		// Check if required SLA table exists
		$sla_table = $wpdb->prefix . 'mets_sla_tracking';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$sla_table'" ) !== $sla_table ) {
			throw new Exception( sprintf( __( 'Required database table %s does not exist. Please deactivate and reactivate the plugin.', METS_TEXT_DOMAIN ), $sla_table ) );
		}

		$date_conditions = $this->get_date_conditions( $period, $date_from, $date_to );

		// Response SLA compliance
		$response_compliance = $wpdb->get_var(
			"SELECT CASE 
				WHEN COUNT(*) > 0 
				THEN (COUNT(CASE WHEN s.response_sla_met = 1 THEN 1 END) * 100.0 / COUNT(*))
				ELSE 0 
			 END
			 FROM {$wpdb->prefix}mets_sla_tracking s
			 INNER JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
			 WHERE " . $date_conditions['current']
		);

		// Resolution SLA compliance  
		$resolution_compliance = $wpdb->get_var(
			"SELECT CASE 
				WHEN COUNT(*) > 0 
				THEN (COUNT(CASE WHEN s.resolution_sla_met = 1 THEN 1 END) * 100.0 / COUNT(*))
				ELSE 0 
			 END
			 FROM {$wpdb->prefix}mets_sla_tracking s
			 INNER JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
			 WHERE t.status IN ('resolved', 'closed') 
			 AND " . str_replace( 'created_at', 't.updated_at', $date_conditions['current'] )
		);

		return array(
			array(
				'key' => 'response_sla',
				'percentage' => round( floatval( $response_compliance ), 1 ),
				'status' => floatval( $response_compliance ) >= 90 ? 'good' : ( floatval( $response_compliance ) >= 75 ? 'warning' : 'critical' )
			),
			array(
				'key' => 'resolution_sla',
				'percentage' => round( floatval( $resolution_compliance ), 1 ),
				'status' => floatval( $resolution_compliance ) >= 85 ? 'good' : ( floatval( $resolution_compliance ) >= 70 ? 'warning' : 'critical' )
			)
		);
	}

	/**
	 * Calculate safe percentage change between two values
	 *
	 * @param float $current Current value
	 * @param float $previous Previous value
	 * @return float Percentage change, handling division by zero
	 * @since 1.0.0
	 */
	private function calculate_safe_percentage_change( $current, $previous ) {
		// Handle null values
		if ( $current === null ) $current = 0;
		if ( $previous === null ) $previous = 0;

		// Handle division by zero
		if ( $previous == 0 ) {
			return $current > 0 ? 100.0 : 0.0;
		}

		return round( ( ( $current - $previous ) / $previous ) * 100, 1 );
	}

	/**
	 * Calculate safe percentage from numerator and denominator
	 *
	 * @param int $numerator Numerator value
	 * @param int $denominator Denominator value  
	 * @return float Percentage value, handling division by zero
	 * @since 1.0.0
	 */
	private function calculate_safe_percentage( $numerator, $denominator ) {
		if ( $denominator == 0 || $denominator === null ) {
			return 0.0;
		}

		return round( ( $numerator / $denominator ) * 100, 1 );
	}

	/**
	 * Validate numeric value for metrics calculations
	 *
	 * @param mixed $value Value to validate
	 * @param float $default Default value if invalid
	 * @return float Validated numeric value
	 * @since 1.0.0
	 */
	private function validate_numeric_metric( $value, $default = 0.0 ) {
		if ( $value === null || !is_numeric( $value ) || $value < 0 ) {
			return $default;
		}

		return floatval( $value );
	}

	/**
	 * Get KPI settings with defaults
	 *
	 * @since    1.0.0
	 * @return   array    KPI settings
	 */
	private function get_kpi_settings() {
		$default_settings = array(
			// Response Time KPIs (in hours)
			'response_time_excellent' => 1.0,
			'response_time_good' => 4.0,
			'response_time_poor' => 8.0,
			
			// Resolution Time KPIs (in hours)
			'resolution_time_excellent' => 24.0,
			'resolution_time_good' => 48.0,
			'resolution_time_poor' => 72.0,
			
			// Agent Workload KPIs (number of active tickets)
			'workload_low' => 10,
			'workload_medium' => 20,
			'workload_high' => 30,
			
			// Customer Satisfaction KPIs (1-5 scale)
			'satisfaction_excellent' => 4.5,
			'satisfaction_good' => 3.5,
			'satisfaction_poor' => 2.5,
			
			// SLA Compliance KPIs (percentage)
			'sla_excellent' => 95,
			'sla_good' => 85,
			'sla_poor' => 75,
			
			// Dashboard Display Settings
			'default_time_period' => 'this_week',
			'show_trends' => 1,
			'auto_refresh_interval' => 300,
		);

		return get_option( 'mets_performance_kpi_settings', $default_settings );
	}

	/**
	 * Get workload status based on KPI settings
	 *
	 * @since    1.0.0
	 * @param    int    $active_tickets    Number of active tickets
	 * @return   string                   Workload status (low, medium, high)
	 */
	private function get_workload_status( $active_tickets ) {
		if ( $active_tickets >= $this->kpi_settings['workload_high'] ) {
			return 'high';
		} elseif ( $active_tickets >= $this->kpi_settings['workload_medium'] ) {
			return 'medium';
		}
		return 'low';
	}

	/**
	 * Get SLA compliance status based on KPI settings
	 *
	 * @since    1.0.0
	 * @param    float  $compliance_percentage    SLA compliance percentage
	 * @return   string                          Status (good, warning, critical)
	 */
	private function get_sla_status( $compliance_percentage ) {
		if ( $compliance_percentage >= $this->kpi_settings['sla_excellent'] ) {
			return 'good';
		} elseif ( $compliance_percentage >= $this->kpi_settings['sla_good'] ) {
			return 'warning';
		}
		return 'critical';
	}

	/**
	 * Get response time status based on KPI settings
	 *
	 * @since    1.0.0
	 * @param    float  $response_time    Response time in hours
	 * @return   string                  Status (excellent, good, poor)
	 */
	private function get_response_time_status( $response_time ) {
		if ( $response_time <= $this->kpi_settings['response_time_excellent'] ) {
			return 'excellent';
		} elseif ( $response_time <= $this->kpi_settings['response_time_good'] ) {
			return 'good';
		}
		return 'poor';
	}

	/**
	 * Get satisfaction status based on KPI settings
	 *
	 * @since    1.0.0
	 * @param    float  $satisfaction    Satisfaction rating (1-5)
	 * @return   string                 Status (excellent, good, poor)
	 */
	private function get_satisfaction_status( $satisfaction ) {
		if ( $satisfaction >= $this->kpi_settings['satisfaction_excellent'] ) {
			return 'excellent';
		} elseif ( $satisfaction >= $this->kpi_settings['satisfaction_good'] ) {
			return 'good';
		}
		return 'poor';
	}
}