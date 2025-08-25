<?php
/**
 * Entity management admin interface
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/entities
 * @since      1.0.0
 */

/**
 * Entity management admin interface class.
 *
 * This class handles the admin interface for managing entities
 * including list view, add/edit forms, and CRUD operations.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/entities
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Entity_Manager {

	/**
	 * Entity model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Entity_Model    $entity_model    Entity model instance.
	 */
	private $entity_model;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$this->entity_model = new METS_Entity_Model();
	}

	/**
	 * Display the entities management page
	 *
	 * @since    1.0.0
	 */
	public function display_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_entities' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Get current action
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$entity_id = isset( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : 0;

		// Display appropriate view
		switch ( $action ) {
			case 'add':
				$this->display_add_form();
				break;
			case 'edit':
				$this->display_edit_form( $entity_id );
				break;
			default:
				$this->display_list();
				break;
		}
	}

	/**
	 * Display entities list
	 *
	 * @since    1.0.0
	 */
	private function display_list() {
		// Check for admin notices
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			delete_transient( 'mets_admin_notice' );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), esc_html( $notice['message'] ) );
		}
		
		// Get search parameters
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 20;
		$offset = ( $paged - 1 ) * $per_page;

		// Get entities
		$args = array(
			'search' => $search,
			'limit'  => $per_page,
			'offset' => $offset,
			'status' => 'all',
			'parent_id' => 'all', // Show all entities, not just top-level
		);

		$entities = $this->entity_model->get_all( $args );
		$total_entities = $this->entity_model->get_count( array( 'search' => $search, 'status' => 'all', 'parent_id' => 'all' ) );

		// Calculate pagination
		$total_pages = ceil( $total_entities / $per_page );
		
		// Debug: Check what's actually in the database
		global $wpdb;
		$table_name = $wpdb->prefix . 'mets_entities';
		$raw_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
		
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'METS Debug - Total entities in DB: ' . $raw_count );
			error_log( 'METS Debug - Query args: ' . print_r( $args, true ) );
			error_log( 'METS Debug - Entities found: ' . count( $entities ) );
		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Entities', METS_TEXT_DOMAIN ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-entities&action=add' ) ); ?>" class="page-title-action">
				<?php _e( 'Add New', METS_TEXT_DOMAIN ); ?>
			</a>

			<?php if ( ! empty( $search ) ) : ?>
				<span class="subtitle"><?php printf( __( 'Search results for: %s', METS_TEXT_DOMAIN ), '<strong>' . esc_html( $search ) . '</strong>' ); ?></span>
			<?php endif; ?>

			<hr class="wp-header-end">
			
			<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<div class="notice notice-info">
					<p>Debug Info: Total entities in database: <?php echo esc_html( $raw_count ); ?> | Query returned: <?php echo count( $entities ); ?> entities</p>
				</div>
			<?php endif; ?>

			<!-- Search Form -->
			<form method="get" action="">
				<input type="hidden" name="page" value="mets-entities">
				<p class="search-box">
					<label class="screen-reader-text" for="entity-search-input"><?php _e( 'Search Entities:', METS_TEXT_DOMAIN ); ?></label>
					<input type="search" id="entity-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Entities', METS_TEXT_DOMAIN ); ?>">
				</p>
			</form>

			<!-- Entities Table -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-name column-primary">
							<?php _e( 'Name', METS_TEXT_DOMAIN ); ?>
						</th>
						<th scope="col" class="manage-column column-slug">
							<?php _e( 'Slug', METS_TEXT_DOMAIN ); ?>
						</th>
						<th scope="col" class="manage-column column-parent">
							<?php _e( 'Parent', METS_TEXT_DOMAIN ); ?>
						</th>
						<th scope="col" class="manage-column column-status">
							<?php _e( 'Status', METS_TEXT_DOMAIN ); ?>
						</th>
						<th scope="col" class="manage-column column-tickets">
							<?php _e( 'Tickets', METS_TEXT_DOMAIN ); ?>
						</th>
						<th scope="col" class="manage-column column-date">
							<?php _e( 'Date', METS_TEXT_DOMAIN ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $entities ) ) : ?>
						<tr class="no-items">
							<td class="colspanchange" colspan="6">
								<?php _e( 'No entities found.', METS_TEXT_DOMAIN ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $entities as $entity ) : ?>
							<?php
							$parent_name = '';
							if ( ! empty( $entity->parent_id ) ) {
								$parent = $this->entity_model->get( $entity->parent_id );
								$parent_name = $parent ? $parent->name : __( 'Unknown', METS_TEXT_DOMAIN );
							}

							// Get ticket count (placeholder for now)
							$ticket_count = 0;
							?>
							<tr>
								<td class="name column-name column-primary" data-colname="<?php _e( 'Name', METS_TEXT_DOMAIN ); ?>">
									<strong>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-entities&action=edit&entity_id=' . $entity->id ) ); ?>">
											<?php 
											// Add indentation for child entities
											if ( ! empty( $entity->parent_id ) ) {
												echo '— ';
											}
											echo esc_html( $entity->name ); 
											?>
										</a>
									</strong>
									<div class="row-actions">
										<span class="edit">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-entities&action=edit&entity_id=' . $entity->id ) ); ?>">
												<?php _e( 'Edit', METS_TEXT_DOMAIN ); ?>
											</a> |
										</span>
										<span class="delete">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mets-entities&action=delete&entity_id=' . $entity->id ), 'delete_entity_' . $entity->id ) ); ?>" 
											   onclick="return confirm('<?php _e( 'Are you sure you want to delete this entity?', METS_TEXT_DOMAIN ); ?>');">
												<?php _e( 'Delete', METS_TEXT_DOMAIN ); ?>
											</a>
										</span>
									</div>
									<button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e( 'Show more details', METS_TEXT_DOMAIN ); ?></span></button>
								</td>
								<td class="slug column-slug" data-colname="<?php _e( 'Slug', METS_TEXT_DOMAIN ); ?>">
									<?php echo esc_html( $entity->slug ); ?>
								</td>
								<td class="parent column-parent" data-colname="<?php _e( 'Parent', METS_TEXT_DOMAIN ); ?>">
									<?php echo esc_html( $parent_name ); ?>
								</td>
								<td class="status column-status" data-colname="<?php _e( 'Status', METS_TEXT_DOMAIN ); ?>">
									<span class="status-<?php echo esc_attr( $entity->status ); ?>">
										<?php echo $entity->status === 'active' ? __( 'Active', METS_TEXT_DOMAIN ) : __( 'Inactive', METS_TEXT_DOMAIN ); ?>
									</span>
								</td>
								<td class="tickets column-tickets" data-colname="<?php _e( 'Tickets', METS_TEXT_DOMAIN ); ?>">
									<?php echo esc_html( $ticket_count ); ?>
								</td>
								<td class="date column-date" data-colname="<?php _e( 'Date', METS_TEXT_DOMAIN ); ?>">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entity->created_at ) ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php printf( _n( '%s item', '%s items', $total_entities, METS_TEXT_DOMAIN ), number_format_i18n( $total_entities ) ); ?>
						</span>
						<?php
						$page_links = paginate_links( array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'prev_text' => __( '&laquo;', METS_TEXT_DOMAIN ),
							'next_text' => __( '&raquo;', METS_TEXT_DOMAIN ),
							'total'   => $total_pages,
							'current' => $paged,
						) );
						if ( $page_links ) {
							echo '<span class="pagination-links">' . $page_links . '</span>';
						}
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display add entity form
	 *
	 * @since    1.0.0
	 */
	private function display_add_form() {
		$this->display_form( null );
	}

	/**
	 * Display edit entity form
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 */
	private function display_edit_form( $entity_id ) {
		$entity = $this->entity_model->get( $entity_id );
		if ( ! $entity ) {
			wp_die( __( 'Entity not found.', METS_TEXT_DOMAIN ) );
		}

		$this->display_form( $entity );
	}

	/**
	 * Display entity form
	 *
	 * @since    1.0.0
	 * @param    object|null    $entity    Entity object for editing, null for adding
	 */
	private function display_form( $entity = null ) {
		$is_edit = ! empty( $entity );
		$form_title = $is_edit ? __( 'Edit Entity', METS_TEXT_DOMAIN ) : __( 'Add New Entity', METS_TEXT_DOMAIN );
		$submit_text = $is_edit ? __( 'Update Entity', METS_TEXT_DOMAIN ) : __( 'Add Entity', METS_TEXT_DOMAIN );

		// Get all entities for parent dropdown
		$all_entities = $this->entity_model->get_all( array( 'status' => 'all' ) );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $form_title ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( $is_edit ? 'update_entity' : 'create_entity', 'entity_nonce' ); ?>
				<?php if ( $is_edit ) : ?>
					<input type="hidden" name="entity_id" value="<?php echo esc_attr( $entity->id ); ?>">
					<input type="hidden" name="action" value="update">
				<?php else : ?>
					<input type="hidden" name="action" value="create">
				<?php endif; ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="entity_name"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<input name="entity_name" type="text" id="entity_name" value="<?php echo $is_edit ? esc_attr( $entity->name ) : ''; ?>" class="regular-text" required>
								<p class="description"><?php _e( 'The name of the entity.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="entity_slug"><?php _e( 'Slug', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input name="entity_slug" type="text" id="entity_slug" value="<?php echo $is_edit ? esc_attr( $entity->slug ) : ''; ?>" class="regular-text">
								<p class="description"><?php _e( 'The URL-friendly version of the name. Leave blank to auto-generate.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="entity_parent"><?php _e( 'Parent Entity', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select name="entity_parent" id="entity_parent">
									<option value=""><?php _e( 'None (Top Level)', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $all_entities as $parent_entity ) : ?>
										<?php if ( ! $is_edit || $parent_entity->id !== $entity->id ) : ?>
											<option value="<?php echo esc_attr( $parent_entity->id ); ?>" 
													<?php echo ( $is_edit && $entity->parent_id == $parent_entity->id ) ? 'selected' : ''; ?>>
												<?php echo esc_html( $parent_entity->name ); ?>
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php _e( 'Select a parent entity to create a hierarchy.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="entity_description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<textarea name="entity_description" id="entity_description" rows="5" cols="50" class="large-text"><?php echo $is_edit ? esc_textarea( $entity->description ) : ''; ?></textarea>
								<p class="description"><?php _e( 'A brief description of the entity.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="entity_status"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select name="entity_status" id="entity_status">
									<option value="active" <?php echo ( ! $is_edit || $entity->status === 'active' ) ? 'selected' : ''; ?>>
										<?php _e( 'Active', METS_TEXT_DOMAIN ); ?>
									</option>
									<option value="inactive" <?php echo ( $is_edit && $entity->status === 'inactive' ) ? 'selected' : ''; ?>>
										<?php _e( 'Inactive', METS_TEXT_DOMAIN ); ?>
									</option>
								</select>
								<p class="description"><?php _e( 'Inactive entities will not accept new tickets.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="entity_logo_url"><?php _e( 'Logo URL', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input name="entity_logo_url" type="url" id="entity_logo_url" value="<?php echo $is_edit ? esc_attr( $entity->logo_url ) : ''; ?>" class="regular-text">
								<p class="description"><?php _e( 'URL to the entity logo image.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="entity_primary_color"><?php _e( 'Primary Color', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input name="entity_primary_color" type="color" id="entity_primary_color" value="<?php echo $is_edit ? esc_attr( $entity->primary_color ) : '#007cba'; ?>">
								<p class="description"><?php _e( 'Primary brand color for this entity.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="entity_secondary_color"><?php _e( 'Secondary Color', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input name="entity_secondary_color" type="color" id="entity_secondary_color" value="<?php echo $is_edit ? esc_attr( $entity->secondary_color ) : '#f0f0f1'; ?>">
								<p class="description"><?php _e( 'Secondary brand color for this entity.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( $submit_text ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-entities' ) ); ?>" class="button button-secondary">
					<?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?>
				</a>
			</form>
		</div>
		<?php
	}
}