<?php
/**
 * Bulk Operations Admin Page
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * The Bulk Operations Admin Page class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Bulk_Operations_Page {

	/**
	 * Display the bulk operations page
	 *
	 * @since    1.0.0
	 */
	public function display_bulk_operations_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'Bulk Operations & Advanced Features', METS_TEXT_DOMAIN ); ?></h1>
			
			<div class="mets-bulk-operations-container">
				<div class="mets-bulk-tabs">
					<nav class="nav-tab-wrapper">
						<a href="#bulk-tickets" class="nav-tab nav-tab-active"><?php _e( 'Ticket Operations', METS_TEXT_DOMAIN ); ?></a>
						<a href="#bulk-entities" class="nav-tab"><?php _e( 'Entity Operations', METS_TEXT_DOMAIN ); ?></a>
						<a href="#bulk-kb" class="nav-tab"><?php _e( 'KB Operations', METS_TEXT_DOMAIN ); ?></a>
						<a href="#import-export" class="nav-tab"><?php _e( 'Import/Export', METS_TEXT_DOMAIN ); ?></a>
						<a href="#automation" class="nav-tab"><?php _e( 'Automation', METS_TEXT_DOMAIN ); ?></a>
						<a href="#maintenance" class="nav-tab"><?php _e( 'Maintenance', METS_TEXT_DOMAIN ); ?></a>
					</nav>
				</div>

				<!-- Ticket Operations Tab -->
				<div id="bulk-tickets" class="mets-tab-content active">
					<div class="mets-bulk-section">
						<h2><?php _e( 'Bulk Ticket Operations', METS_TEXT_DOMAIN ); ?></h2>
						
						<div class="mets-bulk-filters">
							<h3><?php _e( 'Select Tickets', METS_TEXT_DOMAIN ); ?></h3>
							<div class="filter-row">
								<label for="ticket-status-filter"><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></label>
								<select id="ticket-status-filter" multiple>
									<option value="all"><?php _e( 'All Statuses', METS_TEXT_DOMAIN ); ?></option>
									<option value="open"><?php _e( 'Open', METS_TEXT_DOMAIN ); ?></option>
									<option value="in_progress"><?php _e( 'In Progress', METS_TEXT_DOMAIN ); ?></option>
									<option value="resolved"><?php _e( 'Resolved', METS_TEXT_DOMAIN ); ?></option>
									<option value="closed"><?php _e( 'Closed', METS_TEXT_DOMAIN ); ?></option>
									<option value="on_hold"><?php _e( 'On Hold', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<label for="ticket-priority-filter"><?php _e( 'Priority:', METS_TEXT_DOMAIN ); ?></label>
								<select id="ticket-priority-filter" multiple>
									<option value="all"><?php _e( 'All Priorities', METS_TEXT_DOMAIN ); ?></option>
									<option value="low"><?php _e( 'Low', METS_TEXT_DOMAIN ); ?></option>
									<option value="medium"><?php _e( 'Medium', METS_TEXT_DOMAIN ); ?></option>
									<option value="high"><?php _e( 'High', METS_TEXT_DOMAIN ); ?></option>
									<option value="critical"><?php _e( 'Critical', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<label for="ticket-entity-filter"><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></label>
								<select id="ticket-entity-filter">
									<option value=""><?php _e( 'All Entities', METS_TEXT_DOMAIN ); ?></option>
									<?php
									global $wpdb;
									$entities = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}mets_entities WHERE status = 'active' ORDER BY name" );
									foreach ( $entities as $entity ) {
										echo '<option value="' . $entity->id . '">' . esc_html( $entity->name ) . '</option>';
									}
									?>
								</select>
								
								<button type="button" id="load-tickets" class="button"><?php _e( 'Load Tickets', METS_TEXT_DOMAIN ); ?></button>
							</div>
							
							<div class="filter-row">
								<label for="ticket-date-from"><?php _e( 'Date From:', METS_TEXT_DOMAIN ); ?></label>
								<input type="date" id="ticket-date-from">
								
								<label for="ticket-date-to"><?php _e( 'Date To:', METS_TEXT_DOMAIN ); ?></label>
								<input type="date" id="ticket-date-to">
								
								<label for="ticket-assigned-filter"><?php _e( 'Assigned To:', METS_TEXT_DOMAIN ); ?></label>
								<select id="ticket-assigned-filter">
									<option value=""><?php _e( 'All Agents', METS_TEXT_DOMAIN ); ?></option>
									<option value="unassigned"><?php _e( 'Unassigned', METS_TEXT_DOMAIN ); ?></option>
									<?php
									$agents = get_users( array( 'capability' => 'manage_tickets' ) );
									foreach ( $agents as $agent ) {
										echo '<option value="' . $agent->ID . '">' . esc_html( $agent->display_name ) . '</option>';
									}
									?>
								</select>
							</div>
						</div>

						<div id="ticket-results" class="mets-results-container">
							<p><?php _e( 'Use the filters above to load tickets for bulk operations.', METS_TEXT_DOMAIN ); ?></p>
						</div>

						<div class="mets-bulk-actions" style="display: none;">
							<h3><?php _e( 'Bulk Actions', METS_TEXT_DOMAIN ); ?></h3>
							<div class="action-row">
								<select id="ticket-bulk-action">
									<option value=""><?php _e( 'Select Action', METS_TEXT_DOMAIN ); ?></option>
									<option value="update_status"><?php _e( 'Update Status', METS_TEXT_DOMAIN ); ?></option>
									<option value="update_priority"><?php _e( 'Update Priority', METS_TEXT_DOMAIN ); ?></option>
									<option value="assign_agent"><?php _e( 'Assign Agent', METS_TEXT_DOMAIN ); ?></option>
									<option value="move_entity"><?php _e( 'Move to Entity', METS_TEXT_DOMAIN ); ?></option>
									<option value="add_reply"><?php _e( 'Add Reply', METS_TEXT_DOMAIN ); ?></option>
									<option value="merge_tickets"><?php _e( 'Merge Tickets', METS_TEXT_DOMAIN ); ?></option>
									<option value="archive"><?php _e( 'Archive', METS_TEXT_DOMAIN ); ?></option>
									<option value="delete"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<div id="action-parameters" style="display: none;"></div>
								
								<button type="button" id="execute-ticket-action" class="button button-primary"><?php _e( 'Execute Action', METS_TEXT_DOMAIN ); ?></button>
							</div>
						</div>
					</div>
				</div>

				<!-- Entity Operations Tab -->
				<div id="bulk-entities" class="mets-tab-content">
					<div class="mets-bulk-section">
						<h2><?php _e( 'Bulk Entity Operations', METS_TEXT_DOMAIN ); ?></h2>
						
						<div class="mets-bulk-filters">
							<h3><?php _e( 'Select Entities', METS_TEXT_DOMAIN ); ?></h3>
							<div class="filter-row">
								<label for="entity-type-filter"><?php _e( 'Type:', METS_TEXT_DOMAIN ); ?></label>
								<select id="entity-type-filter" multiple>
									<option value="all"><?php _e( 'All Types', METS_TEXT_DOMAIN ); ?></option>
									<option value="company"><?php _e( 'Company', METS_TEXT_DOMAIN ); ?></option>
									<option value="department"><?php _e( 'Department', METS_TEXT_DOMAIN ); ?></option>
									<option value="team"><?php _e( 'Team', METS_TEXT_DOMAIN ); ?></option>
									<option value="other"><?php _e( 'Other', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<label for="entity-status-filter"><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></label>
								<select id="entity-status-filter" multiple>
									<option value="all"><?php _e( 'All Statuses', METS_TEXT_DOMAIN ); ?></option>
									<option value="active"><?php _e( 'Active', METS_TEXT_DOMAIN ); ?></option>
									<option value="inactive"><?php _e( 'Inactive', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<button type="button" id="load-entities" class="button"><?php _e( 'Load Entities', METS_TEXT_DOMAIN ); ?></button>
							</div>
						</div>

						<div id="entity-results" class="mets-results-container">
							<p><?php _e( 'Use the filters above to load entities for bulk operations.', METS_TEXT_DOMAIN ); ?></p>
						</div>

						<div class="mets-bulk-actions" style="display: none;">
							<h3><?php _e( 'Bulk Actions', METS_TEXT_DOMAIN ); ?></h3>
							<div class="action-row">
								<select id="entity-bulk-action">
									<option value=""><?php _e( 'Select Action', METS_TEXT_DOMAIN ); ?></option>
									<option value="activate"><?php _e( 'Activate', METS_TEXT_DOMAIN ); ?></option>
									<option value="deactivate"><?php _e( 'Deactivate', METS_TEXT_DOMAIN ); ?></option>
									<option value="change_parent"><?php _e( 'Change Parent', METS_TEXT_DOMAIN ); ?></option>
									<option value="delete"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<div id="entity-action-parameters" style="display: none;"></div>
								
								<button type="button" id="execute-entity-action" class="button button-primary"><?php _e( 'Execute Action', METS_TEXT_DOMAIN ); ?></button>
							</div>
						</div>
					</div>
				</div>

				<!-- KB Operations Tab -->
				<div id="bulk-kb" class="mets-tab-content">
					<div class="mets-bulk-section">
						<h2><?php _e( 'Bulk Knowledge Base Operations', METS_TEXT_DOMAIN ); ?></h2>
						
						<div class="mets-bulk-filters">
							<h3><?php _e( 'Select Articles', METS_TEXT_DOMAIN ); ?></h3>
							<div class="filter-row">
								<label for="kb-status-filter"><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></label>
								<select id="kb-status-filter" multiple>
									<option value="all"><?php _e( 'All Statuses', METS_TEXT_DOMAIN ); ?></option>
									<option value="draft"><?php _e( 'Draft', METS_TEXT_DOMAIN ); ?></option>
									<option value="pending_review"><?php _e( 'Pending Review', METS_TEXT_DOMAIN ); ?></option>
									<option value="published"><?php _e( 'Published', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<label for="kb-category-filter"><?php _e( 'Category:', METS_TEXT_DOMAIN ); ?></label>
								<select id="kb-category-filter">
									<option value=""><?php _e( 'All Categories', METS_TEXT_DOMAIN ); ?></option>
									<?php
									$categories = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}mets_kb_categories ORDER BY name" );
									foreach ( $categories as $category ) {
										echo '<option value="' . $category->id . '">' . esc_html( $category->name ) . '</option>';
									}
									?>
								</select>
								
								<label for="kb-featured-filter"><?php _e( 'Featured:', METS_TEXT_DOMAIN ); ?></label>
								<select id="kb-featured-filter">
									<option value=""><?php _e( 'All', METS_TEXT_DOMAIN ); ?></option>
									<option value="1"><?php _e( 'Featured Only', METS_TEXT_DOMAIN ); ?></option>
									<option value="0"><?php _e( 'Non-Featured Only', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<button type="button" id="load-kb-articles" class="button"><?php _e( 'Load Articles', METS_TEXT_DOMAIN ); ?></button>
							</div>
						</div>

						<div id="kb-results" class="mets-results-container">
							<p><?php _e( 'Use the filters above to load KB articles for bulk operations.', METS_TEXT_DOMAIN ); ?></p>
						</div>

						<div class="mets-bulk-actions" style="display: none;">
							<h3><?php _e( 'Bulk Actions', METS_TEXT_DOMAIN ); ?></h3>
							<div class="action-row">
								<select id="kb-bulk-action">
									<option value=""><?php _e( 'Select Action', METS_TEXT_DOMAIN ); ?></option>
									<option value="publish"><?php _e( 'Publish', METS_TEXT_DOMAIN ); ?></option>
									<option value="draft"><?php _e( 'Move to Draft', METS_TEXT_DOMAIN ); ?></option>
									<option value="feature"><?php _e( 'Mark as Featured', METS_TEXT_DOMAIN ); ?></option>
									<option value="unfeature"><?php _e( 'Remove Featured', METS_TEXT_DOMAIN ); ?></option>
									<option value="change_category"><?php _e( 'Change Category', METS_TEXT_DOMAIN ); ?></option>
									<option value="delete"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></option>
								</select>
								
								<div id="kb-action-parameters" style="display: none;"></div>
								
								<button type="button" id="execute-kb-action" class="button button-primary"><?php _e( 'Execute Action', METS_TEXT_DOMAIN ); ?></button>
							</div>
						</div>
					</div>
				</div>

				<!-- Import/Export Tab -->
				<div id="import-export" class="mets-tab-content">
					<div class="mets-bulk-section">
						<h2><?php _e( 'Import & Export Data', METS_TEXT_DOMAIN ); ?></h2>
						
						<div class="mets-import-export-grid">
							<div class="export-section">
								<h3><?php _e( 'Export Data', METS_TEXT_DOMAIN ); ?></h3>
								<form id="export-form">
									<div class="form-group">
										<label for="export-type"><?php _e( 'Data Type:', METS_TEXT_DOMAIN ); ?></label>
										<select id="export-type" name="export_type">
											<option value="tickets"><?php _e( 'Tickets', METS_TEXT_DOMAIN ); ?></option>
											<option value="entities"><?php _e( 'Entities', METS_TEXT_DOMAIN ); ?></option>
											<option value="kb_articles"><?php _e( 'KB Articles', METS_TEXT_DOMAIN ); ?></option>
											<option value="analytics"><?php _e( 'Analytics Data', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>
									
									<div class="form-group">
										<label for="export-format"><?php _e( 'Format:', METS_TEXT_DOMAIN ); ?></label>
										<select id="export-format" name="format">
											<option value="csv"><?php _e( 'CSV', METS_TEXT_DOMAIN ); ?></option>
											<option value="json"><?php _e( 'JSON', METS_TEXT_DOMAIN ); ?></option>
											<option value="xml"><?php _e( 'XML', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>
									
									<div id="export-filters">
										<!-- Dynamic filters based on export type -->
									</div>
									
									<button type="submit" class="button button-primary"><?php _e( 'Export Data', METS_TEXT_DOMAIN ); ?></button>
								</form>
								
								<div id="export-results" style="margin-top: 20px;"></div>
							</div>
							
							<div class="import-section">
								<h3><?php _e( 'Import Data', METS_TEXT_DOMAIN ); ?></h3>
								<form id="import-form" enctype="multipart/form-data">
									<div class="form-group">
										<label for="import-type"><?php _e( 'Data Type:', METS_TEXT_DOMAIN ); ?></label>
										<select id="import-type" name="import_type">
											<option value="tickets"><?php _e( 'Tickets', METS_TEXT_DOMAIN ); ?></option>
											<option value="entities"><?php _e( 'Entities', METS_TEXT_DOMAIN ); ?></option>
											<option value="kb_articles"><?php _e( 'KB Articles', METS_TEXT_DOMAIN ); ?></option>
										</select>
									</div>
									
									<div class="form-group">
										<label for="import-file"><?php _e( 'File:', METS_TEXT_DOMAIN ); ?></label>
										<input type="file" id="import-file" name="import_file" accept=".csv,.json,.xml" required>
										<p class="description"><?php _e( 'Supported formats: CSV, JSON, XML', METS_TEXT_DOMAIN ); ?></p>
									</div>
									
									<div class="form-group">
										<label>
											<input type="checkbox" id="import-update-existing" name="update_existing">
											<?php _e( 'Update existing records', METS_TEXT_DOMAIN ); ?>
										</label>
									</div>
									
									<button type="submit" class="button button-primary"><?php _e( 'Import Data', METS_TEXT_DOMAIN ); ?></button>
								</form>
								
								<div id="import-results" style="margin-top: 20px;"></div>
							</div>
						</div>
					</div>
				</div>

				<!-- Automation Tab -->
				<div id="automation" class="mets-tab-content">
					<div class="mets-bulk-section">
						<h2><?php _e( 'Advanced Automation Rules', METS_TEXT_DOMAIN ); ?></h2>
						
						<div class="automation-controls">
							<button type="button" id="add-automation-rule" class="button button-primary"><?php _e( 'Add New Rule', METS_TEXT_DOMAIN ); ?></button>
							<button type="button" id="test-automation-rules" class="button"><?php _e( 'Test All Rules', METS_TEXT_DOMAIN ); ?></button>
						</div>
						
						<div id="automation-rules-list">
							<div class="automation-rule-template" style="display: none;">
								<div class="rule-header">
									<h4 class="rule-name"></h4>
									<div class="rule-controls">
										<label class="rule-toggle">
											<input type="checkbox" class="rule-active">
											<span><?php _e( 'Active', METS_TEXT_DOMAIN ); ?></span>
										</label>
										<button type="button" class="button edit-rule"><?php _e( 'Edit', METS_TEXT_DOMAIN ); ?></button>
										<button type="button" class="button test-rule"><?php _e( 'Test', METS_TEXT_DOMAIN ); ?></button>
										<button type="button" class="button delete-rule"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></button>
									</div>
								</div>
								<div class="rule-details">
									<p class="rule-description"></p>
									<div class="rule-stats">
										<span class="stat-item">Trigger: <strong class="rule-trigger"></strong></span>
										<span class="stat-item">Conditions: <strong class="rule-conditions-count"></strong></span>
										<span class="stat-item">Actions: <strong class="rule-actions-count"></strong></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Maintenance Tab -->
				<div id="maintenance" class="mets-tab-content">
					<div class="mets-bulk-section">
						<h2><?php _e( 'System Maintenance', METS_TEXT_DOMAIN ); ?></h2>
						
						<div class="maintenance-grid">
							<div class="maintenance-card">
								<h3><?php _e( 'Database Cleanup', METS_TEXT_DOMAIN ); ?></h3>
								<p><?php _e( 'Clean up orphaned records and optimize database tables.', METS_TEXT_DOMAIN ); ?></p>
								<button type="button" id="cleanup-database" class="button"><?php _e( 'Run Cleanup', METS_TEXT_DOMAIN ); ?></button>
								<div id="cleanup-results"></div>
							</div>
							
							<div class="maintenance-card">
								<h3><?php _e( 'Archive Old Tickets', METS_TEXT_DOMAIN ); ?></h3>
								<p><?php _e( 'Archive closed tickets older than specified days.', METS_TEXT_DOMAIN ); ?></p>
								<div class="form-group">
									<label for="archive-days"><?php _e( 'Days:', METS_TEXT_DOMAIN ); ?></label>
									<input type="number" id="archive-days" value="90" min="1">
								</div>
								<button type="button" id="archive-old-tickets" class="button"><?php _e( 'Archive Tickets', METS_TEXT_DOMAIN ); ?></button>
								<div id="archive-results"></div>
							</div>
							
							<div class="maintenance-card">
								<h3><?php _e( 'Regenerate Statistics', METS_TEXT_DOMAIN ); ?></h3>
								<p><?php _e( 'Recalculate all system statistics and metrics.', METS_TEXT_DOMAIN ); ?></p>
								<button type="button" id="regenerate-stats" class="button"><?php _e( 'Regenerate', METS_TEXT_DOMAIN ); ?></button>
								<div id="stats-results"></div>
							</div>
							
							<div class="maintenance-card">
								<h3><?php _e( 'Clear Cache', METS_TEXT_DOMAIN ); ?></h3>
								<p><?php _e( 'Clear all system caches and temporary data.', METS_TEXT_DOMAIN ); ?></p>
								<button type="button" id="clear-cache" class="button"><?php _e( 'Clear Cache', METS_TEXT_DOMAIN ); ?></button>
								<div id="cache-results"></div>
							</div>
							
							<div class="maintenance-card">
								<h3><?php _e( 'Export Logs', METS_TEXT_DOMAIN ); ?></h3>
								<p><?php _e( 'Export system logs for analysis or backup.', METS_TEXT_DOMAIN ); ?></p>
								<div class="form-group">
									<label for="log-days"><?php _e( 'Days:', METS_TEXT_DOMAIN ); ?></label>
									<input type="number" id="log-days" value="30" min="1">
								</div>
								<button type="button" id="export-logs" class="button"><?php _e( 'Export Logs', METS_TEXT_DOMAIN ); ?></button>
								<div id="log-results"></div>
							</div>
							
							<div class="maintenance-card">
								<h3><?php _e( 'System Health Check', METS_TEXT_DOMAIN ); ?></h3>
								<p><?php _e( 'Run comprehensive system health and integrity check.', METS_TEXT_DOMAIN ); ?></p>
								<button type="button" id="health-check" class="button"><?php _e( 'Run Check', METS_TEXT_DOMAIN ); ?></button>
								<div id="health-results"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Automation Rule Modal -->
		<div id="automation-rule-modal" class="mets-modal" style="display: none;">
			<div class="mets-modal-content">
				<div class="mets-modal-header">
					<h3><?php _e( 'Automation Rule', METS_TEXT_DOMAIN ); ?></h3>
					<button type="button" class="mets-modal-close">&times;</button>
				</div>
				<div class="mets-modal-body">
					<form id="automation-rule-form">
						<input type="hidden" id="rule-id">
						
						<div class="form-group">
							<label for="rule-name"><?php _e( 'Rule Name:', METS_TEXT_DOMAIN ); ?></label>
							<input type="text" id="rule-name" name="name" required>
						</div>
						
						<div class="form-group">
							<label for="rule-description"><?php _e( 'Description:', METS_TEXT_DOMAIN ); ?></label>
							<textarea id="rule-description" name="description" rows="3"></textarea>
						</div>
						
						<div class="form-group">
							<label for="rule-trigger"><?php _e( 'Trigger:', METS_TEXT_DOMAIN ); ?></label>
							<select id="rule-trigger" name="trigger">
								<option value="ticket_created"><?php _e( 'Ticket Created', METS_TEXT_DOMAIN ); ?></option>
								<option value="ticket_updated"><?php _e( 'Ticket Updated', METS_TEXT_DOMAIN ); ?></option>
								<option value="status_changed"><?php _e( 'Status Changed', METS_TEXT_DOMAIN ); ?></option>
								<option value="ticket_assigned"><?php _e( 'Ticket Assigned', METS_TEXT_DOMAIN ); ?></option>
								<option value="ticket_replied"><?php _e( 'Ticket Replied', METS_TEXT_DOMAIN ); ?></option>
								<option value="scheduled"><?php _e( 'Scheduled', METS_TEXT_DOMAIN ); ?></option>
							</select>
						</div>
						
						<div class="form-group" id="schedule-options" style="display: none;">
							<label for="rule-schedule"><?php _e( 'Schedule:', METS_TEXT_DOMAIN ); ?></label>
							<select id="rule-schedule" name="schedule">
								<option value="hourly"><?php _e( 'Hourly', METS_TEXT_DOMAIN ); ?></option>
								<option value="daily"><?php _e( 'Daily', METS_TEXT_DOMAIN ); ?></option>
								<option value="every_15_minutes"><?php _e( 'Every 15 Minutes', METS_TEXT_DOMAIN ); ?></option>
							</select>
						</div>
						
						<div class="form-section">
							<h4><?php _e( 'Conditions', METS_TEXT_DOMAIN ); ?></h4>
							<div id="rule-conditions">
								<!-- Dynamic conditions -->
							</div>
							<button type="button" id="add-condition" class="button"><?php _e( 'Add Condition', METS_TEXT_DOMAIN ); ?></button>
						</div>
						
						<div class="form-section">
							<h4><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></h4>
							<div id="rule-actions">
								<!-- Dynamic actions -->
							</div>
							<button type="button" id="add-action" class="button"><?php _e( 'Add Action', METS_TEXT_DOMAIN ); ?></button>
						</div>
						
						<div class="form-group">
							<label>
								<input type="checkbox" id="rule-active-checkbox" name="active">
								<?php _e( 'Active', METS_TEXT_DOMAIN ); ?>
							</label>
						</div>
					</form>
				</div>
				<div class="mets-modal-footer">
					<button type="button" class="button" id="cancel-rule"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
					<button type="button" class="button button-primary" id="save-rule"><?php _e( 'Save Rule', METS_TEXT_DOMAIN ); ?></button>
				</div>
			</div>
		</div>

		<style>
		.mets-bulk-operations-container {
			max-width: 1200px;
		}
		
		.mets-tab-content {
			display: none;
			padding: 20px 0;
		}
		
		.mets-tab-content.active {
			display: block;
		}
		
		.mets-bulk-section {
			background: #fff;
			padding: 20px;
			margin-bottom: 20px;
			border: 1px solid #ddd;
			border-radius: 5px;
		}
		
		.mets-bulk-filters {
			margin-bottom: 20px;
		}
		
		.filter-row {
			display: flex;
			align-items: center;
			gap: 15px;
			margin-bottom: 15px;
			flex-wrap: wrap;
		}
		
		.filter-row label {
			font-weight: bold;
			min-width: 80px;
		}
		
		.filter-row select,
		.filter-row input {
			min-width: 150px;
		}
		
		.mets-results-container {
			min-height: 200px;
			border: 1px solid #ddd;
			padding: 15px;
			background: #f9f9f9;
			border-radius: 3px;
		}
		
		.mets-results-table {
			width: 100%;
			border-collapse: collapse;
		}
		
		.mets-results-table th,
		.mets-results-table td {
			padding: 8px 12px;
			border: 1px solid #ddd;
			text-align: left;
		}
		
		.mets-results-table th {
			background: #f0f0f0;
			font-weight: bold;
		}
		
		.mets-results-table tr:nth-child(even) {
			background: #f9f9f9;
		}
		
		.mets-bulk-actions {
			margin-top: 20px;
			padding: 15px;
			background: #f0f0f0;
			border-radius: 3px;
		}
		
		.action-row {
			display: flex;
			align-items: center;
			gap: 15px;
			flex-wrap: wrap;
		}
		
		.mets-import-export-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 30px;
		}
		
		.form-group {
			margin-bottom: 15px;
		}
		
		.form-group label {
			display: block;
			font-weight: bold;
			margin-bottom: 5px;
		}
		
		.form-group input,
		.form-group select,
		.form-group textarea {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 3px;
		}
		
		.maintenance-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
		}
		
		.maintenance-card {
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 5px;
			background: #f9f9f9;
		}
		
		.maintenance-card h3 {
			margin-top: 0;
			margin-bottom: 10px;
		}
		
		.maintenance-card p {
			margin-bottom: 15px;
			color: #666;
		}
		
		.automation-controls {
			margin-bottom: 20px;
		}
		
		.automation-rule-template {
			border: 1px solid #ddd;
			border-radius: 5px;
			margin-bottom: 15px;
			background: #fff;
		}
		
		.rule-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 15px;
			background: #f0f0f0;
			border-bottom: 1px solid #ddd;
		}
		
		.rule-controls {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		
		.rule-details {
			padding: 15px;
		}
		
		.rule-stats {
			display: flex;
			gap: 20px;
			margin-top: 10px;
		}
		
		.stat-item {
			font-size: 12px;
			color: #666;
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
			max-width: 800px;
			max-height: 90vh;
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
		
		.mets-modal-footer {
			display: flex;
			justify-content: flex-end;
			gap: 10px;
			padding: 20px;
			background: #f1f1f1;
			border-top: 1px solid #ddd;
		}
		
		.mets-modal-close {
			background: none;
			border: none;
			font-size: 24px;
			cursor: pointer;
			color: #999;
		}
		
		.form-section {
			margin: 20px 0;
			padding: 15px;
			border: 1px solid #ddd;
			border-radius: 3px;
			background: #f9f9f9;
		}
		
		.form-section h4 {
			margin-top: 0;
			margin-bottom: 15px;
		}
		
		.health-check-results h4 {
			margin-top: 20px;
			margin-bottom: 10px;
			font-weight: bold;
		}
		
		.health-check-results ul {
			list-style: none;
			padding: 0;
		}
		
		.health-check-results li {
			padding: 8px 12px;
			margin-bottom: 5px;
			border-radius: 3px;
		}
		
		.health-check-results li.success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}
		
		.health-check-results li.warning {
			background: #fff3cd;
			color: #856404;
			border: 1px solid #ffeaa7;
		}
		
		.health-check-results li.error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}
		
		.success {
			color: #155724;
		}
		
		.error {
			color: #721c24;
		}
		
		.warning {
			color: #856404;
		}

		@media (max-width: 768px) {
			.mets-import-export-grid {
				grid-template-columns: 1fr;
			}
			
			.filter-row {
				flex-direction: column;
				align-items: flex-start;
			}
			
			.action-row {
				flex-direction: column;
				align-items: flex-start;
			}
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Tab switching
			$('.nav-tab').on('click', function(e) {
				e.preventDefault();
				var target = $(this).attr('href');
				
				$('.nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				
				$('.mets-tab-content').removeClass('active');
				$(target).addClass('active');
			});
			
			// Initialize bulk operations functionality
			initializeBulkOperations();
		});

		function initializeBulkOperations() {
			// Ticket operations
			$('#load-tickets').on('click', loadTickets);
			$('#ticket-bulk-action').on('change', showActionParameters);
			$('#execute-ticket-action').on('click', executeTicketAction);
			
			// Entity operations
			$('#load-entities').on('click', loadEntities);
			$('#entity-bulk-action').on('change', showEntityActionParameters);
			$('#execute-entity-action').on('click', executeEntityAction);
			
			// KB operations
			$('#load-kb-articles').on('click', loadKBArticles);
			$('#kb-bulk-action').on('change', showKBActionParameters);
			$('#execute-kb-action').on('click', executeKBAction);
			
			// Import/Export
			$('#export-form').on('submit', exportData);
			$('#import-form').on('submit', importData);
			
			// Automation
			$('#add-automation-rule').on('click', showAutomationRuleModal);
			$('#save-rule').on('click', saveAutomationRule);
			$('#cancel-rule').on('click', function() { $('#automation-rule-modal').hide(); });
			$('.mets-modal-close').on('click', function() { $('#automation-rule-modal').hide(); });
			
			// Maintenance
			$('#cleanup-database').on('click', cleanupDatabase);
			$('#archive-old-tickets').on('click', archiveOldTickets);
			$('#regenerate-stats').on('click', regenerateStats);
			$('#clear-cache').on('click', clearCache);
			$('#export-logs').on('click', exportLogs);
			$('#health-check').on('click', runHealthCheck);
			
			// Load initial automation rules
			loadAutomationRules();
		}

		function loadTickets() {
			var filters = {
				status: $('#ticket-status-filter').val(),
				priority: $('#ticket-priority-filter').val(),
				entity_id: $('#ticket-entity-filter').val(),
				date_from: $('#ticket-date-from').val(),
				date_to: $('#ticket-date-to').val(),
				assigned_to: $('#ticket-assigned-filter').val()
			};
			
			$.post(ajaxurl, {
				action: 'mets_load_bulk_tickets',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				filters: filters
			}, function(response) {
				if (response.success) {
					displayTicketResults(response.data);
					$('.mets-bulk-actions').show();
				} else {
					alert('Error loading tickets: ' + response.data);
				}
			});
		}

		function displayTicketResults(tickets) {
			var html = '<table class="mets-results-table">';
			html += '<thead><tr>';
			html += '<th><input type="checkbox" id="select-all-tickets"></th>';
			html += '<th>ID</th><th>Subject</th><th>Status</th><th>Priority</th><th>Entity</th><th>Created</th>';
			html += '</tr></thead><tbody>';
			
			$.each(tickets, function(i, ticket) {
				html += '<tr>';
				html += '<td><input type="checkbox" class="ticket-checkbox" value="' + ticket.id + '"></td>';
				html += '<td>#' + ticket.id + '</td>';
				html += '<td>' + ticket.subject + '</td>';
				html += '<td>' + ticket.status + '</td>';
				html += '<td>' + ticket.priority + '</td>';
				html += '<td>' + (ticket.entity_name || '') + '</td>';
				html += '<td>' + ticket.created_at + '</td>';
				html += '</tr>';
			});
			
			html += '</tbody></table>';
			$('#ticket-results').html(html);
			
			// Select all functionality
			$('#select-all-tickets').on('change', function() {
				$('.ticket-checkbox').prop('checked', $(this).prop('checked'));
			});
		}

		function showActionParameters() {
			var action = $('#ticket-bulk-action').val();
			var html = '';
			
			switch(action) {
				case 'update_status':
					html = '<select id="new-status"><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option><option value="on_hold">On Hold</option></select>';
					break;
				case 'update_priority':
					html = '<select id="new-priority"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select>';
					break;
				case 'assign_agent':
					html = '<select id="agent-id"><?php 
					$agents = get_users( array( "capability" => "manage_tickets" ) );
					foreach ( $agents as $agent ) {
						echo "<option value='" . $agent->ID . "'>" . esc_html( $agent->display_name ) . "</option>";
					}
					?></select>';
					break;
				case 'add_reply':
					html = '<textarea id="reply-content" placeholder="Reply content..." rows="3" style="width: 300px;"></textarea>';
					html += '<label><input type="checkbox" id="is-internal"> Internal note</label>';
					break;
			}
			
			$('#action-parameters').html(html).toggle(html !== '');
		}

		function executeTicketAction() {
			var action = $('#ticket-bulk-action').val();
			var ticketIds = $('.ticket-checkbox:checked').map(function() {
				return $(this).val();
			}).get();
			
			if (!action || ticketIds.length === 0) {
				alert('Please select an action and at least one ticket.');
				return;
			}
			
			var actionData = {
				action_type: action,
				ticket_ids: ticketIds
			};
			
			// Add action-specific parameters
			switch(action) {
				case 'update_status':
					actionData.new_status = $('#new-status').val();
					break;
				case 'update_priority':
					actionData.new_priority = $('#new-priority').val();
					break;
				case 'assign_agent':
					actionData.agent_id = $('#agent-id').val();
					break;
				case 'add_reply':
					actionData.reply_content = $('#reply-content').val();
					actionData.is_internal = $('#is-internal').prop('checked');
					break;
			}
			
			$.post(ajaxurl, {
				action: 'mets_bulk_ticket_action',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				...actionData
			}, function(response) {
				if (response.success) {
					alert(response.data.message);
					loadTickets(); // Reload tickets
				} else {
					alert('Error: ' + response.data);
				}
			});
		}

		function displayEntityResults(entities) {
			var html = '<table class="mets-results-table">';
			html += '<thead><tr>';
			html += '<th><input type="checkbox" id="select-all-entities"></th>';
			html += '<th>ID</th><th>Name</th><th>Status</th><th>Parent</th><th>Created</th>';
			html += '</tr></thead><tbody>';
			
			$.each(entities, function(i, entity) {
				html += '<tr>';
				html += '<td><input type="checkbox" class="entity-checkbox" value="' + entity.id + '"></td>';
				html += '<td>#' + entity.id + '</td>';
				html += '<td>' + entity.name + '</td>';
				html += '<td>' + entity.status + '</td>';
				html += '<td>' + (entity.parent_name || '-') + '</td>';
				html += '<td>' + entity.created_at + '</td>';
				html += '</tr>';
			});
			
			html += '</tbody></table>';
			$('#entity-results').html(html);
			
			// Select all functionality
			$('#select-all-entities').on('change', function() {
				$('.entity-checkbox').prop('checked', $(this).prop('checked'));
			});
		}

		function displayKBResults(articles) {
			var html = '<table class="mets-results-table">';
			html += '<thead><tr>';
			html += '<th><input type="checkbox" id="select-all-kb"></th>';
			html += '<th>ID</th><th>Title</th><th>Status</th><th>Entity</th><th>Author</th><th>Created</th>';
			html += '</tr></thead><tbody>';
			
			$.each(articles, function(i, article) {
				html += '<tr>';
				html += '<td><input type="checkbox" class="kb-checkbox" value="' + article.id + '"></td>';
				html += '<td>#' + article.id + '</td>';
				html += '<td>' + article.title + '</td>';
				html += '<td>' + article.status + '</td>';
				html += '<td>' + (article.entity_name || 'Global') + '</td>';
				html += '<td>' + article.author_name + '</td>';
				html += '<td>' + article.created_at + '</td>';
				html += '</tr>';
			});
			
			html += '</tbody></table>';
			$('#kb-results').html(html);
			
			// Select all functionality
			$('#select-all-kb').on('change', function() {
				$('.kb-checkbox').prop('checked', $(this).prop('checked'));
			});
		}

		function showEntityActionParameters() {
			var action = $('#entity-bulk-action').val();
			var html = '';
			
			switch(action) {
				case 'update_status':
					html = '<select id="entity-new-status"><option value="active">Active</option><option value="inactive">Inactive</option></select>';
					break;
				case 'update_parent':
					html = '<select id="entity-new-parent"><?php 
					// Load parent entities
					require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
					$entity_model = new METS_Entity_Model();
					$parent_entities = $entity_model->get_all( array( 'parent_id' => null ) );
					echo '<option value="">No Parent</option>';
					foreach ( $parent_entities as $entity ) {
						echo '<option value="' . $entity->id . '">' . esc_html( $entity->name ) . '</option>';
					}
					?></select>';
					break;
			}
			
			$('#entity-action-parameters').html(html).toggle(html !== '');
		}

		function executeEntityAction() {
			var action = $('#entity-bulk-action').val();
			var entityIds = $('.entity-checkbox:checked').map(function() {
				return $(this).val();
			}).get();
			
			if (!action || entityIds.length === 0) {
				alert('Please select an action and at least one entity.');
				return;
			}
			
			var actionData = {
				action_type: action,
				entity_ids: entityIds
			};
			
			// Add action-specific parameters
			switch(action) {
				case 'update_status':
					actionData.new_status = $('#entity-new-status').val();
					break;
				case 'update_parent':
					actionData.new_parent = $('#entity-new-parent').val();
					break;
			}
			
			$.post(ajaxurl, {
				action: 'mets_bulk_entity_action',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				...actionData
			}, function(response) {
				if (response.success) {
					alert(response.data.message);
					loadEntities(); // Reload entities
				} else {
					alert('Error: ' + response.data);
				}
			});
		}

		function showKBActionParameters() {
			var action = $('#kb-bulk-action').val();
			var html = '';
			
			switch(action) {
				case 'update_status':
					html = '<select id="kb-new-status"><option value="draft">Draft</option><option value="pending_review">Pending Review</option><option value="approved">Approved</option><option value="published">Published</option><option value="archived">Archived</option></select>';
					break;
				case 'update_visibility':
					html = '<select id="kb-new-visibility"><option value="internal">Internal</option><option value="staff">Staff</option><option value="customer">Customer</option></select>';
					break;
				case 'assign_category':
					html = '<select id="kb-new-category"><?php 
					// Load KB categories
					global $wpdb;
					$categories = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}mets_kb_categories ORDER BY name" );
					foreach ( $categories as $category ) {
						echo '<option value="' . $category->id . '">' . esc_html( $category->name ) . '</option>';
					}
					?></select>';
					break;
			}
			
			$('#kb-action-parameters').html(html).toggle(html !== '');
		}

		function executeKBAction() {
			var action = $('#kb-bulk-action').val();
			var articleIds = $('.kb-checkbox:checked').map(function() {
				return $(this).val();
			}).get();
			
			if (!action || articleIds.length === 0) {
				alert('Please select an action and at least one KB article.');
				return;
			}
			
			var actionData = {
				action_type: action,
				article_ids: articleIds
			};
			
			// Add action-specific parameters
			switch(action) {
				case 'update_status':
					actionData.new_status = $('#kb-new-status').val();
					break;
				case 'update_visibility':
					actionData.new_visibility = $('#kb-new-visibility').val();
					break;
				case 'assign_category':
					actionData.category_id = $('#kb-new-category').val();
					break;
			}
			
			$.post(ajaxurl, {
				action: 'mets_bulk_kb_action',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				...actionData
			}, function(response) {
				if (response.success) {
					alert(response.data.message);
					loadKBArticles(); // Reload KB articles
				} else {
					alert('Error: ' + response.data);
				}
			});
		}

		function loadAutomationRules() {
			$.post(ajaxurl, {
				action: 'mets_load_automation_rules',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>'
			}, function(response) {
				if (response.success) {
					displayAutomationRules(response.data.rules);
				}
			});
		}

		function displayAutomationRules(rules) {
			var html = '';
			$.each(rules, function(i, rule) {
				var ruleHtml = $('.automation-rule-template').html();
				ruleHtml = ruleHtml.replace('{{id}}', rule.id);
				ruleHtml = ruleHtml.replace('{{name}}', rule.name);
				ruleHtml = ruleHtml.replace('{{description}}', rule.description);
				ruleHtml = ruleHtml.replace('{{trigger}}', rule.trigger);
				ruleHtml = ruleHtml.replace('{{conditions}}', rule.conditions ? rule.conditions.length : 0);
				ruleHtml = ruleHtml.replace('{{actions}}', rule.actions ? rule.actions.length : 0);
				html += '<div class="automation-rule" data-rule-id="' + rule.id + '">' + ruleHtml + '</div>';
			});
			$('#automation-rules-list').html(html);
		}

		function loadEntities() {
			const filters = {
				status: $('#entity-status-filter').val(),
				search: $('#entity-search').val(),
				parent_id: $('#entity-parent-filter').val()
			};

			$('#entity-results').html('<p>Loading entities...</p>');

			$.post(ajaxurl, {
				action: 'mets_load_bulk_entities',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				filters: filters
			}, function(response) {
				if (response.success) {
					displayEntityResults(response.data.entities);
					$('.mets-bulk-actions').show();
				} else {
					$('#entity-results').html('<p class="error">Error loading entities: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#entity-results').html('<p class="error">Failed to load entities. Please try again.</p>');
			});
		}

		function loadKBArticles() {
			const filters = {
				status: $('#kb-status-filter').val(),
				search: $('#kb-search').val(),
				entity_id: $('#kb-entity-filter').val(),
				category_id: $('#kb-category-filter').val(),
				date_from: $('#kb-date-from').val(),
				date_to: $('#kb-date-to').val()
			};

			$('#kb-results').html('<p>Loading KB articles...</p>');

			$.post(ajaxurl, {
				action: 'mets_load_bulk_kb_articles',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				filters: filters
			}, function(response) {
				if (response.success) {
					displayKBResults(response.data.articles);
					$('.mets-bulk-actions').show();
				} else {
					$('#kb-results').html('<p class="error">Error loading KB articles: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#kb-results').html('<p class="error">Failed to load KB articles. Please try again.</p>');
			});
		}

		function exportData(e) {
			e.preventDefault();
			
			const exportType = $('#export-type').val();
			const exportFormat = $('#export-format').val();
			const dateFrom = $('#export-date-from').val();
			const dateTo = $('#export-date-to').val();
			const entityId = $('#export-entity-filter').val();

			if (!exportType) {
				alert('Please select data type to export.');
				return;
			}

			$('#export-results').html('<p>Preparing export...</p>');

			$.post(ajaxurl, {
				action: 'mets_export_data',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				export_type: exportType,
				format: exportFormat,
				date_from: dateFrom,
				date_to: dateTo,
				entity_id: entityId
			}, function(response) {
				if (response.success) {
					const downloadLink = '<a href="' + response.data.download_url + '" class="button button-primary" download>Download Export File</a>';
					$('#export-results').html('<p class="success">Export completed successfully!</p>' + downloadLink);
				} else {
					$('#export-results').html('<p class="error">Export failed: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#export-results').html('<p class="error">Export failed. Please try again.</p>');
			});
		}

		function importData(e) {
			e.preventDefault();
			
			const formData = new FormData();
			const fileInput = $('#import-file')[0];
			const importType = $('#import-type').val();
			const updateExisting = $('#import-update-existing').prop('checked');

			if (!fileInput.files[0]) {
				alert('Please select a file to import.');
				return;
			}

			if (!importType) {
				alert('Please select import type.');
				return;
			}

			formData.append('action', 'mets_import_data');
			formData.append('nonce', '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>');
			formData.append('import_file', fileInput.files[0]);
			formData.append('import_type', importType);
			formData.append('update_existing', updateExisting ? '1' : '0');

			$('#import-results').html('<p>Processing import...</p>');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						let resultsHtml = '<p class="success">Import completed successfully!</p>';
						if (response.data.imported) {
							resultsHtml += '<p>Imported: ' + response.data.imported + ' records</p>';
						}
						if (response.data.updated) {
							resultsHtml += '<p>Updated: ' + response.data.updated + ' records</p>';
						}
						if (response.data.errors && response.data.errors.length > 0) {
							resultsHtml += '<p class="error">Errors: ' + response.data.errors.join(', ') + '</p>';
						}
						$('#import-results').html(resultsHtml);
					} else {
						$('#import-results').html('<p class="error">Import failed: ' + response.data + '</p>');
					}
				},
				error: function() {
					$('#import-results').html('<p class="error">Import failed. Please try again.</p>');
				}
			});
		}

		function showAutomationRuleModal() {
			$('#automation-rule-modal').show();
			$('#rule-id').val('');
			$('#automation-rule-form')[0].reset();
			loadAutomationRules();
		}

		function saveAutomationRule() {
			const ruleData = {
				id: $('#rule-id').val(),
				name: $('#rule-name').val(),
				description: $('#rule-description').val(),
				trigger: $('#rule-trigger').val(),
				schedule: $('#rule-schedule').val(),
				active: $('#rule-active-checkbox').prop('checked'),
				conditions: [], // Would collect from dynamic conditions
				actions: [] // Would collect from dynamic actions
			};

			if (!ruleData.name) {
				alert('Please enter a rule name.');
				return;
			}

			$.post(ajaxurl, {
				action: 'mets_save_automation_rule',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				rule_data: ruleData
			}, function(response) {
				if (response.success) {
					$('#automation-rule-modal').hide();
					loadAutomationRules();
					alert('Automation rule saved successfully!');
				} else {
					alert('Error saving rule: ' + response.data);
				}
			});
		}

		function cleanupDatabase() {
			if (!confirm('This will clean up orphaned records and optimize database tables. Continue?')) {
				return;
			}

			$('#cleanup-results').html('<p>Running database cleanup...</p>');

			$.post(ajaxurl, {
				action: 'mets_cleanup_database',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>'
			}, function(response) {
				if (response.success) {
					let resultsHtml = '<p class="success">Database cleanup completed!</p>';
					if (response.data.orphaned_removed) {
						resultsHtml += '<p>Orphaned records removed: ' + response.data.orphaned_removed + '</p>';
					}
					if (response.data.tables_optimized) {
						resultsHtml += '<p>Tables optimized: ' + response.data.tables_optimized + '</p>';
					}
					$('#cleanup-results').html(resultsHtml);
				} else {
					$('#cleanup-results').html('<p class="error">Cleanup failed: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#cleanup-results').html('<p class="error">Cleanup failed. Please try again.</p>');
			});
		}

		function archiveOldTickets() {
			const days = $('#archive-days').val();
			
			if (!days || days < 1) {
				alert('Please enter a valid number of days.');
				return;
			}

			if (!confirm('This will archive all closed tickets older than ' + days + ' days. Continue?')) {
				return;
			}

			$('#archive-results').html('<p>Archiving old tickets...</p>');

			$.post(ajaxurl, {
				action: 'mets_archive_old_tickets',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				days: days
			}, function(response) {
				if (response.success) {
					$('#archive-results').html('<p class="success">Archived ' + response.data.archived_count + ' tickets.</p>');
				} else {
					$('#archive-results').html('<p class="error">Archive failed: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#archive-results').html('<p class="error">Archive failed. Please try again.</p>');
			});
		}

		function regenerateStats() {
			if (!confirm('This will recalculate all system statistics. This may take several minutes. Continue?')) {
				return;
			}

			$('#stats-results').html('<p>Regenerating statistics...</p>');

			$.post(ajaxurl, {
				action: 'mets_regenerate_stats',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>'
			}, function(response) {
				if (response.success) {
					let resultsHtml = '<p class="success">Statistics regenerated successfully!</p>';
					if (response.data.stats_updated) {
						resultsHtml += '<p>Statistics updated: ' + response.data.stats_updated + '</p>';
					}
					if (response.data.cache_cleared) {
						resultsHtml += '<p>Cache entries cleared: ' + response.data.cache_cleared + '</p>';
					}
					$('#stats-results').html(resultsHtml);
				} else {
					$('#stats-results').html('<p class="error">Regeneration failed: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#stats-results').html('<p class="error">Regeneration failed. Please try again.</p>');
			});
		}

		function clearCache() {
			if (!confirm('This will clear all system caches. Continue?')) {
				return;
			}

			$('#cache-results').html('<p>Clearing cache...</p>');

			$.post(ajaxurl, {
				action: 'mets_clear_cache',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>'
			}, function(response) {
				if (response.success) {
					$('#cache-results').html('<p class="success">Cache cleared successfully! Cleared ' + response.data.cache_entries + ' entries.</p>');
				} else {
					$('#cache-results').html('<p class="error">Cache clear failed: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#cache-results').html('<p class="error">Cache clear failed. Please try again.</p>');
			});
		}

		function exportLogs() {
			const days = $('#log-days').val();
			
			if (!days || days < 1) {
				alert('Please enter a valid number of days.');
				return;
			}

			$('#log-results').html('<p>Preparing log export...</p>');

			$.post(ajaxurl, {
				action: 'mets_export_logs',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>',
				days: days
			}, function(response) {
				if (response.success) {
					const downloadLink = '<a href="' + response.data.download_url + '" class="button button-primary" download>Download Log File</a>';
					$('#log-results').html('<p class="success">Log export completed!</p>' + downloadLink);
				} else {
					$('#log-results').html('<p class="error">Log export failed: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#log-results').html('<p class="error">Log export failed. Please try again.</p>');
			});
		}

		function runHealthCheck() {
			$('#health-results').html('<p>Running system health check...</p>');

			$.post(ajaxurl, {
				action: 'mets_health_check',
				nonce: '<?php echo wp_create_nonce( 'mets_bulk_action' ); ?>'
			}, function(response) {
				if (response.success) {
					let resultsHtml = '<div class="health-check-results">';
					
					$.each(response.data.checks, function(category, checks) {
						resultsHtml += '<h4>' + category + '</h4><ul>';
						$.each(checks, function(i, check) {
							const statusClass = check.status === 'pass' ? 'success' : (check.status === 'warning' ? 'warning' : 'error');
							resultsHtml += '<li class="' + statusClass + '">' + check.name + ': ' + check.message + '</li>';
						});
						resultsHtml += '</ul>';
					});
					
					resultsHtml += '</div>';
					$('#health-results').html(resultsHtml);
				} else {
					$('#health-results').html('<p class="error">Health check failed: ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#health-results').html('<p class="error">Health check failed. Please try again.</p>');
			});
		}
		</script>
		<?php
	}
}