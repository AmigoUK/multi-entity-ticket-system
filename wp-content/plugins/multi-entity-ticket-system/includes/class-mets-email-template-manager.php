<?php
/**
 * Email Template Manager
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Email Template Manager class.
 *
 * This class handles email template database operations, CRUD functionality,
 * and integration with the existing template engine.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Email_Template_Manager {

	/**
	 * Database table name for templates
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $templates_table
	 */
	private $templates_table;

	/**
	 * Database table name for template history
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $history_table
	 */
	private $history_table;

	/**
	 * Available template variables
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $available_variables
	 */
	private $available_variables;

	/**
	 * Default template types
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $default_templates
	 */
	private $default_templates;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->templates_table = $wpdb->prefix . 'mets_email_templates';
		$this->history_table = $wpdb->prefix . 'mets_email_template_history';
		
		$this->setup_available_variables();
		$this->setup_default_templates();
	}

	/**
	 * Setup available template variables
	 *
	 * @since    1.0.0
	 */
	private function setup_available_variables() {
		$this->available_variables = array(
			'ticket_number' => __( 'Ticket number (e.g. #12345)', METS_TEXT_DOMAIN ),
			'ticket_number_safe' => __( 'Ticket number (HTML safe)', METS_TEXT_DOMAIN ),
			'ticket_subject' => __( 'Ticket subject/title', METS_TEXT_DOMAIN ),
			'ticket_subject_safe' => __( 'Ticket subject (HTML safe)', METS_TEXT_DOMAIN ),
			'ticket_content' => __( 'Ticket description/content', METS_TEXT_DOMAIN ),
			'ticket_content_html' => __( 'Ticket content (HTML formatted)', METS_TEXT_DOMAIN ),
			'ticket_url' => __( 'Customer ticket view URL', METS_TEXT_DOMAIN ),
			'ticket_url_safe' => __( 'Customer ticket view URL (HTML safe)', METS_TEXT_DOMAIN ),
			'admin_ticket_url' => __( 'Admin ticket management URL', METS_TEXT_DOMAIN ),
			'admin_ticket_url_safe' => __( 'Admin ticket URL (HTML safe)', METS_TEXT_DOMAIN ),
			'customer_name' => __( 'Customer full name', METS_TEXT_DOMAIN ),
			'customer_name_safe' => __( 'Customer name (HTML safe)', METS_TEXT_DOMAIN ),
			'customer_email' => __( 'Customer email address', METS_TEXT_DOMAIN ),
			'customer_email_safe' => __( 'Customer email (HTML safe)', METS_TEXT_DOMAIN ),
			'customer_phone' => __( 'Customer phone number', METS_TEXT_DOMAIN ),
			'agent_name' => __( 'Assigned agent name', METS_TEXT_DOMAIN ),
			'agent_name_safe' => __( 'Agent name (HTML safe)', METS_TEXT_DOMAIN ),
			'agent_email' => __( 'Assigned agent email', METS_TEXT_DOMAIN ),
			'entity_name' => __( 'Entity/company name', METS_TEXT_DOMAIN ),
			'entity_name_safe' => __( 'Entity name (HTML safe)', METS_TEXT_DOMAIN ),
			'entity_email' => __( 'Entity contact email', METS_TEXT_DOMAIN ),
			'portal_url' => __( 'Customer portal URL', METS_TEXT_DOMAIN ),
			'ticket_status' => __( 'Current ticket status', METS_TEXT_DOMAIN ),
			'status' => __( 'Current ticket status', METS_TEXT_DOMAIN ),
			'status_safe' => __( 'Current ticket status (HTML safe)', METS_TEXT_DOMAIN ),
			'priority' => __( 'Ticket priority level', METS_TEXT_DOMAIN ),
			'priority_safe' => __( 'Ticket priority (HTML safe)', METS_TEXT_DOMAIN ),
			'category' => __( 'Ticket category', METS_TEXT_DOMAIN ),
			'category_safe' => __( 'Ticket category (HTML safe)', METS_TEXT_DOMAIN ),
			'created_date' => __( 'Ticket creation date', METS_TEXT_DOMAIN ),
			'created_date_safe' => __( 'Creation date (HTML safe)', METS_TEXT_DOMAIN ),
			'updated_date' => __( 'Last update date', METS_TEXT_DOMAIN ),
			'reply_content' => __( 'Reply message content', METS_TEXT_DOMAIN ),
			'reply_content_html' => __( 'Reply content (HTML formatted)', METS_TEXT_DOMAIN ),
			'reply_date' => __( 'Reply date and time', METS_TEXT_DOMAIN ),
			'reply_date_safe' => __( 'Reply date (HTML safe)', METS_TEXT_DOMAIN ),
			'due_date' => __( 'Ticket due date', METS_TEXT_DOMAIN ),
			'response_due' => __( 'Response due date/time', METS_TEXT_DOMAIN ),
			'resolution_due' => __( 'Resolution due date/time', METS_TEXT_DOMAIN ),
			'escalation_due' => __( 'Escalation due date/time', METS_TEXT_DOMAIN ),
			'sla_type' => __( 'SLA rule type/name', METS_TEXT_DOMAIN ),
			'sla_type_safe' => __( 'SLA type (HTML safe)', METS_TEXT_DOMAIN ),
			'time_remaining' => __( 'Time remaining until SLA breach', METS_TEXT_DOMAIN ),
			'time_remaining_safe' => __( 'Time remaining (HTML safe)', METS_TEXT_DOMAIN ),
			'site_name' => __( 'WordPress site name', METS_TEXT_DOMAIN ),
			'site_name_safe' => __( 'Site name (HTML safe)', METS_TEXT_DOMAIN ),
			'site_url' => __( 'WordPress site URL', METS_TEXT_DOMAIN ),
			'site_url_safe' => __( 'Site URL (HTML safe)', METS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Setup default template configurations
	 *
	 * @since    1.0.0
	 */
	private function setup_default_templates() {
		$this->default_templates = array(
			'ticket-created-customer' => array(
				'name' => __( 'Ticket Created (Customer)', METS_TEXT_DOMAIN ),
				'type' => 'customer_notification',
				'description' => __( 'Sent to customers when a new ticket is created', METS_TEXT_DOMAIN ),
			),
			'ticket-created-agent' => array(
				'name' => __( 'Ticket Created (Agent)', METS_TEXT_DOMAIN ),
				'type' => 'agent_notification',
				'description' => __( 'Sent to agents when a new ticket is assigned', METS_TEXT_DOMAIN ),
			),
			'ticket-reply-customer' => array(
				'name' => __( 'Ticket Reply (Customer)', METS_TEXT_DOMAIN ),
				'type' => 'customer_notification',
				'description' => __( 'Sent to customers when agent replies to ticket', METS_TEXT_DOMAIN ),
			),
			'sla-warning' => array(
				'name' => __( 'SLA Warning', METS_TEXT_DOMAIN ),
				'type' => 'alert',
				'description' => __( 'Sent when SLA deadline is approaching', METS_TEXT_DOMAIN ),
			),
			'sla-breach' => array(
				'name' => __( 'SLA Breach', METS_TEXT_DOMAIN ),
				'type' => 'alert',
				'description' => __( 'Sent when SLA deadline has been breached', METS_TEXT_DOMAIN ),
			),
			'sla-breach-warning' => array(
				'name' => __( 'SLA Breach Warning', METS_TEXT_DOMAIN ),
				'type' => 'alert',
				'description' => __( 'Sent before SLA breach occurs', METS_TEXT_DOMAIN ),
			),
		);
	}

	/**
	 * Create a new email template
	 *
	 * @since    1.0.0
	 * @param    array   $data    Template data
	 * @return   int|WP_Error     Template ID or error
	 */
	public function create_template( $data ) {
		global $wpdb;

		// Validate required fields
		$required = array( 'template_name', 'subject_line', 'content' );
		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new WP_Error( 'missing_field', 
					sprintf( __( 'Required field missing: %s', METS_TEXT_DOMAIN ), $field ) );
			}
		}

		// Validate template name uniqueness
		if ( $this->template_exists( $data['template_name'] ) ) {
			return new WP_Error( 'template_exists', 
				__( 'A template with this name already exists', METS_TEXT_DOMAIN ) );
		}

		// Sanitize and validate content
		$content_validation = $this->validate_template_content( $data['content'] );
		if ( is_wp_error( $content_validation ) ) {
			return $content_validation;
		}

		// Prepare data for insertion
		$insert_data = array(
			'template_name' => sanitize_text_field( $data['template_name'] ),
			'template_type' => sanitize_text_field( $data['template_type'] ?? 'custom' ),
			'subject_line' => sanitize_text_field( $data['subject_line'] ),
			'content' => $this->sanitize_template_content( $data['content'] ),
			'plain_text_content' => $this->generate_plain_text( $data['content'] ),
			'is_active' => intval( $data['is_active'] ?? 1 ),
			'is_default' => intval( $data['is_default'] ?? 0 ),
			'created_by' => get_current_user_id(),
			'version' => 1,
		);

		$insert_formats = array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' );

		$result = $wpdb->insert( $this->templates_table, $insert_data, $insert_formats );

		if ( $result === false ) {
			$error_message = $wpdb->last_error ? $wpdb->last_error : __( 'Unknown database error', METS_TEXT_DOMAIN );
			return new WP_Error( 'db_error', 
				sprintf( __( 'Failed to create template: %s', METS_TEXT_DOMAIN ), $error_message ) );
		}

		$template_id = $wpdb->insert_id;

		// Create initial history entry
		$this->create_history_entry( $template_id, $insert_data, __( 'Template created', METS_TEXT_DOMAIN ) );

		do_action( 'mets_email_template_created', $template_id, $insert_data );

		return $template_id;
	}

	/**
	 * Update an existing email template
	 *
	 * @since    1.0.0
	 * @param    int     $template_id    Template ID
	 * @param    array   $data          Updated template data
	 * @return   bool|WP_Error          Success or error
	 */
	public function update_template( $template_id, $data ) {
		global $wpdb;

		// Get existing template
		$existing = $this->get_template( $template_id );
		if ( ! $existing ) {
			return new WP_Error( 'template_not_found', 
				__( 'Template not found', METS_TEXT_DOMAIN ) );
		}

		// Validate content if provided
		if ( ! empty( $data['content'] ) ) {
			$content_validation = $this->validate_template_content( $data['content'] );
			if ( is_wp_error( $content_validation ) ) {
				return $content_validation;
			}
		}

		// Prepare update data
		$update_data = array();
		$update_formats = array();

		$allowed_fields = array(
			'template_type' => '%s',
			'subject_line' => '%s',
			'content' => '%s',
			'is_active' => '%d',
			'is_default' => '%d',
		);

		foreach ( $allowed_fields as $field => $format ) {
			if ( isset( $data[ $field ] ) ) {
				if ( $field === 'content' ) {
					$update_data[ $field ] = $this->sanitize_template_content( $data[ $field ] );
					$update_data['plain_text_content'] = $this->generate_plain_text( $data[ $field ] );
					$update_formats[] = $format;
					$update_formats[] = '%s';
				} elseif ( in_array( $format, array( '%d' ) ) ) {
					$update_data[ $field ] = intval( $data[ $field ] );
					$update_formats[] = $format;
				} else {
					$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
					$update_formats[] = $format;
				}
			}
		}

		if ( empty( $update_data ) ) {
			return new WP_Error( 'no_data', 
				__( 'No valid data provided for update', METS_TEXT_DOMAIN ) );
		}

		// Increment version
		$update_data['version'] = $existing->version + 1;
		$update_formats[] = '%d';

		$result = $wpdb->update(
			$this->templates_table,
			$update_data,
			array( 'id' => $template_id ),
			$update_formats,
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', 
				__( 'Failed to update template', METS_TEXT_DOMAIN ) );
		}

		// Create history entry
		$notes = $data['notes'] ?? sprintf( __( 'Template updated to version %d', METS_TEXT_DOMAIN ), $update_data['version'] );
		$this->create_history_entry( $template_id, $update_data, $notes );

		do_action( 'mets_email_template_updated', $template_id, $update_data );

		return true;
	}

	/**
	 * Get a template by ID
	 *
	 * @since    1.0.0
	 * @param    int    $template_id    Template ID
	 * @return   object|null            Template object or null
	 */
	public function get_template( $template_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->templates_table} WHERE id = %d",
			$template_id
		) );
	}

	/**
	 * Get a template by name
	 *
	 * @since    1.0.0
	 * @param    string    $template_name    Template name
	 * @return   object|null                 Template object or null
	 */
	public function get_template_by_name( $template_name ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->templates_table} WHERE template_name = %s AND is_active = 1",
			$template_name
		) );
	}

	/**
	 * Get all templates
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array of template objects
	 */
	public function get_templates( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'template_type' => '',
			'is_active' => null,
			'order_by' => 'template_name',
			'order' => 'ASC',
			'limit' => 100,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$prepare_values = array();

		if ( ! empty( $args['template_type'] ) ) {
			$where_clauses[] = 'template_type = %s';
			$prepare_values[] = $args['template_type'];
		}

		if ( $args['is_active'] !== null ) {
			$where_clauses[] = 'is_active = %d';
			$prepare_values[] = intval( $args['is_active'] );
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$order_by = sanitize_sql_orderby( $args['order_by'] . ' ' . $args['order'] );
		$limit_sql = sprintf( 'LIMIT %d OFFSET %d', intval( $args['limit'] ), intval( $args['offset'] ) );

		$sql = "SELECT * FROM {$this->templates_table} {$where_sql} ORDER BY {$order_by} {$limit_sql}";

		if ( ! empty( $prepare_values ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $prepare_values ) );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Delete a template
	 *
	 * @since    1.0.0
	 * @param    int    $template_id    Template ID
	 * @return   bool|WP_Error          Success or error
	 */
	public function delete_template( $template_id ) {
		global $wpdb;

		$template = $this->get_template( $template_id );
		if ( ! $template ) {
			return new WP_Error( 'template_not_found', 
				__( 'Template not found', METS_TEXT_DOMAIN ) );
		}

		// Prevent deletion of default templates
		if ( $template->is_default ) {
			return new WP_Error( 'cannot_delete_default', 
				__( 'Cannot delete default template', METS_TEXT_DOMAIN ) );
		}

		$result = $wpdb->delete(
			$this->templates_table,
			array( 'id' => $template_id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', 
				__( 'Failed to delete template', METS_TEXT_DOMAIN ) );
		}

		do_action( 'mets_email_template_deleted', $template_id, $template );

		return true;
	}

	/**
	 * Check if template exists
	 *
	 * @since    1.0.0
	 * @param    string    $template_name    Template name
	 * @return   bool                        True if exists
	 */
	public function template_exists( $template_name ) {
		global $wpdb;

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->templates_table} WHERE template_name = %s",
			$template_name
		) );

		return $count > 0;
	}

	/**
	 * Create history entry
	 *
	 * @since    1.0.0
	 * @param    int      $template_id    Template ID
	 * @param    array    $data          Template data
	 * @param    string   $notes         History notes
	 * @return   bool                    Success
	 */
	private function create_history_entry( $template_id, $data, $notes = '' ) {
		global $wpdb;

		$history_data = array(
			'template_id' => $template_id,
			'subject_line' => $data['subject_line'],
			'content' => $data['content'],
			'plain_text_content' => $data['plain_text_content'] ?? '',
			'version' => $data['version'],
			'created_by' => get_current_user_id(),
			'notes' => $notes,
		);

		$result = $wpdb->insert(
			$this->history_table,
			$history_data,
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Get template history
	 *
	 * @since    1.0.0
	 * @param    int    $template_id    Template ID
	 * @param    int    $limit         Number of entries to return
	 * @return   array                 History entries
	 */
	public function get_template_history( $template_id, $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT h.*, u.display_name as created_by_name 
			 FROM {$this->history_table} h
			 LEFT JOIN {$wpdb->users} u ON h.created_by = u.ID
			 WHERE h.template_id = %d
			 ORDER BY h.created_at DESC
			 LIMIT %d",
			$template_id,
			$limit
		) );
	}

	/**
	 * Validate template content
	 *
	 * @since    1.0.0
	 * @param    string    $content    Template content
	 * @return   bool|WP_Error        True if valid, error if not
	 */
	private function validate_template_content( $content ) {
		// Check for malicious scripts
		if ( preg_match( '/<script[^>]*>.*?<\/script>/is', $content ) ) {
			return new WP_Error( 'script_not_allowed', 
				__( 'Script tags are not allowed in templates', METS_TEXT_DOMAIN ) );
		}

		// Validate template variables
		$used_variables = $this->extract_template_variables( $content );
		$available_variables = array_keys( $this->available_variables );
		$invalid_variables = array_diff( $used_variables, $available_variables );

		if ( ! empty( $invalid_variables ) ) {
			return new WP_Error( 'invalid_variables', 
				sprintf( __( 'Invalid variables found: %s', METS_TEXT_DOMAIN ), 
					implode( ', ', $invalid_variables ) ) );
		}

		return true;
	}

	/**
	 * Extract template variables from content
	 *
	 * @since    1.0.0
	 * @param    string    $content    Template content
	 * @return   array                Array of variable names
	 */
	private function extract_template_variables( $content ) {
		preg_match_all( '/\{\{([^}]+)\}\}/', $content, $matches );
		return array_unique( $matches[1] );
	}

	/**
	 * Sanitize template content
	 *
	 * @since    1.0.0
	 * @param    string    $content    Template content
	 * @return   string               Sanitized content
	 */
	private function sanitize_template_content( $content ) {
		$allowed_html = array(
			'html' => array(),
			'head' => array(),
			'title' => array(),
			'meta' => array( 'charset' => true, 'name' => true, 'content' => true, 'viewport' => true ),
			'style' => array( 'type' => true ),
			'body' => array( 'style' => true, 'class' => true ),
			'div' => array( 'class' => true, 'style' => true, 'id' => true ),
			'p' => array( 'class' => true, 'style' => true ),
			'a' => array( 'href' => true, 'class' => true, 'style' => true, 'target' => true, 'title' => true ),
			'img' => array( 'src' => true, 'alt' => true, 'width' => true, 'height' => true, 'style' => true, 'class' => true ),
			'table' => array( 'class' => true, 'style' => true, 'cellpadding' => true, 'cellspacing' => true, 'width' => true ),
			'thead' => array( 'class' => true, 'style' => true ),
			'tbody' => array( 'class' => true, 'style' => true ),
			'tr' => array( 'class' => true, 'style' => true ),
			'th' => array( 'class' => true, 'style' => true, 'colspan' => true, 'rowspan' => true, 'scope' => true ),
			'td' => array( 'class' => true, 'style' => true, 'colspan' => true, 'rowspan' => true ),
			'h1' => array( 'class' => true, 'style' => true ),
			'h2' => array( 'class' => true, 'style' => true ),
			'h3' => array( 'class' => true, 'style' => true ),
			'h4' => array( 'class' => true, 'style' => true ),
			'h5' => array( 'class' => true, 'style' => true ),
			'h6' => array( 'class' => true, 'style' => true ),
			'strong' => array( 'class' => true, 'style' => true ),
			'b' => array( 'class' => true, 'style' => true ),
			'em' => array( 'class' => true, 'style' => true ),
			'i' => array( 'class' => true, 'style' => true ),
			'u' => array( 'class' => true, 'style' => true ),
			'br' => array(),
			'hr' => array( 'style' => true, 'class' => true ),
			'ul' => array( 'class' => true, 'style' => true ),
			'ol' => array( 'class' => true, 'style' => true ),
			'li' => array( 'class' => true, 'style' => true ),
			'span' => array( 'class' => true, 'style' => true ),
			'center' => array( 'class' => true, 'style' => true ),
		);

		return wp_kses( $content, $allowed_html );
	}

	/**
	 * Generate plain text version from HTML content
	 *
	 * @since    1.0.0
	 * @param    string    $html_content    HTML content
	 * @return   string                    Plain text content
	 */
	private function generate_plain_text( $html_content ) {
		// Remove HTML tags but preserve line breaks
		$text = preg_replace( '/<br\s*\/?>/i', "\n", $html_content );
		$text = preg_replace( '/<\/p>/i', "\n\n", $text );
		$text = preg_replace( '/<\/div>/i', "\n", $text );
		$text = preg_replace( '/<\/h[1-6]>/i', "\n\n", $text );
		
		// Strip remaining HTML tags
		$text = wp_strip_all_tags( $text );
		
		// Clean up whitespace
		$text = preg_replace( '/\n\s*\n/', "\n\n", $text );
		$text = trim( $text );
		
		return $text;
	}

	/**
	 * Get available variables for templates
	 *
	 * @since    1.0.0
	 * @return   array    Available variables with descriptions
	 */
	public function get_available_variables() {
		return $this->available_variables;
	}

	/**
	 * Get available template types
	 *
	 * @since    1.0.0
	 * @return   array    Template types
	 */
	public function get_template_types() {
		return array(
			'customer_notification' => __( 'Customer Notification', METS_TEXT_DOMAIN ),
			'agent_notification' => __( 'Agent Notification', METS_TEXT_DOMAIN ),
			'alert' => __( 'System Alert', METS_TEXT_DOMAIN ),
			'custom' => __( 'Custom Template', METS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Get default template configurations
	 *
	 * @since    1.0.0
	 * @return   array    Default templates
	 */
	public function get_default_templates() {
		return $this->default_templates;
	}

	/**
	 * Migrate existing PHP templates to database
	 *
	 * @since    1.0.0
	 * @return   array    Migration results
	 */
	public function migrate_existing_templates() {
		$results = array(
			'success' => array(),
			'errors' => array(),
			'skipped' => array(),
		);

		foreach ( $this->default_templates as $template_key => $template_info ) {
			$template_file = METS_PLUGIN_PATH . 'includes/email-templates/' . $template_key . '.php';
			
			if ( file_exists( $template_file ) ) {
				if ( $this->template_exists( $template_key ) ) {
					$results['skipped'][ $template_key ] = 'Template already exists in database';
					continue;
				}
				
				$migration_result = $this->migrate_template_file( $template_key, $template_file, $template_info );
				
				if ( is_wp_error( $migration_result ) ) {
					$results['errors'][ $template_key ] = $migration_result->get_error_message();
				} else {
					$results['success'][ $template_key ] = $migration_result;
				}
			} else {
				$results['errors'][ $template_key ] = 'Template file not found: ' . $template_file;
			}
		}

		return $results;
	}

	/**
	 * Migrate a single template file to database
	 *
	 * @since    1.0.0
	 * @param    string    $template_key     Template key
	 * @param    string    $template_file    Template file path
	 * @param    array     $template_info    Template info
	 * @return   int|WP_Error               Template ID or error
	 */
	private function migrate_template_file( $template_key, $template_file, $template_info ) {
		try {
			// Initialize template data array
			$template_data = array();
			
			// Capture the output from the template file
			ob_start();
			include $template_file;
			$content = ob_get_clean();
			
			// Clean up the content - remove the EOF line if present
			$content = preg_replace( '/EOF\s*<\s*\/dev\/null\s*$/m', '', $content );
			$content = trim( $content );
			
			// Extract subject line from template_data or use default
			$subject = isset( $template_data['subject'] ) ? $template_data['subject'] : sprintf( '[%s] %s', '{{entity_name}}', $template_info['name'] );
			
			// Create template in database
			$data = array(
				'template_name' => $template_key,
				'template_type' => $template_info['type'],
				'subject_line' => $subject,
				'content' => $content,
				'is_active' => 1,
				'is_default' => 1,
			);

			return $this->create_template( $data );
			
		} catch ( Exception $e ) {
			return new WP_Error( 'migration_failed', 
				sprintf( __( 'Failed to migrate template %s: %s', METS_TEXT_DOMAIN ), $template_key, $e->getMessage() ) );
		}
	}
}