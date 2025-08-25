<?php
/**
 * User Roles & Permissions Management Interface
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * User Roles & Permissions functionality
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_User_Roles_Permissions {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_User_Roles_Permissions    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_User_Roles_Permissions    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_mets_update_user_role', array( $this, 'ajax_update_user_role' ) );
		add_action( 'wp_ajax_mets_get_role_details', array( $this, 'ajax_get_role_details' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @since    1.0.0
	 * @param    string    $hook    The current admin page hook
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'mets' ) === false ) {
			return;
		}

		wp_enqueue_script( 'mets-roles-permissions', METS_PLUGIN_URL . 'assets/js/mets-roles-permissions.js', array( 'jquery' ), METS_VERSION, true );
		wp_localize_script( 'mets-roles-permissions', 'metsRolesAjax', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'mets-roles-nonce' ),
			'strings' => array(
				'updateSuccess' => __( 'Role updated successfully!', METS_TEXT_DOMAIN ),
				'updateError' => __( 'Error updating role. Please try again.', METS_TEXT_DOMAIN ),
				'confirmChange' => __( 'Are you sure you want to change this user\'s role?', METS_TEXT_DOMAIN ),
			)
		) );
	}

	/**
	 * Display the main User Roles & Permissions page
	 *
	 * @since    1.0.0
	 */
	public function display_roles_permissions_page() {
		$role_manager = METS_Role_Manager::get_instance();
		$roles = $role_manager->get_roles();
		?>
		<div class="wrap mets-roles-permissions-wrap">
			<div class="mets-header">
				<h1 class="mets-page-title">
					<i class="dashicons dashicons-admin-users"></i>
					<?php _e( 'Team Management', METS_TEXT_DOMAIN ); ?>
				</h1>
				<p class="mets-page-description">
					<?php _e( 'Manage user roles, permissions, and team members for the Multi-Entity Ticket System. Overview of all ticket system roles and their primary functions, ordered from highest to lowest access level. Control who can access different features and manage your support team.', METS_TEXT_DOMAIN ); ?>
				</p>
			</div>

			<div class="mets-content-area">
				<!-- Section 1: Available Roles -->
				<?php $this->display_roles_overview( $roles ); ?>
				
				<!-- Section 2: Current Team Members -->
				<?php $this->display_agent_management_section(); ?>
				
				<!-- Section 3: Quick Actions -->
				<?php $this->display_quick_actions_section(); ?>
			</div>
		</div>

		<?php $this->output_styles(); ?>
		<?php
	}

	/**
	 * Display roles overview section
	 *
	 * @since    1.0.0
	 * @param    array    $roles    Available roles
	 */
	private function display_roles_overview( $roles ) {
		// Order roles by hierarchy (highest to lowest)
		$role_order = array( 'support_supervisor', 'ticket_manager', 'senior_agent', 'ticket_agent' );
		$ordered_roles = array();
		
		foreach ( $role_order as $role_key ) {
			if ( isset( $roles[ $role_key ] ) ) {
				$ordered_roles[ $role_key ] = $roles[ $role_key ];
			}
		}
		?>
		<div class="mets-roles-overview">
			<div class="mets-roles-unified-column">
				<div class="mets-roles-list">
					<?php foreach ( $ordered_roles as $role_key => $role_data ) : ?>
						<div class="mets-role-item role-<?php echo esc_attr( $role_key ); ?>" data-role="<?php echo esc_attr( $role_key ); ?>">
							<div class="role-header">
								<div class="role-icon role-icon-<?php echo esc_attr( $role_key ); ?>">
									<?php echo $this->get_role_icon( $role_key ); ?>
								</div>
								<div class="role-info">
									<h4 class="role-title"><?php echo esc_html( $role_data['display_name'] ); ?></h4>
									<div class="role-hierarchy-badge">
										<?php echo $this->get_role_hierarchy_badge( $role_key ); ?>
									</div>
								</div>
								<div class="role-stats">
									<span class="capability-count" title="<?php _e( 'Permissions', METS_TEXT_DOMAIN ); ?>">
										<?php echo count( $role_data['capabilities'] ); ?> <small><?php _e( 'perms', METS_TEXT_DOMAIN ); ?></small>
									</span>
									<span class="users-count" title="<?php _e( 'Users', METS_TEXT_DOMAIN ); ?>">
										<?php echo $this->get_role_user_count( $role_key ); ?> <small><?php _e( 'users', METS_TEXT_DOMAIN ); ?></small>
									</span>
								</div>
							</div>

							<div class="role-description">
								<p><?php echo esc_html( $role_data['description'] ); ?></p>
							</div>

							<div class="role-actions">
								<a href="<?php echo admin_url( 'user-new.php?mets_role=' . $role_key ); ?>" class="button button-primary add-user-role" data-role="<?php echo esc_attr( $role_key ); ?>">
									<i class="dashicons dashicons-plus-alt"></i>
									<?php printf( __( 'Add user with %s privileges', METS_TEXT_DOMAIN ), esc_html( $role_data['display_name'] ) ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display user management section
	 *
	 * @since    1.0.0
	 */
	private function display_user_management() {
		$users = $this->get_mets_users();
		?>
		<div class="mets-user-management">
			<div class="mets-section-header">
				<h2><?php _e( 'User Role Management', METS_TEXT_DOMAIN ); ?></h2>
				<p><?php _e( 'Assign and manage roles for users in your ticket system.', METS_TEXT_DOMAIN ); ?></p>
			</div>

			<div class="mets-users-filter">
				<label for="role-filter"><?php _e( 'Filter by Role:', METS_TEXT_DOMAIN ); ?></label>
				<select id="role-filter" class="regular-text">
					<option value=""><?php _e( 'All Roles', METS_TEXT_DOMAIN ); ?></option>
					<?php
					$role_manager = METS_Role_Manager::get_instance();
					$roles = $role_manager->get_roles();
					foreach ( $roles as $role_key => $role_data ) :
					?>
						<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_data['display_name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="mets-users-table-container">
				<table class="wp-list-table widefat fixed striped mets-users-table">
					<thead>
						<tr>
							<th class="user-avatar"><?php _e( 'User', METS_TEXT_DOMAIN ); ?></th>
							<th class="user-name"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?></th>
							<th class="user-email"><?php _e( 'Email', METS_TEXT_DOMAIN ); ?></th>
							<th class="user-role"><?php _e( 'Current Role', METS_TEXT_DOMAIN ); ?></th>
							<th class="user-entities"><?php _e( 'Assigned Entities', METS_TEXT_DOMAIN ); ?></th>
							<th class="user-actions"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $users as $user ) : ?>
							<tr data-user-id="<?php echo esc_attr( $user->ID ); ?>" data-user-role="<?php echo esc_attr( $this->get_user_mets_role( $user ) ); ?>">
								<td class="user-avatar">
									<?php echo get_avatar( $user->ID, 40 ); ?>
								</td>
								<td class="user-name">
									<strong><?php echo esc_html( $user->display_name ); ?></strong>
									<br><small><?php echo esc_html( $user->user_login ); ?></small>
								</td>
								<td class="user-email">
									<?php echo esc_html( $user->user_email ); ?>
								</td>
								<td class="user-role">
									<?php
									$current_role = $this->get_user_mets_role( $user );
									if ( $current_role ) :
										$role_data = $roles[ $current_role ];
									?>
										<span class="role-badge role-<?php echo esc_attr( $current_role ); ?>">
											<?php echo $this->get_role_icon( $current_role ); ?>
											<?php echo esc_html( $role_data['display_name'] ); ?>
										</span>
									<?php else : ?>
										<span class="role-badge role-none">
											<i class="dashicons dashicons-minus"></i>
											<?php _e( 'No METS Role', METS_TEXT_DOMAIN ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td class="user-entities">
									<?php
									$entities = $this->get_user_assigned_entities( $user->ID );
									if ( ! empty( $entities ) ) :
										echo '<ul class="entity-list">';
										foreach ( $entities as $entity ) :
											echo '<li>' . esc_html( $entity ) . '</li>';
										endforeach;
										echo '</ul>';
									else :
										echo '<span class="no-entities">' . __( 'No entities assigned', METS_TEXT_DOMAIN ) . '</span>';
									endif;
									?>
								</td>
								<td class="user-actions">
									<button type="button" class="button button-primary change-user-role" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
										<i class="dashicons dashicons-edit"></i>
										<?php _e( 'Change Role', METS_TEXT_DOMAIN ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Display permissions matrix
	 *
	 * @since    1.0.0
	 * @param    array    $roles    Available roles
	 */
	private function display_permissions_matrix( $roles ) {
		// Get all unique capabilities across all roles
		$all_capabilities = array();
		foreach ( $roles as $role_data ) {
			$all_capabilities = array_merge( $all_capabilities, array_keys( $role_data['capabilities'] ) );
		}
		$all_capabilities = array_unique( $all_capabilities );
		
		// Group capabilities by category
		$capability_groups = $this->group_capabilities_by_category( $all_capabilities );
		?>
		<div class="mets-permissions-matrix">
			<div class="mets-section-header">
				<h2><?php _e( 'Permissions Matrix', METS_TEXT_DOMAIN ); ?></h2>
				<p><?php _e( 'Detailed breakdown of permissions for each role across different system areas.', METS_TEXT_DOMAIN ); ?></p>
			</div>

			<?php foreach ( $capability_groups as $group_name => $capabilities ) : ?>
				<div class="capability-group">
					<h3 class="group-title">
						<?php echo esc_html( $this->get_capability_group_title( $group_name ) ); ?>
						<span class="group-description"><?php echo esc_html( $this->get_capability_group_description( $group_name ) ); ?></span>
					</h3>
					
					<div class="matrix-table-container">
						<table class="matrix-table">
							<thead>
								<tr>
									<th class="capability-name"><?php _e( 'Permission', METS_TEXT_DOMAIN ); ?></th>
									<?php foreach ( $roles as $role_key => $role_data ) : ?>
										<th class="role-column" title="<?php echo esc_attr( $role_data['display_name'] ); ?>">
											<?php echo $this->get_role_icon( $role_key ); ?>
											<span class="role-name"><?php echo esc_html( $role_data['display_name'] ); ?></span>
										</th>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $capabilities as $capability ) : ?>
									<tr>
										<td class="capability-name">
											<span class="capability-title"><?php echo esc_html( $this->get_capability_display_name( $capability ) ); ?></span>
											<div class="capability-description"><?php echo esc_html( $this->get_capability_description( $capability ) ); ?></div>
										</td>
										<?php foreach ( $roles as $role_key => $role_data ) : ?>
											<td class="permission-cell">
												<?php if ( isset( $role_data['capabilities'][ $capability ] ) && $role_data['capabilities'][ $capability ] ) : ?>
													<span class="permission-granted" title="<?php _e( 'Permission granted', METS_TEXT_DOMAIN ); ?>">
														<i class="dashicons dashicons-yes"></i>
													</span>
												<?php else : ?>
													<span class="permission-denied" title="<?php _e( 'Permission denied', METS_TEXT_DOMAIN ); ?>">
														<i class="dashicons dashicons-no"></i>
													</span>
												<?php endif; ?>
											</td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Display user guide section
	 *
	 * @since    1.0.0
	 */
	private function display_user_guide() {
		?>
		<div class="mets-user-guide">
			<div class="mets-section-header">
				<h2><?php _e( 'User Roles & Permissions Guide', METS_TEXT_DOMAIN ); ?></h2>
				<p><?php _e( 'Complete guide to understanding and managing user roles and permissions in the ticket system.', METS_TEXT_DOMAIN ); ?></p>
			</div>

			<div class="guide-content">
				<!-- Quick Start -->
				<div class="guide-section">
					<h3><i class="dashicons dashicons-controls-play"></i><?php _e( 'Quick Start', METS_TEXT_DOMAIN ); ?></h3>
					<div class="guide-steps">
						<div class="step">
							<div class="step-number">1</div>
							<div class="step-content">
								<h4><?php _e( 'Understand Role Hierarchy', METS_TEXT_DOMAIN ); ?></h4>
								<p><?php _e( 'Start by familiarizing yourself with the different roles available in the system and their hierarchy.', METS_TEXT_DOMAIN ); ?></p>
							</div>
						</div>
						<div class="step">
							<div class="step-number">2</div>
							<div class="step-content">
								<h4><?php _e( 'Assign Initial Roles', METS_TEXT_DOMAIN ); ?></h4>
								<p><?php _e( 'Go to the User Management tab to assign appropriate roles to your team members.', METS_TEXT_DOMAIN ); ?></p>
							</div>
						</div>
						<div class="step">
							<div class="step-number">3</div>
							<div class="step-content">
								<h4><?php _e( 'Review Permissions', METS_TEXT_DOMAIN ); ?></h4>
								<p><?php _e( 'Use the Permissions Matrix to understand what each role can and cannot do.', METS_TEXT_DOMAIN ); ?></p>
							</div>
						</div>
						<div class="step">
							<div class="step-number">4</div>
							<div class="step-content">
								<h4><?php _e( 'Test and Adjust', METS_TEXT_DOMAIN ); ?></h4>
								<p><?php _e( 'Have users test their access and adjust roles as needed for your organization.', METS_TEXT_DOMAIN ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Role Descriptions -->
				<div class="guide-section">
					<h3><i class="dashicons dashicons-groups"></i><?php _e( 'Detailed Role Descriptions', METS_TEXT_DOMAIN ); ?></h3>
					<?php $this->display_detailed_role_descriptions(); ?>
				</div>

				<!-- Best Practices -->
				<div class="guide-section">
					<h3><i class="dashicons dashicons-lightbulb"></i><?php _e( 'Best Practices', METS_TEXT_DOMAIN ); ?></h3>
					<div class="best-practices">
						<div class="practice-item">
							<h4><?php _e( 'Principle of Least Privilege', METS_TEXT_DOMAIN ); ?></h4>
							<p><?php _e( 'Always assign the minimum level of access required for a user to perform their job functions effectively.', METS_TEXT_DOMAIN ); ?></p>
						</div>
						<div class="practice-item">
							<h4><?php _e( 'Regular Access Reviews', METS_TEXT_DOMAIN ); ?></h4>
							<p><?php _e( 'Periodically review user roles and permissions to ensure they still align with current job responsibilities.', METS_TEXT_DOMAIN ); ?></p>
						</div>
						<div class="practice-item">
							<h4><?php _e( 'Clear Role Documentation', METS_TEXT_DOMAIN ); ?></h4>
							<p><?php _e( 'Maintain clear documentation of what each role can do and when to assign specific roles to users.', METS_TEXT_DOMAIN ); ?></p>
						</div>
						<div class="practice-item">
							<h4><?php _e( 'Gradual Privilege Escalation', METS_TEXT_DOMAIN ); ?></h4>
							<p><?php _e( 'Start new users with basic roles and gradually increase permissions as they demonstrate competency and need.', METS_TEXT_DOMAIN ); ?></p>
						</div>
					</div>
				</div>

				<!-- FAQ -->
				<div class="guide-section">
					<h3><i class="dashicons dashicons-editor-help"></i><?php _e( 'Frequently Asked Questions', METS_TEXT_DOMAIN ); ?></h3>
					<?php $this->display_faq_section(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get role icon
	 *
	 * @since    1.0.0
	 * @param    string    $role_key    Role key
	 * @return   string                 HTML icon
	 */
	private function get_role_icon( $role_key ) {
		$icons = array(
			'ticket_agent' => '<i class="dashicons dashicons-businessman"></i>',
			'senior_agent' => '<i class="dashicons dashicons-groups"></i>',
			'ticket_manager' => '<i class="dashicons dashicons-admin-users"></i>',
			'support_supervisor' => '<i class="dashicons dashicons-admin-generic"></i>'
		);
		
		return isset( $icons[ $role_key ] ) ? $icons[ $role_key ] : '<i class="dashicons dashicons-admin-users"></i>';
	}

	/**
	 * Get role hierarchy badge
	 *
	 * @since    1.0.0
	 * @param    string    $role_key    Role key
	 * @return   string                 HTML badge
	 */
	private function get_role_hierarchy_badge( $role_key ) {
		$hierarchy = array(
			'ticket_agent' => array( 'level' => 1, 'label' => __( 'Entry Level', METS_TEXT_DOMAIN ) ),
			'senior_agent' => array( 'level' => 2, 'label' => __( 'Experienced', METS_TEXT_DOMAIN ) ),
			'ticket_manager' => array( 'level' => 3, 'label' => __( 'Management', METS_TEXT_DOMAIN ) ),
			'support_supervisor' => array( 'level' => 4, 'label' => __( 'Executive', METS_TEXT_DOMAIN ) )
		);
		
		if ( isset( $hierarchy[ $role_key ] ) ) {
			$data = $hierarchy[ $role_key ];
			return sprintf( 
				'<span class="hierarchy-badge level-%d">%s</span>',
				$data['level'],
				esc_html( $data['label'] )
			);
		}
		
		return '';
	}

	/**
	 * Get role user count
	 *
	 * @since    1.0.0
	 * @param    string    $role_key    Role key
	 * @return   int                    Number of users with this role
	 */
	private function get_role_user_count( $role_key ) {
		$users = get_users( array( 'role' => $role_key ) );
		return count( $users );
	}

	/**
	 * Display role hierarchy diagram
	 *
	 * @since    1.0.0
	 */
	private function display_role_hierarchy() {
		?>
		<div class="mets-hierarchy-diagram">
			<div class="hierarchy-level level-4">
				<div class="hierarchy-item">
					<div class="hierarchy-icon">
						<i class="dashicons dashicons-admin-generic"></i>
					</div>
					<div class="hierarchy-label">
						<strong><?php _e( 'Support Supervisor', METS_TEXT_DOMAIN ); ?></strong>
						<small><?php _e( 'System-wide access', METS_TEXT_DOMAIN ); ?></small>
					</div>
				</div>
			</div>
			<div class="hierarchy-connector"></div>
			<div class="hierarchy-level level-3">
				<div class="hierarchy-item">
					<div class="hierarchy-icon">
						<i class="dashicons dashicons-admin-users"></i>
					</div>
					<div class="hierarchy-label">
						<strong><?php _e( 'Ticket Manager', METS_TEXT_DOMAIN ); ?></strong>
						<small><?php _e( 'Entity management', METS_TEXT_DOMAIN ); ?></small>
					</div>
				</div>
			</div>
			<div class="hierarchy-connector"></div>
			<div class="hierarchy-level level-2">
				<div class="hierarchy-item">
					<div class="hierarchy-icon">
						<i class="dashicons dashicons-groups"></i>
					</div>
					<div class="hierarchy-label">
						<strong><?php _e( 'Senior Agent', METS_TEXT_DOMAIN ); ?></strong>
						<small><?php _e( 'Advanced permissions', METS_TEXT_DOMAIN ); ?></small>
					</div>
				</div>
			</div>
			<div class="hierarchy-connector"></div>
			<div class="hierarchy-level level-1">
				<div class="hierarchy-item">
					<div class="hierarchy-icon">
						<i class="dashicons dashicons-businessman"></i>
					</div>
					<div class="hierarchy-label">
						<strong><?php _e( 'Ticket Agent', METS_TEXT_DOMAIN ); ?></strong>
						<small><?php _e( 'Core functions', METS_TEXT_DOMAIN ); ?></small>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Group capabilities by category
	 *
	 * @since    1.0.0
	 * @param    array    $capabilities    All capabilities
	 * @return   array                     Grouped capabilities
	 */
	private function group_capabilities_by_category( $capabilities ) {
		$groups = array(
			'tickets' => array(),
			'customers' => array(),
			'entities' => array(),
			'knowledge_base' => array(),
			'reports' => array(),
			'management' => array(),
			'system' => array()
		);
		
		foreach ( $capabilities as $capability ) {
			if ( strpos( $capability, 'ticket' ) !== false ) {
				$groups['tickets'][] = $capability;
			} elseif ( strpos( $capability, 'customer' ) !== false ) {
				$groups['customers'][] = $capability;
			} elseif ( strpos( $capability, 'entity' ) !== false || strpos( $capability, 'entities' ) !== false ) {
				$groups['entities'][] = $capability;
			} elseif ( strpos( $capability, 'kb' ) !== false || strpos( $capability, 'knowledge' ) !== false ) {
				$groups['knowledge_base'][] = $capability;
			} elseif ( strpos( $capability, 'report' ) !== false || strpos( $capability, 'time' ) !== false ) {
				$groups['reports'][] = $capability;
			} elseif ( strpos( $capability, 'manage' ) !== false || strpos( $capability, 'assign' ) !== false ) {
				$groups['management'][] = $capability;
			} else {
				$groups['system'][] = $capability;
			}
		}
		
		return array_filter( $groups );
	}

	/**
	 * Get capability group title
	 *
	 * @since    1.0.0
	 * @param    string    $group_key    Group key
	 * @return   string                  Group title
	 */
	private function get_capability_group_title( $group_key ) {
		$titles = array(
			'tickets' => __( 'Ticket Management', METS_TEXT_DOMAIN ),
			'customers' => __( 'Customer Management', METS_TEXT_DOMAIN ),
			'entities' => __( 'Entity Management', METS_TEXT_DOMAIN ),
			'knowledge_base' => __( 'Knowledge Base', METS_TEXT_DOMAIN ),
			'reports' => __( 'Reports & Analytics', METS_TEXT_DOMAIN ),
			'management' => __( 'User & System Management', METS_TEXT_DOMAIN ),
			'system' => __( 'System Administration', METS_TEXT_DOMAIN )
		);
		
		return isset( $titles[ $group_key ] ) ? $titles[ $group_key ] : ucfirst( str_replace( '_', ' ', $group_key ) );
	}

	/**
	 * Get capability group description
	 *
	 * @since    1.0.0
	 * @param    string    $group_key    Group key
	 * @return   string                  Group description
	 */
	private function get_capability_group_description( $group_key ) {
		$descriptions = array(
			'tickets' => __( 'Permissions related to creating, viewing, editing, and managing support tickets.', METS_TEXT_DOMAIN ),
			'customers' => __( 'Permissions for viewing and managing customer information and interactions.', METS_TEXT_DOMAIN ),
			'entities' => __( 'Permissions for managing organizational entities and their assignments.', METS_TEXT_DOMAIN ),
			'knowledge_base' => __( 'Permissions for creating, editing, and managing knowledge base articles.', METS_TEXT_DOMAIN ),
			'reports' => __( 'Permissions for viewing reports, analytics, and time tracking information.', METS_TEXT_DOMAIN ),
			'management' => __( 'Permissions for managing users, agents, and organizational structures.', METS_TEXT_DOMAIN ),
			'system' => __( 'High-level system administration and configuration permissions.', METS_TEXT_DOMAIN )
		);
		
		return isset( $descriptions[ $group_key ] ) ? $descriptions[ $group_key ] : '';
	}

	/**
	 * Get capability display name
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Capability key
	 * @return   string                   Display name
	 */
	private function get_capability_display_name( $capability ) {
		// Convert capability key to readable format
		$name = str_replace( '_', ' ', $capability );
		$name = ucwords( $name );
		return $name;
	}

	/**
	 * Get capability description
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Capability key
	 * @return   string                   Description
	 */
	private function get_capability_description( $capability ) {
		$descriptions = array(
			'view_tickets' => __( 'Can view tickets assigned to them or their entities', METS_TEXT_DOMAIN ),
			'edit_assigned_tickets' => __( 'Can edit tickets that are assigned to them', METS_TEXT_DOMAIN ),
			'edit_any_ticket' => __( 'Can edit any ticket in the system', METS_TEXT_DOMAIN ),
			'reply_to_tickets' => __( 'Can add replies to tickets', METS_TEXT_DOMAIN ),
			'change_ticket_status' => __( 'Can change the status of tickets', METS_TEXT_DOMAIN ),
			'assign_tickets_to_self' => __( 'Can assign tickets to themselves', METS_TEXT_DOMAIN ),
			'reassign_tickets' => __( 'Can reassign tickets to other agents', METS_TEXT_DOMAIN ),
			'escalate_tickets' => __( 'Can escalate tickets to higher priority', METS_TEXT_DOMAIN ),
			'merge_tickets' => __( 'Can merge multiple tickets together', METS_TEXT_DOMAIN ),
			'close_tickets' => __( 'Can close resolved tickets', METS_TEXT_DOMAIN ),
			'reopen_tickets' => __( 'Can reopen closed tickets', METS_TEXT_DOMAIN ),
			'view_customers' => __( 'Can view customer information', METS_TEXT_DOMAIN ),
			'edit_customer_details' => __( 'Can modify customer information', METS_TEXT_DOMAIN ),
			'manage_entities' => __( 'Can create, edit, and manage organizational entities', METS_TEXT_DOMAIN ),
			'view_kb_articles' => __( 'Can view knowledge base articles', METS_TEXT_DOMAIN ),
			'create_kb_articles' => __( 'Can create new knowledge base articles', METS_TEXT_DOMAIN ),
			'edit_kb_articles' => __( 'Can edit any knowledge base article', METS_TEXT_DOMAIN ),
			'manage_agents' => __( 'Can manage agent accounts and assignments', METS_TEXT_DOMAIN ),
			'view_reports' => __( 'Can view system reports and analytics', METS_TEXT_DOMAIN ),
			'manage_ticket_system' => __( 'Can configure system-wide ticket settings', METS_TEXT_DOMAIN )
		);
		
		return isset( $descriptions[ $capability ] ) ? $descriptions[ $capability ] : __( 'System permission', METS_TEXT_DOMAIN );
	}

	/**
	 * Get METS users
	 *
	 * @since    1.0.0
	 * @return   array    Users with METS roles
	 */
	private function get_mets_users() {
		$role_manager = METS_Role_Manager::get_instance();
		$mets_roles = array_keys( $role_manager->get_roles() );
		
		$users = array();
		foreach ( $mets_roles as $role ) {
			$role_users = get_users( array( 'role' => $role ) );
			$users = array_merge( $users, $role_users );
		}
		
		// Also get users who might have been assigned METS roles as secondary roles
		$all_users = get_users();
		foreach ( $all_users as $user ) {
			foreach ( $mets_roles as $role ) {
				if ( in_array( $role, $user->roles ) && ! in_array( $user, $users ) ) {
					$users[] = $user;
				}
			}
		}
		
		return $users;
	}

	/**
	 * Get user's METS role
	 *
	 * @since    1.0.0
	 * @param    WP_User    $user    User object
	 * @return   string|false       Role key or false
	 */
	private function get_user_mets_role( $user ) {
		$role_manager = METS_Role_Manager::get_instance();
		$mets_roles = array_keys( $role_manager->get_roles() );
		
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $mets_roles ) ) {
				return $role;
			}
		}
		
		return false;
	}

	/**
	 * Get user assigned entities
	 *
	 * @since    1.0.0
	 * @param    int      $user_id    User ID
	 * @return   array                Assigned entities
	 */
	private function get_user_assigned_entities( $user_id ) {
		// This would fetch from a user meta or dedicated table
		// For now, return empty array
		return array();
	}

	/**
	 * Display detailed role descriptions
	 *
	 * @since    1.0.0
	 */
	private function display_detailed_role_descriptions() {
		$role_manager = METS_Role_Manager::get_instance();
		$roles = $role_manager->get_roles();
		
		$detailed_descriptions = array(
			'ticket_agent' => array(
				'summary' => __( 'Front-line support agents who handle customer inquiries and basic ticket management.', METS_TEXT_DOMAIN ),
				'responsibilities' => array(
					__( 'Respond to customer tickets assigned to them', METS_TEXT_DOMAIN ),
					__( 'Update ticket status and add internal notes', METS_TEXT_DOMAIN ),
					__( 'Access and contribute to the knowledge base', METS_TEXT_DOMAIN ),
					__( 'Log time spent on tickets', METS_TEXT_DOMAIN ),
					__( 'View customer interaction history', METS_TEXT_DOMAIN )
				),
				'when_to_assign' => __( 'Assign to new support team members, part-time agents, or staff handling basic customer inquiries.', METS_TEXT_DOMAIN )
			),
			'senior_agent' => array(
				'summary' => __( 'Experienced agents with additional permissions for handling complex issues and mentoring junior staff.', METS_TEXT_DOMAIN ),
				'responsibilities' => array(
					__( 'Handle escalated and complex tickets', METS_TEXT_DOMAIN ),
					__( 'Reassign tickets between agents', METS_TEXT_DOMAIN ),
					__( 'Edit customer information when needed', METS_TEXT_DOMAIN ),
					__( 'Merge duplicate tickets', METS_TEXT_DOMAIN ),
					__( 'Review and publish knowledge base articles', METS_TEXT_DOMAIN ),
					__( 'Mentor junior agents', METS_TEXT_DOMAIN )
				),
				'when_to_assign' => __( 'Assign to experienced agents who handle complex issues, team leads, or senior staff members.', METS_TEXT_DOMAIN )
			),
			'ticket_manager' => array(
				'summary' => __( 'Middle management role with comprehensive ticket system oversight and entity management.', METS_TEXT_DOMAIN ),
				'responsibilities' => array(
					__( 'Manage agent assignments and workload', METS_TEXT_DOMAIN ),
					__( 'Oversee entity operations and assignments', METS_TEXT_DOMAIN ),
					__( 'Create and manage custom reports', METS_TEXT_DOMAIN ),
					__( 'Configure workflows and automation rules', METS_TEXT_DOMAIN ),
					__( 'Manage SLA policies and monitoring', METS_TEXT_DOMAIN ),
					__( 'Bulk operations on tickets and data', METS_TEXT_DOMAIN )
				),
				'when_to_assign' => __( 'Assign to department managers, team supervisors, or staff responsible for operational oversight.', METS_TEXT_DOMAIN )
			),
			'support_supervisor' => array(
				'summary' => __( 'Executive level role with system-wide access and configuration permissions.', METS_TEXT_DOMAIN ),
				'responsibilities' => array(
					__( 'Configure system-wide settings and integrations', METS_TEXT_DOMAIN ),
					__( 'Manage email templates and business hours', METS_TEXT_DOMAIN ),
					__( 'Access system logs and security settings', METS_TEXT_DOMAIN ),
					__( 'Import/export system data', METS_TEXT_DOMAIN ),
					__( 'Manage API access and third-party integrations', METS_TEXT_DOMAIN ),
					__( 'Oversight of all system operations', METS_TEXT_DOMAIN )
				),
				'when_to_assign' => __( 'Assign to senior management, IT administrators, or executives with system-wide responsibility.', METS_TEXT_DOMAIN )
			)
		);
		
		?>
		<div class="detailed-role-descriptions">
			<?php foreach ( $roles as $role_key => $role_data ) : ?>
				<?php if ( isset( $detailed_descriptions[ $role_key ] ) ) : ?>
					<div class="role-detail-card">
						<div class="role-detail-header">
							<div class="role-detail-icon">
								<?php echo $this->get_role_icon( $role_key ); ?>
							</div>
							<div class="role-detail-title">
								<h4><?php echo esc_html( $role_data['display_name'] ); ?></h4>
								<p><?php echo esc_html( $detailed_descriptions[ $role_key ]['summary'] ); ?></p>
							</div>
						</div>
						
						<div class="role-detail-content">
							<div class="responsibilities">
								<h5><?php _e( 'Key Responsibilities:', METS_TEXT_DOMAIN ); ?></h5>
								<ul>
									<?php foreach ( $detailed_descriptions[ $role_key ]['responsibilities'] as $responsibility ) : ?>
										<li><?php echo esc_html( $responsibility ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							
							<div class="assignment-guide">
								<h5><?php _e( 'When to Assign:', METS_TEXT_DOMAIN ); ?></h5>
								<p><?php echo esc_html( $detailed_descriptions[ $role_key ]['when_to_assign'] ); ?></p>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Display FAQ section
	 *
	 * @since    1.0.0
	 */
	private function display_faq_section() {
		$faqs = array(
			array(
				'question' => __( 'Can a user have multiple METS roles?', METS_TEXT_DOMAIN ),
				'answer' => __( 'No, each user should have only one METS role at a time. However, they can also have other WordPress roles alongside their METS role.', METS_TEXT_DOMAIN )
			),
			array(
				'question' => __( 'What happens when I change a user\'s role?', METS_TEXT_DOMAIN ),
				'answer' => __( 'The user\'s previous METS role is removed and the new role is assigned immediately. Their access permissions will change according to the new role.', METS_TEXT_DOMAIN )
			),
			array(
				'question' => __( 'Can I create custom roles?', METS_TEXT_DOMAIN ),
				'answer' => __( 'The system comes with pre-defined roles that cover most use cases. Custom role creation would require development work and is not available through the interface.', METS_TEXT_DOMAIN )
			),
			array(
				'question' => __( 'How do entity assignments work with roles?', METS_TEXT_DOMAIN ),
				'answer' => __( 'Entity assignments are separate from roles. A user\'s role determines what they can do, while entity assignments determine which tickets and data they can access.', METS_TEXT_DOMAIN )
			),
			array(
				'question' => __( 'What if I accidentally remove someone\'s role?', METS_TEXT_DOMAIN ),
				'answer' => __( 'You can immediately reassign their role through the User Management tab. Their previous entity assignments and settings will be preserved.', METS_TEXT_DOMAIN )
			)
		);
		
		?>
		<div class="faq-section">
			<?php foreach ( $faqs as $index => $faq ) : ?>
				<div class="faq-item">
					<h4 class="faq-question">
						<i class="dashicons dashicons-arrow-right-alt2"></i>
						<?php echo esc_html( $faq['question'] ); ?>
					</h4>
					<div class="faq-answer">
						<p><?php echo esc_html( $faq['answer'] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler to update user role
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_user_role() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mets-roles-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', METS_TEXT_DOMAIN ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to manage user roles.', METS_TEXT_DOMAIN ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$new_role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';

		if ( ! $user_id || ! $new_role ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', METS_TEXT_DOMAIN ) ) );
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'User not found.', METS_TEXT_DOMAIN ) ) );
		}

		// Remove existing METS roles
		$role_manager = METS_Role_Manager::get_instance();
		$mets_roles = array_keys( $role_manager->get_roles() );
		foreach ( $mets_roles as $role ) {
			$user->remove_role( $role );
		}

		// Add new role
		$user->add_role( $new_role );

		wp_send_json_success( array( 'message' => __( 'Role updated successfully!', METS_TEXT_DOMAIN ) ) );
	}

	/**
	 * AJAX handler to get role details
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_role_details() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mets-roles-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', METS_TEXT_DOMAIN ) ) );
		}

		$role_key = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( ! $role_key ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role.', METS_TEXT_DOMAIN ) ) );
		}

		$role_manager = METS_Role_Manager::get_instance();
		$roles = $role_manager->get_roles();
		
		if ( ! isset( $roles[ $role_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Role not found.', METS_TEXT_DOMAIN ) ) );
		}

		$role_data = $roles[ $role_key ];
		
		wp_send_json_success( array(
			'role' => $role_data,
			'capabilities_count' => count( $role_data['capabilities'] ),
			'users_count' => $this->get_role_user_count( $role_key )
		) );
	}

	/**
	 * Output styles for the interface
	 *
	 * @since    1.0.0
	 */
	private function output_styles() {
		?>
		<style>
		/* Main Layout */
		.mets-roles-permissions-wrap {
			background: #f1f1f1;
			margin: 20px 0 0 -20px;
			padding: 0;
		}
		
		.mets-header {
			background: #fff;
			padding: 30px;
			border-bottom: 1px solid #ddd;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		
		.mets-page-title {
			font-size: 28px;
			margin: 0 0 10px 0;
			color: #23282d;
		}
		
		.mets-page-title i {
			color: #0073aa;
			margin-right: 10px;
		}
		
		.mets-page-description {
			font-size: 14px;
			color: #666;
			margin: 0;
			line-height: 1.5;
		}
		
		.mets-content-area {
			background: #fff;
			margin: 0;
		}
		
		/* Navigation Tabs */
		.nav-tab-wrapper {
			border-bottom: 1px solid #ccd0d4;
			background: #f9f9f9;
			margin: 0;
			padding: 0 30px;
		}
		
		.nav-tab {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-size: 14px;
			padding: 12px 20px;
			margin: 0 5px 0 0;
		}
		
		.nav-tab i {
			font-size: 16px;
		}
		
		/* Tab Content */
		.tab-content {
			display: none;
			padding: 30px;
		}
		
		.tab-content.active {
			display: block;
		}
		
		/* Section Headers */
		.mets-section-header {
			margin-bottom: 30px;
			padding-bottom: 15px;
			border-bottom: 2px solid #0073aa;
		}
		
		.mets-section-header h2 {
			margin: 0 0 10px 0;
			color: #23282d;
			font-size: 24px;
		}
		
		.mets-section-header p {
			margin: 0;
			color: #666;
			font-size: 14px;
		}
		
		/* Unified Column Layout */
		.mets-roles-unified-column {
			max-width: 800px;
			margin: 0 auto;
		}
		
		/* Roles List */
		.mets-roles-list {
			display: flex;
			flex-direction: column;
			gap: 20px;
		}
		
		.mets-role-item {
			border: 1px solid #e0e0e0;
			border-radius: 8px;
			padding: 25px;
			background: #fff;
			transition: all 0.2s ease;
			box-shadow: 0 2px 8px rgba(0,0,0,0.05);
		}
		
		.mets-role-item:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 15px rgba(0,0,0,0.1);
		}
		
		/* Role-specific colors and styles */
		.mets-role-item.role-support_supervisor {
			border-left: 4px solid #d63384;
		}
		
		.mets-role-item.role-support_supervisor:hover {
			border-color: #d63384;
			box-shadow: 0 4px 15px rgba(214,51,132,0.15);
		}
		
		.mets-role-item.role-ticket_manager {
			border-left: 4px solid #fd7e14;
		}
		
		.mets-role-item.role-ticket_manager:hover {
			border-color: #fd7e14;
			box-shadow: 0 4px 15px rgba(253,126,20,0.15);
		}
		
		.mets-role-item.role-senior_agent {
			border-left: 4px solid #198754;
		}
		
		.mets-role-item.role-senior_agent:hover {
			border-color: #198754;
			box-shadow: 0 4px 15px rgba(25,135,84,0.15);
		}
		
		.mets-role-item.role-ticket_agent {
			border-left: 4px solid #0d6efd;
		}
		
		.mets-role-item.role-ticket_agent:hover {
			border-color: #0d6efd;
			box-shadow: 0 4px 15px rgba(13,110,253,0.15);
		}
		
		.mets-role-item .role-header {
			display: flex;
			align-items: center;
			margin-bottom: 15px;
			gap: 15px;
		}
		
		/* Role-specific icon colors */
		.mets-role-item .role-icon-support_supervisor {
			background: #d63384;
			color: #fff;
		}
		
		.mets-role-item .role-icon-ticket_manager {
			background: #fd7e14;
			color: #fff;
		}
		
		.mets-role-item .role-icon-senior_agent {
			background: #198754;
			color: #fff;
		}
		
		.mets-role-item .role-icon-ticket_agent {
			background: #0d6efd;
			color: #fff;
		}
		
		.mets-role-item .role-icon {
			width: 50px;
			height: 50px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}
		
		.mets-role-item .role-icon i {
			font-size: 20px;
		}
		
		.mets-role-item .role-info {
			flex: 1;
		}
		
		.mets-role-item .role-title {
			margin: 0 0 5px 0;
			font-size: 18px;
			color: #23282d;
			font-weight: 600;
		}
		
		.mets-role-item .role-hierarchy-badge {
			font-size: 12px;
			padding: 2px 8px;
			border-radius: 12px;
			font-weight: 500;
			text-transform: uppercase;
		}
		
		/* Role-specific badge colors */
		.role-support_supervisor .role-hierarchy-badge {
			background: rgba(214,51,132,0.1);
			color: #d63384;
		}
		
		.role-ticket_manager .role-hierarchy-badge {
			background: rgba(253,126,20,0.1);
			color: #fd7e14;
		}
		
		.role-senior_agent .role-hierarchy-badge {
			background: rgba(25,135,84,0.1);
			color: #198754;
		}
		
		.role-ticket_agent .role-hierarchy-badge {
			background: rgba(13,110,253,0.1);
			color: #0d6efd;
		}
		
		.mets-role-item .role-stats {
			display: flex;
			gap: 20px;
			font-size: 14px;
			color: #666;
		}
		
		.mets-role-item .role-stats .capability-count,
		.mets-role-item .role-stats .users-count {
			font-weight: 600;
			color: #0073aa;
			font-size: 16px;
		}
		
		.mets-role-item .role-description {
			margin-bottom: 20px;
		}
		
		.mets-role-item .role-description p {
			margin: 0;
			font-size: 14px;
			line-height: 1.5;
			color: #555;
		}
		
		.mets-role-item .role-actions {
			text-align: center;
		}
		
		.mets-role-item .add-user-role {
			padding: 8px 16px;
			font-size: 13px;
		}
		
		/* Roles Grid (Legacy - kept for compatibility) */
		.mets-roles-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
			gap: 25px;
			margin-bottom: 40px;
		}
		
		.mets-role-card {
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 25px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			transition: transform 0.2s, box-shadow 0.2s;
			position: relative;
		}
		
		.mets-role-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 15px rgba(0,0,0,0.15);
		}
		
		.role-header {
			display: flex;
			align-items: flex-start;
			margin-bottom: 20px;
		}
		
		.role-icon {
			background: #0073aa;
			color: #fff;
			width: 50px;
			height: 50px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 15px;
			flex-shrink: 0;
		}
		
		.role-icon i {
			font-size: 20px;
		}
		
		.role-info {
			flex: 1;
		}
		
		.role-title {
			margin: 0 0 8px 0;
			font-size: 18px;
			color: #23282d;
		}
		
		.role-description {
			margin: 0;
			font-size: 14px;
			color: #666;
			line-height: 1.5;
		}
		
		.role-details {
			display: flex;
			justify-content: space-between;
			margin: 20px 0;
			padding: 15px;
			background: #f8f9fa;
			border-radius: 6px;
		}
		
		.role-capabilities-count,
		.role-users-count {
			text-align: center;
		}
		
		.capability-count,
		.users-count {
			display: block;
			font-size: 24px;
			font-weight: bold;
			color: #0073aa;
			line-height: 1;
		}
		
		.capability-label,
		.users-label {
			display: block;
			font-size: 12px;
			color: #666;
			margin-top: 4px;
		}
		
		.role-actions {
			text-align: center;
			margin-bottom: 15px;
		}
		
		.view-role-details {
			display: inline-flex;
			align-items: center;
			gap: 6px;
		}
		
		.role-hierarchy-indicator {
			position: absolute;
			top: 15px;
			right: 15px;
		}
		
		.hierarchy-badge {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 12px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		
		.hierarchy-badge.level-1 {
			background: #e3f2fd;
			color: #1976d2;
		}
		
		.hierarchy-badge.level-2 {
			background: #e8f5e8;
			color: #388e3c;
		}
		
		.hierarchy-badge.level-3 {
			background: #fff3e0;
			color: #f57c00;
		}
		
		.hierarchy-badge.level-4 {
			background: #fce4ec;
			color: #c2185b;
		}
		
		/* Role Hierarchy Diagram */
		.mets-hierarchy-section {
			margin-top: 40px;
			padding-top: 30px;
			border-top: 1px solid #ddd;
		}
		
		.mets-hierarchy-section h3 {
			margin-bottom: 20px;
			color: #23282d;
		}
		
		.mets-hierarchy-diagram {
			display: flex;
			flex-direction: column;
			align-items: center;
			max-width: 400px;
			margin: 30px auto;
		}
		
		.hierarchy-level {
			width: 100%;
			margin: 10px 0;
		}
		
		.hierarchy-item {
			display: flex;
			align-items: center;
			padding: 15px 20px;
			background: #fff;
			border: 2px solid #ddd;
			border-radius: 8px;
			box-shadow: 0 2px 6px rgba(0,0,0,0.1);
		}
		
		.hierarchy-level.level-4 .hierarchy-item {
			border-color: #c2185b;
			background: #fce4ec;
		}
		
		.hierarchy-level.level-3 .hierarchy-item {
			border-color: #f57c00;
			background: #fff3e0;
		}
		
		.hierarchy-level.level-2 .hierarchy-item {
			border-color: #388e3c;
			background: #e8f5e8;
		}
		
		.hierarchy-level.level-1 .hierarchy-item {
			border-color: #1976d2;
			background: #e3f2fd;
		}
		
		.hierarchy-icon {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 15px;
			color: #fff;
		}
		
		.hierarchy-level.level-4 .hierarchy-icon {
			background: #c2185b;
		}
		
		.hierarchy-level.level-3 .hierarchy-icon {
			background: #f57c00;
		}
		
		.hierarchy-level.level-2 .hierarchy-icon {
			background: #388e3c;
		}
		
		.hierarchy-level.level-1 .hierarchy-icon {
			background: #1976d2;
		}
		
		.hierarchy-label strong {
			display: block;
			font-size: 16px;
			margin-bottom: 2px;
		}
		
		.hierarchy-label small {
			font-size: 12px;
			opacity: 0.8;
		}
		
		.hierarchy-connector {
			width: 2px;
			height: 20px;
			background: #ddd;
			margin: 5px 0;
		}
		
		/* User Management */
		.mets-users-filter {
			margin-bottom: 20px;
			padding: 15px;
			background: #f8f9fa;
			border-radius: 6px;
		}
		
		.mets-users-filter label {
			font-weight: 600;
			margin-right: 10px;
		}
		
		.mets-users-table-container {
			overflow-x: auto;
		}
		
		.mets-users-table {
			border: 1px solid #ddd;
		}
		
		.mets-users-table th {
			background: #f8f9fa;
			font-weight: 600;
			padding: 12px;
		}
		
		.mets-users-table td {
			padding: 12px;
			vertical-align: middle;
		}
		
		.role-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			border-radius: 15px;
			font-size: 12px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		
		.role-badge.role-ticket_agent {
			background: #e3f2fd;
			color: #1976d2;
		}
		
		.role-badge.role-senior_agent {
			background: #e8f5e8;
			color: #388e3c;
		}
		
		.role-badge.role-ticket_manager {
			background: #fff3e0;
			color: #f57c00;
		}
		
		.role-badge.role-support_supervisor {
			background: #fce4ec;
			color: #c2185b;
		}
		
		.role-badge.role-none {
			background: #f5f5f5;
			color: #666;
		}
		
		.entity-list {
			margin: 0;
			padding-left: 15px;
		}
		
		.entity-list li {
			font-size: 12px;
			margin-bottom: 2px;
		}
		
		.no-entities {
			font-size: 12px;
			color: #999;
			font-style: italic;
		}
		
		.change-user-role {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-size: 12px;
		}
		
		/* Permissions Matrix */
		.capability-group {
			margin-bottom: 40px;
		}
		
		.group-title {
			font-size: 18px;
			margin-bottom: 8px;
			color: #23282d;
			display: flex;
			flex-direction: column;
		}
		
		.group-description {
			font-size: 13px;
			font-weight: normal;
			color: #666;
			margin-top: 4px;
		}
		
		.matrix-table-container {
			overflow-x: auto;
			border: 1px solid #ddd;
			border-radius: 6px;
		}
		
		.matrix-table {
			width: 100%;
			border-collapse: collapse;
			background: #fff;
		}
		
		.matrix-table th {
			background: #f8f9fa;
			padding: 12px;
			text-align: left;
			border-bottom: 2px solid #ddd;
			font-weight: 600;
			white-space: nowrap;
		}
		
		.matrix-table td {
			padding: 12px;
			border-bottom: 1px solid #eee;
		}
		
		.capability-name {
			min-width: 250px;
		}
		
		.capability-title {
			font-weight: 600;
			color: #23282d;
			display: block;
			margin-bottom: 4px;
		}
		
		.capability-description {
			font-size: 12px;
			color: #666;
			line-height: 1.4;
		}
		
		.role-column {
			text-align: center;
			min-width: 120px;
		}
		
		.role-name {
			display: block;
			font-size: 11px;
			margin-top: 4px;
		}
		
		.permission-cell {
			text-align: center;
			width: 60px;
		}
		
		.permission-granted {
			color: #46b450;
			font-size: 18px;
		}
		
		.permission-denied {
			color: #dc3232;
			font-size: 18px;
		}
		
		/* User Guide */
		.guide-content {
			max-width: 900px;
		}
		
		.guide-section {
			margin-bottom: 40px;
			padding: 25px;
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 8px;
			box-shadow: 0 2px 6px rgba(0,0,0,0.05);
		}
		
		.guide-section h3 {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 20px;
			color: #23282d;
			font-size: 20px;
		}
		
		.guide-section h3 i {
			color: #0073aa;
		}
		
		.guide-steps {
			display: grid;
			gap: 20px;
		}
		
		.step {
			display: flex;
			align-items: flex-start;
			gap: 20px;
			padding: 20px;
			background: #f8f9fa;
			border-radius: 8px;
			border-left: 4px solid #0073aa;
		}
		
		.step-number {
			background: #0073aa;
			color: #fff;
			width: 30px;
			height: 30px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: bold;
			flex-shrink: 0;
		}
		
		.step-content h4 {
			margin: 0 0 8px 0;
			color: #23282d;
		}
		
		.step-content p {
			margin: 0;
			color: #666;
			line-height: 1.5;
		}
		
		.detailed-role-descriptions {
			display: grid;
			gap: 25px;
		}
		
		.role-detail-card {
			border: 1px solid #ddd;
			border-radius: 8px;
			overflow: hidden;
		}
		
		.role-detail-header {
			display: flex;
			align-items: flex-start;
			gap: 15px;
			padding: 20px;
			background: #f8f9fa;
			border-bottom: 1px solid #ddd;
		}
		
		.role-detail-icon {
			background: #0073aa;
			color: #fff;
			width: 50px;
			height: 50px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}
		
		.role-detail-title h4 {
			margin: 0 0 8px 0;
			font-size: 18px;
		}
		
		.role-detail-title p {
			margin: 0;
			color: #666;
			line-height: 1.5;
		}
		
		.role-detail-content {
			padding: 20px;
			display: grid;
			gap: 20px;
		}
		
		.responsibilities h5,
		.assignment-guide h5 {
			margin: 0 0 10px 0;
			color: #23282d;
			font-size: 14px;
			font-weight: 600;
		}
		
		.responsibilities ul {
			margin: 0;
			padding-left: 20px;
		}
		
		.responsibilities li {
			margin-bottom: 8px;
			line-height: 1.4;
		}
		
		.assignment-guide p {
			margin: 0;
			color: #666;
			line-height: 1.5;
		}
		
		.best-practices {
			display: grid;
			gap: 20px;
		}
		
		.practice-item {
			padding: 20px;
			background: #f8f9fa;
			border-radius: 8px;
			border-left: 4px solid #0073aa;
		}
		
		.practice-item h4 {
			margin: 0 0 10px 0;
			color: #23282d;
		}
		
		.practice-item p {
			margin: 0;
			color: #666;
			line-height: 1.5;
		}
		
		.faq-section {
			display: grid;
			gap: 15px;
		}
		
		.faq-item {
			border: 1px solid #ddd;
			border-radius: 6px;
			overflow: hidden;
		}
		
		.faq-question {
			margin: 0;
			padding: 15px 20px;
			background: #f8f9fa;
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: 10px;
			font-size: 14px;
			font-weight: 600;
			transition: background-color 0.2s;
		}
		
		.faq-question:hover {
			background: #e9ecef;
		}
		
		.faq-question i {
			transition: transform 0.2s;
		}
		
		.faq-item.open .faq-question i {
			transform: rotate(90deg);
		}
		
		.faq-answer {
			padding: 0 20px;
			max-height: 0;
			overflow: hidden;
			transition: max-height 0.3s, padding 0.3s;
		}
		
		.faq-item.open .faq-answer {
			padding: 15px 20px;
			max-height: 200px;
		}
		
		.faq-answer p {
			margin: 0;
			color: #666;
			line-height: 1.5;
		}
		
		/* Responsive Design */
		@media (max-width: 768px) {
			.mets-header {
				padding: 20px;
			}
			
			.tab-content {
				padding: 20px;
			}
			
			/* Unified Column Layout - Mobile */
			.mets-roles-unified-column {
				max-width: 100%;
				padding: 0 10px;
			}
			
			.mets-role-item {
				padding: 20px;
			}
			
			.mets-role-item .role-header {
				flex-wrap: wrap;
				gap: 10px;
			}
			
			.mets-role-item .role-icon {
				width: 40px;
				height: 40px;
			}
			
			.mets-role-item .role-icon i {
				font-size: 16px;
			}
			
			.mets-role-item .role-title {
				font-size: 16px;
			}
			
			.mets-role-item .role-stats {
				flex-direction: column;
				gap: 5px;
			}
			
			.mets-role-item .add-user-role {
				padding: 10px 16px;
				font-size: 12px;
			}
			
			/* Legacy Grid Layout - Mobile */
			.mets-roles-grid {
				grid-template-columns: 1fr;
			}
			
			.role-details {
				flex-direction: column;
				gap: 10px;
			}
			
			.matrix-table-container {
				font-size: 12px;
			}
			
			.step {
				flex-direction: column;
				text-align: center;
			}
		}
		
		/* Section Dividers & Headers */
		.mets-section-divider {
			height: 1px;
			background: linear-gradient(to right, transparent, #dcdcde, transparent);
			margin: 40px 0 30px 0;
		}
		
		.mets-section-header {
			margin-bottom: 25px;
		}
		
		.mets-section-header h2 {
			font-size: 20px;
			margin: 0 0 8px 0;
			color: #1d2327;
		}
		
		.mets-section-header h2 .dashicons {
			margin-right: 8px;
			color: #2271b1;
		}
		
		.mets-section-header p {
			margin: 0;
			color: #646970;
			font-size: 14px;
		}
		
		/* Agent Management Section */
		.mets-agent-management-embedded {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			overflow: hidden;
		}
		
		.mets-empty-state {
			text-align: center;
			padding: 60px 40px;
			color: #646970;
		}
		
		.mets-empty-icon {
			font-size: 48px;
			margin-bottom: 20px;
			opacity: 0.7;
		}
		
		.mets-empty-state h3 {
			font-size: 18px;
			margin: 0 0 15px 0;
			color: #1d2327;
		}
		
		.mets-empty-state > p {
			font-size: 14px;
			margin-bottom: 30px;
			max-width: 500px;
			margin-left: auto;
			margin-right: auto;
		}
		
		.mets-setup-guide {
			background: #f6f7f7;
			border-radius: 4px;
			padding: 20px;
			margin: 30px auto;
			max-width: 600px;
			text-align: left;
		}
		
		.mets-setup-guide h4 {
			margin: 0 0 15px 0;
			color: #1d2327;
			font-size: 14px;
		}
		
		.mets-setup-guide ol {
			margin: 0;
			padding-left: 20px;
		}
		
		.mets-setup-guide li {
			margin-bottom: 8px;
			font-size: 13px;
			line-height: 1.5;
		}
		
		.mets-empty-actions {
			display: flex;
			gap: 15px;
			justify-content: center;
			margin-top: 25px;
		}
		
		.mets-empty-actions .button {
			display: inline-flex;
			align-items: center;
			gap: 6px;
		}
		
		/* Agents Table */
		.mets-agents-table-container {
			overflow-x: auto;
		}
		
		.mets-agents-table {
			margin: 0;
		}
		
		.mets-agents-table th,
		.mets-agents-table td {
			padding: 12px 15px;
		}
		
		.mets-agents-table .agent-info {
			min-width: 200px;
		}
		
		.agent-details strong {
			display: block;
			font-weight: 600;
			color: #1d2327;
		}
		
		.agent-meta {
			font-size: 12px;
			color: #646970;
			margin-top: 2px;
		}
		
		.role-badge {
			display: inline-block;
			padding: 4px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		
		.role-badge.role-support_supervisor {
			background: #d63384;
			color: white;
		}
		
		.role-badge.role-ticket_manager {
			background: #fd7e14;
			color: white;
		}
		
		.role-badge.role-senior_agent {
			background: #198754;
			color: white;
		}
		
		.role-badge.role-ticket_agent {
			background: #0d6efd;
			color: white;
		}
		
		.status-indicator {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 12px;
			font-size: 11px;
			font-weight: 500;
			text-transform: capitalize;
		}
		
		.status-indicator.status-available {
			background: #d1e7dd;
			color: #0a3622;
		}
		
		.status-indicator.status-busy {
			background: #fff3cd;
			color: #664d03;
		}
		
		.status-indicator.status-away {
			background: #f8d7da;
			color: #58151c;
		}
		
		.status-indicator.status-offline {
			background: #e2e3e5;
			color: #41464b;
		}
		
		.status-message {
			font-size: 11px;
			color: #646970;
			margin-top: 2px;
			font-style: italic;
		}
		
		.workload-info {
			min-width: 80px;
		}
		
		.workload-numbers {
			display: block;
			font-size: 13px;
			font-weight: 600;
			margin-bottom: 4px;
		}
		
		.workload-bar {
			height: 6px;
			background: #e2e4e7;
			border-radius: 3px;
			overflow: hidden;
		}
		
		.workload-fill {
			height: 100%;
			background: #2271b1;
			transition: width 0.3s ease;
		}
		
		.performance-stats .stat {
			text-align: center;
		}
		
		.stat-value {
			display: block;
			font-size: 16px;
			font-weight: bold;
			color: #2271b1;
		}
		
		.stat-label {
			font-size: 11px;
			color: #646970;
			text-transform: lowercase;
		}
		
		/* Quick Actions Grid */
		.mets-quick-actions-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 20px;
			margin-top: 20px;
		}
		
		.mets-quick-action-card {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			padding: 20px;
			text-decoration: none;
			color: inherit;
			transition: all 0.2s ease;
			display: block;
		}
		
		.mets-quick-action-card:hover {
			border-color: #2271b1;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			transform: translateY(-1px);
			color: inherit;
			text-decoration: none;
		}
		
		.mets-quick-action-card .dashicons {
			font-size: 24px;
			color: #2271b1;
			margin-bottom: 10px;
			display: block;
		}
		
		.mets-quick-action-card h4 {
			margin: 0 0 8px 0;
			font-size: 16px;
			color: #1d2327;
		}
		
		.mets-quick-action-card p {
			margin: 0;
			font-size: 13px;
			color: #646970;
			line-height: 1.4;
		}
		
		.mets-action-bulk-roles,
		.mets-action-help {
			cursor: pointer;
		}
		
		.mets-action-bulk-roles:hover,
		.mets-action-help:hover {
			border-color: #8c8f94;
		}
		
		/* Responsive Design */
		@media (max-width: 1200px) {
			.mets-quick-actions-grid {
				grid-template-columns: repeat(2, 1fr);
			}
		}
		
		@media (max-width: 782px) {
			.mets-quick-actions-grid {
				grid-template-columns: 1fr;
			}
			
			.mets-agents-table-container {
				font-size: 12px;
			}
			
			.mets-agents-table th,
			.mets-agents-table td {
				padding: 8px 10px;
			}
			
			.mets-empty-actions {
				flex-direction: column;
				align-items: center;
			}
		}
		</style>
		<?php
	}

	/**
	 * Display agent management section
	 *
	 * @since    1.0.0
	 */
	private function display_agent_management_section() {
		?>
		<div class="mets-section-divider"></div>
		<div class="mets-section-header">
			<h2><i class="dashicons dashicons-groups"></i> <?php _e( 'Current Team Members', METS_TEXT_DOMAIN ); ?></h2>
			<p><?php _e( 'Manage existing agents and their assignments. View workloads, performance, and entity assignments.', METS_TEXT_DOMAIN ); ?></p>
		</div>
		
		<div class="mets-agent-management-embedded">
			<?php
			// Get users with METS roles
			$mets_roles = array( 'ticket_agent', 'senior_agent', 'ticket_manager', 'support_supervisor' );
			$users = get_users( array( 
				'role__in' => $mets_roles,
				'orderby' => 'display_name',
				'order' => 'ASC'
			) );
			
			if ( empty( $users ) ) {
				$this->display_empty_agent_state();
			} else {
				$this->display_agents_table( $users );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Display empty agent state
	 *
	 * @since    1.0.0
	 */
	private function display_empty_agent_state() {
		?>
		<div class="mets-empty-state">
			<div class="mets-empty-icon">👥</div>
			<h3><?php _e( 'No Team Members Found', METS_TEXT_DOMAIN ); ?></h3>
			<p><?php _e( 'Get started by creating your first support agent using the "Add user with role privileges" buttons above, or follow the setup guide below.', METS_TEXT_DOMAIN ); ?></p>
			
			<div class="mets-setup-guide">
				<h4><?php _e( 'Quick Setup Guide:', METS_TEXT_DOMAIN ); ?></h4>
				<ol>
					<li><?php _e( 'Click "Add user with [role] privileges" button above for the desired role level', METS_TEXT_DOMAIN ); ?></li>
					<li><?php _e( 'Or create a new WordPress user manually and assign them a METS role', METS_TEXT_DOMAIN ); ?></li>
					<li><?php _e( 'Configure their profile and entity assignments in the user management area', METS_TEXT_DOMAIN ); ?></li>
					<li><?php _e( 'Return here to see them listed as active team members', METS_TEXT_DOMAIN ); ?></li>
				</ol>
			</div>
			
			<div class="mets-empty-actions">
				<a href="<?php echo admin_url('user-new.php'); ?>" class="button button-primary">
					<i class="dashicons dashicons-plus-alt"></i>
					<?php _e( 'Add New User', METS_TEXT_DOMAIN ); ?>
				</a>
				<a href="<?php echo admin_url('users.php'); ?>" class="button">
					<i class="dashicons dashicons-admin-users"></i>
					<?php _e( 'Manage All Users', METS_TEXT_DOMAIN ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Display agents table
	 *
	 * @since    1.0.0
	 * @param    array    $users    Users with METS roles
	 */
	private function display_agents_table( $users ) {
		$role_manager = METS_Role_Manager::get_instance();
		?>
		<div class="mets-agents-table-container">
			<table class="mets-agents-table wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="agent-info"><?php _e( 'Agent', METS_TEXT_DOMAIN ); ?></th>
						<th class="role"><?php _e( 'Role', METS_TEXT_DOMAIN ); ?></th>
						<th class="status"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
						<th class="workload"><?php _e( 'Workload', METS_TEXT_DOMAIN ); ?></th>
						<th class="performance"><?php _e( 'This Week', METS_TEXT_DOMAIN ); ?></th>
						<th class="actions"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $users as $user ) : ?>
						<?php
						$user_roles = $user->roles;
						$mets_role = '';
						foreach ( $user_roles as $role ) {
							if ( in_array( $role, array( 'ticket_agent', 'senior_agent', 'ticket_manager', 'support_supervisor' ) ) ) {
								$mets_role = $role;
								break;
							}
						}
						
						$preferences = get_user_meta( $user->ID, 'mets_agent_preferences', true );
						if ( ! is_array( $preferences ) ) {
							$preferences = array(
								'status' => 'offline',
								'status_message' => '',
								'max_tickets' => 10
							);
						}
						
						$workload = $role_manager->get_agent_workload( $user->ID );
						$performance = $role_manager->get_agent_performance( $user->ID, 'week' );
						?>
						<tr>
							<td class="agent-info">
								<div class="agent-details">
									<strong><?php echo esc_html( $user->display_name ); ?></strong>
									<div class="agent-meta">
										<?php echo esc_html( $user->user_email ); ?>
									</div>
								</div>
							</td>
							<td class="role">
								<span class="role-badge role-<?php echo esc_attr( $mets_role ); ?>">
									<?php echo esc_html( ucwords( str_replace( '_', ' ', $mets_role ) ) ); ?>
								</span>
							</td>
							<td class="status">
								<span class="status-indicator status-<?php echo esc_attr( $preferences['status'] ); ?>">
									<?php echo esc_html( ucfirst( $preferences['status'] ) ); ?>
								</span>
								<?php if ( ! empty( $preferences['status_message'] ) ) : ?>
									<div class="status-message"><?php echo esc_html( $preferences['status_message'] ); ?></div>
								<?php endif; ?>
							</td>
							<td class="workload">
								<div class="workload-info">
									<span class="workload-numbers"><?php echo esc_html( $workload ); ?> / <?php echo esc_html( $preferences['max_tickets'] ); ?></span>
									<div class="workload-bar">
										<div class="workload-fill" style="width: <?php echo esc_attr( min( ( $workload / $preferences['max_tickets'] ) * 100, 100 ) ); ?>%"></div>
									</div>
								</div>
							</td>
							<td class="performance">
								<div class="performance-stats">
									<div class="stat">
										<span class="stat-value"><?php echo esc_html( $performance['resolved_tickets'] ); ?></span>
										<span class="stat-label"><?php _e( 'resolved', METS_TEXT_DOMAIN ); ?></span>
									</div>
								</div>
							</td>
							<td class="actions">
								<a href="<?php echo admin_url( 'user-edit.php?user_id=' . $user->ID ); ?>" class="button button-small">
									<?php _e( 'Edit', METS_TEXT_DOMAIN ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Display quick actions section
	 *
	 * @since    1.0.0
	 */
	private function display_quick_actions_section() {
		?>
		<div class="mets-section-divider"></div>
		<div class="mets-section-header">
			<h2><i class="dashicons dashicons-admin-tools"></i> <?php _e( 'Quick Actions', METS_TEXT_DOMAIN ); ?></h2>
			<p><?php _e( 'Common team management tasks and shortcuts for efficient user and role management.', METS_TEXT_DOMAIN ); ?></p>
		</div>
		
		<div class="mets-quick-actions-grid">
			<a href="<?php echo admin_url('user-new.php'); ?>" class="mets-quick-action-card">
				<i class="dashicons dashicons-plus-alt"></i>
				<h4><?php _e( 'Add New User', METS_TEXT_DOMAIN ); ?></h4>
				<p><?php _e( 'Create a new team member and assign roles', METS_TEXT_DOMAIN ); ?></p>
			</a>
			
			<a href="<?php echo admin_url('users.php'); ?>" class="mets-quick-action-card">
				<i class="dashicons dashicons-admin-users"></i>
				<h4><?php _e( 'Manage All Users', METS_TEXT_DOMAIN ); ?></h4>
				<p><?php _e( 'WordPress user management interface', METS_TEXT_DOMAIN ); ?></p>
			</a>
			
			<div class="mets-quick-action-card mets-action-bulk-roles" id="bulk-role-assignment">
				<i class="dashicons dashicons-groups"></i>
				<h4><?php _e( 'Bulk Role Assignment', METS_TEXT_DOMAIN ); ?></h4>
				<p><?php _e( 'Assign roles to multiple users at once', METS_TEXT_DOMAIN ); ?></p>
			</div>
			
			<a href="<?php echo admin_url('admin.php?page=mets-manager-dashboard'); ?>" class="mets-quick-action-card">
				<i class="dashicons dashicons-chart-area"></i>
				<h4><?php _e( 'Team Performance', METS_TEXT_DOMAIN ); ?></h4>
				<p><?php _e( 'View detailed team analytics and reports', METS_TEXT_DOMAIN ); ?></p>
			</a>
			
			<a href="<?php echo admin_url('admin.php?page=mets-entities'); ?>" class="mets-quick-action-card">
				<i class="dashicons dashicons-building"></i>
				<h4><?php _e( 'Entity Management', METS_TEXT_DOMAIN ); ?></h4>
				<p><?php _e( 'Manage organizational entities and assignments', METS_TEXT_DOMAIN ); ?></p>
			</a>
			
			<div class="mets-quick-action-card mets-action-help" id="team-help">
				<i class="dashicons dashicons-sos"></i>
				<h4><?php _e( 'Need Help?', METS_TEXT_DOMAIN ); ?></h4>
				<p><?php _e( 'Team management documentation and guides', METS_TEXT_DOMAIN ); ?></p>
			</div>
		</div>
		<?php
	}
}
?>