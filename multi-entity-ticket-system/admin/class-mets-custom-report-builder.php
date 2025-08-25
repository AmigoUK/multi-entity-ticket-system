<?php
/**
 * Custom Report Builder
 *
 * Handles creating custom reports with flexible filtering and export options
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * The Custom Report Builder class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Custom_Report_Builder {

	/**
	 * Display the custom report builder interface
	 *
	 * @since    1.0.0
	 */
	public function display_report_builder() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Handle report generation
		$report_data = null;
		$export_data = null;
		if ( isset( $_POST['generate_report'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'mets_generate_report' ) ) {
			$report_data = $this->generate_custom_report( $_POST );
			$export_data = $this->prepare_export_data( $report_data, $_POST );
		}

		// Get entities for filtering
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entities = $entity_model->get_all( array( 'status' => 'active', 'parent_id' => 'all' ) );

		// Get agents for filtering
		$agents = get_users( array( 
			'role__in' => array( 'mets_agent', 'mets_manager', 'administrator' ),
			'fields' => array( 'ID', 'display_name' )
		) );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Custom Report Builder', METS_TEXT_DOMAIN ); ?></h1>
			
			<div class="mets-report-builder">
				<!-- Report Configuration Form -->
				<div class="mets-builder-form">
					<form method="post" action="" id="mets-report-form">
						<?php wp_nonce_field( 'mets_generate_report' ); ?>
						
						<div class="mets-builder-sections">
							<!-- Report Type Section -->
							<div class="mets-builder-section">
								<h3><?php _e( '1. Select Report Type', METS_TEXT_DOMAIN ); ?></h3>
								<div class="mets-report-types">
									<label class="mets-report-type-option">
										<input type="radio" name="report_type" value="tickets" checked>
										<span class="mets-report-type-label">
											<strong><?php _e( 'Ticket Reports', METS_TEXT_DOMAIN ); ?></strong>
											<span><?php _e( 'Analyze ticket data, status, priorities, and assignments', METS_TEXT_DOMAIN ); ?></span>
										</span>
									</label>
									<label class="mets-report-type-option">
										<input type="radio" name="report_type" value="sla">
										<span class="mets-report-type-label">
											<strong><?php _e( 'SLA Reports', METS_TEXT_DOMAIN ); ?></strong>
											<span><?php _e( 'Track SLA compliance, breaches, and response times', METS_TEXT_DOMAIN ); ?></span>
										</span>
									</label>
									<label class="mets-report-type-option">
										<input type="radio" name="report_type" value="agent">
										<span class="mets-report-type-label">
											<strong><?php _e( 'Agent Performance', METS_TEXT_DOMAIN ); ?></strong>
											<span><?php _e( 'Evaluate agent productivity and performance metrics', METS_TEXT_DOMAIN ); ?></span>
										</span>
									</label>
									<?php if ( current_user_can( 'read_kb_articles' ) ) : ?>
									<label class="mets-report-type-option">
										<input type="radio" name="report_type" value="knowledgebase">
										<span class="mets-report-type-label">
											<strong><?php _e( 'Knowledge Base', METS_TEXT_DOMAIN ); ?></strong>
											<span><?php _e( 'Analyze article performance and user engagement', METS_TEXT_DOMAIN ); ?></span>
										</span>
									</label>
									<?php endif; ?>
								</div>
							</div>

							<!-- Date Range Section -->
							<div class="mets-builder-section">
								<h3><?php _e( '2. Select Date Range', METS_TEXT_DOMAIN ); ?></h3>
								<div class="mets-date-options">
									<div class="mets-date-presets">
										<label><input type="radio" name="date_range" value="today"> <?php _e( 'Today', METS_TEXT_DOMAIN ); ?></label>
										<label><input type="radio" name="date_range" value="yesterday"> <?php _e( 'Yesterday', METS_TEXT_DOMAIN ); ?></label>
										<label><input type="radio" name="date_range" value="last_7_days" checked> <?php _e( 'Last 7 Days', METS_TEXT_DOMAIN ); ?></label>
										<label><input type="radio" name="date_range" value="last_30_days"> <?php _e( 'Last 30 Days', METS_TEXT_DOMAIN ); ?></label>
										<label><input type="radio" name="date_range" value="last_90_days"> <?php _e( 'Last 90 Days', METS_TEXT_DOMAIN ); ?></label>
										<label><input type="radio" name="date_range" value="custom"> <?php _e( 'Custom Range', METS_TEXT_DOMAIN ); ?></label>
									</div>
									<div class="mets-custom-dates" style="display: none;">
										<label>
											<?php _e( 'From:', METS_TEXT_DOMAIN ); ?>
											<input type="date" name="date_from" value="<?php echo date( 'Y-m-d', strtotime( '-7 days' ) ); ?>">
										</label>
										<label>
											<?php _e( 'To:', METS_TEXT_DOMAIN ); ?>
											<input type="date" name="date_to" value="<?php echo date( 'Y-m-d' ); ?>">
										</label>
									</div>
								</div>
							</div>

							<!-- Filters Section -->
							<div class="mets-builder-section">
								<h3><?php _e( '3. Apply Filters', METS_TEXT_DOMAIN ); ?></h3>
								<div class="mets-filters-grid">
									<div class="mets-filter-group">
										<label><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></label>
										<select name="filter_entity_id" multiple>
											<option value=""><?php _e( 'All Entities', METS_TEXT_DOMAIN ); ?></option>
											<?php foreach ( $entities as $entity ) : ?>
												<option value="<?php echo esc_attr( $entity->id ); ?>">
													<?php echo esc_html( $entity->name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>

									<div class="mets-filter-group ticket-filters">
										<label><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></label>
										<select name="filter_status[]" multiple>
											<option value=""><?php _e( 'All Statuses', METS_TEXT_DOMAIN ); ?></option>
											<option value="open"><?php _e( 'Open', METS_TEXT_DOMAIN ); ?></option>
											<option value="in_progress"><?php _e( 'In Progress', METS_TEXT_DOMAIN ); ?></option>
											<option value="resolved"><?php _e( 'Resolved', METS_TEXT_DOMAIN ); ?></option>
											<option value="closed"><?php _e( 'Closed', METS_TEXT_DOMAIN ); ?></option>
											<option value="on_hold"><?php _e( 'On Hold', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>

									<div class="mets-filter-group ticket-filters">
										<label><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></label>
										<select name="filter_priority[]" multiple>
											<option value=""><?php _e( 'All Priorities', METS_TEXT_DOMAIN ); ?></option>
											<option value="critical"><?php _e( 'Critical', METS_TEXT_DOMAIN ); ?></option>
											<option value="high"><?php _e( 'High', METS_TEXT_DOMAIN ); ?></option>
											<option value="medium"><?php _e( 'Medium', METS_TEXT_DOMAIN ); ?></option>
											<option value="low"><?php _e( 'Low', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>

									<div class="mets-filter-group ticket-filters agent-filters">
										<label><?php _e( 'Assigned Agent', METS_TEXT_DOMAIN ); ?></label>
										<select name="filter_agent_id[]" multiple>
											<option value=""><?php _e( 'All Agents', METS_TEXT_DOMAIN ); ?></option>
											<option value="unassigned"><?php _e( 'Unassigned', METS_TEXT_DOMAIN ); ?></option>
											<?php foreach ( $agents as $agent ) : ?>
												<option value="<?php echo esc_attr( $agent->ID ); ?>">
													<?php echo esc_html( $agent->display_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>

									<div class="mets-filter-group sla-filters" style="display: none;">
										<label><?php _e( 'SLA Status', METS_TEXT_DOMAIN ); ?></label>
										<select name="filter_sla_status[]" multiple>
											<option value=""><?php _e( 'All SLA Statuses', METS_TEXT_DOMAIN ); ?></option>
											<option value="met"><?php _e( 'Met', METS_TEXT_DOMAIN ); ?></option>
											<option value="warning"><?php _e( 'Warning', METS_TEXT_DOMAIN ); ?></option>
											<option value="breached"><?php _e( 'Breached', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>
								</div>
							</div>

							<!-- Grouping & Display Options -->
							<div class="mets-builder-section">
								<h3><?php _e( '4. Configure Display Options', METS_TEXT_DOMAIN ); ?></h3>
								<div class="mets-display-options">
									<div class="mets-option-group">
										<label><?php _e( 'Group By', METS_TEXT_DOMAIN ); ?></label>
										<select name="group_by">
											<option value=""><?php _e( 'No Grouping', METS_TEXT_DOMAIN ); ?></option>
											<option value="status"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></option>
											<option value="priority"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></option>
											<option value="entity"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></option>
											<option value="agent"><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></option>
											<option value="date"><?php _e( 'Date Created', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>

									<div class="mets-option-group">
										<label><?php _e( 'Sort By', METS_TEXT_DOMAIN ); ?></label>
										<select name="sort_by">
											<option value="created_at"><?php _e( 'Date Created', METS_TEXT_DOMAIN ); ?></option>
											<option value="updated_at"><?php _e( 'Last Updated', METS_TEXT_DOMAIN ); ?></option>
											<option value="resolved_at"><?php _e( 'Resolution Date', METS_TEXT_DOMAIN ); ?></option>
											<option value="priority"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></option>
											<option value="status"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>

									<div class="mets-option-group">
										<label><?php _e( 'Sort Order', METS_TEXT_DOMAIN ); ?></label>
										<select name="sort_order">
											<option value="DESC"><?php _e( 'Descending', METS_TEXT_DOMAIN ); ?></option>
											<option value="ASC"><?php _e( 'Ascending', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>

									<div class="mets-option-group">
										<label><?php _e( 'Limit Results', METS_TEXT_DOMAIN ); ?></label>
										<select name="limit">
											<option value="100"><?php _e( '100 Records', METS_TEXT_DOMAIN ); ?></option>
											<option value="500"><?php _e( '500 Records', METS_TEXT_DOMAIN ); ?></option>
											<option value="1000"><?php _e( '1000 Records', METS_TEXT_DOMAIN ); ?></option>
											<option value="all"><?php _e( 'All Records', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>
								</div>

								<div class="mets-display-format">
									<h4><?php _e( 'Display Format', METS_TEXT_DOMAIN ); ?></h4>
									<label><input type="checkbox" name="show_summary" value="1" checked> <?php _e( 'Show Summary Statistics', METS_TEXT_DOMAIN ); ?></label>
									<label><input type="checkbox" name="show_charts" value="1" checked> <?php _e( 'Include Charts', METS_TEXT_DOMAIN ); ?></label>
									<label><input type="checkbox" name="show_details" value="1" checked> <?php _e( 'Show Detailed Data', METS_TEXT_DOMAIN ); ?></label>
								</div>
							</div>

							<!-- Actions -->
							<div class="mets-builder-actions">
								<button type="submit" name="generate_report" class="button button-primary button-large">
									<span class="dashicons dashicons-chart-line"></span>
									<?php _e( 'Generate Report', METS_TEXT_DOMAIN ); ?>
								</button>
								<button type="button" class="button button-secondary" id="mets-save-template">
									<span class="dashicons dashicons-saved"></span>
									<?php _e( 'Save as Template', METS_TEXT_DOMAIN ); ?>
								</button>
								<button type="button" class="button button-secondary" id="mets-load-template">
									<span class="dashicons dashicons-upload"></span>
									<?php _e( 'Load Template', METS_TEXT_DOMAIN ); ?>
								</button>
							</div>
						</div>
					</form>
				</div>

				<!-- Report Results -->
				<?php if ( $report_data ) : ?>
				<div class="mets-report-results">
					<div class="mets-report-header">
						<h2><?php _e( 'Report Results', METS_TEXT_DOMAIN ); ?></h2>
						<div class="mets-export-options">
							<button type="button" class="button" onclick="metsExportReport('csv')">
								<span class="dashicons dashicons-media-spreadsheet"></span>
								<?php _e( 'Export CSV', METS_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="button" onclick="metsExportReport('pdf')">
								<span class="dashicons dashicons-pdf"></span>
								<?php _e( 'Export PDF', METS_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="button" onclick="window.print()">
								<span class="dashicons dashicons-printer"></span>
								<?php _e( 'Print', METS_TEXT_DOMAIN ); ?>
							</button>
						</div>
					</div>
					
					<?php $this->render_report_results( $report_data, $_POST ); ?>
				</div>

				<!-- Hidden export data -->
				<form id="mets-export-form" method="post" style="display: none;">
					<?php wp_nonce_field( 'mets_export_report' ); ?>
					<input type="hidden" name="export_format" id="export-format">
					<input type="hidden" name="export_data" value="<?php echo esc_attr( json_encode( $export_data ) ); ?>">
					<input type="hidden" name="export_config" value="<?php echo esc_attr( json_encode( $_POST ) ); ?>">
					<button type="submit" name="do_export" id="do-export"></button>
				</form>
				<?php endif; ?>
			</div>
		</div>

		<?php $this->render_report_builder_styles(); ?>
		<?php $this->render_report_builder_scripts(); ?>
		<?php
	}

	/**
	 * Generate custom report based on configuration
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   array               Report data
	 */
	private function generate_custom_report( $config ) {
		global $wpdb;

		$report_type = sanitize_text_field( $config['report_type'] ?? 'tickets' );
		
		switch ( $report_type ) {
			case 'tickets':
				return $this->generate_ticket_report( $config );
			case 'sla':
				return $this->generate_sla_report( $config );
			case 'agent':
				return $this->generate_agent_report( $config );
			case 'knowledgebase':
				return $this->generate_kb_report( $config );
			default:
				return array();
		}
	}

	/**
	 * Generate ticket report
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   array               Report data
	 */
	private function generate_ticket_report( $config ) {
		global $wpdb;

		// Build WHERE clause
		$where_conditions = array( '1=1' );
		$where_values = array();

		// Date range
		$date_where = $this->build_date_where( $config );
		if ( $date_where ) {
			$where_conditions[] = $date_where;
		}

		// Entity filter
		if ( ! empty( $config['filter_entity_id'] ) ) {
			$entity_ids = array_map( 'intval', (array) $config['filter_entity_id'] );
			$placeholders = implode( ',', array_fill( 0, count( $entity_ids ), '%d' ) );
			$where_conditions[] = "entity_id IN ({$placeholders})";
			$where_values = array_merge( $where_values, $entity_ids );
		}

		// Status filter
		if ( ! empty( $config['filter_status'] ) ) {
			$statuses = array_map( 'sanitize_text_field', $config['filter_status'] );
			$statuses = array_filter( $statuses );
			if ( ! empty( $statuses ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
				$where_conditions[] = "status IN ({$placeholders})";
				$where_values = array_merge( $where_values, $statuses );
			}
		}

		// Priority filter
		if ( ! empty( $config['filter_priority'] ) ) {
			$priorities = array_map( 'sanitize_text_field', $config['filter_priority'] );
			$priorities = array_filter( $priorities );
			if ( ! empty( $priorities ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $priorities ), '%s' ) );
				$where_conditions[] = "priority IN ({$placeholders})";
				$where_values = array_merge( $where_values, $priorities );
			}
		}

		// Agent filter
		if ( ! empty( $config['filter_agent_id'] ) ) {
			$agent_ids = (array) $config['filter_agent_id'];
			$agent_conditions = array();
			
			foreach ( $agent_ids as $agent_id ) {
				if ( $agent_id === 'unassigned' ) {
					$agent_conditions[] = 'assigned_to IS NULL';
				} elseif ( is_numeric( $agent_id ) ) {
					$agent_conditions[] = 'assigned_to = %d';
					$where_values[] = intval( $agent_id );
				}
			}
			
			if ( ! empty( $agent_conditions ) ) {
				$where_conditions[] = '(' . implode( ' OR ', $agent_conditions ) . ')';
			}
		}

		// Build ORDER BY
		$sort_by = sanitize_text_field( $config['sort_by'] ?? 'created_at' );
		$sort_order = sanitize_text_field( $config['sort_order'] ?? 'DESC' );
		$order_by = "ORDER BY {$sort_by} {$sort_order}";

		// Build LIMIT
		$limit = sanitize_text_field( $config['limit'] ?? '100' );
		$limit_clause = $limit === 'all' ? '' : 'LIMIT ' . intval( $limit );

		// Main query
		$where_clause = implode( ' AND ', $where_conditions );
		
		$sql = "SELECT 
			t.*,
			e.name as entity_name,
			u.display_name as customer_name,
			a.display_name as agent_name
		FROM {$wpdb->prefix}mets_tickets t
		LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
		LEFT JOIN {$wpdb->prefix}users u ON t.customer_id = u.ID
		LEFT JOIN {$wpdb->prefix}users a ON t.assigned_to = a.ID
		WHERE {$where_clause}
		{$order_by}
		{$limit_clause}";

		if ( ! empty( $where_values ) ) {
			$tickets = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ) );
		} else {
			$tickets = $wpdb->get_results( $sql );
		}

		// Generate summary statistics
		$summary_sql = "SELECT 
			COUNT(*) as total_tickets,
			COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
			COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
			COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
			AVG(CASE WHEN resolved_at IS NOT NULL 
				THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_time,
			COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_tickets,
			COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_tickets
		FROM {$wpdb->prefix}mets_tickets t
		WHERE {$where_clause}";

		if ( ! empty( $where_values ) ) {
			$summary = $wpdb->get_row( $wpdb->prepare( $summary_sql, ...$where_values ) );
		} else {
			$summary = $wpdb->get_row( $summary_sql );
		}

		// Group data if requested
		$grouped_data = array();
		if ( ! empty( $config['group_by'] ) ) {
			$grouped_data = $this->group_ticket_data( $tickets, $config['group_by'] );
		}

		return array(
			'type' => 'tickets',
			'tickets' => $tickets,
			'summary' => $summary,
			'grouped_data' => $grouped_data,
			'total_records' => count( $tickets )
		);
	}

	/**
	 * Generate SLA report
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   array               Report data
	 */
	private function generate_sla_report( $config ) {
		global $wpdb;

		// Build WHERE clause similar to ticket report
		$where_conditions = array( '1=1', 'sla_due_date IS NOT NULL' );
		$where_values = array();

		// Date range
		$date_where = $this->build_date_where( $config );
		if ( $date_where ) {
			$where_conditions[] = $date_where;
		}

		// Entity filter
		if ( ! empty( $config['filter_entity_id'] ) ) {
			$entity_ids = array_map( 'intval', (array) $config['filter_entity_id'] );
			$placeholders = implode( ',', array_fill( 0, count( $entity_ids ), '%d' ) );
			$where_conditions[] = "entity_id IN ({$placeholders})";
			$where_values = array_merge( $where_values, $entity_ids );
		}

		// SLA Status filter
		if ( ! empty( $config['filter_sla_status'] ) ) {
			$sla_statuses = array_map( 'sanitize_text_field', $config['filter_sla_status'] );
			$sla_statuses = array_filter( $sla_statuses );
			if ( ! empty( $sla_statuses ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $sla_statuses ), '%s' ) );
				$where_conditions[] = "sla_status IN ({$placeholders})";
				$where_values = array_merge( $where_values, $sla_statuses );
			}
		}

		$where_clause = implode( ' AND ', $where_conditions );
		
		$sql = "SELECT 
			t.*,
			e.name as entity_name,
			u.display_name as customer_name,
			a.display_name as agent_name,
			TIMESTAMPDIFF(MINUTE, t.created_at, COALESCE(t.first_response_at, NOW())) as response_time_minutes,
			TIMESTAMPDIFF(MINUTE, t.created_at, COALESCE(t.resolved_at, NOW())) as resolution_time_minutes
		FROM {$wpdb->prefix}mets_tickets t
		LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
		LEFT JOIN {$wpdb->prefix}users u ON t.customer_id = u.ID
		LEFT JOIN {$wpdb->prefix}users a ON t.assigned_to = a.ID
		WHERE {$where_clause}
		ORDER BY sla_due_date ASC";

		if ( ! empty( $where_values ) ) {
			$sla_tickets = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ) );
		} else {
			$sla_tickets = $wpdb->get_results( $sql );
		}

		// Calculate SLA metrics
		$summary_sql = "SELECT 
			COUNT(*) as total_with_sla,
			COUNT(CASE WHEN sla_status = 'met' THEN 1 END) as sla_met,
			COUNT(CASE WHEN sla_status = 'breached' THEN 1 END) as sla_breached,
			COUNT(CASE WHEN sla_status = 'warning' THEN 1 END) as sla_warning,
			AVG(CASE WHEN first_response_at IS NOT NULL 
				THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_response_time,
			AVG(CASE WHEN resolved_at IS NOT NULL 
				THEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) END) as avg_resolution_time
		FROM {$wpdb->prefix}mets_tickets t
		WHERE {$where_clause}";

		if ( ! empty( $where_values ) ) {
			$sla_summary = $wpdb->get_row( $wpdb->prepare( $summary_sql, ...$where_values ) );
		} else {
			$sla_summary = $wpdb->get_row( $summary_sql );
		}

		return array(
			'type' => 'sla',
			'tickets' => $sla_tickets,
			'summary' => $sla_summary,
			'total_records' => count( $sla_tickets )
		);
	}

	/**
	 * Generate agent performance report
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   array               Report data
	 */
	private function generate_agent_report( $config ) {
		global $wpdb;

		$where_conditions = array( '1=1', 'assigned_to IS NOT NULL' );
		$where_values = array();

		// Date range
		$date_where = $this->build_date_where( $config );
		if ( $date_where ) {
			$where_conditions[] = $date_where;
		}

		// Entity filter
		if ( ! empty( $config['filter_entity_id'] ) ) {
			$entity_ids = array_map( 'intval', (array) $config['filter_entity_id'] );
			$placeholders = implode( ',', array_fill( 0, count( $entity_ids ), '%d' ) );
			$where_conditions[] = "entity_id IN ({$placeholders})";
			$where_values = array_merge( $where_values, $entity_ids );
		}

		// Agent filter
		if ( ! empty( $config['filter_agent_id'] ) ) {
			$agent_ids = array_filter( (array) $config['filter_agent_id'], 'is_numeric' );
			if ( ! empty( $agent_ids ) ) {
				$agent_ids = array_map( 'intval', $agent_ids );
				$placeholders = implode( ',', array_fill( 0, count( $agent_ids ), '%d' ) );
				$where_conditions[] = "assigned_to IN ({$placeholders})";
				$where_values = array_merge( $where_values, $agent_ids );
			}
		}

		$where_clause = implode( ' AND ', $where_conditions );

		$sql = "SELECT 
			u.ID as agent_id,
			u.display_name as agent_name,
			COUNT(t.id) as tickets_assigned,
			COUNT(CASE WHEN t.status = 'resolved' THEN 1 END) as tickets_resolved,
			COUNT(CASE WHEN t.status = 'closed' THEN 1 END) as tickets_closed,
			AVG(CASE WHEN t.resolved_at IS NOT NULL 
				THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) END) as avg_resolution_time,
			AVG(CASE WHEN t.first_response_at IS NOT NULL 
				THEN TIMESTAMPDIFF(HOUR, t.created_at, t.first_response_at) END) as avg_response_time,
			COUNT(CASE WHEN t.sla_status = 'met' THEN 1 END) as sla_met,
			COUNT(CASE WHEN t.sla_status = 'breached' THEN 1 END) as sla_breached
		FROM {$wpdb->prefix}users u
		JOIN {$wpdb->prefix}mets_tickets t ON u.ID = t.assigned_to
		WHERE {$where_clause}
		GROUP BY u.ID, u.display_name
		ORDER BY tickets_assigned DESC";

		if ( ! empty( $where_values ) ) {
			$agents = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ) );
		} else {
			$agents = $wpdb->get_results( $sql );
		}

		// Calculate additional metrics for each agent
		foreach ( $agents as &$agent ) {
			$agent->resolution_rate = $agent->tickets_assigned > 0 ? 
				round( ( ( $agent->tickets_resolved + $agent->tickets_closed ) / $agent->tickets_assigned ) * 100, 1 ) : 0;
			$agent->sla_compliance = $agent->tickets_assigned > 0 ? 
				round( ( $agent->sla_met / $agent->tickets_assigned ) * 100, 1 ) : 0;
		}

		return array(
			'type' => 'agent',
			'agents' => $agents,
			'total_records' => count( $agents )
		);
	}

	/**
	 * Generate knowledge base report
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   array               Report data
	 */
	private function generate_kb_report( $config ) {
		if ( ! current_user_can( 'read_kb_articles' ) ) {
			return array();
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();

		$period = $this->convert_date_range_to_period( $config );
		$entity_id = ! empty( $config['filter_entity_id'] ) ? intval( $config['filter_entity_id'][0] ) : null;

		$top_articles = $analytics_model->get_top_articles( 50, $period, $entity_id, 'views' );
		$search_analytics = $analytics_model->get_search_analytics( $period, $entity_id );

		return array(
			'type' => 'knowledgebase',
			'articles' => $top_articles,
			'search_data' => $search_analytics,
			'total_records' => count( $top_articles )
		);
	}

	/**
	 * Build date WHERE clause
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   string             WHERE clause
	 */
	private function build_date_where( $config ) {
		$date_range = sanitize_text_field( $config['date_range'] ?? 'last_7_days' );
		
		switch ( $date_range ) {
			case 'today':
				return "DATE(created_at) = CURDATE()";
			case 'yesterday':
				return "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
			case 'last_7_days':
				return "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
			case 'last_30_days':
				return "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
			case 'last_90_days':
				return "created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
			case 'custom':
				$date_from = sanitize_text_field( $config['date_from'] ?? '' );
				$date_to = sanitize_text_field( $config['date_to'] ?? '' );
				if ( $date_from && $date_to ) {
					return "DATE(created_at) BETWEEN '{$date_from}' AND '{$date_to}'";
				}
				break;
		}
		
		return '';
	}

	/**
	 * Convert date range to analytics period format
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   string             Period string
	 */
	private function convert_date_range_to_period( $config ) {
		$date_range = sanitize_text_field( $config['date_range'] ?? 'last_7_days' );
		
		switch ( $date_range ) {
			case 'today':
			case 'yesterday':
				return '24hours';
			case 'last_7_days':
				return '7days';
			case 'last_30_days':
				return '30days';
			case 'last_90_days':
			case 'custom':
			default:
				return '30days';
		}
	}

	/**
	 * Group ticket data by specified field
	 *
	 * @since    1.0.0
	 * @param    array    $tickets    Ticket data
	 * @param    string   $group_by   Field to group by
	 * @return   array                Grouped data
	 */
	private function group_ticket_data( $tickets, $group_by ) {
		$grouped = array();
		
		foreach ( $tickets as $ticket ) {
			$key = '';
			switch ( $group_by ) {
				case 'status':
					$key = ucfirst( $ticket->status );
					break;
				case 'priority':
					$key = ucfirst( $ticket->priority );
					break;
				case 'entity':
					$key = $ticket->entity_name ?: __( 'No Entity', METS_TEXT_DOMAIN );
					break;
				case 'agent':
					$key = $ticket->agent_name ?: __( 'Unassigned', METS_TEXT_DOMAIN );
					break;
				case 'date':
					$key = date( 'Y-m-d', strtotime( $ticket->created_at ) );
					break;
				default:
					$key = __( 'Other', METS_TEXT_DOMAIN );
			}
			
			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}
			$grouped[ $key ][] = $ticket;
		}
		
		return $grouped;
	}

	/**
	 * Prepare data for export
	 *
	 * @since    1.0.0
	 * @param    array    $report_data    Report data
	 * @param    array    $config         Report configuration
	 * @return   array                    Export data
	 */
	private function prepare_export_data( $report_data, $config ) {
		if ( empty( $report_data ) ) {
			return array();
		}

		$export_data = array(
			'title' => $this->get_report_title( $config ),
			'generated_at' => current_time( 'mysql' ),
			'config' => $config,
			'summary' => isset( $report_data['summary'] ) ? $report_data['summary'] : null,
			'data' => array()
		);

		switch ( $report_data['type'] ) {
			case 'tickets':
				$export_data['headers'] = array(
					'ID', 'Subject', 'Status', 'Priority', 'Entity', 'Customer', 'Agent', 'Created', 'Updated', 'Resolved'
				);
				foreach ( $report_data['tickets'] as $ticket ) {
					$export_data['data'][] = array(
						$ticket->id,
						$ticket->subject,
						ucfirst( $ticket->status ),
						ucfirst( $ticket->priority ),
						$ticket->entity_name,
						$ticket->customer_name,
						$ticket->agent_name ?: 'Unassigned',
						$ticket->created_at,
						$ticket->updated_at,
						$ticket->resolved_at ?: 'Not resolved'
					);
				}
				break;
				
			case 'sla':
				$export_data['headers'] = array(
					'ID', 'Subject', 'SLA Status', 'Due Date', 'Response Time', 'Resolution Time', 'Entity', 'Agent'
				);
				foreach ( $report_data['tickets'] as $ticket ) {
					$export_data['data'][] = array(
						$ticket->id,
						$ticket->subject,
						ucfirst( $ticket->sla_status ),
						$ticket->sla_due_date,
						round( $ticket->response_time_minutes / 60, 2 ) . 'h',
						round( $ticket->resolution_time_minutes / 60, 2 ) . 'h',
						$ticket->entity_name,
						$ticket->agent_name ?: 'Unassigned'
					);
				}
				break;
				
			case 'agent':
				$export_data['headers'] = array(
					'Agent', 'Tickets Assigned', 'Tickets Resolved', 'Resolution Rate', 'Avg Resolution Time', 'SLA Compliance'
				);
				foreach ( $report_data['agents'] as $agent ) {
					$export_data['data'][] = array(
						$agent->agent_name,
						$agent->tickets_assigned,
						$agent->tickets_resolved,
						$agent->resolution_rate . '%',
						round( $agent->avg_resolution_time, 1 ) . 'h',
						$agent->sla_compliance . '%'
					);
				}
				break;
		}

		return $export_data;
	}

	/**
	 * Get report title based on configuration
	 *
	 * @since    1.0.0
	 * @param    array    $config    Report configuration
	 * @return   string              Report title
	 */
	private function get_report_title( $config ) {
		$type = ucfirst( sanitize_text_field( $config['report_type'] ?? 'tickets' ) );
		$date_range = sanitize_text_field( $config['date_range'] ?? 'last_7_days' );
		
		$date_labels = array(
			'today' => __( 'Today', METS_TEXT_DOMAIN ),
			'yesterday' => __( 'Yesterday', METS_TEXT_DOMAIN ),
			'last_7_days' => __( 'Last 7 Days', METS_TEXT_DOMAIN ),
			'last_30_days' => __( 'Last 30 Days', METS_TEXT_DOMAIN ),
			'last_90_days' => __( 'Last 90 Days', METS_TEXT_DOMAIN ),
			'custom' => __( 'Custom Range', METS_TEXT_DOMAIN )
		);
		
		$date_label = $date_labels[ $date_range ] ?? $date_range;
		
		return sprintf( __( '%s Report - %s', METS_TEXT_DOMAIN ), $type, $date_label );
	}

	/**
	 * Render report results
	 *
	 * @since    1.0.0
	 * @param    array    $report_data    Report data
	 * @param    array    $config         Report configuration
	 */
	private function render_report_results( $report_data, $config ) {
		$show_summary = ! empty( $config['show_summary'] );
		$show_charts = ! empty( $config['show_charts'] );
		$show_details = ! empty( $config['show_details'] );

		echo '<div class="mets-report-content">';
		
		// Summary section
		if ( $show_summary && isset( $report_data['summary'] ) ) {
			$this->render_report_summary( $report_data );
		}
		
		// Charts section
		if ( $show_charts ) {
			$this->render_report_charts( $report_data, $config );
		}
		
		// Detailed data section
		if ( $show_details ) {
			$this->render_report_details( $report_data, $config );
		}
		
		echo '</div>';
	}

	/**
	 * Render report summary
	 *
	 * @since    1.0.0
	 * @param    array    $report_data    Report data
	 */
	private function render_report_summary( $report_data ) {
		$summary = $report_data['summary'];
		$type = $report_data['type'];
		
		echo '<div class="mets-report-summary">';
		echo '<h3>' . __( 'Summary Statistics', METS_TEXT_DOMAIN ) . '</h3>';
		echo '<div class="mets-summary-cards">';
		
		switch ( $type ) {
			case 'tickets':
				?>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo number_format( $summary->total_tickets ); ?></div>
					<div class="mets-summary-label"><?php _e( 'Total Tickets', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo number_format( $summary->open_tickets ); ?></div>
					<div class="mets-summary-label"><?php _e( 'Open Tickets', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo number_format( $summary->resolved_tickets ); ?></div>
					<div class="mets-summary-label"><?php _e( 'Resolved Tickets', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo $summary->avg_resolution_time ? round( $summary->avg_resolution_time, 1 ) . 'h' : '-'; ?></div>
					<div class="mets-summary-label"><?php _e( 'Avg Resolution Time', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<?php
				break;
				
			case 'sla':
				$compliance = $summary->total_with_sla > 0 ? 
					round( ( $summary->sla_met / $summary->total_with_sla ) * 100, 1 ) : 0;
				?>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo number_format( $summary->total_with_sla ); ?></div>
					<div class="mets-summary-label"><?php _e( 'Tickets with SLA', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo $compliance; ?>%</div>
					<div class="mets-summary-label"><?php _e( 'SLA Compliance', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo number_format( $summary->sla_breached ); ?></div>
					<div class="mets-summary-label"><?php _e( 'SLA Breached', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<div class="mets-summary-card">
					<div class="mets-summary-value"><?php echo $summary->avg_response_time ? round( $summary->avg_response_time / 60, 1 ) . 'h' : '-'; ?></div>
					<div class="mets-summary-label"><?php _e( 'Avg Response Time', METS_TEXT_DOMAIN ); ?></div>
				</div>
				<?php
				break;
		}
		
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render report charts
	 *
	 * @since    1.0.0
	 * @param    array    $report_data    Report data
	 * @param    array    $config         Report configuration
	 */
	private function render_report_charts( $report_data, $config ) {
		if ( empty( $report_data['grouped_data'] ) ) {
			return;
		}

		echo '<div class="mets-report-charts">';
		echo '<h3>' . __( 'Visual Analysis', METS_TEXT_DOMAIN ) . '</h3>';
		
		$grouped_data = $report_data['grouped_data'];
		$group_by = sanitize_text_field( $config['group_by'] ?? '' );
		
		echo '<div class="mets-chart-container">';
		echo '<h4>' . sprintf( __( 'Distribution by %s', METS_TEXT_DOMAIN ), ucfirst( $group_by ) ) . '</h4>';
		
		foreach ( $grouped_data as $group_name => $group_tickets ) {
			$count = count( $group_tickets );
			$percentage = $report_data['total_records'] > 0 ? 
				round( ( $count / $report_data['total_records'] ) * 100, 1 ) : 0;
			
			echo '<div class="mets-chart-bar">';
			echo '<div class="mets-chart-label">' . esc_html( $group_name ) . '</div>';
			echo '<div class="mets-chart-progress">';
			echo '<div class="mets-chart-fill" style="width: ' . $percentage . '%;"></div>';
			echo '</div>';
			echo '<div class="mets-chart-value">' . number_format( $count ) . ' (' . $percentage . '%)</div>';
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render report details
	 *
	 * @since    1.0.0
	 * @param    array    $report_data    Report data
	 * @param    array    $config         Report configuration
	 */
	private function render_report_details( $report_data, $config ) {
		echo '<div class="mets-report-details">';
		echo '<h3>' . __( 'Detailed Data', METS_TEXT_DOMAIN ) . '</h3>';
		
		switch ( $report_data['type'] ) {
			case 'tickets':
				$this->render_ticket_details_table( $report_data['tickets'] );
				break;
			case 'sla':
				$this->render_sla_details_table( $report_data['tickets'] );
				break;
			case 'agent':
				$this->render_agent_details_table( $report_data['agents'] );
				break;
			case 'knowledgebase':
				$this->render_kb_details_table( $report_data['articles'] );
				break;
		}
		
		echo '</div>';
	}

	/**
	 * Render ticket details table
	 *
	 * @since    1.0.0
	 * @param    array    $tickets    Ticket data
	 */
	private function render_ticket_details_table( $tickets ) {
		if ( empty( $tickets ) ) {
			echo '<p>' . __( 'No tickets found matching the criteria.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		?>
		<div class="mets-data-table-wrapper">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'ID', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Subject', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Created', METS_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tickets as $ticket ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $ticket->id ); ?></strong></td>
						<td>
							<a href="<?php echo admin_url( 'admin.php?page=mets-ticket-view&ticket_id=' . $ticket->id ); ?>" target="_blank">
								<?php echo esc_html( wp_trim_words( $ticket->subject, 8 ) ); ?>
							</a>
						</td>
						<td>
							<span class="mets-status-badge mets-status-<?php echo esc_attr( $ticket->status ); ?>">
								<?php echo esc_html( ucfirst( $ticket->status ) ); ?>
							</span>
						</td>
						<td>
							<span class="mets-priority-badge mets-priority-<?php echo esc_attr( $ticket->priority ); ?>">
								<?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $ticket->entity_name ); ?></td>
						<td><?php echo esc_html( $ticket->customer_name ?: 'Guest' ); ?></td>
						<td><?php echo esc_html( $ticket->agent_name ?: 'Unassigned' ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render SLA details table
	 *
	 * @since    1.0.0
	 * @param    array    $tickets    SLA ticket data
	 */
	private function render_sla_details_table( $tickets ) {
		if ( empty( $tickets ) ) {
			echo '<p>' . __( 'No SLA data found matching the criteria.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		?>
		<div class="mets-data-table-wrapper">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'ID', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Subject', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'SLA Status', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Due Date', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Response Time', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Resolution Time', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tickets as $ticket ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $ticket->id ); ?></strong></td>
						<td>
							<a href="<?php echo admin_url( 'admin.php?page=mets-ticket-view&ticket_id=' . $ticket->id ); ?>" target="_blank">
								<?php echo esc_html( wp_trim_words( $ticket->subject, 8 ) ); ?>
							</a>
						</td>
						<td>
							<span class="mets-sla-badge mets-sla-<?php echo esc_attr( $ticket->sla_status ); ?>">
								<?php echo esc_html( ucfirst( $ticket->sla_status ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( date_i18n( get_option( 'datetime_format' ), strtotime( $ticket->sla_due_date ) ) ); ?></td>
						<td><?php echo round( $ticket->response_time_minutes / 60, 2 ); ?>h</td>
						<td><?php echo round( $ticket->resolution_time_minutes / 60, 2 ); ?>h</td>
						<td><?php echo esc_html( $ticket->agent_name ?: 'Unassigned' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render agent details table
	 *
	 * @since    1.0.0
	 * @param    array    $agents    Agent data
	 */
	private function render_agent_details_table( $agents ) {
		if ( empty( $agents ) ) {
			echo '<p>' . __( 'No agent data found matching the criteria.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		?>
		<div class="mets-data-table-wrapper">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Tickets Assigned', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Tickets Resolved', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Resolution Rate', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Avg Resolution Time', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Avg Response Time', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'SLA Compliance', METS_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $agents as $agent ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $agent->agent_name ); ?></strong></td>
						<td><?php echo number_format( $agent->tickets_assigned ); ?></td>
						<td><?php echo number_format( $agent->tickets_resolved ); ?></td>
						<td>
							<span class="mets-performance-rate mets-rate-<?php echo $agent->resolution_rate >= 80 ? 'good' : ( $agent->resolution_rate >= 60 ? 'average' : 'poor' ); ?>">
								<?php echo $agent->resolution_rate; ?>%
							</span>
						</td>
						<td><?php echo $agent->avg_resolution_time ? round( $agent->avg_resolution_time, 1 ) . 'h' : '-'; ?></td>
						<td><?php echo $agent->avg_response_time ? round( $agent->avg_response_time, 1 ) . 'h' : '-'; ?></td>
						<td><?php echo $agent->sla_compliance; ?>%</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render KB details table
	 *
	 * @since    1.0.0
	 * @param    array    $articles    Article data
	 */
	private function render_kb_details_table( $articles ) {
		if ( empty( $articles ) ) {
			echo '<p>' . __( 'No knowledge base data found matching the criteria.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		?>
		<div class="mets-data-table-wrapper">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Article', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Views', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Unique Sessions', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Helpful Votes', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Helpfulness Ratio', METS_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $articles as $article ) : ?>
					<tr>
						<td>
							<a href="<?php echo admin_url( 'admin.php?page=mets-kb-edit-article&article_id=' . $article->id ); ?>" target="_blank">
								<strong><?php echo esc_html( $article->title ); ?></strong>
							</a>
						</td>
						<td><?php echo esc_html( $article->entity_name ); ?></td>
						<td><?php echo number_format( $article->views ); ?></td>
						<td><?php echo number_format( $article->unique_sessions ); ?></td>
						<td><?php echo number_format( $article->helpful ); ?></td>
						<td>
							<?php if ( $article->helpfulness_ratio > 0 ) : ?>
								<span class="mets-helpfulness-rate mets-rate-<?php echo $article->helpfulness_ratio >= 70 ? 'good' : ( $article->helpfulness_ratio >= 40 ? 'average' : 'poor' ); ?>">
									<?php echo $article->helpfulness_ratio; ?>%
								</span>
							<?php else : ?>
								-
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render report builder styles
	 *
	 * @since    1.0.0
	 */
	private function render_report_builder_styles() {
		?>
		<style>
		/* Report Builder Layout */
		.mets-report-builder {
			display: grid;
			gap: 30px;
			margin-top: 20px;
		}

		.mets-builder-form {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 0;
		}

		.mets-builder-sections {
			display: grid;
			gap: 0;
		}

		.mets-builder-section {
			padding: 25px;
			border-bottom: 1px solid #ddd;
		}

		.mets-builder-section:last-child {
			border-bottom: none;
		}

		.mets-builder-section h3 {
			margin: 0 0 20px 0;
			font-size: 16px;
			color: #1d2327;
			font-weight: 600;
		}

		/* Report Type Selection */
		.mets-report-types {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 15px;
		}

		.mets-report-type-option {
			display: flex;
			align-items: flex-start;
			padding: 15px;
			border: 2px solid #ddd;
			border-radius: 6px;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.mets-report-type-option:hover {
			border-color: #0073aa;
			background: #f6f7f7;
		}

		.mets-report-type-option input[type="radio"] {
			margin: 4px 12px 0 0;
		}

		.mets-report-type-option input[type="radio"]:checked + .mets-report-type-label {
			color: #0073aa;
		}

		.mets-report-type-option:has(input:checked) {
			border-color: #0073aa;
			background: #f0f6fc;
		}

		.mets-report-type-label {
			display: flex;
			flex-direction: column;
			gap: 5px;
		}

		.mets-report-type-label strong {
			font-weight: 600;
			font-size: 14px;
		}

		.mets-report-type-label span {
			font-size: 12px;
			color: #666;
			line-height: 1.4;
		}

		/* Date Range */
		.mets-date-presets {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
			gap: 10px;
			margin-bottom: 15px;
		}

		.mets-date-presets label {
			display: flex;
			align-items: center;
			font-size: 14px;
			cursor: pointer;
		}

		.mets-date-presets input[type="radio"] {
			margin-right: 8px;
		}

		.mets-custom-dates {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 15px;
			padding: 15px;
			background: #f9f9f9;
			border-radius: 4px;
		}

		.mets-custom-dates label {
			display: flex;
			flex-direction: column;
			gap: 5px;
			font-weight: 600;
		}

		.mets-custom-dates input[type="date"] {
			padding: 6px 8px;
			border: 1px solid #8c8f94;
			border-radius: 3px;
		}

		/* Filters */
		.mets-filters-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
		}

		.mets-filter-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.mets-filter-group label {
			font-weight: 600;
			font-size: 13px;
			color: #1d2327;
		}

		.mets-filter-group select {
			padding: 6px 8px;
			border: 1px solid #8c8f94;
			border-radius: 3px;
			background: #fff;
		}

		.mets-filter-group select[multiple] {
			min-height: 100px;
		}

		/* Display Options */
		.mets-display-options {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 20px;
			margin-bottom: 20px;
		}

		.mets-option-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.mets-option-group label {
			font-weight: 600;
			font-size: 13px;
			color: #1d2327;
		}

		.mets-option-group select {
			padding: 6px 8px;
			border: 1px solid #8c8f94;
			border-radius: 3px;
		}

		.mets-display-format {
			padding-top: 15px;
			border-top: 1px solid #ddd;
		}

		.mets-display-format h4 {
			margin: 0 0 15px 0;
			font-size: 14px;
			font-weight: 600;
		}

		.mets-display-format label {
			display: flex;
			align-items: center;
			margin-bottom: 10px;
			font-size: 14px;
			cursor: pointer;
		}

		.mets-display-format input[type="checkbox"] {
			margin-right: 8px;
		}

		/* Actions */
		.mets-builder-actions {
			padding: 25px;
			background: #f6f7f7;
			border-top: 1px solid #ddd;
			display: flex;
			gap: 10px;
			align-items: center;
		}

		/* Report Results */
		.mets-report-results {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 0;
		}

		.mets-report-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 20px 25px;
			border-bottom: 1px solid #ddd;
			background: #f6f7f7;
		}

		.mets-report-header h2 {
			margin: 0;
			font-size: 18px;
		}

		.mets-export-options {
			display: flex;
			gap: 10px;
		}

		.mets-report-content {
			padding: 25px;
		}

		/* Summary Cards */
		.mets-summary-cards {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}

		.mets-summary-card {
			text-align: center;
			padding: 20px;
			background: #f8f9fa;
			border: 1px solid #dee2e6;
			border-radius: 6px;
		}

		.mets-summary-value {
			font-size: 28px;
			font-weight: bold;
			color: #0073aa;
			margin-bottom: 8px;
		}

		.mets-summary-label {
			font-size: 13px;
			color: #666;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		/* Charts */
		.mets-chart-container {
			margin-bottom: 30px;
		}

		.mets-chart-container h4 {
			margin: 0 0 20px 0;
			font-size: 15px;
			font-weight: 600;
		}

		.mets-chart-bar {
			display: grid;
			grid-template-columns: 150px 1fr 100px;
			gap: 15px;
			align-items: center;
			margin-bottom: 12px;
		}

		.mets-chart-label {
			font-weight: 600;
			font-size: 13px;
		}

		.mets-chart-progress {
			height: 20px;
			background: #f0f0f1;
			border-radius: 10px;
			overflow: hidden;
		}

		.mets-chart-fill {
			height: 100%;
			background: linear-gradient(90deg, #0073aa, #005a87);
			transition: width 0.5s ease;
		}

		.mets-chart-value {
			text-align: right;
			font-size: 13px;
			color: #666;
		}

		/* Data Tables */
		.mets-data-table-wrapper {
			overflow-x: auto;
			margin-top: 20px;
		}

		.mets-data-table-wrapper table {
			min-width: 800px;
		}

		/* Status and Priority Badges */
		.mets-status-badge, .mets-priority-badge, .mets-sla-badge {
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}

		.mets-status-open { background: #ffeaea; color: #d63638; }
		.mets-status-in_progress { background: #fff2e5; color: #b32d2e; }
		.mets-status-resolved { background: #edf7ed; color: #1e8e3e; }
		.mets-status-closed { background: #f0f0f1; color: #50575e; }
		.mets-status-on_hold { background: #f3e5f5; color: #8e24aa; }

		.mets-priority-critical { background: #ffeaea; color: #d32f2f; }
		.mets-priority-high { background: #fff3e0; color: #f57c00; }
		.mets-priority-medium { background: #fffde7; color: #fbc02d; }
		.mets-priority-low { background: #f1f8e9; color: #689f38; }

		.mets-sla-met { background: #edf7ed; color: #1e8e3e; }
		.mets-sla-warning { background: #fff3e0; color: #f57c00; }
		.mets-sla-breached { background: #ffeaea; color: #d63638; }

		.mets-performance-rate, .mets-helpfulness-rate {
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
		}

		.mets-rate-good { background: #edf7ed; color: #1e8e3e; }
		.mets-rate-average { background: #fff3e0; color: #f57c00; }
		.mets-rate-poor { background: #ffeaea; color: #d63638; }

		/* Responsive Design */
		@media (max-width: 1200px) {
			.mets-report-types {
				grid-template-columns: 1fr;
			}
			
			.mets-filters-grid {
				grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
			}
			
			.mets-display-options {
				grid-template-columns: repeat(2, 1fr);
			}
		}

		@media (max-width: 768px) {
			.mets-report-header {
				flex-direction: column;
				gap: 15px;
				align-items: flex-start;
			}
			
			.mets-export-options {
				width: 100%;
				justify-content: flex-start;
			}
			
			.mets-builder-actions {
				flex-direction: column;
				align-items: stretch;
			}
			
			.mets-builder-actions .button {
				justify-content: center;
			}
			
			.mets-chart-bar {
				grid-template-columns: 1fr;
				gap: 8px;
			}
			
			.mets-chart-value {
				text-align: left;
			}
			
			.mets-summary-cards {
				grid-template-columns: repeat(2, 1fr);
			}
			
			.mets-filters-grid {
				grid-template-columns: 1fr;
			}
			
			.mets-display-options {
				grid-template-columns: 1fr;
			}
			
			.mets-custom-dates {
				grid-template-columns: 1fr;
			}
		}

		/* Print Styles */
		@media print {
			.mets-builder-form,
			.mets-report-header .mets-export-options {
				display: none !important;
			}
			
			.mets-report-results {
				border: none;
				box-shadow: none;
			}
			
			.mets-report-content {
				padding: 0;
			}
		}
		</style>
		<?php
	}

	/**
	 * Render report builder scripts
	 *
	 * @since    1.0.0
	 */
	private function render_report_builder_scripts() {
		?>
		<script>
		jQuery(document).ready(function($) {
			// Handle custom date range toggle
			$('input[name="date_range"]').on('change', function() {
				if ($(this).val() === 'custom') {
					$('.mets-custom-dates').slideDown();
				} else {
					$('.mets-custom-dates').slideUp();
				}
			});

			// Handle report type changes to show/hide relevant filters
			$('input[name="report_type"]').on('change', function() {
				var reportType = $(this).val();
				
				// Hide all conditional filters
				$('.ticket-filters, .sla-filters, .agent-filters').hide();
				
				// Show relevant filters based on report type
				switch(reportType) {
					case 'tickets':
						$('.ticket-filters').show();
						break;
					case 'sla':
						$('.ticket-filters, .sla-filters').show();
						break;
					case 'agent':
						$('.agent-filters').show();
						break;
				}
				
				// Update group by options
				updateGroupByOptions(reportType);
			});

			// Update group by options based on report type
			function updateGroupByOptions(reportType) {
				var $groupBy = $('select[name="group_by"]');
				var currentValue = $groupBy.val();
				
				// Clear existing options
				$groupBy.empty();
				$groupBy.append('<option value=""><?php _e( "No Grouping", METS_TEXT_DOMAIN ); ?></option>');
				
				// Add options based on report type
				switch(reportType) {
					case 'tickets':
						$groupBy.append('<option value="status"><?php _e( "Status", METS_TEXT_DOMAIN ); ?></option>');
						$groupBy.append('<option value="priority"><?php _e( "Priority", METS_TEXT_DOMAIN ); ?></option>');
						$groupBy.append('<option value="entity"><?php _e( "Entity", METS_TEXT_DOMAIN ); ?></option>');
						$groupBy.append('<option value="agent"><?php _e( "Agent", METS_TEXT_DOMAIN ); ?></option>');
						$groupBy.append('<option value="date"><?php _e( "Date Created", METS_TEXT_DOMAIN ); ?></option>');
						break;
					case 'sla':
						$groupBy.append('<option value="sla_status"><?php _e( "SLA Status", METS_TEXT_DOMAIN ); ?></option>');
						$groupBy.append('<option value="entity"><?php _e( "Entity", METS_TEXT_DOMAIN ); ?></option>');
						$groupBy.append('<option value="agent"><?php _e( "Agent", METS_TEXT_DOMAIN ); ?></option>');
						break;
					case 'agent':
						$groupBy.append('<option value="entity"><?php _e( "Entity", METS_TEXT_DOMAIN ); ?></option>');
						break;
				}
				
				// Restore previous value if still valid
				if ($groupBy.find('option[value="' + currentValue + '"]').length) {
					$groupBy.val(currentValue);
				}
			}

			// Initialize filters based on default report type
			$('input[name="report_type"]:checked').trigger('change');

			// Form validation
			$('#mets-report-form').on('submit', function(e) {
				var dateRange = $('input[name="date_range"]:checked').val();
				
				if (dateRange === 'custom') {
					var dateFrom = $('input[name="date_from"]').val();
					var dateTo = $('input[name="date_to"]').val();
					
					if (!dateFrom || !dateTo) {
						alert('<?php _e( "Please select both start and end dates for custom range.", METS_TEXT_DOMAIN ); ?>');
						e.preventDefault();
						return false;
					}
					
					if (new Date(dateFrom) > new Date(dateTo)) {
						alert('<?php _e( "Start date must be before end date.", METS_TEXT_DOMAIN ); ?>');
						e.preventDefault();
						return false;
					}
				}
			});

			// Save template functionality (placeholder)
			$('#mets-save-template').on('click', function() {
				// TODO: Implement template saving
				alert('<?php _e( "Template saving functionality coming soon!", METS_TEXT_DOMAIN ); ?>');
			});

			// Load template functionality (placeholder)
			$('#mets-load-template').on('click', function() {
				// TODO: Implement template loading
				alert('<?php _e( "Template loading functionality coming soon!", METS_TEXT_DOMAIN ); ?>');
			});
		});

		// Export functions
		function metsExportReport(format) {
			document.getElementById('export-format').value = format;
			document.getElementById('do-export').click();
		}
		</script>
		<?php
	}
}