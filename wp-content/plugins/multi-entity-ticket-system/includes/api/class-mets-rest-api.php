<?php
/**
 * REST API Controller
 *
 * Handles all REST API endpoints for the Multi-Entity Ticket System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/api
 * @since      1.0.0
 */

/**
 * The REST API Controller class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/api
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_REST_API {

	/**
	 * The namespace for the REST API routes
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $namespace    The namespace for the routes
	 */
	private $namespace = 'mets/v1';

	/**
	 * Initialize the REST API
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST API routes
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		// Entity routes
		$this->register_entity_routes();
		
		// Ticket routes
		$this->register_ticket_routes();
		
		// Customer routes
		$this->register_customer_routes();
		
		// Agent routes
		$this->register_agent_routes();
		
		// SLA routes
		$this->register_sla_routes();
		
		// Knowledge Base routes
		$this->register_kb_routes();
		
		// Reporting routes
		$this->register_reporting_routes();
		
		// System routes
		$this->register_system_routes();
	}

	/**
	 * Register entity-related routes
	 *
	 * @since    1.0.0
	 */
	private function register_entity_routes() {
		// GET /entities - List all entities
		register_rest_route( $this->namespace, '/entities', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_entities' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'status' => array(
						'default'     => 'active',
						'type'        => 'string',
						'enum'        => array( 'active', 'inactive', 'all' ),
					),
					'parent_id' => array(
						'default'     => null,
						'type'        => 'integer',
					),
					'per_page' => array(
						'default'     => 10,
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 100,
					),
					'page' => array(
						'default'     => 1,
						'type'        => 'integer',
						'minimum'     => 1,
					),
				),
			),
		) );

		// GET /entities/{id} - Get single entity
		register_rest_route( $this->namespace, '/entities/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_entity' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
			),
		) );

		// POST /entities - Create new entity
		register_rest_route( $this->namespace, '/entities', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_entity' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => $this->get_entity_creation_args(),
			),
		) );

		// PUT/PATCH /entities/{id} - Update entity
		register_rest_route( $this->namespace, '/entities/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_entity' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => $this->get_entity_update_args(),
			),
		) );

		// DELETE /entities/{id} - Delete entity
		register_rest_route( $this->namespace, '/entities/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_entity' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
		) );
	}

	/**
	 * Register ticket-related routes
	 *
	 * @since    1.0.0
	 */
	private function register_ticket_routes() {
		// GET /tickets - List tickets
		register_rest_route( $this->namespace, '/tickets', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tickets' ),
				'permission_callback' => array( $this, 'check_ticket_read_permission' ),
				'args'                => array(
					'status' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
							'enum' => array( 'open', 'in_progress', 'resolved', 'closed', 'on_hold' ),
						),
					),
					'priority' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
							'enum' => array( 'low', 'medium', 'high', 'critical' ),
						),
					),
					'entity_id' => array(
						'type'        => 'integer',
					),
					'customer_id' => array(
						'type'        => 'integer',
					),
					'assigned_to' => array(
						'type'        => 'integer',
					),
					'search' => array(
						'type'        => 'string',
					),
					'orderby' => array(
						'default'     => 'created_at',
						'type'        => 'string',
						'enum'        => array( 'created_at', 'updated_at', 'priority', 'status' ),
					),
					'order' => array(
						'default'     => 'DESC',
						'type'        => 'string',
						'enum'        => array( 'ASC', 'DESC' ),
					),
					'per_page' => array(
						'default'     => 10,
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 100,
					),
					'page' => array(
						'default'     => 1,
						'type'        => 'integer',
						'minimum'     => 1,
					),
				),
			),
		) );

		// GET /tickets/{id} - Get single ticket
		register_rest_route( $this->namespace, '/tickets/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ticket' ),
				'permission_callback' => array( $this, 'check_single_ticket_permission' ),
			),
		) );

		// POST /tickets - Create ticket
		register_rest_route( $this->namespace, '/tickets', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_ticket' ),
				'permission_callback' => array( $this, 'check_ticket_create_permission' ),
				'args'                => $this->get_ticket_creation_args(),
			),
		) );

		// PUT/PATCH /tickets/{id} - Update ticket
		register_rest_route( $this->namespace, '/tickets/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_ticket' ),
				'permission_callback' => array( $this, 'check_ticket_update_permission' ),
				'args'                => $this->get_ticket_update_args(),
			),
		) );

		// POST /tickets/{id}/replies - Add reply
		register_rest_route( $this->namespace, '/tickets/(?P<id>\d+)/replies', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_ticket_reply' ),
				'permission_callback' => array( $this, 'check_ticket_reply_permission' ),
				'args'                => array(
					'content' => array(
						'required'    => true,
						'type'        => 'string',
					),
					'attachments' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
					),
				),
			),
		) );

		// GET /tickets/{id}/replies - Get ticket replies
		register_rest_route( $this->namespace, '/tickets/(?P<id>\d+)/replies', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ticket_replies' ),
				'permission_callback' => array( $this, 'check_single_ticket_permission' ),
			),
		) );

		// POST /tickets/{id}/assign - Assign ticket
		register_rest_route( $this->namespace, '/tickets/(?P<id>\d+)/assign', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'assign_ticket' ),
				'permission_callback' => array( $this, 'check_ticket_assign_permission' ),
				'args'                => array(
					'agent_id' => array(
						'required'    => true,
						'type'        => 'integer',
					),
				),
			),
		) );

		// POST /tickets/{id}/status - Update ticket status
		register_rest_route( $this->namespace, '/tickets/(?P<id>\d+)/status', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_ticket_status' ),
				'permission_callback' => array( $this, 'check_ticket_update_permission' ),
				'args'                => array(
					'status' => array(
						'required'    => true,
						'type'        => 'string',
						'enum'        => array( 'open', 'in_progress', 'resolved', 'closed', 'on_hold' ),
					),
				),
			),
		) );
	}

	/**
	 * Register customer-related routes
	 *
	 * @since    1.0.0
	 */
	private function register_customer_routes() {
		// GET /customers - List customers
		register_rest_route( $this->namespace, '/customers', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_customers' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'search' => array(
						'type'        => 'string',
					),
					'entity_id' => array(
						'type'        => 'integer',
					),
					'per_page' => array(
						'default'     => 10,
						'type'        => 'integer',
					),
					'page' => array(
						'default'     => 1,
						'type'        => 'integer',
					),
				),
			),
		) );

		// GET /customers/{id} - Get customer details
		register_rest_route( $this->namespace, '/customers/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_customer' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
			),
		) );

		// GET /customers/{id}/tickets - Get customer tickets
		register_rest_route( $this->namespace, '/customers/(?P<id>\d+)/tickets', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_customer_tickets' ),
				'permission_callback' => array( $this, 'check_customer_tickets_permission' ),
			),
		) );
	}

	/**
	 * Register agent-related routes
	 *
	 * @since    1.0.0
	 */
	private function register_agent_routes() {
		// GET /agents - List agents
		register_rest_route( $this->namespace, '/agents', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_agents' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'entity_id' => array(
						'type'        => 'integer',
					),
					'available' => array(
						'type'        => 'boolean',
					),
				),
			),
		) );

		// GET /agents/{id}/stats - Get agent statistics
		register_rest_route( $this->namespace, '/agents/(?P<id>\d+)/stats', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_agent_stats' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'period' => array(
						'default'     => '30days',
						'type'        => 'string',
						'enum'        => array( '24hours', '7days', '30days', '90days', 'all' ),
					),
				),
			),
		) );
	}

	/**
	 * Register SLA-related routes
	 *
	 * @since    1.0.0
	 */
	private function register_sla_routes() {
		// GET /sla/rules - List SLA rules
		register_rest_route( $this->namespace, '/sla/rules', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_sla_rules' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
			),
		) );

		// GET /sla/performance - Get SLA performance metrics
		register_rest_route( $this->namespace, '/sla/performance', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_sla_performance' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'period' => array(
						'default'     => '30days',
						'type'        => 'string',
					),
					'entity_id' => array(
						'type'        => 'integer',
					),
				),
			),
		) );
	}

	/**
	 * Register Knowledge Base routes
	 *
	 * @since    1.0.0
	 */
	private function register_kb_routes() {
		// GET /kb/articles - List KB articles
		register_rest_route( $this->namespace, '/kb/articles', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_kb_articles' ),
				'permission_callback' => '__return_true', // Public access
				'args'                => array(
					'search' => array(
						'type'        => 'string',
					),
					'category_id' => array(
						'type'        => 'integer',
					),
					'entity_id' => array(
						'type'        => 'integer',
					),
					'featured' => array(
						'type'        => 'boolean',
					),
					'per_page' => array(
						'default'     => 10,
						'type'        => 'integer',
					),
					'page' => array(
						'default'     => 1,
						'type'        => 'integer',
					),
				),
			),
		) );

		// GET /kb/articles/{id} - Get single KB article
		register_rest_route( $this->namespace, '/kb/articles/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_kb_article' ),
				'permission_callback' => '__return_true', // Public access
			),
		) );

		// POST /kb/articles - Create KB article
		register_rest_route( $this->namespace, '/kb/articles', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_kb_article' ),
				'permission_callback' => array( $this, 'check_kb_create_permission' ),
				'args'                => $this->get_kb_article_creation_args(),
			),
		) );

		// PUT/PATCH /kb/articles/{id} - Update KB article
		register_rest_route( $this->namespace, '/kb/articles/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_kb_article' ),
				'permission_callback' => array( $this, 'check_kb_edit_permission' ),
				'args'                => $this->get_kb_article_update_args(),
			),
		) );

		// POST /kb/articles/{id}/feedback - Submit article feedback
		register_rest_route( $this->namespace, '/kb/articles/(?P<id>\d+)/feedback', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit_article_feedback' ),
				'permission_callback' => '__return_true', // Public access
				'args'                => array(
					'helpful' => array(
						'required'    => true,
						'type'        => 'boolean',
					),
				),
			),
		) );

		// GET /kb/categories - List KB categories
		register_rest_route( $this->namespace, '/kb/categories', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_kb_categories' ),
				'permission_callback' => '__return_true', // Public access
				'args'                => array(
					'entity_id' => array(
						'type'        => 'integer',
					),
				),
			),
		) );

		// GET /kb/search - Search KB articles
		register_rest_route( $this->namespace, '/kb/search', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_kb_articles' ),
				'permission_callback' => '__return_true', // Public access
				'args'                => array(
					'query' => array(
						'required'    => true,
						'type'        => 'string',
					),
					'entity_id' => array(
						'type'        => 'integer',
					),
					'limit' => array(
						'default'     => 10,
						'type'        => 'integer',
					),
				),
			),
		) );
	}

	/**
	 * Register reporting routes
	 *
	 * @since    1.0.0
	 */
	private function register_reporting_routes() {
		// GET /reports/dashboard - Get dashboard metrics
		register_rest_route( $this->namespace, '/reports/dashboard', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dashboard_metrics' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'period' => array(
						'default'     => '30days',
						'type'        => 'string',
					),
					'entity_id' => array(
						'type'        => 'integer',
					),
				),
			),
		) );

		// POST /reports/custom - Generate custom report
		register_rest_route( $this->namespace, '/reports/custom', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_custom_report' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'report_type' => array(
						'required'    => true,
						'type'        => 'string',
						'enum'        => array( 'tickets', 'sla', 'agent', 'knowledgebase' ),
					),
					'filters' => array(
						'type'        => 'object',
					),
					'date_range' => array(
						'type'        => 'object',
					),
					'format' => array(
						'default'     => 'json',
						'type'        => 'string',
						'enum'        => array( 'json', 'csv' ),
					),
				),
			),
		) );

		// GET /reports/export/{report_id} - Export report
		register_rest_route( $this->namespace, '/reports/export/(?P<report_id>[a-zA-Z0-9]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_report' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'format' => array(
						'default'     => 'csv',
						'type'        => 'string',
						'enum'        => array( 'csv', 'pdf' ),
					),
				),
			),
		) );
	}

	/**
	 * Register system routes
	 *
	 * @since    1.0.0
	 */
	private function register_system_routes() {
		// GET /system/info - Get system information
		register_rest_route( $this->namespace, '/system/info', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_system_info' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
		) );

		// GET /system/status - Get system status
		register_rest_route( $this->namespace, '/system/status', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_system_status' ),
				'permission_callback' => '__return_true', // Public health check
			),
		) );

		// POST /system/test-email - Test email configuration
		register_rest_route( $this->namespace, '/system/test-email', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_email' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'to' => array(
						'required'    => true,
						'type'        => 'string',
						'format'      => 'email',
					),
				),
			),
		) );
	}

	/**
	 * Get entity creation arguments
	 *
	 * @since    1.0.0
	 * @return   array    Arguments for entity creation
	 */
	private function get_entity_creation_args() {
		return array(
			'name' => array(
				'required'    => true,
				'type'        => 'string',
			),
			'type' => array(
				'required'    => true,
				'type'        => 'string',
				'enum'        => array( 'company', 'department', 'team', 'other' ),
			),
			'contact_email' => array(
				'type'        => 'string',
				'format'      => 'email',
			),
			'description' => array(
				'type'        => 'string',
			),
			'parent_id' => array(
				'type'        => 'integer',
			),
			'metadata' => array(
				'type'        => 'object',
			),
		);
	}

	/**
	 * Get entity update arguments
	 *
	 * @since    1.0.0
	 * @return   array    Arguments for entity update
	 */
	private function get_entity_update_args() {
		$args = $this->get_entity_creation_args();
		// Make all fields optional for updates
		foreach ( $args as $key => &$arg ) {
			unset( $arg['required'] );
		}
		return $args;
	}

	/**
	 * Get ticket creation arguments
	 *
	 * @since    1.0.0
	 * @return   array    Arguments for ticket creation
	 */
	private function get_ticket_creation_args() {
		return array(
			'subject' => array(
				'required'    => true,
				'type'        => 'string',
			),
			'description' => array(
				'required'    => true,
				'type'        => 'string',
			),
			'entity_id' => array(
				'required'    => true,
				'type'        => 'integer',
			),
			'priority' => array(
				'default'     => 'medium',
				'type'        => 'string',
				'enum'        => array( 'low', 'medium', 'high', 'critical' ),
			),
			'customer_name' => array(
				'type'        => 'string',
			),
			'customer_email' => array(
				'type'        => 'string',
				'format'      => 'email',
			),
			'attachments' => array(
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
			),
		);
	}

	/**
	 * Get ticket update arguments
	 *
	 * @since    1.0.0
	 * @return   array    Arguments for ticket update
	 */
	private function get_ticket_update_args() {
		return array(
			'subject' => array(
				'type'        => 'string',
			),
			'status' => array(
				'type'        => 'string',
				'enum'        => array( 'open', 'in_progress', 'resolved', 'closed', 'on_hold' ),
			),
			'priority' => array(
				'type'        => 'string',
				'enum'        => array( 'low', 'medium', 'high', 'critical' ),
			),
			'assigned_to' => array(
				'type'        => 'integer',
			),
		);
	}

	/**
	 * Get KB article creation arguments
	 *
	 * @since    1.0.0
	 * @return   array    Arguments for KB article creation
	 */
	private function get_kb_article_creation_args() {
		return array(
			'title' => array(
				'required'    => true,
				'type'        => 'string',
			),
			'content' => array(
				'required'    => true,
				'type'        => 'string',
			),
			'entity_id' => array(
				'type'        => 'integer',
			),
			'category_id' => array(
				'type'        => 'integer',
			),
			'visibility' => array(
				'default'     => 'customer',
				'type'        => 'string',
				'enum'        => array( 'internal', 'staff', 'customer' ),
			),
			'featured' => array(
				'default'     => false,
				'type'        => 'boolean',
			),
			'status' => array(
				'default'     => 'draft',
				'type'        => 'string',
				'enum'        => array( 'draft', 'pending_review', 'published' ),
			),
		);
	}

	/**
	 * Get KB article update arguments
	 *
	 * @since    1.0.0
	 * @return   array    Arguments for KB article update
	 */
	private function get_kb_article_update_args() {
		$args = $this->get_kb_article_creation_args();
		// Make all fields optional for updates
		foreach ( $args as $key => &$arg ) {
			unset( $arg['required'] );
		}
		return $args;
	}

	// Permission callback methods

	/**
	 * Check if user has read permission
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_read_permission( $request ) {
		return is_user_logged_in();
	}

	/**
	 * Check if user has manage permission
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_manage_permission( $request ) {
		return current_user_can( 'manage_tickets' );
	}

	/**
	 * Check if user has admin permission
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_admin_permission( $request ) {
		return current_user_can( 'manage_ticket_system' );
	}

	/**
	 * Check if user can read tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_ticket_read_permission( $request ) {
		if ( current_user_can( 'manage_tickets' ) ) {
			return true;
		}

		// Customers can only see their own tickets
		if ( is_user_logged_in() ) {
			$customer_id = $request->get_param( 'customer_id' );
			return $customer_id && $customer_id == get_current_user_id();
		}

		return false;
	}

	/**
	 * Check if user can access single ticket
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_single_ticket_permission( $request ) {
		if ( current_user_can( 'manage_tickets' ) ) {
			return true;
		}

		// Check if user is the ticket customer
		$ticket_id = $request->get_param( 'id' );
		if ( $ticket_id && is_user_logged_in() ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
			$ticket_model = new METS_Ticket_Model();
			$ticket = $ticket_model->get( $ticket_id );
			
			if ( $ticket && $ticket->created_by == get_current_user_id() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user can create tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool|WP_Error                Whether user has permission or error
	 */
	public function check_ticket_create_permission( $request ) {
		// Allow logged-in users
		if ( is_user_logged_in() ) {
			return true;
		}
		
		// For guest users, validate email and implement rate limiting
		$customer_email = $request->get_param( 'customer_email' );
		
		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			return new WP_Error( 
				'invalid_email', 
				__( 'Valid email required for guest ticket creation', METS_TEXT_DOMAIN ), 
				array( 'status' => 400 ) 
			);
		}
		
		// Check rate limiting for guest submissions
		if ( ! $this->check_guest_rate_limit( $customer_email ) ) {
			return new WP_Error( 
				'rate_limit_exceeded', 
				__( 'Too many tickets submitted. Please try again later.', METS_TEXT_DOMAIN ), 
				array( 'status' => 429 ) 
			);
		}
		
		return true;
	}

	/**
	 * Check rate limiting for guest ticket submissions
	 *
	 * @since    1.0.0
	 * @param    string   $email   Customer email
	 * @return   bool              Whether request is within rate limit
	 */
	private function check_guest_rate_limit( $email ) {
		global $wpdb;
		
		// Check tickets submitted in last hour
		$recent_tickets = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE customer_email = %s 
			AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
			AND created_by IS NULL", // Guest submissions have no created_by
			$email
		) );
		
		// Allow max 3 tickets per hour for guests
		return intval( $recent_tickets ) < 3;
	}

	/**
	 * Check if user can update tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_ticket_update_permission( $request ) {
		return current_user_can( 'manage_tickets' );
	}

	/**
	 * Check if user can reply to tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_ticket_reply_permission( $request ) {
		return $this->check_single_ticket_permission( $request );
	}

	/**
	 * Check if user can assign tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_ticket_assign_permission( $request ) {
		return current_user_can( 'assign_tickets' );
	}

	/**
	 * Check if user can view customer tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_customer_tickets_permission( $request ) {
		if ( current_user_can( 'manage_tickets' ) ) {
			return true;
		}

		// Customers can only see their own tickets
		$customer_id = $request->get_param( 'id' );
		return is_user_logged_in() && $customer_id == get_current_user_id();
	}

	/**
	 * Check if user can create KB articles
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_kb_create_permission( $request ) {
		return current_user_can( 'create_kb_articles' );
	}

	/**
	 * Check if user can edit KB articles
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   bool                         Whether user has permission
	 */
	public function check_kb_edit_permission( $request ) {
		return current_user_can( 'edit_kb_articles' );
	}

	// API Response helper methods

	/**
	 * Prepare response with pagination
	 *
	 * @since    1.0.0
	 * @param    array             $data      The data to return
	 * @param    int               $total     Total number of items
	 * @param    int               $per_page  Items per page
	 * @param    int               $page      Current page
	 * @return   WP_REST_Response             The response object
	 */
	protected function prepare_paginated_response( $data, $total, $per_page, $page ) {
		$response = rest_ensure_response( $data );
		
		$total_pages = ceil( $total / $per_page );
		
		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', (int) $total_pages );
		$response->header( 'X-WP-Page', (int) $page );
		$response->header( 'X-WP-PerPage', (int) $per_page );
		
		return $response;
	}

	/**
	 * Prepare error response
	 *
	 * @since    1.0.0
	 * @param    string            $code      Error code
	 * @param    string            $message   Error message
	 * @param    int               $status    HTTP status code
	 * @return   WP_Error                     The error object
	 */
	protected function prepare_error_response( $code, $message, $status = 400 ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}