<?php
/**
 * Agent Management Class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * Agent Management functionality
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Agent_Management {

	/**
	 * Display agent management page
	 *
	 * @since    1.0.0
	 */
	public function display_agent_management_page() {
		?>
		<div class="wrap">
			<h1><?php _e( '👥 Agent Management', METS_TEXT_DOMAIN ); ?></h1>
			
			<div class="mets-agent-management">
				<div class="mets-management-header">
					<p><?php _e( 'Manage support agents, their permissions, and entity assignments.', METS_TEXT_DOMAIN ); ?></p>
				</div>

				<!-- Agents List -->
				<div class="mets-agents-section">
					<h2><?php _e( 'Support Agents', METS_TEXT_DOMAIN ); ?></h2>

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Role', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Assigned Entities', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Active Tickets', METS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Last Activity', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody id="agents-list">
							<?php $this->display_agents_list(); ?>
						</tbody>
					</table>
				</div>

				<!-- Agent Statistics -->
				<div class="mets-agent-stats">
					<h2><?php _e( 'Agent Statistics', METS_TEXT_DOMAIN ); ?></h2>
					<div class="mets-stats-grid">
						<?php $this->display_agent_statistics(); ?>
					</div>
				</div>
			</div>
		</div>

		<style>
		.mets-agent-management { margin-top: 20px; }
		.mets-agents-section { margin-bottom: 40px; }
		.mets-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
		.mets-stat-card { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; text-align: center; }
		.mets-stat-number { font-size: 2em; font-weight: bold; color: #0073aa; display: block; }
		.mets-stat-label { color: #646970; margin-top: 5px; }
		.agent-status-active { color: #46b450; font-weight: 600; }
		.agent-status-inactive { color: #dc3232; font-weight: 600; }
		</style>
		<?php
	}

	/**
	 * Display agents list
	 *
	 * @since    1.0.0
	 */
	private function display_agents_list() {
		// Get users with agent capabilities
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
			echo '<tr><td colspan="6">' . __( 'No agents found. Agents will appear here once you assign ticket agent or ticket manager roles to users.', METS_TEXT_DOMAIN ) . '</td></tr>';
			return;
		}

		foreach ( $agents as $agent ) {
			$user_meta = get_userdata( $agent->ID );
			$roles = $user_meta->roles;
			$role = ! empty( $roles ) ? $roles[0] : 'subscriber';
			$status = get_user_meta( $agent->ID, 'mets_agent_status', true ) ?: 'active';
			$assigned_entities = get_user_meta( $agent->ID, 'mets_assigned_entities', true ) ?: array();
			
			// Get ticket count
			global $wpdb;
			$ticket_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE assigned_to = %d AND status NOT IN ('closed', 'resolved')",
				$agent->ID
			) );

			$last_activity = get_user_meta( $agent->ID, 'mets_last_activity', true );
			$last_activity_display = $last_activity ? human_time_diff( strtotime( $last_activity ) ) . ' ago' : __( 'Never', METS_TEXT_DOMAIN );

			echo '<tr>';
			echo '<td><strong>' . esc_html( $agent->display_name ) . '</strong><br><small>' . esc_html( $agent->user_email ) . '</small></td>';
			echo '<td>' . esc_html( ucfirst( str_replace( '_', ' ', $role ) ) ) . '</td>';
			echo '<td><span class="agent-status-' . esc_attr( $status ) . '">' . esc_html( ucfirst( $status ) ) . '</span></td>';
			echo '<td>' . count( $assigned_entities ) . ' ' . __( 'entities', METS_TEXT_DOMAIN ) . '</td>';
			echo '<td>' . intval( $ticket_count ) . '</td>';
			echo '<td>' . esc_html( $last_activity_display ) . '</td>';
			echo '</tr>';
		}
	}

	/**
	 * Display agent statistics
	 *
	 * @since    1.0.0
	 */
	public function display_agent_statistics() {
		global $wpdb;

		// Total agents - use proper METS role names and include senior_agent, support_supervisor
		$total_agents = $wpdb->get_var(
			"SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u 
			 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
			 WHERE um.meta_key = 'wp_capabilities' 
			 AND (um.meta_value LIKE '%ticket_agent%' OR um.meta_value LIKE '%senior_agent%' 
			      OR um.meta_value LIKE '%ticket_manager%' OR um.meta_value LIKE '%support_supervisor%'
			      OR um.meta_value LIKE '%mets_agent%' OR um.meta_value LIKE '%mets_manager%')"
		);

		// Active agents - include all METS roles
		$active_agents = $wpdb->get_var(
			"SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u 
			 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
			 LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'mets_agent_status'
			 WHERE um.meta_key = 'wp_capabilities' 
			 AND (um.meta_value LIKE '%ticket_agent%' OR um.meta_value LIKE '%senior_agent%' 
			      OR um.meta_value LIKE '%ticket_manager%' OR um.meta_value LIKE '%support_supervisor%'
			      OR um.meta_value LIKE '%mets_agent%' OR um.meta_value LIKE '%mets_manager%')
			 AND (um2.meta_value = 'active' OR um2.meta_value IS NULL)"
		);

		// Average tickets per agent
		$avg_tickets = $wpdb->get_var(
			"SELECT AVG(ticket_count) FROM (
				SELECT COUNT(*) as ticket_count 
				FROM {$wpdb->prefix}mets_tickets 
				WHERE assigned_to IS NOT NULL 
				AND status NOT IN ('closed', 'resolved')
				GROUP BY assigned_to
			) as agent_counts"
		);

		$stats = array(
			array(
				'number' => intval( $total_agents ),
				'label' => __( 'Total Agents', METS_TEXT_DOMAIN )
			),
			array(
				'number' => intval( $active_agents ),  
				'label' => __( 'Active Agents', METS_TEXT_DOMAIN )
			),
			array(
				'number' => round( floatval( $avg_tickets ), 1 ),
				'label' => __( 'Avg Tickets/Agent', METS_TEXT_DOMAIN )
			),
			array(
				'number' => intval( $total_agents ) - intval( $active_agents ),
				'label' => __( 'Inactive Agents', METS_TEXT_DOMAIN )
			)
		);

		foreach ( $stats as $stat ) {
			echo '<div class="mets-stat-card">';
			echo '<span class="mets-stat-number">' . esc_html( $stat['number'] ) . '</span>';
			echo '<div class="mets-stat-label">' . esc_html( $stat['label'] ) . '</div>';
			echo '</div>';
		}
	}
}