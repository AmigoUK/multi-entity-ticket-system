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
	 * Display manager dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_manager_dashboard_page() {
		?>
		<div class="wrap">
			<h1><?php _e( '📊 Team Performance Dashboard', METS_TEXT_DOMAIN ); ?></h1>
			
			<div class="mets-manager-dashboard">
				<div class="mets-dashboard-header">
					<p><?php _e( 'Monitor team performance, agent workloads, and support metrics.', METS_TEXT_DOMAIN ); ?></p>
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

		<style>
		.mets-manager-dashboard { margin-top: 20px; }
		.mets-performance-overview, .mets-agent-performance, .mets-recent-activity, .mets-sla-performance { margin-bottom: 40px; }
		.mets-metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
		.mets-metric-card { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; text-align: center; }
		.mets-metric-number { font-size: 2em; font-weight: bold; color: #0073aa; display: block; }
		.mets-metric-label { color: #646970; margin-top: 5px; }
		.mets-metric-change { font-size: 12px; margin-top: 5px; }
		.mets-metric-up { color: #46b450; }
		.mets-metric-down { color: #dc3232; }
		.mets-workload-low { color: #46b450; }
		.mets-workload-medium { color: #f0b849; }
		.mets-workload-high { color: #dc3232; }
		.mets-activity-feed { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; max-height: 400px; overflow-y: auto; }
		.mets-activity-item { padding: 10px 0; border-bottom: 1px solid #eee; }
		.mets-activity-item:last-child { border-bottom: none; }
		.mets-activity-time { color: #646970; font-size: 12px; }
		.mets-sla-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
		.mets-sla-card { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; }
		.mets-sla-title { font-weight: 600; margin-bottom: 10px; }
		.mets-sla-percentage { font-size: 1.5em; font-weight: bold; margin-bottom: 5px; }
		.mets-sla-good { color: #46b450; }
		.mets-sla-warning { color: #f0b849; }
		.mets-sla-critical { color: #dc3232; }
		</style>
		<?php
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

		// Resolved tickets today
		$resolved_today = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			 WHERE status = 'resolved' AND DATE(updated_at) = CURDATE()"
		);

		// Average response time (in hours)
		$avg_response = $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) 
			 FROM {$wpdb->prefix}mets_tickets 
			 WHERE first_response_at IS NOT NULL 
			 AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
		);

		// Customer satisfaction
		$satisfaction = $wpdb->get_var(
			"SELECT AVG(rating) FROM {$wpdb->prefix}mets_ticket_ratings 
			 WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
		);

		$metrics = array(
			array(
				'number' => intval( $tickets_today ),
				'label' => __( 'New Tickets Today', METS_TEXT_DOMAIN ),
				'change' => '+12%',
				'trend' => 'up'
			),
			array(
				'number' => intval( $resolved_today ),
				'label' => __( 'Resolved Today', METS_TEXT_DOMAIN ),
				'change' => '+8%',
				'trend' => 'up'
			),
			array(
				'number' => round( floatval( $avg_response ), 1 ) . 'h',
				'label' => __( 'Avg Response Time', METS_TEXT_DOMAIN ),
				'change' => '-15%',
				'trend' => 'up'
			),
			array(
				'number' => round( floatval( $satisfaction ), 1 ) . '/5',
				'label' => __( 'Customer Satisfaction', METS_TEXT_DOMAIN ),
				'change' => '+3%',
				'trend' => 'up'
			)
		);

		foreach ( $metrics as $metric ) {
			echo '<div class="mets-metric-card">';
			echo '<span class="mets-metric-number">' . esc_html( $metric['number'] ) . '</span>';
			echo '<div class="mets-metric-label">' . esc_html( $metric['label'] ) . '</div>';
			echo '<div class="mets-metric-change mets-metric-' . esc_attr( $metric['trend'] ) . '">' . esc_html( $metric['change'] ) . '</div>';
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

			// Workload calculation
			$workload = 'low';
			if ( $active_tickets > 10 ) {
				$workload = 'high';
			} elseif ( $active_tickets > 5 ) {
				$workload = 'medium';
			}

			echo '<tr>';
			echo '<td><strong>' . esc_html( $agent->display_name ) . '</strong></td>';
			echo '<td>' . intval( $active_tickets ) . '</td>';
			echo '<td>' . intval( $resolved_today ) . '</td>';
			echo '<td>' . ( $avg_response ? round( floatval( $avg_response ), 1 ) . 'h' : 'N/A' ) . '</td>';
			echo '<td>' . ( $rating ? round( floatval( $rating ), 1 ) . '/5' : 'N/A' ) . '</td>';
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
			"SELECT (COUNT(CASE WHEN s.response_sla_met = 1 THEN 1 END) * 100.0 / COUNT(*)) 
			 FROM {$wpdb->prefix}mets_sla_tracking s
			 INNER JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
			 WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		// Resolution SLA compliance  
		$resolution_compliance = $wpdb->get_var(
			"SELECT (COUNT(CASE WHEN s.resolution_sla_met = 1 THEN 1 END) * 100.0 / COUNT(*))
			 FROM {$wpdb->prefix}mets_sla_tracking s
			 INNER JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
			 WHERE t.status IN ('resolved', 'closed') 
			 AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		$sla_metrics = array(
			array(
				'title' => __( 'Response SLA Compliance', METS_TEXT_DOMAIN ),
				'percentage' => round( floatval( $response_compliance ), 1 ),
				'status' => floatval( $response_compliance ) >= 90 ? 'good' : ( floatval( $response_compliance ) >= 75 ? 'warning' : 'critical' )
			),
			array(
				'title' => __( 'Resolution SLA Compliance', METS_TEXT_DOMAIN ),
				'percentage' => round( floatval( $resolution_compliance ), 1 ),
				'status' => floatval( $resolution_compliance ) >= 85 ? 'good' : ( floatval( $resolution_compliance ) >= 70 ? 'warning' : 'critical' )
			)
		);

		foreach ( $sla_metrics as $metric ) {
			echo '<div class="mets-sla-card">';
			echo '<div class="mets-sla-title">' . esc_html( $metric['title'] ) . '</div>';
			echo '<div class="mets-sla-percentage mets-sla-' . esc_attr( $metric['status'] ) . '">' . esc_html( $metric['percentage'] ) . '%</div>';
			echo '<div>' . sprintf( __( 'Last 30 days', METS_TEXT_DOMAIN ) ) . '</div>';
			echo '</div>';
		}
	}
}