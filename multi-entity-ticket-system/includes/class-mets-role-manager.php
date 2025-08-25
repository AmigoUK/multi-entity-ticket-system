<?php
/**
 * Agent and Manager Role Management System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Role_Manager {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Role_Manager    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Role definitions with capabilities
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $role_definitions
	 */
	private $role_definitions = array();

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Role_Manager    Single instance
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
	private function __construct() {
		$this->define_roles();
		$this->init_hooks();
	}

	/**
	 * Define all METS roles and their capabilities
	 *
	 * @since    1.0.0
	 */
	private function define_roles() {
		$this->role_definitions = array(
			'ticket_agent' => array(
				'display_name' => __( 'Ticket Agent', METS_TEXT_DOMAIN ),
				'description' => __( 'Can handle assigned tickets, reply to customers, and access knowledge base.', METS_TEXT_DOMAIN ),
				'capabilities' => array(
					// Basic WordPress capabilities
					'read' => true,
					'edit_posts' => false,
					'delete_posts' => false,
					
					// Ticket capabilities
					'view_tickets' => true,
					'edit_assigned_tickets' => true,
					'reply_to_tickets' => true,
					'change_ticket_status' => true,
					'assign_tickets_to_self' => true,
					'view_ticket_notes' => true,
					'add_ticket_notes' => true,
					'upload_ticket_files' => true,
					'view_ticket_history' => true,
					
					// Customer capabilities
					'view_customers' => true,
					'edit_customer_details' => false,
					'view_customer_history' => true,
					
					// Entity capabilities
					'view_assigned_entities' => true,
					'view_entity_details' => true,
					
					// Knowledge base capabilities
					'view_kb_articles' => true,
					'create_kb_articles' => true,
					'edit_own_kb_articles' => true,
					'suggest_kb_articles' => true,
					'link_kb_articles' => true,
					
					// Reporting capabilities
					'view_own_reports' => true,
					'view_assigned_entity_reports' => true,
					
					// Time tracking
					'log_time' => true,
					'view_own_time_logs' => true
				)
			),
			
			'senior_agent' => array(
				'display_name' => __( 'Senior Agent', METS_TEXT_DOMAIN ),
				'description' => __( 'Experienced agent with additional permissions for complex tickets and mentoring.', METS_TEXT_DOMAIN ),
				'capabilities' => array(
					// Inherit all agent capabilities
					'view_tickets' => true,
					'edit_assigned_tickets' => true,
					'reply_to_tickets' => true,
					'change_ticket_status' => true,
					'assign_tickets_to_self' => true,
					'view_ticket_notes' => true,
					'add_ticket_notes' => true,
					'upload_ticket_files' => true,
					'view_ticket_history' => true,
					'view_customers' => true,
					'view_customer_history' => true,
					'view_assigned_entities' => true,
					'view_entity_details' => true,
					'view_kb_articles' => true,
					'create_kb_articles' => true,
					'edit_own_kb_articles' => true,
					'suggest_kb_articles' => true,
					'link_kb_articles' => true,
					'view_own_reports' => true,
					'view_assigned_entity_reports' => true,
					'log_time' => true,
					'view_own_time_logs' => true,
					
					// Additional senior capabilities
					'edit_any_ticket' => true,
					'reassign_tickets' => true,
					'escalate_tickets' => true,
					'view_all_entity_tickets' => true,
					'edit_customer_details' => true,
					'merge_tickets' => true,
					'close_tickets' => true,
					'reopen_tickets' => true,
					'view_internal_notes' => true,
					'add_internal_notes' => true,
					'review_kb_articles' => true,
					'edit_kb_articles' => true,
					'publish_kb_articles' => true,
					'view_team_reports' => true,
					'mentor_agents' => true
				)
			),
			
			'ticket_manager' => array(
				'display_name' => __( 'Ticket Manager', METS_TEXT_DOMAIN ),
				'description' => __( 'Manages ticket workflows, assigns agents, and oversees entity operations.', METS_TEXT_DOMAIN ),
				'capabilities' => array(
					// All senior agent capabilities
					'view_tickets' => true,
					'edit_assigned_tickets' => true,
					'edit_any_ticket' => true,
					'reply_to_tickets' => true,
					'change_ticket_status' => true,
					'assign_tickets_to_self' => true,
					'reassign_tickets' => true,
					'escalate_tickets' => true,
					'merge_tickets' => true,
					'close_tickets' => true,
					'reopen_tickets' => true,
					'view_ticket_notes' => true,
					'add_ticket_notes' => true,
					'view_internal_notes' => true,
					'add_internal_notes' => true,
					'upload_ticket_files' => true,
					'view_ticket_history' => true,
					'view_customers' => true,
					'edit_customer_details' => true,
					'view_customer_history' => true,
					'view_assigned_entities' => true,
					'view_all_entity_tickets' => true,
					'view_entity_details' => true,
					'view_kb_articles' => true,
					'create_kb_articles' => true,
					'edit_own_kb_articles' => true,
					'edit_kb_articles' => true,
					'review_kb_articles' => true,
					'publish_kb_articles' => true,
					'suggest_kb_articles' => true,
					'link_kb_articles' => true,
					'view_own_reports' => true,
					'view_assigned_entity_reports' => true,
					'view_team_reports' => true,
					'log_time' => true,
					'view_own_time_logs' => true,
					'mentor_agents' => true,
					
					// Manager-specific capabilities
					'manage_agents' => true,
					'assign_agents_to_entities' => true,
					'view_all_tickets' => true,
					'create_tickets' => true,
					'delete_tickets' => true,
					'bulk_update_tickets' => true,
					'manage_entities' => true,
					'create_entities' => true,
					'edit_entities' => true,
					'delete_entities' => true,
					'manage_entity_agents' => true,
					'view_all_customers' => true,
					'manage_customers' => true,
					'delete_customers' => true,
					'manage_kb_categories' => true,
					'delete_kb_articles' => true,
					'manage_kb_permissions' => true,
					'view_all_reports' => true,
					'create_custom_reports' => true,
					'export_reports' => true,
					'view_agent_performance' => true,
					'manage_sla_rules' => true,
					'view_all_time_logs' => true,
					'manage_time_tracking' => true,
					'configure_workflows' => true,
					'manage_automations' => true
				)
			),
			
			'support_supervisor' => array(
				'display_name' => __( 'Support Supervisor', METS_TEXT_DOMAIN ),
				'description' => __( 'Oversees multiple managers and has system-wide access.', METS_TEXT_DOMAIN ),
				'capabilities' => array(
					// All manager capabilities plus system-wide access
					'manage_ticket_system' => true,
					'view_system_reports' => true,
					'manage_system_settings' => true,
					'configure_integrations' => true,
					'manage_email_templates' => true,
					'manage_business_hours' => true,
					'view_system_logs' => true,
					'manage_security_settings' => true,
					'perform_bulk_operations' => true,
					'import_export_data' => true,
					'manage_api_access' => true,
					'configure_notifications' => true
				)
			)
		);
	}

	/**
	 * Initialize hooks
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'maybe_create_roles' ) );
		add_action( 'user_register', array( $this, 'assign_default_capabilities' ) );
		add_filter( 'user_has_cap', array( $this, 'check_entity_specific_capabilities' ), 10, 4 );
	}

	/**
	 * Create WordPress roles if they don't exist
	 *
	 * @since    1.0.0
	 */
	public function maybe_create_roles() {
		foreach ( $this->role_definitions as $role_name => $role_data ) {
			if ( ! get_role( $role_name ) ) {
				add_role( $role_name, $role_data['display_name'], $role_data['capabilities'] );
			} else {
				// Update existing role capabilities
				$role = get_role( $role_name );
				foreach ( $role_data['capabilities'] as $cap => $grant ) {
					if ( $grant ) {
						$role->add_cap( $cap );
					} else {
						$role->remove_cap( $cap );
					}
				}
			}
		}
	}

	/**
	 * Get all METS roles
	 *
	 * @since    1.0.0
	 * @return   array    Array of role definitions
	 */
	public function get_roles() {
		return $this->role_definitions;
	}

	/**
	 * Get role capabilities
	 *
	 * @since    1.0.0
	 * @param    string    $role_name    Role name
	 * @return   array                   Role capabilities
	 */
	public function get_role_capabilities( $role_name ) {
		return isset( $this->role_definitions[ $role_name ]['capabilities'] ) 
			? $this->role_definitions[ $role_name ]['capabilities'] 
			: array();
	}

	/**
	 * Check if user has specific METS capability
	 *
	 * @since    1.0.0
	 * @param    int       $user_id       User ID
	 * @param    string    $capability    Capability to check
	 * @param    int       $entity_id     Entity ID for entity-specific checks
	 * @return   bool                     Whether user has capability
	 */
	public function user_can( $user_id, $capability, $entity_id = null ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Check basic capability
		if ( ! $user->has_cap( $capability ) ) {
			return false;
		}

		// Check entity-specific permissions if entity_id provided
		if ( $entity_id && $this->is_entity_specific_capability( $capability ) ) {
			return $this->user_can_access_entity( $user_id, $entity_id );
		}

		return true;
	}

	/**
	 * Check if capability is entity-specific
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Capability name
	 * @return   bool                     Whether capability is entity-specific
	 */
	private function is_entity_specific_capability( $capability ) {
		$entity_specific_caps = array(
			'view_assigned_entities',
			'view_assigned_entity_reports',
			'view_all_entity_tickets',
			'edit_assigned_tickets'
		);

		return in_array( $capability, $entity_specific_caps );
	}

	/**
	 * Check if user can access specific entity
	 *
	 * @since    1.0.0
	 * @param    int    $user_id     User ID
	 * @param    int    $entity_id   Entity ID
	 * @return   bool                Whether user can access entity
	 */
	public function user_can_access_entity( $user_id, $entity_id ) {
		// Managers and supervisors can access all entities
		if ( user_can( $user_id, 'manage_entities' ) || user_can( $user_id, 'manage_ticket_system' ) ) {
			return true;
		}

		// Check if user is assigned to this entity
		$assigned_entities = $this->get_user_assigned_entities( $user_id );
		return in_array( $entity_id, $assigned_entities );
	}

	/**
	 * Get entities assigned to user
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 * @return   array              Array of entity IDs
	 */
	public function get_user_assigned_entities( $user_id ) {
		$assigned_entities = get_user_meta( $user_id, 'mets_assigned_entities', true );
		return is_array( $assigned_entities ) ? $assigned_entities : array();
	}

	/**
	 * Assign entities to user
	 *
	 * @since    1.0.0
	 * @param    int      $user_id      User ID
	 * @param    array    $entity_ids   Array of entity IDs
	 * @return   bool                   Success status
	 */
	public function assign_entities_to_user( $user_id, $entity_ids ) {
		$entity_ids = array_map( 'intval', (array) $entity_ids );
		return update_user_meta( $user_id, 'mets_assigned_entities', $entity_ids );
	}

	/**
	 * Get users by role
	 *
	 * @since    1.0.0
	 * @param    string    $role         Role name
	 * @param    array     $entity_ids   Filter by assigned entities
	 * @return   array                   Array of user objects
	 */
	public function get_users_by_role( $role, $entity_ids = array() ) {
		$args = array(
			'role' => $role,
			'meta_query' => array(),
			'fields' => 'all'
		);

		// Filter by entity assignment if specified
		if ( ! empty( $entity_ids ) ) {
			$args['meta_query'][] = array(
				'key' => 'mets_assigned_entities',
				'value' => $entity_ids,
				'compare' => 'IN'
			);
		}

		return get_users( $args );
	}

	/**
	 * Get available agents for assignment
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id    Entity ID
	 * @param    array    $exclude      User IDs to exclude
	 * @return   array                  Array of available agents
	 */
	public function get_available_agents( $entity_id = null, $exclude = array() ) {
		$agent_roles = array( 'ticket_agent', 'senior_agent', 'ticket_manager' );
		$available_agents = array();

		foreach ( $agent_roles as $role ) {
			$users = $this->get_users_by_role( $role, $entity_id ? array( $entity_id ) : array() );
			
			foreach ( $users as $user ) {
				if ( ! in_array( $user->ID, $exclude ) ) {
					$available_agents[] = array(
						'id' => $user->ID,
						'name' => $user->display_name,
						'email' => $user->user_email,
						'role' => $role,
						'workload' => $this->get_agent_workload( $user->ID )
					);
				}
			}
		}

		// Sort by workload (ascending)
		usort( $available_agents, function( $a, $b ) {
			return $a['workload'] - $b['workload'];
		});

		return $available_agents;
	}

	/**
	 * Get agent's current workload
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 * @return   int                Number of open tickets assigned
	 */
	public function get_agent_workload( $user_id ) {
		global $wpdb;

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE assigned_to = %d 
			AND status IN ('open', 'in_progress', 'waiting')",
			$user_id
		) );

		return (int) $count;
	}

	/**
	 * Get agent performance metrics
	 *
	 * @since    1.0.0
	 * @param    int       $user_id      User ID
	 * @param    string    $period       Time period (week, month, quarter, year)
	 * @return   array                   Performance metrics
	 */
	public function get_agent_performance( $user_id, $period = 'month' ) {
		global $wpdb;

		$date_intervals = array(
			'week' => '7 DAY',
			'month' => '30 DAY',
			'quarter' => '90 DAY',
			'year' => '365 DAY'
		);

		$interval = isset( $date_intervals[ $period ] ) ? $date_intervals[ $period ] : '30 DAY';

		// Get basic ticket metrics
		$metrics = array(
			'total_tickets' => 0,
			'resolved_tickets' => 0,
			'avg_resolution_time' => 0,
			'customer_satisfaction' => 0,
			'response_time' => 0,
			'workload_distribution' => array()
		);

		// Total tickets handled
		$metrics['total_tickets'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE assigned_to = %d 
			AND created_at > DATE_SUB(NOW(), INTERVAL {$interval})",
			$user_id
		) );

		// Resolved tickets
		$metrics['resolved_tickets'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE assigned_to = %d 
			AND status IN ('resolved', 'closed')
			AND updated_at > DATE_SUB(NOW(), INTERVAL {$interval})",
			$user_id
		) );

		// Average resolution time (in hours)
		$avg_resolution = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) 
			FROM {$wpdb->prefix}mets_tickets 
			WHERE assigned_to = %d 
			AND status IN ('resolved', 'closed')
			AND updated_at > DATE_SUB(NOW(), INTERVAL {$interval})",
			$user_id
		) );

		$metrics['avg_resolution_time'] = round( (float) $avg_resolution, 2 );

		// Calculate performance score
		$resolution_rate = $metrics['total_tickets'] > 0 
			? ( $metrics['resolved_tickets'] / $metrics['total_tickets'] ) * 100 
			: 0;

		$metrics['performance_score'] = round( $resolution_rate, 1 );

		return $metrics;
	}

	/**
	 * Assign default capabilities to new user
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 */
	public function assign_default_capabilities( $user_id ) {
		// This can be customized based on registration context
		// For now, we don't assign any METS roles by default
	}

	/**
	 * Check entity-specific capabilities
	 *
	 * @since    1.0.0
	 * @param    array    $allcaps    All capabilities
	 * @param    array    $caps       Required capabilities
	 * @param    array    $args       Arguments
	 * @param    object   $user       User object
	 * @return   array                Modified capabilities
	 */
	public function check_entity_specific_capabilities( $allcaps, $caps, $args, $user ) {
		// This is where we would implement dynamic capability checking
		// based on entity assignments and context
		return $allcaps;
	}

	/**
	 * Get role hierarchy
	 *
	 * @since    1.0.0
	 * @return   array    Role hierarchy (lower to higher)
	 */
	public function get_role_hierarchy() {
		return array(
			'ticket_agent' => 1,
			'senior_agent' => 2,
			'ticket_manager' => 3,
			'support_supervisor' => 4
		);
	}

	/**
	 * Check if user role can manage another role
	 *
	 * @since    1.0.0
	 * @param    string    $manager_role    Manager's role
	 * @param    string    $target_role     Target role to manage
	 * @return   bool                       Whether manager can manage target
	 */
	public function can_manage_role( $manager_role, $target_role ) {
		$hierarchy = $this->get_role_hierarchy();
		
		$manager_level = isset( $hierarchy[ $manager_role ] ) ? $hierarchy[ $manager_role ] : 0;
		$target_level = isset( $hierarchy[ $target_role ] ) ? $hierarchy[ $target_role ] : 0;

		return $manager_level > $target_level;
	}

	/**
	 * Remove METS roles on plugin deactivation
	 *
	 * @since    1.0.0
	 */
	public function remove_roles() {
		foreach ( array_keys( $this->role_definitions ) as $role_name ) {
			remove_role( $role_name );
		}
	}
}