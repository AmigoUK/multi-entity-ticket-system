<?php
/**
 * Email Template Engine
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Email Template Engine class.
 *
 * This class handles email template loading, processing, and variable replacement.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Email_Template_Engine {

	/**
	 * Templates directory path
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $templates_path    Templates directory path.
	 */
	private $templates_path;

	/**
	 * Available template variables
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $available_variables    Available template variables.
	 */
	private $available_variables;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->templates_path = METS_PLUGIN_PATH . 'includes/email-templates/';
		$this->setup_available_variables();
	}

	/**
	 * Setup available template variables
	 *
	 * @since    1.0.0
	 */
	private function setup_available_variables() {
		$this->available_variables = array(
			'ticket_number',
			'ticket_subject',
			'ticket_content',
			'ticket_url',
			'admin_ticket_url',
			'customer_name',
			'customer_email',
			'customer_phone',
			'agent_name',
			'agent_email',
			'entity_name',
			'entity_email',
			'portal_url',
			'ticket_status',
			'status',
			'priority',
			'category',
			'created_date',
			'updated_date',
			'reply_content',
			'reply_date',
			'due_date',
			'response_due',
			'resolution_due',
			'escalation_due',
			'sla_type',
			'time_remaining',
			'site_name',
			'site_url',
		);
	}

	/**
	 * Load and process email template
	 *
	 * @since    1.0.0
	 * @param    string   $template_name    Template name
	 * @param    array    $data            Template data
	 * @return   string                    Processed template content
	 */
	public function load_template( $template_name, $data = array() ) {
		$template_file = $this->get_template_path( $template_name );

		if ( ! file_exists( $template_file ) ) {
			return $this->get_fallback_template( $template_name, $data );
		}

		// Start output buffering
		ob_start();

		// Extract template data for use in template
		$template_data = $data;
		
		// Include the template file
		include $template_file;

		// Get the content
		$content = ob_get_clean();

		// Process template variables
		$content = $this->process_variables( $content, $data );

		return $content;
	}

	/**
	 * Get template file path
	 *
	 * @since    1.0.0
	 * @param    string   $template_name    Template name
	 * @return   string                    Template file path
	 */
	private function get_template_path( $template_name ) {
		// Allow theme override
		$theme_template = get_template_directory() . '/mets-templates/email/' . $template_name . '.php';
		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		// Check child theme
		$child_template = get_stylesheet_directory() . '/mets-templates/email/' . $template_name . '.php';
		if ( file_exists( $child_template ) ) {
			return $child_template;
		}

		// Default plugin template
		return $this->templates_path . $template_name . '.php';
	}

	/**
	 * Process template variables
	 *
	 * @since    1.0.0
	 * @param    string   $content    Template content
	 * @param    array    $data       Template data
	 * @return   string               Processed content
	 */
	private function process_variables( $content, $data ) {
		// Add default variables
		$data = array_merge( $this->get_default_variables(), $data );

		// Create sanitized versions of all variables for security
		$sanitized_data = $this->create_sanitized_variables( $data );
		$data = array_merge( $data, $sanitized_data );

		// Replace template variables
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) || is_numeric( $value ) ) {
				$content = str_replace( '{{' . $key . '}}', $value, $content );
			}
		}

		// Clean up any remaining unreplaced variables
		$content = preg_replace( '/\{\{[^}]+\}\}/', '', $content );

		// Inline CSS for better email client compatibility
		$content = $this->inline_css( $content );

		return $content;
	}

	/**
	 * Inline CSS styles for email compatibility
	 *
	 * @since    1.0.0
	 * @param    string   $html    HTML content with embedded CSS
	 * @return   string            HTML content with inline styles
	 */
	private function inline_css( $html ) {
		// Extract CSS from style tags
		preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $style_matches );
		
		if ( empty( $style_matches[1] ) ) {
			return $html;
		}

		// Combine all CSS rules
		$css = implode( "\n", $style_matches[1] );
		
		// Remove style tags from HTML
		$html = preg_replace( '/<style[^>]*>.*?<\/style>/is', '', $html );

		// Parse CSS rules
		$css_rules = $this->parse_css_rules( $css );

		// Apply CSS rules as inline styles
		foreach ( $css_rules as $selector => $styles ) {
			$html = $this->apply_inline_styles( $html, $selector, $styles );
		}

		return $html;
	}

	/**
	 * Parse CSS rules from CSS text
	 *
	 * @since    1.0.0
	 * @param    string   $css    CSS text
	 * @return   array            Parsed CSS rules
	 */
	private function parse_css_rules( $css ) {
		$rules = array();
		
		// Remove comments
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );
		
		// Match CSS rules
		preg_match_all( '/([^{]+)\{([^}]+)\}/', $css, $matches, PREG_SET_ORDER );
		
		foreach ( $matches as $match ) {
			$selectors = array_map( 'trim', explode( ',', $match[1] ) );
			$styles = trim( $match[2] );
			
			foreach ( $selectors as $selector ) {
				$rules[ $selector ] = $styles;
			}
		}
		
		return $rules;
	}

	/**
	 * Apply inline styles to HTML elements matching a CSS selector
	 *
	 * @since    1.0.0
	 * @param    string   $html       HTML content
	 * @param    string   $selector   CSS selector
	 * @param    string   $styles     CSS styles
	 * @return   string               HTML content with inline styles applied
	 */
	private function apply_inline_styles( $html, $selector, $styles ) {
		// Convert CSS selector to regex pattern
		$pattern = $this->selector_to_regex( $selector );
		
		// Apply styles to matching elements
		return preg_replace_callback( 
			$pattern, 
			function( $matches ) use ( $styles ) {
				$full_match = $matches[0];
				$existing_style = '';
				
				// Check if element already has a style attribute
				if ( preg_match( '/style="([^"]*)"/', $full_match, $style_matches ) ) {
					$existing_style = rtrim( $style_matches[1], ';' ) . ';';
					// Remove existing style attribute
					$full_match = preg_replace( '/\s+style="[^"]*"/', '', $full_match );
				}
				
				// Add style attribute with combined styles
				$new_style = $existing_style . $styles;
				$full_match = preg_replace( '/(<[a-z][^>]*?)>/i', '$1 style="' . esc_attr( $new_style ) . '">', $full_match, 1 );
				
				return $full_match;
			}, 
			$html 
		);
	}

	/**
	 * Convert CSS selector to regex pattern
	 *
	 * @since    1.0.0
	 * @param    string   $selector   CSS selector
	 * @return   string               Regex pattern
	 */
	private function selector_to_regex( $selector ) {
		$selector = trim( $selector );
		
		// Handle element selectors (e.g., "p", "div")
		if ( preg_match( '/^[a-z]+$/', $selector ) ) {
			return '/(<(' . preg_quote( $selector ) . ')\b[^>]*>)/i';
		}
		
		// Handle class selectors (e.g., ".class-name")
		if ( preg_match( '/^\.[a-z0-9_-]+$/i', $selector ) ) {
			$class_name = substr( $selector, 1 );
			return '/(<[a-z][^>]*class="[^"]*' . preg_quote( $class_name ) . '[^"]*"[^>]*>)/i';
		}
		
		// Handle id selectors (e.g., "#id-name")
		if ( preg_match( '/^#[a-z0-9_-]+$/i', $selector ) ) {
			$id_name = substr( $selector, 1 );
			return '/(<[a-z][^>]*id="[^"]*' . preg_quote( $id_name ) . '[^"]*"[^>]*>)/i';
		}
		
		// Handle element.class selectors (e.g., "p.class-name")
		if ( preg_match( '/^[a-z]+\.[a-z0-9_-]+$/i', $selector ) ) {
			$parts = explode( '.', $selector );
			$element = $parts[0];
			$class_name = $parts[1];
			return '/(<(' . preg_quote( $element ) . ')\b[^>]*class="[^"]*' . preg_quote( $class_name ) . '[^"]*"[^>]*>)/i';
		}
		
		// Default pattern for other selectors
		return '/(<[a-z][^>]*>)/i';
	}

	/**
	 * Create sanitized versions of variables for security
	 *
	 * @since    1.0.0
	 * @param    array    $data    Original data
	 * @return   array             Sanitized data with _safe and _html suffixes
	 */
	private function create_sanitized_variables( $data ) {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) || is_numeric( $value ) ) {
				// Create _safe version (HTML escaped)
				$sanitized[ $key . '_safe' ] = esc_html( (string) $value );

				// Create _html version (allowed HTML tags only)
				$allowed_tags = array(
					'p' => array(),
					'br' => array(),
					'strong' => array(),
					'em' => array(),
					'ul' => array(),
					'ol' => array(),
					'li' => array(),
					'blockquote' => array(),
					'h1' => array(),
					'h2' => array(),
					'h3' => array(),
					'h4' => array(),
					'h5' => array(),
					'h6' => array(),
				);
				$sanitized[ $key . '_html' ] = wp_kses( (string) $value, $allowed_tags );

				// Create _url version for URLs
				if ( strpos( $key, 'url' ) !== false || $key === 'site_url' || $key === 'portal_url' ) {
					$sanitized[ $key . '_safe' ] = esc_url( (string) $value );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Get default template variables
	 *
	 * @since    1.0.0
	 * @return   array    Default variables
	 */
	private function get_default_variables() {
		return array(
			'site_name' => get_bloginfo( 'name' ),
			'site_url' => home_url(),
			'portal_url' => METS_Core::get_ticket_portal_url() ?: home_url(),
		);
	}

	/**
	 * Get fallback template content
	 *
	 * @since    1.0.0
	 * @param    string   $template_name    Template name
	 * @param    array    $data            Template data
	 * @return   string                    Fallback content
	 */
	private function get_fallback_template( $template_name, $data ) {
		$subject = $data['ticket_number'] ?? 'Ticket Update';
		$customer_name = $data['customer_name'] ?? 'Customer';
		$entity_name = $data['entity_name'] ?? get_bloginfo( 'name' );

		$content = "<!DOCTYPE html>
<html>
<head>
	<meta charset='UTF-8'>
	<title>{$subject}</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
	<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
		<h2>{$entity_name}</h2>
		<p>Dear {$customer_name},</p>
		<p>This is an automated notification regarding your support ticket.</p>";

		switch ( $template_name ) {
			case 'ticket-created-customer':
				$content .= "<p>Your ticket has been created successfully.</p>";
				break;
			case 'ticket-reply-customer':
				$content .= "<p>A new reply has been posted to your ticket.</p>";
				break;
			case 'sla-breach-warning':
				$content .= "<p>This ticket requires immediate attention due to SLA requirements.</p>";
				break;
			default:
				$content .= "<p>Your ticket has been updated.</p>";
		}

		$content .= "
		<p>You can view your ticket by clicking the link below:</p>
		<p><a href='{$data['ticket_url']}' style='display: inline-block; background-color: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;'>View Ticket</a></p>
		
		<p>For your convenience, you can also access your ticket directly using this secure link (valid for 48 hours):</p>
		<p><a href='{$data['guest_access_url']}' style='display: inline-block; background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;'>Direct Access to Ticket</a></p>

		<p>Thank you for contacting us.</p>
		<p>Best regards,<br>{$entity_name} Support Team</p>
	</div>
</body>
</html>";

		return $this->process_variables( $content, $data );
	}

	/**
	 * Generate email data from ticket
	 *
	 * @since    1.0.0
	 * @param    object   $ticket    Ticket object
	 * @param    array    $extra     Extra data
	 * @return   array               Email template data
	 */
	public function generate_ticket_data( $ticket, $extra = array() ) {
		// Get entity data
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entity = $entity_model->get_by_id( $ticket->entity_id );

		// Get assigned agent data
		$agent_name = '';
		$agent_email = '';
		if ( $ticket->assigned_to ) {
			$agent = get_user_by( 'id', $ticket->assigned_to );
			if ( $agent ) {
				$agent_name = $agent->display_name;
				$agent_email = $agent->user_email;
			}
		}

		// Format dates
		$created_date = $ticket->created_at ? 
			wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ) : '';
		
		$updated_date = $ticket->updated_at ? 
			wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->updated_at ) ) : '';

		// Generate URLs
		$portal_url = METS_Core::get_ticket_portal_url();
		$ticket_url = $portal_url ? $portal_url . '?ticket=' . $ticket->ticket_number : '';
		$admin_ticket_url = admin_url( 'admin.php?page=mets-all-tickets&action=view&id=' . $ticket->id );
		
		// Generate guest access URL
		$guest_access_url = '';
		if ( ! empty( $ticket->customer_email ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-guest-access-token-model.php';
			$token_model = new METS_Guest_Access_Token_Model();
			$guest_token = $token_model->generate_token( $ticket->id, $ticket->customer_email );
			
			if ( $guest_token ) {
				$guest_access_url = add_query_arg( 
					array(
						'mets_view' => 'ticket',
						'mets_ticket' => $ticket->id,
						'access_token' => $guest_token,
					),
					home_url( '/guest-ticket-access' )
				);
			}
		}

		$data = array(
			'ticket_number' => $ticket->ticket_number,
			'ticket_subject' => $ticket->subject,
			'ticket_content' => $ticket->description,
			'ticket_url' => $ticket_url,
			'admin_ticket_url' => $admin_ticket_url,
			'guest_access_url' => $guest_access_url,
			'customer_name' => $ticket->customer_name,
			'customer_email' => $ticket->customer_email,
			'customer_phone' => $ticket->customer_phone,
			'agent_name' => $agent_name,
			'agent_email' => $agent_email,
			'entity_name' => $entity ? $entity->name : get_bloginfo( 'name' ),
			'entity_email' => $entity && $entity->settings ? 
				( json_decode( $entity->settings, true )['support_email'] ?? get_option( 'admin_email' ) ) : 
				get_option( 'admin_email' ),
			'portal_url' => $portal_url,
			'status' => $this->format_status( $ticket->status ),
			'priority' => $this->format_priority( $ticket->priority ),
			'category' => $ticket->category,
			'created_date' => $created_date,
			'updated_date' => $updated_date,
		);

		// Add SLA data if available
		if ( $ticket->sla_response_due ) {
			$data['response_due'] = wp_date( 
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), 
				strtotime( $ticket->sla_response_due ) 
			);
		}

		if ( $ticket->sla_resolution_due ) {
			$data['resolution_due'] = wp_date( 
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), 
				strtotime( $ticket->sla_resolution_due ) 
			);
		}

		if ( $ticket->sla_escalation_due ) {
			$data['escalation_due'] = wp_date( 
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), 
				strtotime( $ticket->sla_escalation_due ) 
			);
		}

		// Merge with extra data
		return array_merge( $data, $extra );
	}

	/**
	 * Format status for display
	 *
	 * @since    1.0.0
	 * @param    string   $status    Status slug
	 * @return   string              Formatted status
	 */
	private function format_status( $status ) {
		$statuses = get_option( 'mets_ticket_statuses', array() );
		return isset( $statuses[ $status ]['label'] ) ? $statuses[ $status ]['label'] : ucfirst( $status );
	}

	/**
	 * Format priority for display
	 *
	 * @since    1.0.0
	 * @param    string   $priority    Priority slug
	 * @return   string                Formatted priority
	 */
	private function format_priority( $priority ) {
		$priorities = get_option( 'mets_ticket_priorities', array() );
		return isset( $priorities[ $priority ]['label'] ) ? $priorities[ $priority ]['label'] : ucfirst( $priority );
	}

	/**
	 * Get available templates
	 *
	 * @since    1.0.0
	 * @return   array    Available templates
	 */
	public function get_available_templates() {
		$templates = array();
		$template_files = glob( $this->templates_path . '*.php' );

		foreach ( $template_files as $file ) {
			$name = basename( $file, '.php' );
			$templates[ $name ] = $this->get_template_info( $name );
		}

		return $templates;
	}

	/**
	 * Get template information
	 *
	 * @since    1.0.0
	 * @param    string   $template_name    Template name
	 * @return   array                     Template info
	 */
	private function get_template_info( $template_name ) {
		$info = array(
			'name' => $template_name,
			'title' => $this->get_template_title( $template_name ),
			'description' => $this->get_template_description( $template_name ),
		);

		return $info;
	}

	/**
	 * Get template title
	 *
	 * @since    1.0.0
	 * @param    string   $template_name    Template name
	 * @return   string                    Template title
	 */
	private function get_template_title( $template_name ) {
		$titles = array(
			'ticket-created-customer' => __( 'Ticket Created - Customer', METS_TEXT_DOMAIN ),
			'ticket-created-agent' => __( 'Ticket Created - Agent', METS_TEXT_DOMAIN ),
			'ticket-reply-customer' => __( 'New Reply - Customer', METS_TEXT_DOMAIN ),
			'ticket-reply-agent' => __( 'New Reply - Agent', METS_TEXT_DOMAIN ),
			'ticket-assigned' => __( 'Ticket Assigned', METS_TEXT_DOMAIN ),
			'ticket-status-changed' => __( 'Status Changed', METS_TEXT_DOMAIN ),
			'sla-breach-warning' => __( 'SLA Breach Warning', METS_TEXT_DOMAIN ),
			'sla-breach-notification' => __( 'SLA Breach Notification', METS_TEXT_DOMAIN ),
			'escalation-notification' => __( 'Escalation Notification', METS_TEXT_DOMAIN ),
		);

		return $titles[ $template_name ] ?? ucwords( str_replace( array( '-', '_' ), ' ', $template_name ) );
	}

	/**
	 * Get template description
	 *
	 * @since    1.0.0
	 * @param    string   $template_name    Template name
	 * @return   string                    Template description
	 */
	private function get_template_description( $template_name ) {
		$descriptions = array(
			'ticket-created-customer' => __( 'Sent to customers when they create a new ticket', METS_TEXT_DOMAIN ),
			'ticket-created-agent' => __( 'Sent to agents when a new ticket is created', METS_TEXT_DOMAIN ),
			'ticket-reply-customer' => __( 'Sent to customers when agents reply to their tickets', METS_TEXT_DOMAIN ),
			'ticket-reply-agent' => __( 'Sent to agents when customers reply to tickets', METS_TEXT_DOMAIN ),
			'sla-breach-warning' => __( 'Sent to agents when SLA deadline is approaching', METS_TEXT_DOMAIN ),
		);

		return $descriptions[ $template_name ] ?? '';
	}

	/**
	 * Get available template variables
	 *
	 * @since    1.0.0
	 * @return   array    Available variables
	 */
	public function get_available_variables() {
		return $this->available_variables;
	}

	/**
	 * Preview template with sample data
	 *
	 * @since    1.0.0
	 * @param    string   $template_name    Template name
	 * @return   string                    Preview content
	 */
	public function preview_template( $template_name ) {
		$sample_data = array(
			'ticket_number' => 'DEMO-001',
			'ticket_subject' => 'Sample Support Request',
			'ticket_content' => 'This is a sample ticket message for preview purposes.',
			'customer_name' => 'John Doe',
			'customer_email' => 'john@example.com',
			'agent_name' => 'Support Agent',
			'entity_name' => get_bloginfo( 'name' ),
			'status' => 'Open',
			'priority' => 'Normal',
			'created_date' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'reply_content' => 'This is a sample reply for preview purposes.',
			'reply_date' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
		);

		return $this->load_template( $template_name, $sample_data );
	}
}