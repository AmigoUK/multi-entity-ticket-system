<?php
/**
 * Enhanced Reporting Dashboard
 *
 * Handles the comprehensive reporting dashboard for tickets, SLA, and knowledgebase
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * The Enhanced Reporting Dashboard class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Reporting_Dashboard {

	/**
	 * Display the main reporting dashboard
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		$period = sanitize_text_field( $_GET['period'] ?? '30days' );
		$entity_id = isset( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : null;

		// Get entities for filter
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entities = $entity_model->get_all( array( 'status' => 'active', 'parent_id' => 'all' ) );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Comprehensive Reporting Dashboard', METS_TEXT_DOMAIN ); ?></h1>
			
			<!-- Dashboard Filters -->
			<div class="mets-dashboard-filters" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
				<form method="get" action="" style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
					<input type="hidden" name="page" value="mets-reporting-dashboard">
					
					<label for="period">
						<strong><?php _e( 'Period:', METS_TEXT_DOMAIN ); ?></strong>
						<select name="period" id="period" style="margin-left: 5px; min-width: 120px;">
							<option value="24hours" <?php selected( $period, '24hours' ); ?>><?php _e( 'Last 24 Hours', METS_TEXT_DOMAIN ); ?></option>
							<option value="7days" <?php selected( $period, '7days' ); ?>><?php _e( 'Last 7 Days', METS_TEXT_DOMAIN ); ?></option>
							<option value="30days" <?php selected( $period, '30days' ); ?>><?php _e( 'Last 30 Days', METS_TEXT_DOMAIN ); ?></option>
							<option value="90days" <?php selected( $period, '90days' ); ?>><?php _e( 'Last 90 Days', METS_TEXT_DOMAIN ); ?></option>
							<option value="all" <?php selected( $period, 'all' ); ?>><?php _e( 'All Time', METS_TEXT_DOMAIN ); ?></option>
						</select>
					</label>
					
					<label for="entity_id">
						<strong><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></strong>
						<select name="entity_id" id="entity_id" style="margin-left: 5px; min-width: 150px;">
							<option value=""><?php _e( 'All Entities', METS_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $entities as $entity ) : ?>
								<option value="<?php echo esc_attr( $entity->id ); ?>" <?php selected( $entity_id, $entity->id ); ?>>
									<?php echo esc_html( $entity->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Apply Filters', METS_TEXT_DOMAIN ); ?>">
					<a href="<?php echo admin_url( 'admin.php?page=mets-reporting-dashboard' ); ?>" class="button"><?php _e( 'Reset', METS_TEXT_DOMAIN ); ?></a>
				</form>
			</div>

			<!-- Dashboard Grid -->
			<div class="mets-dashboard-grid">
				<!-- Key Performance Indicators -->
				<div class="mets-dashboard-section">
					<h2><?php _e( 'Key Performance Indicators', METS_TEXT_DOMAIN ); ?></h2>
					<?php $this->render_kpi_cards( $period, $entity_id ); ?>
				</div>

				<!-- Ticket Analytics -->
				<div class="mets-dashboard-section">
					<h2><?php _e( 'Ticket Analytics', METS_TEXT_DOMAIN ); ?></h2>
					<div class="mets-dashboard-columns">
						<div class="mets-dashboard-column">
							<?php $this->render_ticket_status_breakdown( $period, $entity_id ); ?>
						</div>
						<div class="mets-dashboard-column">
							<?php $this->render_priority_distribution( $period, $entity_id ); ?>
						</div>
					</div>
				</div>

				<!-- SLA Performance -->
				<div class="mets-dashboard-section">
					<h2><?php _e( 'SLA Performance', METS_TEXT_DOMAIN ); ?></h2>
					<div class="mets-dashboard-columns">
						<div class="mets-dashboard-column">
							<?php $this->render_sla_compliance( $period, $entity_id ); ?>
						</div>
						<div class="mets-dashboard-column">
							<?php $this->render_response_times( $period, $entity_id ); ?>
						</div>
					</div>
				</div>

				<!-- Agent Performance -->
				<div class="mets-dashboard-section">
					<h2><?php _e( 'Agent Performance', METS_TEXT_DOMAIN ); ?></h2>
					<?php $this->render_agent_performance( $period, $entity_id ); ?>
				</div>

				<!-- Knowledge Base Performance -->
				<?php if ( current_user_can( 'read_kb_articles' ) ) : ?>
				<div class="mets-dashboard-section">
					<h2><?php _e( 'Knowledge Base Performance', METS_TEXT_DOMAIN ); ?></h2>
					<div class="mets-dashboard-columns">
						<div class="mets-dashboard-column">
							<?php $this->render_kb_usage_stats( $period, $entity_id ); ?>
						</div>
						<div class="mets-dashboard-column">
							<?php $this->render_top_articles( $period, $entity_id ); ?>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Recent Activity -->
				<div class="mets-dashboard-section">
					<h2><?php _e( 'Recent Activity', METS_TEXT_DOMAIN ); ?></h2>
					<?php $this->render_recent_activity( $entity_id ); ?>
				</div>
			</div>
		</div>

		<?php $this->render_dashboard_styles(); ?>
		<?php
	}

	/**
	 * Render KPI cards
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_kpi_cards( $period, $entity_id ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND entity_id = %d", $entity_id ) : "";

		// Get ticket metrics
		$ticket_sql = "SELECT 
			COUNT(*) as total_tickets,
			COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
			COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
			COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
			AVG(CASE WHEN resolved_at IS NOT NULL 
				THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_time
		FROM {$wpdb->prefix}mets_tickets 
		WHERE 1=1 {$where_date} {$entity_where}";

		$ticket_stats = $wpdb->get_row( $ticket_sql );
		
		// Handle potential SQL errors
		if ( $wpdb->last_error ) {
			error_log( 'METS Reporting Dashboard SQL Error: ' . $wpdb->last_error );
			$ticket_stats = (object) array(
				'total_tickets' => 0,
				'open_tickets' => 0, 
				'resolved_tickets' => 0,
				'closed_tickets' => 0,
				'avg_resolution_time' => 0
			);
		}

		// Get SLA compliance
		$sla_sql = "SELECT 
			COUNT(*) as total_with_sla,
			COUNT(CASE WHEN (sla_response_breached = 0 AND sla_resolution_breached = 0) THEN 1 END) as sla_met,
			COUNT(CASE WHEN (sla_response_breached = 1 OR sla_resolution_breached = 1) THEN 1 END) as sla_breached
		FROM {$wpdb->prefix}mets_tickets 
		WHERE (sla_response_due IS NOT NULL OR sla_resolution_due IS NOT NULL) {$where_date} {$entity_where}";

		$sla_stats = $wpdb->get_row( $sla_sql );
		
		// Handle potential SQL errors
		if ( $wpdb->last_error ) {
			error_log( 'METS Reporting Dashboard SLA SQL Error: ' . $wpdb->last_error );
			$sla_stats = (object) array(
				'total_with_sla' => 0,
				'sla_met' => 0,
				'sla_breached' => 0
			);
		}

		$sla_compliance = $sla_stats->total_with_sla > 0 ? 
			round( ( $sla_stats->sla_met / $sla_stats->total_with_sla ) * 100, 1 ) : 0;

		?>
		<div class="mets-kpi-grid">
			<div class="mets-kpi-card">
				<div class="mets-kpi-icon">📊</div>
				<div class="mets-kpi-content">
					<div class="mets-kpi-value"><?php echo number_format( $ticket_stats->total_tickets ); ?></div>
					<div class="mets-kpi-label"><?php _e( 'Total Tickets', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>

			<div class="mets-kpi-card">
				<div class="mets-kpi-icon">🔓</div>
				<div class="mets-kpi-content">
					<div class="mets-kpi-value"><?php echo number_format( $ticket_stats->open_tickets ); ?></div>
					<div class="mets-kpi-label"><?php _e( 'Open Tickets', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>

			<div class="mets-kpi-card">
				<div class="mets-kpi-icon">✅</div>
				<div class="mets-kpi-content">
					<div class="mets-kpi-value"><?php echo number_format( $ticket_stats->resolved_tickets ); ?></div>
					<div class="mets-kpi-label"><?php _e( 'Resolved Tickets', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>

			<div class="mets-kpi-card">
				<div class="mets-kpi-icon">⏱️</div>
				<div class="mets-kpi-content">
					<div class="mets-kpi-value"><?php echo $ticket_stats->avg_resolution_time ? round( $ticket_stats->avg_resolution_time, 1 ) . 'h' : '-'; ?></div>
					<div class="mets-kpi-label"><?php _e( 'Avg Resolution Time', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>

			<div class="mets-kpi-card">
				<div class="mets-kpi-icon">📈</div>
				<div class="mets-kpi-content">
					<div class="mets-kpi-value"><?php echo $sla_compliance; ?>%</div>
					<div class="mets-kpi-label"><?php _e( 'SLA Compliance', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render ticket status breakdown
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_ticket_status_breakdown( $period, $entity_id ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
			status,
			COUNT(*) as count,
			ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE 1=1 {$where_date} {$entity_where})), 1) as percentage
		FROM {$wpdb->prefix}mets_tickets 
		WHERE 1=1 {$where_date} {$entity_where}
		GROUP BY status
		ORDER BY count DESC";

		$results = $wpdb->get_results( $sql );
		
		// Handle SQL errors
		if ( $wpdb->last_error ) {
			error_log( 'METS Reporting Dashboard Status Chart SQL Error: ' . $wpdb->last_error );
			$results = array();
		}

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Ticket Status Distribution', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<?php if ( ! empty( $results ) ) : ?>
					<div class="mets-status-chart">
						<?php foreach ( $results as $status ) : ?>
							<div class="mets-status-item">
								<div class="mets-status-bar">
									<div class="mets-status-fill mets-status-<?php echo esc_attr( $status->status ); ?>" 
										 style="width: <?php echo $status->percentage; ?>%;"></div>
								</div>
								<div class="mets-status-details">
									<span class="mets-status-name"><?php echo ucfirst( esc_html( $status->status ) ); ?></span>
									<span class="mets-status-count"><?php echo number_format( $status->count ); ?> (<?php echo $status->percentage; ?>%)</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php _e( 'No tickets found for the selected criteria.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render priority distribution
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_priority_distribution( $period, $entity_id ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
			priority,
			COUNT(*) as count,
			ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE 1=1 {$where_date} {$entity_where})), 1) as percentage
		FROM {$wpdb->prefix}mets_tickets 
		WHERE 1=1 {$where_date} {$entity_where}
		GROUP BY priority
		ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low')";

		$results = $wpdb->get_results( $sql );

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Priority Distribution', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<?php if ( ! empty( $results ) ) : ?>
					<div class="mets-priority-chart">
						<?php foreach ( $results as $priority ) : ?>
							<div class="mets-priority-item">
								<div class="mets-priority-bar">
									<div class="mets-priority-fill mets-priority-<?php echo esc_attr( $priority->priority ); ?>" 
										 style="width: <?php echo $priority->percentage; ?>%;"></div>
								</div>
								<div class="mets-priority-details">
									<span class="mets-priority-name"><?php echo ucfirst( esc_html( $priority->priority ) ); ?></span>
									<span class="mets-priority-count"><?php echo number_format( $priority->count ); ?> (<?php echo $priority->percentage; ?>%)</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php _e( 'No tickets found for the selected criteria.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render SLA compliance
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_sla_compliance( $period, $entity_id ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
			CASE 
				WHEN sla_response_breached = 1 OR sla_resolution_breached = 1 THEN 'breached'
				WHEN (sla_response_due IS NOT NULL AND sla_response_due < NOW()) OR 
					 (sla_resolution_due IS NOT NULL AND sla_resolution_due < NOW()) THEN 'warning'
				ELSE 'met'
			END as sla_status,
			COUNT(*) as count
		FROM {$wpdb->prefix}mets_tickets 
		WHERE (sla_response_due IS NOT NULL OR sla_resolution_due IS NOT NULL) {$where_date} {$entity_where}
		GROUP BY sla_status";

		$results = $wpdb->get_results( $sql );

		$total = array_sum( array_column( $results, 'count' ) );

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'SLA Compliance', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<?php if ( ! empty( $results ) && $total > 0 ) : ?>
					<div class="mets-sla-chart">
						<?php foreach ( $results as $sla ) : ?>
							<?php $percentage = round( ( $sla->count / $total ) * 100, 1 ); ?>
							<div class="mets-sla-item">
								<div class="mets-sla-indicator mets-sla-<?php echo esc_attr( $sla->sla_status ); ?>"></div>
								<div class="mets-sla-details">
									<span class="mets-sla-status"><?php echo ucfirst( esc_html( $sla->sla_status ) ); ?></span>
									<span class="mets-sla-count"><?php echo number_format( $sla->count ); ?> (<?php echo $percentage; ?>%)</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php _e( 'No SLA data available for the selected criteria.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render response times
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_response_times( $period, $entity_id ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND t.entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
			AVG(TIMESTAMPDIFF(MINUTE, t.created_at, r.created_at)) as avg_first_response,
			MIN(TIMESTAMPDIFF(MINUTE, t.created_at, r.created_at)) as min_first_response,
			MAX(TIMESTAMPDIFF(MINUTE, t.created_at, r.created_at)) as max_first_response,
			COUNT(*) as total_responses
		FROM {$wpdb->prefix}mets_tickets t
		JOIN {$wpdb->prefix}mets_ticket_replies r ON t.id = r.ticket_id
		WHERE r.user_type != 'customer' {$where_date} {$entity_where}
		AND r.id = (
			SELECT MIN(r2.id) 
			FROM {$wpdb->prefix}mets_ticket_replies r2 
			WHERE r2.ticket_id = t.id AND r2.user_type != 'customer'
		)";

		$response_stats = $wpdb->get_row( $sql );

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Response Times', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<?php if ( $response_stats && $response_stats->total_responses > 0 ) : ?>
					<div class="mets-response-stats">
						<div class="mets-response-stat">
							<div class="mets-response-value"><?php echo round( $response_stats->avg_first_response / 60, 1 ); ?>h</div>
							<div class="mets-response-label"><?php _e( 'Avg First Response', METS_TEXT_DOMAIN ); ?></div>
						</div>
						<div class="mets-response-stat">
							<div class="mets-response-value"><?php echo round( $response_stats->min_first_response / 60, 1 ); ?>h</div>
							<div class="mets-response-label"><?php _e( 'Fastest Response', METS_TEXT_DOMAIN ); ?></div>
						</div>
						<div class="mets-response-stat">
							<div class="mets-response-value"><?php echo round( $response_stats->max_first_response / 60, 1 ); ?>h</div>
							<div class="mets-response-label"><?php _e( 'Slowest Response', METS_TEXT_DOMAIN ); ?></div>
						</div>
					</div>
				<?php else : ?>
					<p><?php _e( 'No response time data available.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render agent performance
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_agent_performance( $period, $entity_id ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND t.entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
			u.display_name,
			COUNT(DISTINCT t.id) as tickets_handled,
			COUNT(DISTINCT CASE WHEN t.status = 'resolved' THEN t.id END) as tickets_resolved,
			AVG(CASE WHEN t.resolved_at IS NOT NULL 
				THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) END) as avg_resolution_time,
			COUNT(DISTINCT r.id) as total_replies
		FROM {$wpdb->prefix}users u
		LEFT JOIN {$wpdb->prefix}mets_tickets t ON u.ID = t.assigned_to
		LEFT JOIN {$wpdb->prefix}mets_ticket_replies r ON t.id = r.ticket_id AND r.user_id = u.ID AND r.user_type = 'agent'
		WHERE u.ID IN (
			SELECT DISTINCT assigned_to 
			FROM {$wpdb->prefix}mets_tickets 
			WHERE assigned_to IS NOT NULL {$where_date} {$entity_where}
		)
		GROUP BY u.ID, u.display_name
		ORDER BY tickets_handled DESC
		LIMIT 10";

		$agents = $wpdb->get_results( $sql );

		?>
		<div class="postbox" style="grid-column: 1 / -1;">
			<h3 class="hndle"><span><?php _e( 'Top Agent Performance', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<?php if ( ! empty( $agents ) ) : ?>
					<div class="mets-agent-table">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Tickets Handled', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Tickets Resolved', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Resolution Rate', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Avg Resolution Time', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Total Replies', METS_TEXT_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $agents as $agent ) : ?>
									<?php $resolution_rate = $agent->tickets_handled > 0 ? round( ( $agent->tickets_resolved / $agent->tickets_handled ) * 100, 1 ) : 0; ?>
									<tr>
										<td><strong><?php echo esc_html( $agent->display_name ); ?></strong></td>
										<td><?php echo number_format( $agent->tickets_handled ); ?></td>
										<td><?php echo number_format( $agent->tickets_resolved ); ?></td>
										<td>
											<span class="mets-performance-rate mets-rate-<?php echo $resolution_rate >= 80 ? 'good' : ( $resolution_rate >= 60 ? 'average' : 'poor' ); ?>">
												<?php echo $resolution_rate; ?>%
											</span>
										</td>
										<td><?php echo $agent->avg_resolution_time ? round( $agent->avg_resolution_time, 1 ) . 'h' : '-'; ?></td>
										<td><?php echo number_format( $agent->total_replies ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<p><?php _e( 'No agent performance data available.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render KB usage stats
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_kb_usage_stats( $period, $entity_id ) {
		// Initialize default data
		$search_data = array(
			'total_searches' => 0,
			'overall_ctr' => 0
		);

		// Try to load KB analytics model safely
		if ( file_exists( METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
			if ( class_exists( 'METS_KB_Analytics_Model' ) ) {
				$analytics_model = new METS_KB_Analytics_Model();
				$search_data_result = $analytics_model->get_search_analytics( $period, $entity_id );
				if ( is_array( $search_data_result ) ) {
					$search_data = array_merge( $search_data, $search_data_result );
				}
			}
		}

		$where_date = $this->get_date_where_clause( $period, 'created_at' );
		$entity_where = $entity_id ? " AND article_id IN (SELECT id FROM {$GLOBALS['wpdb']->prefix}mets_kb_articles WHERE entity_id = " . intval( $entity_id ) . ")" : "";

		global $wpdb;
		// Check if KB analytics table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}mets_kb_analytics'" );
		if ( ! $table_exists ) {
			$view_stats = (object) array( 'total_views' => 0, 'articles_viewed' => 0 );
		} else {
			$view_stats = $wpdb->get_row( "SELECT COUNT(*) as total_views, COUNT(DISTINCT article_id) as articles_viewed FROM {$wpdb->prefix}mets_kb_analytics WHERE action = 'view' {$where_date} {$entity_where}" );
			$view_stats = $view_stats ?: (object) array( 'total_views' => 0, 'articles_viewed' => 0 );
		}

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Knowledge Base Usage', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<div class="mets-kb-stats">
					<div class="mets-kb-stat">
						<div class="mets-kb-stat-value"><?php echo number_format( $view_stats->total_views ); ?></div>
						<div class="mets-kb-stat-label"><?php _e( 'Article Views', METS_TEXT_DOMAIN ); ?></div>
					</div>
					<div class="mets-kb-stat">
						<div class="mets-kb-stat-value"><?php echo number_format( $search_data['total_searches'] ); ?></div>
						<div class="mets-kb-stat-label"><?php _e( 'Searches', METS_TEXT_DOMAIN ); ?></div>
					</div>
					<div class="mets-kb-stat">
						<div class="mets-kb-stat-value"><?php echo $search_data['overall_ctr']; ?>%</div>
						<div class="mets-kb-stat-label"><?php _e( 'Search CTR', METS_TEXT_DOMAIN ); ?></div>
					</div>
					<div class="mets-kb-stat">
						<div class="mets-kb-stat-value"><?php echo number_format( $view_stats->articles_viewed ); ?></div>
						<div class="mets-kb-stat-label"><?php _e( 'Articles Used', METS_TEXT_DOMAIN ); ?></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render top articles
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_top_articles( $period, $entity_id ) {
		$articles = array();

		// Try to load KB analytics model safely
		if ( file_exists( METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
			if ( class_exists( 'METS_KB_Analytics_Model' ) ) {
				$analytics_model = new METS_KB_Analytics_Model();
				$articles_result = $analytics_model->get_top_articles( 5, $period, $entity_id, 'views' );
				if ( is_array( $articles_result ) ) {
					$articles = $articles_result;
				}
			}
		}

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Top Knowledge Base Articles', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<?php if ( ! empty( $articles ) ) : ?>
					<div class="mets-top-articles">
						<?php foreach ( $articles as $article ) : ?>
							<div class="mets-article-item">
								<div class="mets-article-title">
									<a href="<?php echo admin_url( 'admin.php?page=mets-kb-edit-article&article_id=' . $article->id ); ?>" target="_blank">
										<?php echo esc_html( $article->title ); ?>
									</a>
								</div>
								<div class="mets-article-stats">
									<span class="mets-article-views"><?php echo number_format( $article->views ); ?> <?php _e( 'views', METS_TEXT_DOMAIN ); ?></span>
									<?php if ( $article->helpfulness_ratio > 0 ) : ?>
										<span class="mets-article-helpful"><?php echo $article->helpfulness_ratio; ?>% <?php _e( 'helpful', METS_TEXT_DOMAIN ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<p><a href="<?php echo admin_url( 'admin.php?page=mets-kb-analytics' ); ?>"><?php _e( 'View Full KB Analytics →', METS_TEXT_DOMAIN ); ?></a></p>
				<?php else : ?>
					<p><?php _e( 'No article data available.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render recent activity
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_recent_activity( $entity_id ) {
		global $wpdb;

		$entity_where = $entity_id ? $wpdb->prepare( " AND t.entity_id = %d", $entity_id ) : "";

		$sql = "
		(SELECT 'ticket_created' as activity_type, t.id as item_id, t.subject as title, 
			t.created_at as activity_date, u.display_name as user_name, 'ticket' as item_type
			FROM {$wpdb->prefix}mets_tickets t
			LEFT JOIN {$wpdb->prefix}users u ON t.customer_id = u.ID
			WHERE 1=1 {$entity_where})
		UNION ALL
		(SELECT 'ticket_reply' as activity_type, r.ticket_id as item_id, t.subject as title,
			r.created_at as activity_date, u.display_name as user_name, 'reply' as item_type
			FROM {$wpdb->prefix}mets_ticket_replies r
			JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
			LEFT JOIN {$wpdb->prefix}users u ON r.author_id = u.ID
			WHERE 1=1 {$entity_where})
		ORDER BY activity_date DESC
		LIMIT 20";

		$activities = $wpdb->get_results( $sql );

		?>
		<div class="postbox" style="grid-column: 1 / -1;">
			<h3 class="hndle"><span><?php _e( 'Recent Activity', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<?php if ( ! empty( $activities ) ) : ?>
					<div class="mets-activity-feed">
						<?php foreach ( $activities as $activity ) : ?>
							<div class="mets-activity-item">
								<div class="mets-activity-icon">
									<?php if ( $activity->activity_type === 'ticket_created' ) : ?>
										<span class="dashicons dashicons-plus-alt"></span>
									<?php else : ?>
										<span class="dashicons dashicons-format-chat"></span>
									<?php endif; ?>
								</div>
								<div class="mets-activity-content">
									<div class="mets-activity-description">
										<?php if ( $activity->activity_type === 'ticket_created' ) : ?>
											<strong><?php echo esc_html( $activity->user_name ?: __( 'Guest', METS_TEXT_DOMAIN ) ); ?></strong> 
											<?php _e( 'created ticket', METS_TEXT_DOMAIN ); ?>
											<a href="<?php echo admin_url( 'admin.php?page=mets-ticket-view&ticket_id=' . $activity->item_id ); ?>">
												"<?php echo esc_html( wp_trim_words( $activity->title, 8 ) ); ?>"
											</a>
										<?php else : ?>
											<strong><?php echo esc_html( $activity->user_name ?: __( 'Guest', METS_TEXT_DOMAIN ) ); ?></strong> 
											<?php _e( 'replied to ticket', METS_TEXT_DOMAIN ); ?>
											<a href="<?php echo admin_url( 'admin.php?page=mets-ticket-view&ticket_id=' . $activity->item_id ); ?>">
												"<?php echo esc_html( wp_trim_words( $activity->title, 8 ) ); ?>"
											</a>
										<?php endif; ?>
									</div>
									<div class="mets-activity-time">
										<?php echo human_time_diff( strtotime( $activity->activity_date ), current_time( 'timestamp' ) ); ?> <?php _e( 'ago', METS_TEXT_DOMAIN ); ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php _e( 'No recent activity found.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get date WHERE clause for SQL queries
	 *
	 * @since    1.0.0
	 * @param    string   $period   Period (30days, 7days, 24hours, all)
	 * @param    string   $column   Column name (default: created_at)
	 * @return   string             WHERE clause
	 */
	private function get_date_where_clause( $period, $column = 'created_at' ) {
		switch ( $period ) {
			case '24hours':
				return " AND {$column} >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
			case '7days':
				return " AND {$column} >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
			case '30days':
				return " AND {$column} >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
			case '90days':
				return " AND {$column} >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
			case 'all':
			default:
				return "";
		}
	}

	/**
	 * Render dashboard styles
	 *
	 * @since    1.0.0
	 */
	private function render_dashboard_styles() {
		?>
		<style>
		.mets-dashboard-grid {
			display: grid;
			gap: 30px;
			margin-top: 20px;
		}

		.mets-dashboard-section {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 20px;
		}

		.mets-dashboard-section h2 {
			margin: 0 0 20px 0;
			font-size: 18px;
			color: #1d2327;
			border-bottom: 1px solid #ddd;
			padding-bottom: 10px;
		}

		.mets-dashboard-columns {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
		}

		.mets-dashboard-column .postbox {
			margin: 0;
		}

		/* KPI Cards */
		.mets-kpi-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
			margin-bottom: 10px;
		}

		.mets-kpi-card {
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 20px;
			text-align: center;
			box-shadow: 0 2px 5px rgba(0,0,0,0.05);
			transition: transform 0.2s ease;
		}

		.mets-kpi-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 10px rgba(0,0,0,0.1);
		}

		.mets-kpi-icon {
			font-size: 32px;
			margin-bottom: 10px;
		}

		.mets-kpi-value {
			font-size: 28px;
			font-weight: bold;
			color: #0073aa;
			margin-bottom: 5px;
		}

		.mets-kpi-label {
			font-size: 14px;
			color: #666;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		/* Status Charts */
		.mets-status-item, .mets-priority-item {
			margin-bottom: 15px;
		}

		.mets-status-bar, .mets-priority-bar {
			height: 20px;
			background: #f0f0f1;
			border-radius: 10px;
			overflow: hidden;
			margin-bottom: 5px;
		}

		.mets-status-fill, .mets-priority-fill {
			height: 100%;
			transition: width 0.5s ease;
		}

		.mets-status-open { background: #ff6b6b; }
		.mets-status-in_progress { background: #ffa726; }
		.mets-status-resolved { background: #66bb6a; }
		.mets-status-closed { background: #78909c; }
		.mets-status-on_hold { background: #ab47bc; }

		.mets-priority-critical { background: #d32f2f; }
		.mets-priority-high { background: #f57c00; }
		.mets-priority-medium { background: #fbc02d; }
		.mets-priority-low { background: #689f38; }

		.mets-status-details, .mets-priority-details {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.mets-status-name, .mets-priority-name {
			font-weight: 600;
		}

		.mets-status-count, .mets-priority-count {
			color: #666;
			font-size: 13px;
		}

		/* SLA Charts */
		.mets-sla-item {
			display: flex;
			align-items: center;
			margin-bottom: 15px;
		}

		.mets-sla-indicator {
			width: 20px;
			height: 20px;
			border-radius: 50%;
			margin-right: 15px;
		}

		.mets-sla-met { background: #66bb6a; }
		.mets-sla-warning { background: #ffa726; }
		.mets-sla-breached { background: #ff6b6b; }

		.mets-sla-details {
			flex: 1;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.mets-sla-status {
			font-weight: 600;
		}

		.mets-sla-count {
			color: #666;
			font-size: 13px;
		}

		/* Response Stats */
		.mets-response-stats {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 20px;
			text-align: center;
		}

		.mets-response-stat {
			padding: 15px;
			background: #f8f9fa;
			border-radius: 5px;
		}

		.mets-response-value {
			font-size: 24px;
			font-weight: bold;
			color: #0073aa;
			margin-bottom: 5px;
		}

		.mets-response-label {
			font-size: 12px;
			color: #666;
			text-transform: uppercase;
		}

		/* Agent Performance */
		.mets-performance-rate {
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 12px;
			font-weight: 600;
		}

		.mets-rate-good { background: #d4edda; color: #155724; }
		.mets-rate-average { background: #fff3cd; color: #856404; }
		.mets-rate-poor { background: #f8d7da; color: #721c24; }

		/* KB Stats */
		.mets-kb-stats {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 15px;
			text-align: center;
		}

		.mets-kb-stat {
			padding: 15px;
			background: #f8f9fa;
			border-radius: 5px;
		}

		.mets-kb-stat-value {
			font-size: 20px;
			font-weight: bold;
			color: #0073aa;
			margin-bottom: 5px;
		}

		.mets-kb-stat-label {
			font-size: 11px;
			color: #666;
			text-transform: uppercase;
		}

		/* Top Articles */
		.mets-article-item {
			padding: 10px 0;
			border-bottom: 1px solid #eee;
		}

		.mets-article-item:last-child {
			border-bottom: none;
		}

		.mets-article-title a {
			font-weight: 600;
			color: #0073aa;
			text-decoration: none;
		}

		.mets-article-title a:hover {
			color: #005a87;
		}

		.mets-article-stats {
			margin-top: 5px;
			font-size: 12px;
			color: #666;
			display: flex;
			gap: 15px;
		}

		.mets-article-views, .mets-article-helpful {
			background: #f0f0f1;
			padding: 2px 6px;
			border-radius: 3px;
		}

		/* Activity Feed */
		.mets-activity-feed {
			max-height: 400px;
			overflow-y: auto;
		}

		.mets-activity-item {
			display: flex;
			align-items: flex-start;
			padding: 15px 0;
			border-bottom: 1px solid #eee;
		}

		.mets-activity-item:last-child {
			border-bottom: none;
		}

		.mets-activity-icon {
			margin-right: 15px;
			color: #0073aa;
		}

		.mets-activity-content {
			flex: 1;
		}

		.mets-activity-description {
			margin-bottom: 5px;
		}

		.mets-activity-description a {
			color: #0073aa;
			text-decoration: none;
		}

		.mets-activity-description a:hover {
			color: #005a87;
		}

		.mets-activity-time {
			font-size: 12px;
			color: #666;
		}

		/* Responsive Design */
		@media (max-width: 1200px) {
			.mets-dashboard-columns {
				grid-template-columns: 1fr;
			}
			
			.mets-kpi-grid {
				grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			}
			
			.mets-response-stats {
				grid-template-columns: 1fr;
			}
			
			.mets-kb-stats {
				grid-template-columns: repeat(2, 1fr);
			}
		}

		@media (max-width: 768px) {
			.mets-dashboard-filters form {
				flex-direction: column;
				align-items: flex-start;
			}
			
			.mets-kpi-grid {
				grid-template-columns: 1fr;
			}
			
			.mets-kb-stats {
				grid-template-columns: 1fr;
			}
		}
		</style>
		<?php
	}
}