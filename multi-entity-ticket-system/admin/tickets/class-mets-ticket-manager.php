<?php
/**
 * Ticket manager class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/tickets
 * @since      1.0.0
 */

/**
 * Ticket manager class.
 *
 * This class handles the admin interface for managing tickets
 * including add/edit forms and ticket detail views.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/tickets
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Ticket_Manager {

	/**
	 * Ticket model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Ticket_Model    $ticket_model    Ticket model instance.
	 */
	private $ticket_model;

	/**
	 * Entity model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Entity_Model    $entity_model    Entity model instance.
	 */
	private $entity_model;

	/**
	 * Reply model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Ticket_Reply_Model    $reply_model    Reply model instance.
	 */
	private $reply_model;

	/**
	 * Attachment model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Attachment_Model    $attachment_model    Attachment model instance.
	 */
	private $attachment_model;

	/**
	 * KB Ticket Link model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_KB_Ticket_Link_Model    $kb_link_model    KB ticket link model instance.
	 */
	private $kb_link_model;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-attachment-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-ticket-link-model.php';
		
		$this->ticket_model = new METS_Ticket_Model();
		$this->entity_model = new METS_Entity_Model();
		$this->reply_model = new METS_Ticket_Reply_Model();
		$this->attachment_model = new METS_Attachment_Model();
		$this->kb_link_model = new METS_KB_Ticket_Link_Model();
	}

	/**
	 * Display add ticket page
	 *
	 * @since    1.0.0
	 */
	public function display_add_page() {
		// Check user capabilities
		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		$this->display_form( null );
	}

	/**
	 * Display edit ticket page
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 */
	public function display_edit_page( $ticket_id ) {
		// Check user capabilities
		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		$ticket = $this->ticket_model->get( $ticket_id );
		if ( ! $ticket ) {
			wp_die( __( 'Ticket not found.', METS_TEXT_DOMAIN ) );
		}

		// Check entity access
		if ( ! $this->user_can_access_entity( $ticket->entity_id ) ) {
			wp_die( __( 'You do not have access to this ticket.', METS_TEXT_DOMAIN ) );
		}

		$this->display_form( $ticket );
	}

	/**
	 * Display ticket form
	 *
	 * @since    1.0.0
	 * @param    object|null    $ticket    Ticket object for editing, null for adding
	 */
	private function display_form( $ticket = null ) {
		$is_edit = ! empty( $ticket );
		$form_title = $is_edit ? sprintf( __( 'Edit Ticket: %s', METS_TEXT_DOMAIN ), $ticket->ticket_number ) : __( 'Add New Ticket', METS_TEXT_DOMAIN );

		// Get entities for dropdown
		$entities = $this->get_accessible_entities();
		
		// Get statuses, priorities, and categories
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$priorities = get_option( 'mets_ticket_priorities', array() );
		$categories = get_option( 'mets_ticket_categories', array() );
		
		// Fallback if options are empty
		if ( empty( $statuses ) ) {
			$statuses = array(
				'new' => array( 'label' => __( 'New', METS_TEXT_DOMAIN ), 'color' => '#007cba' ),
				'open' => array( 'label' => __( 'Open', METS_TEXT_DOMAIN ), 'color' => '#00a32a' ),
				'closed' => array( 'label' => __( 'Closed', METS_TEXT_DOMAIN ), 'color' => '#787c82' ),
			);
		}
		
		if ( empty( $priorities ) ) {
			$priorities = array(
				'low' => array( 'label' => __( 'Low', METS_TEXT_DOMAIN ), 'color' => '#00a32a', 'order' => 1 ),
				'normal' => array( 'label' => __( 'Normal', METS_TEXT_DOMAIN ), 'color' => '#007cba', 'order' => 2 ),
				'high' => array( 'label' => __( 'High', METS_TEXT_DOMAIN ), 'color' => '#f0b849', 'order' => 3 ),
			);
		}

		// Sort priorities by order
		if ( ! empty( $priorities ) ) {
			uasort( $priorities, function( $a, $b) {
				return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
			});
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $form_title ); ?></h1>

			<?php if ( $is_edit ) : ?>
				<div class="mets-ticket-header" style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-left: 4px solid #007cba;">
					<div style="display: flex; justify-content: space-between; align-items: center;">
						<div>
							<strong><?php echo esc_html( $ticket->ticket_number ); ?></strong> - 
							<span><?php echo esc_html( $ticket->subject ); ?></span>
						</div>
						<div>
							<?php _e( 'Created:', METS_TEXT_DOMAIN ); ?> 
							<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<!-- Main content -->
					<div id="post-body-content">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" id="ticket-form" enctype="multipart/form-data">
							<input type="hidden" name="page" value="<?php echo $is_edit ? 'mets-tickets' : 'mets-add-ticket'; ?>">
							<?php wp_nonce_field( $is_edit ? 'update_ticket' : 'create_ticket', 'ticket_nonce' ); ?>
							<?php if ( $is_edit ) : ?>
								<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
								<input type="hidden" name="action" value="update">
							<?php else : ?>
								<input type="hidden" name="action" value="create">
							<?php endif; ?>

							<div class="postbox">
								<h3 class="hndle"><span><?php _e( 'Ticket Details', METS_TEXT_DOMAIN ); ?></span></h3>
								<div class="inside">
									<table class="form-table">
										<tbody>
											<tr>
												<th scope="row">
													<label for="ticket_entity"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
												</th>
												<td>
													<select name="ticket_entity" id="ticket_entity" class="regular-text" required>
														<option value=""><?php _e( 'Select Entity', METS_TEXT_DOMAIN ); ?></option>
														<?php foreach ( $entities as $entity ) : ?>
															<?php $prefix = ! empty( $entity->parent_id ) ? '— ' : ''; ?>
															<option value="<?php echo esc_attr( $entity->id ); ?>" 
																	<?php echo ( $is_edit && $ticket->entity_id == $entity->id ) ? 'selected' : ''; ?>>
																<?php echo $prefix . esc_html( $entity->name ); ?>
															</option>
														<?php endforeach; ?>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="ticket_subject"><?php _e( 'Subject', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
												</th>
												<td>
													<input name="ticket_subject" type="text" id="ticket_subject" 
														   value="<?php echo $is_edit ? esc_attr( $ticket->subject ) : ''; ?>" 
														   class="large-text" required>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="ticket_description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
												</th>
												<td>
													<textarea name="ticket_description" id="ticket_description" 
															  rows="8" cols="50" class="large-text" required 
															  style="width: 100%; background: white !important; color: #333 !important; border: 1px solid #8c8f94; padding: 8px; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif; font-size: 13px; line-height: 1.4; z-index: 1; position: relative;"><?php echo $is_edit ? esc_textarea( $ticket->description ) : ''; ?></textarea>
													<p class="description"><?php _e( 'Provide a detailed description of the issue or request.', METS_TEXT_DOMAIN ); ?></p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="ticket_attachments"><?php _e( 'Attachments', METS_TEXT_DOMAIN ); ?></label>
												</th>
												<td>
													<div class="file-upload-section">
														<input type="file" name="ticket_attachments[]" id="ticket_attachments" class="file-upload-input" multiple 
															   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
														<p class="description">
															<?php _e( 'You can upload multiple files. Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX, TXT, ZIP. Maximum file size: 20MB each.', METS_TEXT_DOMAIN ); ?>
														</p>
														<div id="file-preview"></div>
													</div>
												</td>
											</tr>
											<?php if ( $is_edit && $ticket ) : ?>
											<tr>
												<th scope="row">
													<?php _e( 'Original Attachments', METS_TEXT_DOMAIN ); ?>
												</th>
												<td>
													<?php echo $this->display_admin_attachments( $ticket->id ); ?>
												</td>
											</tr>
											<?php endif; ?>
										</tbody>
									</table>
								</div>
							</div>

							<div class="postbox">
								<h3 class="hndle"><span><?php _e( 'Customer Information', METS_TEXT_DOMAIN ); ?></span></h3>
								<div class="inside">
									<table class="form-table">
										<tbody>
											<tr>
												<th scope="row">
													<label for="customer_name"><?php _e( 'Customer Name', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
												</th>
												<td>
													<input name="customer_name" type="text" id="customer_name" 
														   value="<?php echo $is_edit ? esc_attr( $ticket->customer_name ) : ''; ?>" 
														   class="regular-text" required>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="customer_email"><?php _e( 'Customer Email', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
												</th>
												<td>
													<input name="customer_email" type="email" id="customer_email" 
														   value="<?php echo $is_edit ? esc_attr( $ticket->customer_email ) : ''; ?>" 
														   class="regular-text" required>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="customer_phone"><?php _e( 'Customer Phone', METS_TEXT_DOMAIN ); ?></label>
												</th>
												<td>
													<input name="customer_phone" type="tel" id="customer_phone" 
														   value="<?php echo $is_edit ? esc_attr( $ticket->customer_phone ) : ''; ?>" 
														   class="regular-text">
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>

							<p class="submit">
								<?php submit_button( $is_edit ? __( 'Update Ticket Details', METS_TEXT_DOMAIN ) : __( 'Create Ticket', METS_TEXT_DOMAIN ), 'primary', 'submit_ticket', false, array( 'form' => 'ticket-form' ) ); ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-tickets' ) ); ?>" class="button button-secondary">
									<?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?>
								</a>
							</p>
						</form>
						
						<?php if ( $is_edit ) : ?>
							<?php $this->display_ticket_replies( $ticket ); ?>
						<?php endif; ?>
					</div>

					<!-- Sidebar -->
					<div id="postbox-container-1" class="postbox-container">
						<div class="postbox">
							<h3 class="hndle"><span><?php _e( 'Ticket Properties', METS_TEXT_DOMAIN ); ?></span></h3>
							<div class="inside">
								<p>
									<label for="sidebar_ticket_status"><strong><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></strong></label><br>
									<select name="sidebar_ticket_status" id="sidebar_ticket_status" style="width: 100%;">
										<?php foreach ( $statuses as $status_key => $status_data ) : ?>
											<option value="<?php echo esc_attr( $status_key ); ?>" 
													<?php echo ( $is_edit && $ticket->status === $status_key ) || ( ! $is_edit && $status_key === 'new' ) ? 'selected' : ''; ?>>
												<?php echo esc_html( $status_data['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
								
								<p>
									<label for="sidebar_ticket_priority"><strong><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></strong></label><br>
									<select name="sidebar_ticket_priority" id="sidebar_ticket_priority" style="width: 100%;">
										<?php foreach ( $priorities as $priority_key => $priority_data ) : ?>
											<option value="<?php echo esc_attr( $priority_key ); ?>" 
													<?php echo ( $is_edit && $ticket->priority === $priority_key ) || ( ! $is_edit && $priority_key === 'normal' ) ? 'selected' : ''; ?>>
												<?php echo esc_html( $priority_data['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>

								<?php if ( ! empty( $categories ) ) : ?>
									<p>
										<label for="sidebar_ticket_category"><strong><?php _e( 'Category', METS_TEXT_DOMAIN ); ?></strong></label><br>
										<select name="sidebar_ticket_category" id="sidebar_ticket_category" style="width: 100%;">
											<option value=""><?php _e( 'Select Category', METS_TEXT_DOMAIN ); ?></option>
											<?php foreach ( $categories as $category_key => $category_label ) : ?>
												<option value="<?php echo esc_attr( $category_key ); ?>" 
														<?php echo ( $is_edit && $ticket->category === $category_key ) ? 'selected' : ''; ?>>
													<?php echo esc_html( $category_label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</p>
								<?php endif; ?>

								<p>
									<label for="sidebar_assigned_to"><strong><?php _e( 'Assign To', METS_TEXT_DOMAIN ); ?></strong></label><br>
									<select name="sidebar_assigned_to" id="sidebar_assigned_to" style="width: 100%;">
										<option value=""><?php _e( 'Unassigned', METS_TEXT_DOMAIN ); ?></option>
										<?php 
										// Get agents based on whether we're editing or adding
										$entity_id = $is_edit ? $ticket->entity_id : null;
										$agents = array();
										
										if ( $entity_id ) {
											$agents = $this->ticket_model->get_available_agents( $entity_id );
										} else {
											// For new tickets, show all potential agents  
											$agents = get_users( array(
												'meta_query' => array(
													array(
														'key'     => 'wp_capabilities',
														'value'   => 'ticket',
														'compare' => 'LIKE',
													),
												),
											) );
										}
										
										foreach ( $agents as $agent ) : ?>
											<option value="<?php echo esc_attr( $agent->ID ); ?>" 
													<?php echo ( $is_edit && $ticket->assigned_to == $agent->ID ) ? 'selected' : ''; ?>>
												<?php echo esc_html( $agent->display_name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
								
								<?php if ( $is_edit ) : ?>
									<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
										<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" id="properties-form" style="margin: 0;">
											<input type="hidden" name="page" value="mets-tickets">
											<?php wp_nonce_field( 'update_properties', 'properties_nonce' ); ?>
											<input type="hidden" name="action" value="update_properties">
											<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
											<input type="hidden" name="ticket_status" id="properties_ticket_status" value="<?php echo esc_attr( $ticket->status ); ?>">
											<input type="hidden" name="ticket_priority" id="properties_ticket_priority" value="<?php echo esc_attr( $ticket->priority ); ?>">
											<input type="hidden" name="ticket_category" id="properties_ticket_category" value="<?php echo esc_attr( $ticket->category ); ?>">
											<input type="hidden" name="assigned_to" id="properties_assigned_to" value="<?php echo $ticket->assigned_to ? esc_attr( $ticket->assigned_to ) : ''; ?>">
											
											<?php submit_button( __( 'Save Properties', METS_TEXT_DOMAIN ), 'primary', 'save_properties', false, array( 'style' => 'width: 100%;' ) ); ?>
										</form>
									</div>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( $is_edit ) : ?>
							<div class="postbox">
								<h3 class="hndle"><span><?php _e( 'Customer Information', METS_TEXT_DOMAIN ); ?></span></h3>
								<div class="inside">
									<p><strong><?php _e( 'Name:', METS_TEXT_DOMAIN ); ?></strong><br><?php echo esc_html( $ticket->customer_name ); ?></p>
									<p><strong><?php _e( 'Email:', METS_TEXT_DOMAIN ); ?></strong><br><a href="mailto:<?php echo esc_attr( $ticket->customer_email ); ?>"><?php echo esc_html( $ticket->customer_email ); ?></a></p>
									<?php if ( ! empty( $ticket->customer_phone ) ) : ?>
										<p><strong><?php _e( 'Phone:', METS_TEXT_DOMAIN ); ?></strong><br><a href="tel:<?php echo esc_attr( $ticket->customer_phone ); ?>"><?php echo esc_html( $ticket->customer_phone ); ?></a></p>
									<?php endif; ?>
								</div>
							</div>

							<div class="postbox">
								<h3 class="hndle"><span><?php _e( 'Ticket Information', METS_TEXT_DOMAIN ); ?></span></h3>
								<div class="inside">
									<p><strong><?php _e( 'Ticket Number:', METS_TEXT_DOMAIN ); ?></strong><br><?php echo esc_html( $ticket->ticket_number ); ?></p>
									<p><strong><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></strong><br><?php echo esc_html( $ticket->entity_name ); ?></p>
									<p><strong><?php _e( 'Created:', METS_TEXT_DOMAIN ); ?></strong><br><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ); ?></p>
									<?php if ( ! empty( $ticket->updated_at ) && $ticket->updated_at !== $ticket->created_at ) : ?>
										<p><strong><?php _e( 'Last Updated:', METS_TEXT_DOMAIN ); ?></strong><br><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->updated_at ) ); ?></p>
									<?php endif; ?>
									<?php if ( ! empty( $ticket->created_by_name ) ) : ?>
										<p><strong><?php _e( 'Created By:', METS_TEXT_DOMAIN ); ?></strong><br><?php echo esc_html( $ticket->created_by_name ); ?></p>  
									<?php endif; ?>
								</div>
							</div>

							<?php if ( $is_edit ) : ?>
								<div class="postbox">
									<h3 class="hndle"><span><?php _e( 'KB Tools', METS_TEXT_DOMAIN ); ?></span></h3>
									<div class="inside">
										<div id="mets-kb-tools">
											<div class="mets-kb-search-section">
												<p><strong><?php _e( 'Search Articles:', METS_TEXT_DOMAIN ); ?></strong></p>
												<input type="text" id="kb-search-input" class="regular-text" placeholder="<?php esc_attr_e( 'Search knowledge base...', METS_TEXT_DOMAIN ); ?>">
												<button type="button" id="kb-search-btn" class="button"><?php _e( 'Search', METS_TEXT_DOMAIN ); ?></button>
											</div>
											
											<div id="kb-search-results" style="display: none;">
												<p><strong><?php _e( 'Search Results:', METS_TEXT_DOMAIN ); ?></strong></p>
												<div id="kb-results-list"></div>
											</div>
											
											<div class="mets-kb-quick-actions" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
												<p><strong><?php _e( 'Quick Actions:', METS_TEXT_DOMAIN ); ?></strong></p>
												<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article' ); ?>" class="button button-secondary" target="_blank">
													<?php _e( 'Create New Article', METS_TEXT_DOMAIN ); ?>
												</a>
												<br><br>
												<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles' ); ?>" class="button button-secondary" target="_blank">
													<?php _e( 'Browse All Articles', METS_TEXT_DOMAIN ); ?>
												</a>
											</div>
											
											<div class="mets-kb-suggested-articles" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
												<p><strong><?php _e( 'Suggested Articles:', METS_TEXT_DOMAIN ); ?></strong></p>
												<div id="kb-suggested-list">
													<p class="description"><?php _e( 'Articles related to this ticket will appear here...', METS_TEXT_DOMAIN ); ?></p>
												</div>
											</div>
											
											<div class="mets-kb-linked-articles" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
												<p><strong><?php _e( 'Linked Articles:', METS_TEXT_DOMAIN ); ?></strong></p>
												<div id="kb-linked-list">
													<?php echo $this->render_linked_articles( $ticket->id ); ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( $is_edit && ( current_user_can( 'mets_manager' ) || current_user_can( 'mets_admin' ) ) ) : ?>
								<div class="postbox">
									<h3 class="hndle"><span><?php _e( 'Ticket Relationships', METS_TEXT_DOMAIN ); ?></span></h3>
									<div class="inside">
										<div id="mets-ticket-relationships">
											<!-- Related Tickets Display -->
											<div class="mets-related-tickets-section" style="margin-bottom: 15px;">
												<p><strong><?php _e( 'Related Tickets:', METS_TEXT_DOMAIN ); ?></strong></p>
												<div id="related-tickets-list">
													<?php echo $this->render_related_tickets( $ticket->id ); ?>
												</div>
											</div>

											<!-- Relationship Actions -->
											<div class="mets-relationship-actions" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
												<p><strong><?php _e( 'Actions:', METS_TEXT_DOMAIN ); ?></strong></p>

												<button type="button" class="button button-secondary" id="btn-merge-ticket" style="width: 100%; margin-bottom: 8px;">
													<?php _e( 'Merge with Another Ticket', METS_TEXT_DOMAIN ); ?>
												</button>

												<button type="button" class="button button-secondary" id="btn-link-ticket" style="width: 100%; margin-bottom: 8px;">
													<?php _e( 'Link Related Ticket', METS_TEXT_DOMAIN ); ?>
												</button>

												<button type="button" class="button button-secondary" id="btn-mark-duplicate" style="width: 100%; margin-bottom: 8px;">
													<?php _e( 'Mark as Duplicate', METS_TEXT_DOMAIN ); ?>
												</button>
											</div>
										</div>
									</div>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var originalStatus = $('#sidebar_ticket_status').val();
			var workflowValidationEnabled = true;
			
			// Sync sidebar fields with properties form hidden fields on change
			function syncSidebarToProperties() {
				var status = $('#sidebar_ticket_status').val();
				var priority = $('#sidebar_ticket_priority').val();
				var category = $('#sidebar_ticket_category').val();
				var assigned = $('#sidebar_assigned_to').val();
				
				console.log('Syncing properties values:', {status: status, priority: priority, category: category, assigned: assigned});
				
				$('#properties_ticket_status').val(status);
				$('#properties_ticket_priority').val(priority);
				$('#properties_ticket_category').val(category);
				$('#properties_assigned_to').val(assigned);
				
				console.log('Properties form values after sync:', {
					status: $('#properties_ticket_status').val(),
					priority: $('#properties_ticket_priority').val(),
					category: $('#properties_ticket_category').val(),
					assigned: $('#properties_assigned_to').val()
				});
			}
			
			// Update status dropdown with allowed transitions
			function updateAllowedStatuses() {
				var currentStatus = originalStatus;
				var ticketData = {
					priority: $('#sidebar_ticket_priority').val(),
					category: $('#sidebar_ticket_category').val()
				};
				
				if (!workflowValidationEnabled) return;
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_get_allowed_transitions',
					from_status: currentStatus,
					ticket_data: ticketData,
					nonce: mets_admin_ajax.nonce
				}, function(response) {
					if (response.success) {
						var $statusSelect = $('#sidebar_ticket_status');
						var currentValue = $statusSelect.val();
						
						// Clear and repopulate status options
						$statusSelect.empty();
						
						$.each(response.data.status_options, function(i, status) {
							var selected = status.key === currentValue ? 'selected' : '';
							$statusSelect.append('<option value="' + status.key + '" ' + selected + '>' + status.label + '</option>');
						});
						
						// If current selection is not allowed, reset to original
						if (response.data.allowed_statuses.indexOf(currentValue) === -1) {
							$statusSelect.val(originalStatus);
							syncSidebarToProperties();
						}
					}
				}).fail(function() {
					console.error('Failed to load allowed status transitions');
				});
			}
			
			// Validate status change with workflow rules
			function validateStatusChange(fromStatus, toStatus, callback) {
				if (!workflowValidationEnabled) {
					callback(true);
					return;
				}
				
				var ticketData = {
					priority: $('#sidebar_ticket_priority').val(),
					category: $('#sidebar_ticket_category').val()
				};
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_check_workflow_transition',
					from_status: fromStatus,
					to_status: toStatus,
					ticket_data: ticketData,
					nonce: mets_admin_ajax.nonce
				}, function(response) {
					if (response.success) {
						if (response.data.requires_note) {
							// Show note requirement message
							var noteMessage = '<?php echo esc_js( __( 'This status change requires a note. Please add a reply explaining the change.', METS_TEXT_DOMAIN ) ); ?>';
							$('#workflow-note-reminder').remove();
							$('#properties-form').before('<div id="workflow-note-reminder" class="notice notice-info"><p>' + noteMessage + '</p></div>');
						} else {
							$('#workflow-note-reminder').remove();
						}
						callback(true);
					} else {
						alert(response.data.message || '<?php echo esc_js( __( 'This status change is not allowed.', METS_TEXT_DOMAIN ) ); ?>');
						callback(false);
					}
				}).fail(function() {
					console.error('Failed to validate workflow transition');
					callback(false);
				});
			}
			
			// Handle status change with validation
			$('#sidebar_ticket_status').on('change', function() {
				var newStatus = $(this).val();
				var previousStatus = originalStatus;
				var $statusSelect = $(this);
				
				if (newStatus === previousStatus) {
					syncSidebarToProperties();
					return;
				}
				
				validateStatusChange(previousStatus, newStatus, function(isValid) {
					if (isValid) {
						originalStatus = newStatus;
						syncSidebarToProperties();
					} else {
						// Revert to previous status
						$statusSelect.val(previousStatus);
						syncSidebarToProperties();
					}
				});
			});
			
			// Sync on other field changes and update allowed statuses
			$('#sidebar_ticket_priority, #sidebar_ticket_category').on('change', function() {
				syncSidebarToProperties();
				updateAllowedStatuses();
			});
			
			// Sync assignment changes
			$('#sidebar_assigned_to').on('change', syncSidebarToProperties);
			
			// Sync before properties form submission
			$('#properties-form').on('submit', function(e) {
				console.log('Properties form submission triggered');
				syncSidebarToProperties();
				
				// Check if all required fields have values
				var formData = $(this).serialize();
				console.log('Properties form data being submitted:', formData);
				
				// Don't prevent submission, just log for debugging
				return true;
			});
			
			// Initial sync for edit forms
			syncSidebarToProperties();
			
			// Initialize workflow validation on page load
			if ($('#sidebar_ticket_status').length) {
				updateAllowedStatuses();
			}

			// Update agents dropdown when entity changes
			$('#ticket_entity').on('change', function() {
				var entityId = $(this).val();
				var $assignSelect = $('#sidebar_assigned_to');
				
				if (entityId) {
					// Show loading state
					$assignSelect.prop('disabled', true);
					$assignSelect.empty().append('<option value=""><?php _e( 'Loading...', METS_TEXT_DOMAIN ); ?></option>');
					
					// AJAX call to get agents for this entity
					$.post(mets_admin_ajax.ajax_url, {
						action: 'mets_get_entity_agents',
						entity_id: entityId,
						nonce: mets_admin_ajax.nonce
					}, function(response) {
						$assignSelect.prop('disabled', false);
						
						if (response.success) {
							$assignSelect.empty().append('<option value=""><?php _e( 'Unassigned', METS_TEXT_DOMAIN ); ?></option>');
							
							if (response.data && response.data.length > 0) {
								$.each(response.data, function(i, agent) {
									$assignSelect.append('<option value="' + agent.ID + '">' + agent.display_name + '</option>');
								});
							}
						} else {
							$assignSelect.empty().append('<option value=""><?php _e( 'No agents available', METS_TEXT_DOMAIN ); ?></option>');
							console.error('Failed to load agents:', response.data || 'Unknown error');
						}
						
						// Sync after updating agents
						syncSidebarToProperties();
					}).fail(function() {
						$assignSelect.prop('disabled', false);
						$assignSelect.empty().append('<option value=""><?php _e( 'Error loading agents', METS_TEXT_DOMAIN ); ?></option>');
						console.error('AJAX request failed');
					});
				} else {
					// Reset to default when no entity selected
					$assignSelect.empty().append('<option value=""><?php _e( 'Select entity first', METS_TEXT_DOMAIN ); ?></option>');
					syncSidebarToProperties();
				}
			});
			
			// KB Tools functionality
			var kbSearchTimeout;
			
			// KB Search functionality
			$('#kb-search-btn, #kb-search-input').on('click keypress', function(e) {
				if (e.type === 'click' || e.which === 13) {
					e.preventDefault();
					performKBSearch();
				}
			});
			
			// Real-time search with debounce
			$('#kb-search-input').on('input', function() {
				clearTimeout(kbSearchTimeout);
				var searchTerm = $(this).val().trim();
				
				if (searchTerm.length < 2) {
					$('#kb-search-results').hide();
					return;
				}
				
				kbSearchTimeout = setTimeout(function() {
					performKBSearch();
				}, 500);
			});
			
			function performKBSearch() {
				var searchTerm = $('#kb-search-input').val().trim();
				
				if (searchTerm.length < 2) {
					$('#kb-search-results').hide();
					return;
				}
				
				$('#kb-results-list').html('<p><?php _e( 'Searching...', METS_TEXT_DOMAIN ); ?></p>');
				$('#kb-search-results').show();
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_admin_search_kb_articles',
					nonce: mets_admin_ajax.nonce,
					search: searchTerm,
					entity_id: $('#ticket_entity').val() || 0
				}, function(response) {
					if (response.success) {
						displayKBResults(response.data.articles);
					} else {
						$('#kb-results-list').html('<p class="error"><?php _e( 'Search failed. Please try again.', METS_TEXT_DOMAIN ); ?></p>');
					}
				}).fail(function() {
					$('#kb-results-list').html('<p class="error"><?php _e( 'Search request failed.', METS_TEXT_DOMAIN ); ?></p>');
				});
			}
			
			function displayKBResults(articles) {
				var resultsHtml = '';
				
				if (articles.length === 0) {
					resultsHtml = '<p><?php _e( 'No articles found.', METS_TEXT_DOMAIN ); ?></p>';
				} else {
					resultsHtml = '<div class="kb-articles-list">';
					$.each(articles, function(i, article) {
						var helpfulScore = article.helpful_yes > 0 || article.helpful_no > 0 
							? Math.round((article.helpful_yes / (article.helpful_yes + article.helpful_no)) * 100) + '% helpful'
							: '';
						
						resultsHtml += '<div class="kb-article-item" style="padding: 10px; border: 1px solid #ddd; margin-bottom: 8px; border-radius: 4px;">';
						resultsHtml += '<div class="kb-article-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px;">';
						resultsHtml += '<a href="' + article.url + '" target="_blank" style="font-weight: 600; color: #0073aa; text-decoration: none; flex: 1;">' + article.title + '</a>';
						resultsHtml += '<small style="color: #666; margin-left: 10px;">' + article.entity_name + '</small>';
						resultsHtml += '</div>';
						if (article.excerpt) {
							resultsHtml += '<div class="kb-article-excerpt" style="color: #666; font-size: 13px; margin-bottom: 5px;">' + article.excerpt + '</div>';
						}
						if (helpfulScore) {
							resultsHtml += '<div class="kb-article-meta" style="font-size: 12px; color: #28a745;">' + helpfulScore + '</div>';
						}
						resultsHtml += '</div>';
					});
					resultsHtml += '</div>';
				}
				
				$('#kb-results-list').html(resultsHtml);
			}
			
			// Load suggested articles when ticket loads
			<?php if ( $is_edit && $ticket ) : ?>
			function loadSuggestedArticles() {
				var searchTerm = '<?php echo esc_js( $ticket->subject . ' ' . wp_strip_all_tags( $ticket->description ) ); ?>';
				
				if (searchTerm.length < 3) return;
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_admin_search_kb_articles',
					nonce: mets_admin_ajax.nonce,
					search: searchTerm,
					entity_id: <?php echo intval( $ticket->entity_id ); ?>,
					limit: 3
				}, function(response) {
					if (response.success && response.data.articles.length > 0) {
						var suggestedHtml = '<div class="kb-suggested-list">';
						$.each(response.data.articles, function(i, article) {
							suggestedHtml += '<div class="kb-suggested-item" style="padding: 8px; border: 1px solid #e1e1e1; margin-bottom: 6px; border-radius: 3px; background: #f9f9f9;">';
							suggestedHtml += '<a href="' + article.url + '" target="_blank" style="font-weight: 500; color: #0073aa; text-decoration: none; font-size: 13px;">' + article.title + '</a>';
							if (article.excerpt) {
								suggestedHtml += '<div style="color: #666; font-size: 12px; margin-top: 3px;">' + article.excerpt.substring(0, 80) + '...</div>';
							}
							suggestedHtml += '</div>';
						});
						suggestedHtml += '</div>';
						$('#kb-suggested-list').html(suggestedHtml);
					}
				});
			}
			
			// Load suggested articles on page load
			loadSuggestedArticles();
			<?php endif; ?>
			
			// KB Article Linking functionality
			$('#kb-link-article-btn').on('click', function() {
				var button = $(this);
				var ticketId = button.data('ticket-id');
				var articleId = $('#link-article-id').val();
				var linkType = $('#link-type').val();
				var notes = $('#link-notes').val();
				
				if (!articleId || articleId < 1) {
					alert('<?php esc_js_e( 'Please enter a valid article ID.', METS_TEXT_DOMAIN ); ?>');
					return;
				}
				
				button.prop('disabled', true).text('<?php esc_js_e( 'Linking...', METS_TEXT_DOMAIN ); ?>');
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_link_kb_article',
					nonce: mets_admin_ajax.nonce,
					ticket_id: ticketId,
					article_id: articleId,
					link_type: linkType,
					agent_notes: notes
				}, function(response) {
					button.prop('disabled', false).text('<?php esc_js_e( 'Link Article', METS_TEXT_DOMAIN ); ?>');
					
					if (response.success) {
						// Clear form
						$('#link-article-id').val('');
						$('#link-notes').val('');
						$('#link-type').val('related');
						
						// Reload linked articles section
						location.reload();
					} else {
						alert(response.data.message || '<?php esc_js_e( 'Failed to link article.', METS_TEXT_DOMAIN ); ?>');
					}
				}).fail(function() {
					button.prop('disabled', false).text('<?php esc_js_e( 'Link Article', METS_TEXT_DOMAIN ); ?>');
					alert('<?php esc_js_e( 'Request failed. Please try again.', METS_TEXT_DOMAIN ); ?>');
				});
			});
			
			// Unlink article functionality
			$(document).on('click', '.kb-unlink-article', function() {
				var button = $(this);
				var linkId = button.data('link-id');
				
				if (!confirm('<?php esc_js_e( 'Are you sure you want to remove this article link?', METS_TEXT_DOMAIN ); ?>')) {
					return;
				}
				
				button.prop('disabled', true);
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_unlink_kb_article',
					nonce: mets_admin_ajax.nonce,
					link_id: linkId
				}, function(response) {
					if (response.success) {
						button.closest('.kb-linked-article-item').fadeOut(300, function() {
							$(this).remove();
							// Check if no articles left
							if ($('.kb-linked-article-item').length === 0) {
								$('#kb-linked-list').html('<p class="description"><?php esc_js_e( 'No articles have been linked to this ticket yet.', METS_TEXT_DOMAIN ); ?></p>');
							}
						});
					} else {
						button.prop('disabled', false);
						alert(response.data.message || '<?php esc_js_e( 'Failed to remove article link.', METS_TEXT_DOMAIN ); ?>');
					}
				}).fail(function() {
					button.prop('disabled', false);
					alert('<?php esc_js_e( 'Request failed. Please try again.', METS_TEXT_DOMAIN ); ?>');
				});
			});
			
			// Mark article as helpful/not helpful
			$(document).on('click', '.kb-mark-helpful', function() {
				var button = $(this);
				var ticketId = button.data('ticket-id');
				var articleId = button.data('article-id');
				var helpful = button.data('helpful');
				
				// Remove active state from both buttons
				button.parent().find('.kb-mark-helpful').removeClass('button-primary');
				
				// Add active state to clicked button
				button.addClass('button-primary');
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_mark_kb_helpful',
					nonce: mets_admin_ajax.nonce,
					ticket_id: ticketId,
					article_id: articleId,
					helpful: helpful
				}, function(response) {
					if (!response.success) {
						// Revert on failure
						button.removeClass('button-primary');
						alert(response.data.message || '<?php esc_js_e( 'Failed to update feedback.', METS_TEXT_DOMAIN ); ?>');
					}
				}).fail(function() {
					// Revert on failure
					button.removeClass('button-primary');
					alert('<?php esc_js_e( 'Request failed. Please try again.', METS_TEXT_DOMAIN ); ?>');
				});
			});
			
			// File upload preview functionality for both forms
			function setupFilePreview(inputSelector, previewSelector) {
				$(inputSelector).on('change', function() {
					var files = this.files;
					var preview = $(previewSelector);
					preview.empty();
					
					if (files.length > 0) {
						var fileList = $('<div class="mets-file-list"></div>');
						
						for (var i = 0; i < files.length; i++) {
							var file = files[i];
							var fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
							var fileItem = $('<div class="mets-file-item"></div>');
							
							// Check file size (20MB limit)
							if (file.size > 20 * 1024 * 1024) {
								fileItem.addClass('mets-file-error');
								fileItem.html('<span class="mets-file-name">' + file.name + '</span> <span class="mets-file-size">(' + fileSize + ' MB)</span> <span class="mets-file-error-msg">' + '<?php echo esc_js( __( 'File too large (max 20MB)', METS_TEXT_DOMAIN ) ); ?>' + '</span>');
							} else {
								fileItem.html('<span class="mets-file-name">' + file.name + '</span> <span class="mets-file-size">(' + fileSize + ' MB)</span>');
							}
							
							fileList.append(fileItem);
						}
						
						preview.append('<p><strong>' + '<?php echo esc_js( __( 'Selected Files:', METS_TEXT_DOMAIN ) ); ?>' + '</strong></p>');
						preview.append(fileList);
					}
				});
			}
			
			// Setup file preview for both ticket and reply forms  
			setupFilePreview('#ticket_attachments', '#file-preview');
			setupFilePreview('#reply_attachments', '#reply-file-preview');
			
			// Initialize and ensure visibility of textarea fields
			function initializeTextareas() {
				$('#ticket_description, #reply_content').each(function() {
					var $textarea = $(this);
					
					// Force proper styling
					$textarea.css({
						'background': 'white !important',
						'color': '#333 !important',
						'border': '1px solid #8c8f94',
						'font-family': '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif',
						'font-size': '13px',
						'line-height': '1.4',
						'padding': '8px',
						'width': '100%',
						'z-index': '1',
						'position': 'relative',
						'opacity': '1',
						'visibility': 'visible'
					});
					
					// Remove any conflicting styles
					$textarea.removeClass('wp-editor-area');
					$textarea.attr('style', $textarea.attr('style'));
				});
			}
			
			// Focus management for textarea fields
			$('#ticket_description, #reply_content').on('focus', function() {
				$(this).css({
					'background': 'white !important',
					'color': '#333 !important',
					'border-color': '#0073aa'
				});
			}).on('blur', function() {
				$(this).css('border-color', '#8c8f94');
			});
			
			// Initialize textareas on page load
			initializeTextareas();
			
			// Re-initialize if needed (in case of dynamic loading)
			setTimeout(initializeTextareas, 100);

			// Ticket Relationships Functionality
			<?php if ( $is_edit && ( current_user_can( 'mets_manager' ) || current_user_can( 'mets_admin' ) ) ) : ?>
				// Merge Ticket Modal
				$('#btn-merge-ticket').on('click', function() {
					var ticketId = <?php echo $ticket->id; ?>;
					var ticketNumber = '<?php echo esc_js( $ticket->ticket_number ); ?>';

					var modal = $('<div class="mets-modal-overlay"></div>');
					var modalContent = $('<div class="mets-modal-content"></div>');

					modalContent.html(`
						<h3><?php _e( 'Merge Ticket', METS_TEXT_DOMAIN ); ?></h3>
						<p><?php _e( 'Merge this ticket into another ticket. All replies and attachments will be moved.', METS_TEXT_DOMAIN ); ?></p>
						<form id="merge-ticket-form">
							<p>
								<label><strong><?php _e( 'Merge Into Ticket ID:', METS_TEXT_DOMAIN ); ?></strong></label><br>
								<input type="number" id="merge-primary-id" class="regular-text" required min="1" placeholder="<?php esc_attr_e( 'Enter ticket ID', METS_TEXT_DOMAIN ); ?>">
								<p class="description"><?php _e( 'The ticket that will receive all data from the current ticket.', METS_TEXT_DOMAIN ); ?></p>
							</p>
							<p>
								<label><strong><?php _e( 'Notes (Optional):', METS_TEXT_DOMAIN ); ?></strong></label><br>
								<textarea id="merge-notes" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Add any notes about this merge...', METS_TEXT_DOMAIN ); ?>"></textarea>
							</p>
							<div class="mets-modal-actions">
								<button type="submit" class="button button-primary"><?php _e( 'Merge Tickets', METS_TEXT_DOMAIN ); ?></button>
								<button type="button" class="button button-secondary mets-modal-close"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
							</div>
							<div class="mets-modal-message" style="display: none; margin-top: 15px;"></div>
						</form>
					`);

					modal.append(modalContent);
					$('body').append(modal);
					modal.fadeIn(200);

					// Close modal
					modal.find('.mets-modal-close').on('click', function() {
						modal.fadeOut(200, function() { modal.remove(); });
					});

					modal.on('click', function(e) {
						if ($(e.target).hasClass('mets-modal-overlay')) {
							modal.fadeOut(200, function() { modal.remove(); });
						}
					});

					// Handle form submission
					$('#merge-ticket-form').on('submit', function(e) {
						e.preventDefault();

						var primaryId = $('#merge-primary-id').val();
						var notes = $('#merge-notes').val();

						if (!primaryId) {
							alert('<?php esc_js_e( 'Please enter a ticket ID', METS_TEXT_DOMAIN ); ?>');
							return;
						}

						// Show loading
						modalContent.find('.mets-modal-message').html('<p><?php _e( 'Merging tickets...', METS_TEXT_DOMAIN ); ?></p>').show();
						modalContent.find('button[type="submit"]').prop('disabled', true);

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'mets_merge_tickets',
								nonce: '<?php echo wp_create_nonce( 'mets_ticket_relationships' ); ?>',
								secondary_id: ticketId,
								primary_id: primaryId,
								notes: notes
							},
							success: function(response) {
								if (response.success) {
									modalContent.find('.mets-modal-message').html('<p style="color: green;">' + response.data.message + '</p>');
									setTimeout(function() {
										window.location.href = '<?php echo admin_url( 'admin.php?page=mets-tickets&action=edit&ticket_id=' ); ?>' + primaryId;
									}, 1500);
								} else {
									modalContent.find('.mets-modal-message').html('<p style="color: red;">' + response.data.message + '</p>');
									modalContent.find('button[type="submit"]').prop('disabled', false);
								}
							},
							error: function() {
								modalContent.find('.mets-modal-message').html('<p style="color: red;"><?php _e( 'An error occurred. Please try again.', METS_TEXT_DOMAIN ); ?></p>');
								modalContent.find('button[type="submit"]').prop('disabled', false);
							}
						});
					});
				});

				// Link Related Ticket Modal
				$('#btn-link-ticket').on('click', function() {
					var ticketId = <?php echo $ticket->id; ?>;

					var modal = $('<div class="mets-modal-overlay"></div>');
					var modalContent = $('<div class="mets-modal-content"></div>');

					modalContent.html(`
						<h3><?php _e( 'Link Related Ticket', METS_TEXT_DOMAIN ); ?></h3>
						<p><?php _e( 'Link another ticket as related to this one.', METS_TEXT_DOMAIN ); ?></p>
						<form id="link-ticket-form">
							<p>
								<label><strong><?php _e( 'Related Ticket ID:', METS_TEXT_DOMAIN ); ?></strong></label><br>
								<input type="number" id="link-ticket-id" class="regular-text" required min="1" placeholder="<?php esc_attr_e( 'Enter ticket ID', METS_TEXT_DOMAIN ); ?>">
								<p class="description"><?php _e( 'The ticket you want to link as related.', METS_TEXT_DOMAIN ); ?></p>
							</p>
							<p>
								<label><strong><?php _e( 'Notes (Optional):', METS_TEXT_DOMAIN ); ?></strong></label><br>
								<textarea id="link-notes" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Add any notes about this relationship...', METS_TEXT_DOMAIN ); ?>"></textarea>
							</p>
							<div class="mets-modal-actions">
								<button type="submit" class="button button-primary"><?php _e( 'Link Tickets', METS_TEXT_DOMAIN ); ?></button>
								<button type="button" class="button button-secondary mets-modal-close"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
							</div>
							<div class="mets-modal-message" style="display: none; margin-top: 15px;"></div>
						</form>
					`);

					modal.append(modalContent);
					$('body').append(modal);
					modal.fadeIn(200);

					// Close modal
					modal.find('.mets-modal-close').on('click', function() {
						modal.fadeOut(200, function() { modal.remove(); });
					});

					modal.on('click', function(e) {
						if ($(e.target).hasClass('mets-modal-overlay')) {
							modal.fadeOut(200, function() { modal.remove(); });
						}
					});

					// Handle form submission
					$('#link-ticket-form').on('submit', function(e) {
						e.preventDefault();

						var relatedId = $('#link-ticket-id').val();
						var notes = $('#link-notes').val();

						if (!relatedId) {
							alert('<?php esc_js_e( 'Please enter a ticket ID', METS_TEXT_DOMAIN ); ?>');
							return;
						}

						// Show loading
						modalContent.find('.mets-modal-message').html('<p><?php _e( 'Linking tickets...', METS_TEXT_DOMAIN ); ?></p>').show();
						modalContent.find('button[type="submit"]').prop('disabled', true);

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'mets_link_tickets',
								nonce: '<?php echo wp_create_nonce( 'mets_ticket_relationships' ); ?>',
								ticket_id_1: ticketId,
								ticket_id_2: relatedId,
								notes: notes
							},
							success: function(response) {
								if (response.success) {
									modalContent.find('.mets-modal-message').html('<p style="color: green;">' + response.data.message + '</p>');
									setTimeout(function() {
										location.reload();
									}, 1500);
								} else {
									modalContent.find('.mets-modal-message').html('<p style="color: red;">' + response.data.message + '</p>');
									modalContent.find('button[type="submit"]').prop('disabled', false);
								}
							},
							error: function() {
								modalContent.find('.mets-modal-message').html('<p style="color: red;"><?php _e( 'An error occurred. Please try again.', METS_TEXT_DOMAIN ); ?></p>');
								modalContent.find('button[type="submit"]').prop('disabled', false);
							}
						});
					});
				});

				// Mark as Duplicate Modal
				$('#btn-mark-duplicate').on('click', function() {
					var ticketId = <?php echo $ticket->id; ?>;

					var modal = $('<div class="mets-modal-overlay"></div>');
					var modalContent = $('<div class="mets-modal-content"></div>');

					modalContent.html(`
						<h3><?php _e( 'Mark as Duplicate', METS_TEXT_DOMAIN ); ?></h3>
						<p><?php _e( 'Mark this ticket as a duplicate of another ticket.', METS_TEXT_DOMAIN ); ?></p>
						<form id="duplicate-ticket-form">
							<p>
								<label><strong><?php _e( 'Original Ticket ID:', METS_TEXT_DOMAIN ); ?></strong></label><br>
								<input type="number" id="duplicate-original-id" class="regular-text" required min="1" placeholder="<?php esc_attr_e( 'Enter original ticket ID', METS_TEXT_DOMAIN ); ?>">
								<p class="description"><?php _e( 'The original ticket that this one duplicates.', METS_TEXT_DOMAIN ); ?></p>
							</p>
							<p>
								<label><strong><?php _e( 'Notes (Optional):', METS_TEXT_DOMAIN ); ?></strong></label><br>
								<textarea id="duplicate-notes" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Add any notes about why this is a duplicate...', METS_TEXT_DOMAIN ); ?>"></textarea>
							</p>
							<div class="mets-modal-actions">
								<button type="submit" class="button button-primary"><?php _e( 'Mark as Duplicate', METS_TEXT_DOMAIN ); ?></button>
								<button type="button" class="button button-secondary mets-modal-close"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
							</div>
							<div class="mets-modal-message" style="display: none; margin-top: 15px;"></div>
						</form>
					`);

					modal.append(modalContent);
					$('body').append(modal);
					modal.fadeIn(200);

					// Close modal
					modal.find('.mets-modal-close').on('click', function() {
						modal.fadeOut(200, function() { modal.remove(); });
					});

					modal.on('click', function(e) {
						if ($(e.target).hasClass('mets-modal-overlay')) {
							modal.fadeOut(200, function() { modal.remove(); });
						}
					});

					// Handle form submission
					$('#duplicate-ticket-form').on('submit', function(e) {
						e.preventDefault();

						var originalId = $('#duplicate-original-id').val();
						var notes = $('#duplicate-notes').val();

						if (!originalId) {
							alert('<?php esc_js_e( 'Please enter a ticket ID', METS_TEXT_DOMAIN ); ?>');
							return;
						}

						// Show loading
						modalContent.find('.mets-modal-message').html('<p><?php _e( 'Marking as duplicate...', METS_TEXT_DOMAIN ); ?></p>').show();
						modalContent.find('button[type="submit"]').prop('disabled', true);

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'mets_mark_duplicate',
								nonce: '<?php echo wp_create_nonce( 'mets_ticket_relationships' ); ?>',
								duplicate_id: ticketId,
								original_id: originalId,
								notes: notes
							},
							success: function(response) {
								if (response.success) {
									modalContent.find('.mets-modal-message').html('<p style="color: green;">' + response.data.message + '</p>');
									setTimeout(function() {
										location.reload();
									}, 1500);
								} else {
									modalContent.find('.mets-modal-message').html('<p style="color: red;">' + response.data.message + '</p>');
									modalContent.find('button[type="submit"]').prop('disabled', false);
								}
							},
							error: function() {
								modalContent.find('.mets-modal-message').html('<p style="color: red;"><?php _e( 'An error occurred. Please try again.', METS_TEXT_DOMAIN ); ?></p>');
								modalContent.find('button[type="submit"]').prop('disabled', false);
							}
						});
					});
				});

				// Unlink ticket relationship
				$(document).on('click', '.unlink-ticket', function() {
					if (!confirm('<?php esc_js_e( 'Are you sure you want to remove this relationship?', METS_TEXT_DOMAIN ); ?>')) {
						return;
					}

					var button = $(this);
					var relationshipId = button.data('relationship-id');

					button.prop('disabled', true).text('<?php esc_js_e( 'Removing...', METS_TEXT_DOMAIN ); ?>');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'mets_unlink_tickets',
							nonce: '<?php echo wp_create_nonce( 'mets_ticket_relationships' ); ?>',
							relationship_id: relationshipId
						},
						success: function(response) {
							if (response.success) {
								button.closest('.related-ticket-item').fadeOut(300, function() {
									$(this).remove();
									if ($('.related-ticket-item').length === 0) {
										$('#related-tickets-list').html('<p class="description"><?php esc_js_e( 'No related tickets found.', METS_TEXT_DOMAIN ); ?></p>');
									}
								});
							} else {
								alert(response.data.message);
								button.prop('disabled', false).html('<span class="dashicons dashicons-no-alt" style="font-size: 12px; line-height: 1.2;"></span>');
							}
						},
						error: function() {
							alert('<?php esc_js_e( 'An error occurred. Please try again.', METS_TEXT_DOMAIN ); ?>');
							button.prop('disabled', false).html('<span class="dashicons dashicons-no-alt" style="font-size: 12px; line-height: 1.2;"></span>');
						}
					});
				});
			<?php endif; ?>
		});
		</script>
		
		<style>
		/* File upload preview styles */
		.mets-file-list {
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 10px;
			background: #f9f9f9;
		}
		
		.mets-file-item {
			padding: 5px 0;
			border-bottom: 1px solid #eee;
		}
		
		.mets-file-item:last-child {
			border-bottom: none;
		}
		
		.mets-file-name {
			font-weight: bold;
		}
		
		.mets-file-size {
			color: #666;
			font-size: 0.9em;
		}
		
		.mets-file-error {
			color: #d63638;
		}
		
		.mets-file-error-msg {
			color: #d63638;
			font-weight: bold;
		}
		
		/* Textarea styling for description and replies */
		#ticket_description,
		#reply_content {
			background: white !important;
			color: #333 !important;
			border: 1px solid #8c8f94 !important;
			border-radius: 4px;
			padding: 8px !important;
			font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif !important;
			font-size: 13px !important;
			line-height: 1.4 !important;
			resize: vertical;
			width: 100% !important;
			box-sizing: border-box;
			z-index: 1 !important;
			position: relative !important;
		}
		
		#ticket_description:focus,
		#reply_content:focus {
			border-color: #0073aa !important;
			box-shadow: 0 0 0 1px #0073aa !important;
			outline: none;
		}
		
		/* Ensure no overlays or pseudo-elements interfere */
		#ticket_description::before,
		#ticket_description::after,
		#reply_content::before,
		#reply_content::after {
			display: none !important;
		}
		
		/* Remove any potential overlays */
		.form-table td {
			position: relative;
		}
		</style>
		<?php
	}

	/**
	 * Display ticket replies section
	 *
	 * @since    1.0.0
	 * @param    object    $ticket    Ticket object
	 */
	private function display_ticket_replies( $ticket ) {
		$replies = $this->reply_model->get_by_ticket( $ticket->id );

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Conversation History', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<div id="ticket-replies">
					<?php if ( empty( $replies ) ) : ?>
						<p><em><?php _e( 'No replies yet.', METS_TEXT_DOMAIN ); ?></em></p>
					<?php else : ?>
						<?php foreach ( $replies as $reply ) : 
							// Determine background colors for fallback inline styles
							$bg_color = '#f8f9fa';
							$border_color = '#6c757d';
							$text_color = '#495057';
							
							if ( $reply->is_internal_note ) {
								$bg_color = 'linear-gradient(135deg, #FFF8E1 0%, #FFFDE7 100%)';
								$border_color = '#FF9800';
								$text_color = '#F57C00';
							} elseif ( $reply->user_type === 'customer' ) {
								$bg_color = 'linear-gradient(135deg, #E3F2FD 0%, #F0F8FF 100%)';
								$border_color = '#2196F3';
								$text_color = '#1976D2';
							} elseif ( $reply->user_type === 'system' ) {
								$bg_color = '#f5f5f5';
								$border_color = '#9e9e9e';
								$text_color = '#757575';
							}
						?>
							<div class="ticket-reply" 
								 data-user-type="<?php echo esc_attr( $reply->user_type ); ?>" 
								 data-internal="<?php echo $reply->is_internal_note ? 'true' : 'false'; ?>"
								 data-reply-id="<?php echo esc_attr( $reply->id ); ?>"
								 style="margin-bottom: 20px; padding: 15px; background: <?php echo esc_attr( $bg_color ); ?>; border-left: 4px solid <?php echo esc_attr( $border_color ); ?>; border: 1px solid <?php echo esc_attr( $border_color ); ?>; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
								<div class="reply-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid rgba(0, 0, 0, 0.1);">
									<div class="reply-author" style="font-weight: 600; color: <?php echo esc_attr( $text_color ); ?>;">
										<?php echo esc_html( $reply->user_name ?: __( 'System', METS_TEXT_DOMAIN ) ); ?>
										<?php if ( $reply->is_internal_note ) : ?>
											<span class="internal-note-badge" style="background: #FF9800; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-left: 10px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);"><?php _e( 'Internal', METS_TEXT_DOMAIN ); ?></span>
										<?php elseif ( $reply->user_type === 'customer' ) : ?>
											<span class="customer-badge" style="background: #2196F3; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-left: 10px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);"><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></span>
										<?php endif; ?>
									</div>
									<div class="reply-date" style="color: #666; font-size: 13px; font-style: italic;">
										<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reply->created_at ) ); ?>
									</div>
								</div>
								<div class="reply-content" style="margin-top: 10px; line-height: 1.6; color: #333;">
									<?php echo wp_kses_post( $reply->content ); ?>
								</div>
								
								<!-- Reply Attachments -->
								<?php echo $this->display_admin_attachments( $ticket->id, $reply->id ); ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<hr>

				<div id="add-reply-form">
					<h4><?php _e( 'Add Reply', METS_TEXT_DOMAIN ); ?></h4>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" id="reply-form" enctype="multipart/form-data">
						<input type="hidden" name="page" value="mets-tickets">
						<?php wp_nonce_field( 'add_reply', 'reply_nonce' ); ?>
						<input type="hidden" name="action" value="add_reply">
						<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
						
						<p>
							<textarea name="reply_content" id="reply_content" 
									  rows="5" cols="50" class="large-text" required 
									  placeholder="<?php esc_attr_e( 'Enter your reply...', METS_TEXT_DOMAIN ); ?>"
									  style="width: 100%; background: white !important; color: #333 !important; border: 1px solid #8c8f94; padding: 8px; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif; font-size: 13px; line-height: 1.4; z-index: 1; position: relative;"></textarea>
						</p>
						
						<div class="file-upload-section">
							<label for="reply_attachments"><?php _e( 'Attachments', METS_TEXT_DOMAIN ); ?></label>
							<input type="file" name="reply_attachments[]" id="reply_attachments" class="file-upload-input" multiple 
								   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
							<p class="description">
								<?php _e( 'Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX, TXT, ZIP. Maximum 20MB each.', METS_TEXT_DOMAIN ); ?>
							</p>
							<div id="reply-file-preview"></div>
						</div>
						
						<p>
							<label>
								<input type="checkbox" name="is_internal_note" value="1"> 
								<?php _e( 'Internal note (not visible to customer)', METS_TEXT_DOMAIN ); ?>
							</label>
						</p>
						
						<p>
							<?php submit_button( __( 'Add Reply', METS_TEXT_DOMAIN ), 'secondary', 'add_reply_submit', false, array( 'form' => 'reply-form' ) ); ?>
						</p>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get accessible entities for current user
	 *
	 * @since    1.0.0
	 * @return   array    Array of entity objects
	 */
	private function get_accessible_entities() {
		// Admins can access all entities
		if ( current_user_can( 'manage_ticket_system' ) ) {
			return $this->entity_model->get_all( array( 'status' => 'active', 'parent_id' => 'all' ) );
		}

		// Get entities user has access to
		global $wpdb;
		$current_user = wp_get_current_user();
		
		$entity_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT entity_id FROM {$wpdb->prefix}mets_user_entities WHERE user_id = %d",
			$current_user->ID
		) );

		if ( empty( $entity_ids ) ) {
			return array();
		}

		return $this->entity_model->get_all( array(
			'status' => 'active',
			'parent_id' => 'all',
			'entity_ids' => $entity_ids,
		) );
	}

	/**
	 * Check if user can access entity
	 *
	 * @since    1.0.0
	 * @param    int     $entity_id    Entity ID
	 * @return   bool                  True if user has access
	 */
	private function user_can_access_entity( $entity_id ) {
		// Admins can access all entities
		if ( current_user_can( 'manage_ticket_system' ) ) {
			return true;
		}

		global $wpdb;
		$current_user = wp_get_current_user();
		
		$has_access = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_user_entities WHERE user_id = %d AND entity_id = %d",
			$current_user->ID,
			$entity_id
		) );

		return $has_access > 0;
	}

	/**
	 * Display attachments for admin interface
	 *
	 * @since    1.0.0
	 * @param    int     $ticket_id    Ticket ID
	 * @param    int     $reply_id     Reply ID (optional)
	 * @return   string                HTML output
	 */
	private function display_admin_attachments( $ticket_id, $reply_id = null ) {
		$attachments = $this->attachment_model->get_by_ticket( $ticket_id, $reply_id );
		
		if ( empty( $attachments ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="admin-attachments-section" style="margin-top: 15px; padding: 12px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 6px;">
			<h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #555;">
				<span class="dashicons dashicons-paperclip" style="font-size: 16px; vertical-align: middle; margin-right: 5px;"></span>
				<?php _e( 'Attachments', METS_TEXT_DOMAIN ); ?>
			</h4>
			<div class="attachments-list">
				<?php foreach ( $attachments as $attachment ) : 
					$file_icon = $this->get_admin_file_icon( $attachment->file_type );
					$file_size = $this->format_admin_file_size( $attachment->file_size );
					$is_image = in_array( strtolower( $attachment->file_type ), array( 'jpg', 'jpeg', 'png', 'gif' ) );
				?>
					<div class="admin-attachment-item" style="display: flex; align-items: center; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px; transition: all 0.2s ease;">
						<?php if ( $is_image ) : ?>
							<!-- Image preview for images -->
							<div class="attachment-preview" style="width: 50px; height: 50px; margin-right: 12px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
								<img src="<?php echo esc_url( $attachment->file_url ); ?>" 
									 alt="<?php echo esc_attr( $attachment->file_name ); ?>"
									 style="max-width: 100%; max-height: 100%; object-fit: cover; cursor: pointer;"
									 onclick="window.open('<?php echo esc_url( $attachment->file_url ); ?>', '_blank')"
									 title="<?php _e( 'Click to view full size', METS_TEXT_DOMAIN ); ?>">
							</div>
						<?php else : ?>
							<!-- File icon for non-images -->
							<div class="attachment-icon" style="width: 50px; height: 50px; margin-right: 12px; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
								<span class="dashicons <?php echo esc_attr( $file_icon ); ?>" style="font-size: 24px; color: #666;"></span>
							</div>
						<?php endif; ?>
						
						<div class="attachment-info" style="flex: 1; min-width: 0;">
							<div class="attachment-name" style="font-weight: 600; color: #0073aa; margin-bottom: 2px; word-break: break-all;">
								<a href="<?php echo esc_url( $attachment->file_url ); ?>" 
								   target="_blank" 
								   style="text-decoration: none; color: inherit;"
								   title="<?php _e( 'Click to download', METS_TEXT_DOMAIN ); ?>">
									<?php echo esc_html( $attachment->file_name ); ?>
								</a>
							</div>
							<div class="attachment-meta" style="font-size: 12px; color: #666;">
								<?php 
								printf( 
									__( '%s • Uploaded by %s • %s', METS_TEXT_DOMAIN ),
									esc_html( $file_size ),
									esc_html( $attachment->uploaded_by_name ?: __( 'Customer', METS_TEXT_DOMAIN ) ),
									date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attachment->created_at ) )
								);
								?>
							</div>
						</div>
						
						<div class="attachment-actions" style="margin-left: 12px;">
							<a href="<?php echo esc_url( $attachment->file_url ); ?>" 
							   class="button button-small" 
							   target="_blank"
							   download="<?php echo esc_attr( $attachment->file_name ); ?>"
							   title="<?php _e( 'Download file', METS_TEXT_DOMAIN ); ?>">
								<span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get file icon class for admin interface
	 *
	 * @since    1.0.0
	 * @param    string  $file_type    File extension
	 * @return   string                Dashicon class
	 */
	private function get_admin_file_icon( $file_type ) {
		$icons = array(
			'pdf'  => 'dashicons-pdf',
			'doc'  => 'dashicons-media-document',
			'docx' => 'dashicons-media-document',
			'xls'  => 'dashicons-media-spreadsheet',
			'xlsx' => 'dashicons-media-spreadsheet',
			'txt'  => 'dashicons-media-text',
			'zip'  => 'dashicons-media-archive',
			'rar'  => 'dashicons-media-archive',
			'jpg'  => 'dashicons-format-image',
			'jpeg' => 'dashicons-format-image',
			'png'  => 'dashicons-format-image',
			'gif'  => 'dashicons-format-image',
		);
		
		return isset( $icons[ strtolower( $file_type ) ] ) ? $icons[ strtolower( $file_type ) ] : 'dashicons-media-default';
	}

	/**
	 * Format file size for admin interface
	 *
	 * @since    1.0.0
	 * @param    int     $bytes    File size in bytes
	 * @return   string            Formatted file size
	 */
	private function format_admin_file_size( $bytes ) {
		if ( $bytes >= 1073741824 ) {
			return number_format( $bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			return number_format( $bytes / 1048576, 2 ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			return number_format( $bytes / 1024, 2 ) . ' KB';
		} else {
			return $bytes . ' bytes';
		}
	}

	/**
	 * Render linked articles for a ticket
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @return   string               HTML output
	 */
	/**
	 * Render related tickets
	 *
	 * @since    1.0.1
	 * @param    int    $ticket_id    Ticket ID
	 * @return   string                HTML output
	 */
	private function render_related_tickets( $ticket_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-relationship-model.php';
		$relationship_model = new METS_Ticket_Relationship_Model();

		$relationships = $relationship_model->get_related_tickets( $ticket_id );

		if ( empty( $relationships ) ) {
			return '<p class="description">' . __( 'No related tickets found.', METS_TEXT_DOMAIN ) . '</p>';
		}

		ob_start();
		?>
		<div class="related-tickets-list">
			<?php foreach ( $relationships as $rel ) : ?>
				<?php
				// Determine which ticket is the "other" one
				$other_ticket_id = ( $rel->parent_ticket_id == $ticket_id ) ? $rel->child_ticket_id : $rel->parent_ticket_id;

				// Get the other ticket details
				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
				$ticket_model = new METS_Ticket_Model();
				$other_ticket = $ticket_model->get( $other_ticket_id );

				if ( ! $other_ticket ) {
					continue;
				}

				// Get relationship type label
				$type_labels = array(
					'merged' => __( 'Merged', METS_TEXT_DOMAIN ),
					'split' => __( 'Split', METS_TEXT_DOMAIN ),
					'related' => __( 'Related', METS_TEXT_DOMAIN ),
					'duplicate' => __( 'Duplicate', METS_TEXT_DOMAIN ),
				);
				$type_label = isset( $type_labels[ $rel->relationship_type ] ) ? $type_labels[ $rel->relationship_type ] : ucfirst( $rel->relationship_type );

				// Get status info
				$statuses = get_option( 'mets_ticket_statuses', array() );
				$status_label = isset( $statuses[ $other_ticket->status ] ) ? $statuses[ $other_ticket->status ]['label'] : ucfirst( $other_ticket->status );
				$status_color = isset( $statuses[ $other_ticket->status ] ) ? $statuses[ $other_ticket->status ]['color'] : '#666';
				?>
				<div class="related-ticket-item" style="margin-bottom: 12px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
					<div class="ticket-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
						<div class="ticket-info" style="flex: 1;">
							<h5 style="margin: 0 0 4px 0; font-size: 14px;">
								<a href="<?php echo admin_url( 'admin.php?page=mets-tickets&action=edit&ticket_id=' . $other_ticket_id ); ?>" target="_blank" style="text-decoration: none;">
									<?php echo esc_html( $other_ticket->ticket_number ); ?> - <?php echo esc_html( $other_ticket->subject ); ?>
								</a>
							</h5>
							<div class="relationship-meta" style="font-size: 12px; color: #666;">
								<span class="relationship-type" style="display: inline-block; padding: 2px 6px; background: #007cba; color: white; border-radius: 3px; margin-right: 8px;">
									<?php echo esc_html( $type_label ); ?>
								</span>
								<span class="ticket-status" style="display: inline-block; padding: 2px 6px; background-color: <?php echo esc_attr( $status_color ); ?>15; color: <?php echo esc_attr( $status_color ); ?>; border-radius: 3px; margin-right: 8px;">
									<?php echo esc_html( $status_label ); ?>
								</span>
								<span class="created-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $rel->created_at ) ) ); ?></span>
							</div>
						</div>
						<div class="ticket-actions">
							<button type="button" class="button button-small unlink-ticket" data-relationship-id="<?php echo esc_attr( $rel->id ); ?>" title="<?php esc_attr_e( 'Remove relationship', METS_TEXT_DOMAIN ); ?>">
								<span class="dashicons dashicons-no-alt" style="font-size: 12px; line-height: 1.2;"></span>
							</button>
						</div>
					</div>

					<?php if ( $rel->notes ) : ?>
						<div class="relationship-notes" style="background: #fff; padding: 8px; border-left: 3px solid #007cba; font-size: 12px; color: #555;">
							<strong><?php _e( 'Notes:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $rel->notes ); ?>
						</div>
					<?php endif; ?>

					<div class="ticket-details" style="margin-top: 8px; font-size: 12px; color: #666;">
						<strong><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $other_ticket->entity_name ); ?> |
						<strong><?php _e( 'Customer:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $other_ticket->customer_name ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_linked_articles( $ticket_id ) {
		$linked_articles = $this->kb_link_model->get_by_ticket( $ticket_id );

		if ( empty( $linked_articles ) ) {
			return '<p class="description">' . __( 'No articles have been linked to this ticket yet.', METS_TEXT_DOMAIN ) . '</p>';
		}

		ob_start();
		?>
		<div class="kb-linked-articles-list">
			<?php foreach ( $linked_articles as $link ) : ?>
				<div class="kb-linked-article-item" style="margin-bottom: 12px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
					<div class="article-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
						<div class="article-info" style="flex: 1;">
							<h5 style="margin: 0 0 4px 0; font-size: 14px;">
								<a href="<?php echo admin_url( 'admin.php?page=mets-kb-article&id=' . $link->article_id ); ?>" target="_blank" style="text-decoration: none;">
									<?php echo esc_html( $link->article_title ); ?>
								</a>
							</h5>
							<div class="link-meta" style="font-size: 12px; color: #666;">
								<span class="link-type" style="display: inline-block; padding: 2px 6px; background: #007cba; color: white; border-radius: 3px; margin-right: 8px;">
									<?php echo esc_html( ucfirst( $link->link_type ) ); ?>
								</span>
								<?php if ( $link->suggested_by_name ) : ?>
									<span class="suggested-by"><?php printf( __( 'Linked by %s', METS_TEXT_DOMAIN ), esc_html( $link->suggested_by_name ) ); ?></span>
								<?php endif; ?>
								<span class="link-date" style="margin-left: 8px;"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $link->created_at ) ) ); ?></span>
							</div>
						</div>
						<div class="article-actions">
							<button type="button" class="button button-small kb-unlink-article" data-link-id="<?php echo esc_attr( $link->id ); ?>" title="<?php esc_attr_e( 'Remove link', METS_TEXT_DOMAIN ); ?>">
								<span class="dashicons dashicons-no-alt" style="font-size: 12px; line-height: 1.2;"></span>
							</button>
						</div>
					</div>
					
					<?php if ( $link->article_excerpt ) : ?>
						<div class="article-excerpt" style="font-size: 13px; color: #555; margin-bottom: 8px;">
							<?php echo esc_html( wp_trim_words( $link->article_excerpt, 20 ) ); ?>
						</div>
					<?php endif; ?>
					
					<?php if ( $link->agent_notes ) : ?>
						<div class="agent-notes" style="background: #fff; padding: 8px; border-left: 3px solid #007cba; font-size: 12px; color: #555;">
							<strong><?php _e( 'Notes:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $link->agent_notes ); ?>
						</div>
					<?php endif; ?>
					
					<div class="helpful-feedback" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd;">
						<div class="helpful-buttons" style="display: flex; align-items: center; gap: 10px;">
							<span style="font-size: 12px; color: #666;"><?php _e( 'Was this helpful?', METS_TEXT_DOMAIN ); ?></span>
							<button type="button" class="button button-small kb-mark-helpful <?php echo ( $link->helpful === '1' ) ? 'button-primary' : ''; ?>" 
								data-ticket-id="<?php echo esc_attr( $ticket_id ); ?>" 
								data-article-id="<?php echo esc_attr( $link->article_id ); ?>" 
								data-helpful="1"
								title="<?php esc_attr_e( 'Mark as helpful', METS_TEXT_DOMAIN ); ?>">
								<span class="dashicons dashicons-thumbs-up" style="font-size: 12px; line-height: 1.2;"></span>
							</button>
							<button type="button" class="button button-small kb-mark-helpful <?php echo ( $link->helpful === '0' ) ? 'button-primary' : ''; ?>" 
								data-ticket-id="<?php echo esc_attr( $ticket_id ); ?>" 
								data-article-id="<?php echo esc_attr( $link->article_id ); ?>" 
								data-helpful="0"
								title="<?php esc_attr_e( 'Mark as not helpful', METS_TEXT_DOMAIN ); ?>">
								<span class="dashicons dashicons-thumbs-down" style="font-size: 12px; line-height: 1.2;"></span>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<div class="kb-link-article-section" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
			<p><strong><?php _e( 'Link Article:', METS_TEXT_DOMAIN ); ?></strong></p>
			<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
				<input type="number" id="link-article-id" placeholder="<?php esc_attr_e( 'Article ID', METS_TEXT_DOMAIN ); ?>" style="width: 80px;" min="1">
				<select id="link-type" style="width: 120px;">
					<option value="related"><?php _e( 'Related', METS_TEXT_DOMAIN ); ?></option>
					<option value="suggested"><?php _e( 'Suggested', METS_TEXT_DOMAIN ); ?></option>
					<option value="resolved"><?php _e( 'Resolved', METS_TEXT_DOMAIN ); ?></option>
					<option value="referenced"><?php _e( 'Referenced', METS_TEXT_DOMAIN ); ?></option>
				</select>
				<button type="button" id="kb-link-article-btn" class="button button-secondary" data-ticket-id="<?php echo esc_attr( $ticket_id ); ?>">
					<?php _e( 'Link Article', METS_TEXT_DOMAIN ); ?>
				</button>
			</div>
			<div>
				<input type="text" id="link-notes" placeholder="<?php esc_attr_e( 'Optional notes...', METS_TEXT_DOMAIN ); ?>" style="width: 100%;">
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}