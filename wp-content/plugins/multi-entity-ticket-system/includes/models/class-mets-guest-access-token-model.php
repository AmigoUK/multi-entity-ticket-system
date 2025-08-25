<?php
/**
 * Guest Access Token Model
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Guest Access Token Model class.
 *
 * This class handles guest access token operations.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Guest_Access_Token_Model {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'multi-entity-ticket-system';
		$this->version = '1.0.0';
	}

	/**
	 * Generate a secure guest access token
	 *
	 * @since    1.0.0
	 * @param    int     $ticket_id        Ticket ID
	 * @param    string  $customer_email   Customer email
	 * @param    int     $expires_in_hours Token expiration time in hours (default 48)
	 * @param    int     $max_uses         Maximum number of uses (default 5)
	 * @return   string|false              Token string or false on failure
	 */
	public function generate_token( $ticket_id, $customer_email, $expires_in_hours = 48, $max_uses = 5 ) {
		global $wpdb;

		// Generate a secure random token
		$token = wp_generate_password( 32, false );
		
		// Calculate expiration time
		$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( "+{$expires_in_hours} hours" ) );
		
		// Get client IP address
		$ip_address = $this->get_client_ip();
		
		// Insert token into database
		$table_name = $wpdb->prefix . 'mets_guest_access_tokens';
		$result = $wpdb->insert(
			$table_name,
			array(
				'ticket_id'      => $ticket_id,
				'token'          => $token,
				'customer_email' => $customer_email,
				'expires_at'     => $expires_at,
				'max_uses'       => $max_uses,
				'ip_address'     => $ip_address,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			)
		);
		
		if ( $result === false ) {
			error_log( '[METS] Failed to generate guest access token for ticket ' . $ticket_id );
			return false;
		}
		
		return $token;
	}

	/**
	 * Validate a guest access token
	 *
	 * @since    1.0.0
	 * @param    string  $token            Token to validate
	 * @param    int     $ticket_id        Expected ticket ID
	 * @param    string  $customer_email   Expected customer email
	 * @return   array|false               Token data or false if invalid
	 */
	public function validate_token( $token, $ticket_id = null, $customer_email = null ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mets_guest_access_tokens';
		
		// Build query
		$query = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE token = %s AND expires_at > NOW() AND is_revoked = 0",
			$token
		);
		
		// Add ticket ID condition if provided
		if ( $ticket_id ) {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE token = %s AND ticket_id = %d AND expires_at > NOW() AND is_revoked = 0",
				$token,
				$ticket_id
			);
		}
		
		$token_data = $wpdb->get_row( $query, ARRAY_A );
		
		if ( ! $token_data ) {
			return false;
		}
		
		// Check if token has exceeded max uses
		if ( $token_data['used_count'] >= $token_data['max_uses'] ) {
			// Revoke the token
			$this->revoke_token( $token_data['id'] );
			return false;
		}
		
		// Check if customer email matches if provided
		if ( $customer_email && $token_data['customer_email'] !== $customer_email ) {
			return false;
		}
		
		// Check IP address if stored
		$client_ip = $this->get_client_ip();
		if ( ! empty( $token_data['ip_address'] ) && $token_data['ip_address'] !== $client_ip ) {
			// For now, we'll allow this but could make it configurable
			// return false;
		}
		
		return $token_data;
	}

	/**
	 * Use a guest access token (increment usage count)
	 *
	 * @since    1.0.0
	 * @param    int     $token_id         Token ID
	 * @return   bool                      True on success, false on failure
	 */
	public function use_token( $token_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mets_guest_access_tokens';
		
		// Increment used count
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name} SET used_count = used_count + 1 WHERE id = %d",
				$token_id
			)
		);
		
		return $result !== false;
	}

	/**
	 * Revoke a guest access token
	 *
	 * @since    1.0.0
	 * @param    int     $token_id         Token ID
	 * @return   bool                      True on success, false on failure
	 */
	public function revoke_token( $token_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mets_guest_access_tokens';
		
		$result = $wpdb->update(
			$table_name,
			array( 'is_revoked' => 1 ),
			array( 'id' => $token_id ),
			array( '%d' ),
			array( '%d' )
		);
		
		return $result !== false;
	}

	/**
	 * Clean up expired tokens
	 *
	 * @since    1.0.0
	 * @return   int                       Number of tokens cleaned up
	 */
	public function cleanup_expired_tokens() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mets_guest_access_tokens';
		
		// Delete tokens that expired more than 30 days ago
		$result = $wpdb->query(
			"DELETE FROM {$table_name} WHERE expires_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);
		
		return $result !== false ? $result : 0;
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string                    Client IP address
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					// Validate IP address without relying on constants that may not be available
				$ip = trim( $ip );
				
				// Simple IP validation
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
					// Additional check to exclude private IP ranges
					$ip_long = ip2long( $ip );
					if ( $ip_long !== false ) {
						// Check for private IP ranges
						$private_ranges = array(
							array( '0.0.0.0', '0.255.255.255' ),
							array( '10.0.0.0', '10.255.255.255' ),
							array( '127.0.0.0', '127.255.255.255' ),
							array( '169.254.0.0', '169.254.255.255' ),
							array( '172.16.0.0', '172.31.255.255' ),
							array( '192.0.0.0', '192.0.0.255' ),
							array( '192.0.2.0', '192.0.2.255' ),
							array( '192.168.0.0', '192.168.255.255' ),
							array( '198.18.0.0', '198.19.255.255' ),
							array( '198.51.100.0', '198.51.100.255' ),
							array( '203.0.113.0', '203.0.113.255' ),
							array( '224.0.0.0', '255.255.255.255' )
						);
						
						$is_private = false;
						foreach ( $private_ranges as $range ) {
							$range_start = ip2long( $range[0] );
							$range_end = ip2long( $range[1] );
							if ( $ip_long >= $range_start && $ip_long <= $range_end ) {
								$is_private = true;
								break;
							}
						}
						
						if ( ! $is_private ) {
							return $ip;
						}
					}
				}
				}
			}
		}
		
		return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	}
}