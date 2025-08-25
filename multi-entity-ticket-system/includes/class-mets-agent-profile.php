<?php
/**
 * Agent Profile Management
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Agent_Profile {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Agent_Profile    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Agent_Profile    Single instance
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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Add profile fields to user edit screen
		add_action( 'show_user_profile', array( $this, 'add_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'add_profile_fields' ) );
		
		// Save profile fields
		add_action( 'personal_options_update', array( $this, 'save_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile_fields' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_mets_update_agent_availability', array( $this, 'ajax_update_availability' ) );
		add_action( 'wp_ajax_mets_get_agent_schedule', array( $this, 'ajax_get_schedule' ) );
		add_action( 'wp_ajax_mets_update_agent_skills', array( $this, 'ajax_update_skills' ) );
	}

	/**
	 * Add profile fields to user edit screen
	 *
	 * @since    1.0.0
	 * @param    WP_User    $user    User object
	 */
	public function add_profile_fields( $user ) {
		// Check if user has any METS roles
		$mets_roles = array( 'ticket_agent', 'senior_agent', 'ticket_manager', 'support_supervisor' );
		$has_mets_role = false;
		
		foreach ( $mets_roles as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				$has_mets_role = true;
				break;
			}
		}
		
		if ( ! $has_mets_role && ! current_user_can( 'manage_agents' ) ) {
			return;
		}
		
		// Get profile data
		$skills = $this->get_agent_skills( $user->ID );
		$availability = $this->get_agent_availability( $user->ID );
		$preferences = $this->get_agent_preferences( $user->ID );
		$languages = $this->get_agent_languages( $user->ID );
		
		?>
		<h2><?php _e( 'Agent Profile', METS_TEXT_DOMAIN ); ?></h2>
		
		<table class="form-table">
			<!-- Skills -->
			<tr>
				<th><label><?php _e( 'Skills & Expertise', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<div id="mets-agent-skills">
						<?php
						$available_skills = $this->get_available_skills();
						foreach ( $available_skills as $skill_id => $skill_name ) :
							$checked = in_array( $skill_id, $skills );
						?>
							<label style="display: inline-block; margin-right: 15px;">
								<input type="checkbox" name="mets_skills[]" value="<?php echo esc_attr( $skill_id ); ?>" <?php checked( $checked ); ?> />
								<?php echo esc_html( $skill_name ); ?>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="description"><?php _e( 'Select the areas of expertise for this agent.', METS_TEXT_DOMAIN ); ?></p>
				</td>
			</tr>
			
			<!-- Languages -->
			<tr>
				<th><label><?php _e( 'Languages', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<input type="text" name="mets_languages" id="mets_languages" value="<?php echo esc_attr( implode( ', ', $languages ) ); ?>" class="regular-text" />
					<p class="description"><?php _e( 'Comma-separated list of languages (e.g., English, Spanish, French)', METS_TEXT_DOMAIN ); ?></p>
				</td>
			</tr>
			
			<!-- Availability -->
			<tr>
				<th><label><?php _e( 'Weekly Schedule', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<div id="mets-agent-availability">
						<?php $this->render_availability_schedule( $availability ); ?>
					</div>
				</td>
			</tr>
			
			<!-- Workload Capacity -->
			<tr>
				<th><label for="mets_max_tickets"><?php _e( 'Maximum Concurrent Tickets', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<input type="number" name="mets_max_tickets" id="mets_max_tickets" value="<?php echo esc_attr( $preferences['max_tickets'] ?? 20 ); ?>" min="1" max="100" />
					<p class="description"><?php _e( 'Maximum number of tickets this agent can handle at once.', METS_TEXT_DOMAIN ); ?></p>
				</td>
			</tr>
			
			<!-- Response Time Target -->
			<tr>
				<th><label for="mets_response_time"><?php _e( 'Target Response Time', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<select name="mets_response_time" id="mets_response_time">
						<option value="15" <?php selected( $preferences['response_time'] ?? '', '15' ); ?>><?php _e( '15 minutes', METS_TEXT_DOMAIN ); ?></option>
						<option value="30" <?php selected( $preferences['response_time'] ?? '', '30' ); ?>><?php _e( '30 minutes', METS_TEXT_DOMAIN ); ?></option>
						<option value="60" <?php selected( $preferences['response_time'] ?? '60', '60' ); ?>><?php _e( '1 hour', METS_TEXT_DOMAIN ); ?></option>
						<option value="120" <?php selected( $preferences['response_time'] ?? '', '120' ); ?>><?php _e( '2 hours', METS_TEXT_DOMAIN ); ?></option>
						<option value="240" <?php selected( $preferences['response_time'] ?? '', '240' ); ?>><?php _e( '4 hours', METS_TEXT_DOMAIN ); ?></option>
					</select>
					<p class="description"><?php _e( 'Target time for initial response to new tickets.', METS_TEXT_DOMAIN ); ?></p>
				</td>
			</tr>
			
			<!-- Ticket Preferences -->
			<tr>
				<th><label><?php _e( 'Ticket Preferences', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" name="mets_prefer_priority" value="1" <?php checked( $preferences['prefer_priority'] ?? false ); ?> />
						<?php _e( 'Prefer high priority tickets', METS_TEXT_DOMAIN ); ?>
					</label><br>
					<label>
						<input type="checkbox" name="mets_prefer_new" value="1" <?php checked( $preferences['prefer_new'] ?? false ); ?> />
						<?php _e( 'Prefer new tickets over existing', METS_TEXT_DOMAIN ); ?>
					</label><br>
					<label>
						<input type="checkbox" name="mets_auto_assign" value="1" <?php checked( $preferences['auto_assign'] ?? false ); ?> />
						<?php _e( 'Allow automatic ticket assignment', METS_TEXT_DOMAIN ); ?>
					</label>
				</td>
			</tr>
			
			<!-- Notification Preferences -->
			<tr>
				<th><label><?php _e( 'Notifications', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" name="mets_notify_assignment" value="1" <?php checked( $preferences['notify_assignment'] ?? true ); ?> />
						<?php _e( 'Email on ticket assignment', METS_TEXT_DOMAIN ); ?>
					</label><br>
					<label>
						<input type="checkbox" name="mets_notify_mention" value="1" <?php checked( $preferences['notify_mention'] ?? true ); ?> />
						<?php _e( 'Email when mentioned in ticket', METS_TEXT_DOMAIN ); ?>
					</label><br>
					<label>
						<input type="checkbox" name="mets_notify_escalation" value="1" <?php checked( $preferences['notify_escalation'] ?? true ); ?> />
						<?php _e( 'Email on ticket escalation', METS_TEXT_DOMAIN ); ?>
					</label>
				</td>
			</tr>
			
			<!-- Status -->
			<tr>
				<th><label for="mets_agent_status"><?php _e( 'Current Status', METS_TEXT_DOMAIN ); ?></label></th>
				<td>
					<select name="mets_agent_status" id="mets_agent_status">
						<option value="available" <?php selected( $preferences['status'] ?? 'available', 'available' ); ?>><?php _e( 'Available', METS_TEXT_DOMAIN ); ?></option>
						<option value="busy" <?php selected( $preferences['status'] ?? '', 'busy' ); ?>><?php _e( 'Busy', METS_TEXT_DOMAIN ); ?></option>
						<option value="away" <?php selected( $preferences['status'] ?? '', 'away' ); ?>><?php _e( 'Away', METS_TEXT_DOMAIN ); ?></option>
						<option value="offline" <?php selected( $preferences['status'] ?? '', 'offline' ); ?>><?php _e( 'Offline', METS_TEXT_DOMAIN ); ?></option>
					</select>
					<input type="text" name="mets_status_message" placeholder="<?php esc_attr_e( 'Status message (optional)', METS_TEXT_DOMAIN ); ?>" value="<?php echo esc_attr( $preferences['status_message'] ?? '' ); ?>" class="regular-text" />
				</td>
			</tr>
		</table>
		
		<style>
			.mets-schedule-table {
				border-collapse: collapse;
				margin-top: 10px;
			}
			.mets-schedule-table th,
			.mets-schedule-table td {
				border: 1px solid #ddd;
				padding: 8px;
				text-align: center;
			}
			.mets-schedule-table th {
				background: #f4f4f4;
				font-weight: bold;
			}
			.mets-schedule-table input[type="time"] {
				width: 100px;
			}
		</style>
		<?php
	}

	/**
	 * Render availability schedule table
	 *
	 * @since    1.0.0
	 * @param    array    $availability    Availability data
	 */
	private function render_availability_schedule( $availability ) {
		$days = array(
			'monday' => __( 'Monday', METS_TEXT_DOMAIN ),
			'tuesday' => __( 'Tuesday', METS_TEXT_DOMAIN ),
			'wednesday' => __( 'Wednesday', METS_TEXT_DOMAIN ),
			'thursday' => __( 'Thursday', METS_TEXT_DOMAIN ),
			'friday' => __( 'Friday', METS_TEXT_DOMAIN ),
			'saturday' => __( 'Saturday', METS_TEXT_DOMAIN ),
			'sunday' => __( 'Sunday', METS_TEXT_DOMAIN )
		);
		
		?>
		<table class="mets-schedule-table">
			<thead>
				<tr>
					<th><?php _e( 'Day', METS_TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Available', METS_TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Start Time', METS_TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'End Time', METS_TEXT_DOMAIN ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $days as $day_key => $day_name ) : 
					$day_data = $availability[ $day_key ] ?? array();
					$is_available = $day_data['available'] ?? false;
					$start_time = $day_data['start'] ?? '09:00';
					$end_time = $day_data['end'] ?? '17:00';
				?>
					<tr>
						<td><?php echo esc_html( $day_name ); ?></td>
						<td>
							<input type="checkbox" name="mets_availability[<?php echo esc_attr( $day_key ); ?>][available]" value="1" <?php checked( $is_available ); ?> class="mets-day-available" data-day="<?php echo esc_attr( $day_key ); ?>" />
						</td>
						<td>
							<input type="time" name="mets_availability[<?php echo esc_attr( $day_key ); ?>][start]" value="<?php echo esc_attr( $start_time ); ?>" <?php echo $is_available ? '' : 'disabled'; ?> class="mets-time-<?php echo esc_attr( $day_key ); ?>" />
						</td>
						<td>
							<input type="time" name="mets_availability[<?php echo esc_attr( $day_key ); ?>][end]" value="<?php echo esc_attr( $end_time ); ?>" <?php echo $is_available ? '' : 'disabled'; ?> class="mets-time-<?php echo esc_attr( $day_key ); ?>" />
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<script>
		jQuery(document).ready(function($) {
			$('.mets-day-available').on('change', function() {
				var day = $(this).data('day');
				var isChecked = $(this).is(':checked');
				$('.mets-time-' + day).prop('disabled', !isChecked);
			});
		});
		</script>
		<?php
	}

	/**
	 * Save profile fields
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 */
	public function save_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		
		// Save skills
		$skills = isset( $_POST['mets_skills'] ) ? array_map( 'sanitize_text_field', $_POST['mets_skills'] ) : array();
		update_user_meta( $user_id, 'mets_agent_skills', $skills );
		
		// Save languages
		$languages = isset( $_POST['mets_languages'] ) ? sanitize_text_field( $_POST['mets_languages'] ) : '';
		$languages_array = array_map( 'trim', explode( ',', $languages ) );
		update_user_meta( $user_id, 'mets_agent_languages', array_filter( $languages_array ) );
		
		// Save availability
		$availability = isset( $_POST['mets_availability'] ) ? $_POST['mets_availability'] : array();
		$clean_availability = array();
		
		foreach ( $availability as $day => $data ) {
			$clean_availability[ $day ] = array(
				'available' => isset( $data['available'] ) && $data['available'] == '1',
				'start' => sanitize_text_field( $data['start'] ?? '09:00' ),
				'end' => sanitize_text_field( $data['end'] ?? '17:00' )
			);
		}
		
		update_user_meta( $user_id, 'mets_agent_availability', $clean_availability );
		
		// Save preferences
		$preferences = array(
			'max_tickets' => isset( $_POST['mets_max_tickets'] ) ? intval( $_POST['mets_max_tickets'] ) : 20,
			'response_time' => isset( $_POST['mets_response_time'] ) ? intval( $_POST['mets_response_time'] ) : 60,
			'prefer_priority' => isset( $_POST['mets_prefer_priority'] ) && $_POST['mets_prefer_priority'] == '1',
			'prefer_new' => isset( $_POST['mets_prefer_new'] ) && $_POST['mets_prefer_new'] == '1',
			'auto_assign' => isset( $_POST['mets_auto_assign'] ) && $_POST['mets_auto_assign'] == '1',
			'notify_assignment' => isset( $_POST['mets_notify_assignment'] ) && $_POST['mets_notify_assignment'] == '1',
			'notify_mention' => isset( $_POST['mets_notify_mention'] ) && $_POST['mets_notify_mention'] == '1',
			'notify_escalation' => isset( $_POST['mets_notify_escalation'] ) && $_POST['mets_notify_escalation'] == '1',
			'status' => isset( $_POST['mets_agent_status'] ) ? sanitize_text_field( $_POST['mets_agent_status'] ) : 'available',
			'status_message' => isset( $_POST['mets_status_message'] ) ? sanitize_text_field( $_POST['mets_status_message'] ) : ''
		);
		
		update_user_meta( $user_id, 'mets_agent_preferences', $preferences );
		
		// Update last activity
		update_user_meta( $user_id, 'mets_last_activity', current_time( 'mysql' ) );
	}

	/**
	 * Get available skills
	 *
	 * @since    1.0.0
	 * @return   array    Skills array
	 */
	public function get_available_skills() {
		return apply_filters( 'mets_agent_skills', array(
			'technical' => __( 'Technical Support', METS_TEXT_DOMAIN ),
			'billing' => __( 'Billing & Payments', METS_TEXT_DOMAIN ),
			'sales' => __( 'Sales & Pre-sales', METS_TEXT_DOMAIN ),
			'product' => __( 'Product Knowledge', METS_TEXT_DOMAIN ),
			'troubleshooting' => __( 'Advanced Troubleshooting', METS_TEXT_DOMAIN ),
			'escalation' => __( 'Escalation Handling', METS_TEXT_DOMAIN ),
			'training' => __( 'Customer Training', METS_TEXT_DOMAIN ),
			'api' => __( 'API Support', METS_TEXT_DOMAIN ),
			'integration' => __( 'Integration Support', METS_TEXT_DOMAIN ),
			'security' => __( 'Security Issues', METS_TEXT_DOMAIN ),
			'performance' => __( 'Performance Optimization', METS_TEXT_DOMAIN ),
			'data' => __( 'Data Analysis', METS_TEXT_DOMAIN )
		) );
	}

	/**
	 * Get agent skills
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 * @return   array              Skills array
	 */
	public function get_agent_skills( $user_id ) {
		$skills = get_user_meta( $user_id, 'mets_agent_skills', true );
		return is_array( $skills ) ? $skills : array();
	}

	/**
	 * Get agent languages
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 * @return   array              Languages array
	 */
	public function get_agent_languages( $user_id ) {
		$languages = get_user_meta( $user_id, 'mets_agent_languages', true );
		return is_array( $languages ) ? $languages : array();
	}

	/**
	 * Get agent availability
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 * @return   array              Availability array
	 */
	public function get_agent_availability( $user_id ) {
		$availability = get_user_meta( $user_id, 'mets_agent_availability', true );
		
		// Default availability
		$default = array(
			'monday' => array( 'available' => true, 'start' => '09:00', 'end' => '17:00' ),
			'tuesday' => array( 'available' => true, 'start' => '09:00', 'end' => '17:00' ),
			'wednesday' => array( 'available' => true, 'start' => '09:00', 'end' => '17:00' ),
			'thursday' => array( 'available' => true, 'start' => '09:00', 'end' => '17:00' ),
			'friday' => array( 'available' => true, 'start' => '09:00', 'end' => '17:00' ),
			'saturday' => array( 'available' => false, 'start' => '09:00', 'end' => '17:00' ),
			'sunday' => array( 'available' => false, 'start' => '09:00', 'end' => '17:00' )
		);
		
		return is_array( $availability ) ? array_merge( $default, $availability ) : $default;
	}

	/**
	 * Get agent preferences
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 * @return   array              Preferences array
	 */
	public function get_agent_preferences( $user_id ) {
		$preferences = get_user_meta( $user_id, 'mets_agent_preferences', true );
		
		// Default preferences
		$defaults = array(
			'max_tickets' => 20,
			'response_time' => 60,
			'prefer_priority' => false,
			'prefer_new' => false,
			'auto_assign' => true,
			'notify_assignment' => true,
			'notify_mention' => true,
			'notify_escalation' => true,
			'status' => 'available',
			'status_message' => ''
		);
		
		return is_array( $preferences ) ? array_merge( $defaults, $preferences ) : $defaults;
	}

	/**
	 * Check if agent is available now
	 *
	 * @since    1.0.0
	 * @param    int     $user_id    User ID
	 * @param    string  $timezone   Timezone (optional)
	 * @return   bool                Whether agent is available
	 */
	public function is_agent_available( $user_id, $timezone = null ) {
		$preferences = $this->get_agent_preferences( $user_id );
		
		// Check manual status
		if ( $preferences['status'] === 'offline' || $preferences['status'] === 'away' ) {
			return false;
		}
		
		// Check schedule
		$availability = $this->get_agent_availability( $user_id );
		
		// Get current day and time
		$timezone = $timezone ?: wp_timezone();
		$now = new DateTime( 'now', $timezone );
		$current_day = strtolower( $now->format( 'l' ) );
		$current_time = $now->format( 'H:i' );
		
		// Check if available today
		if ( ! isset( $availability[ $current_day ] ) || ! $availability[ $current_day ]['available'] ) {
			return false;
		}
		
		// Check if within working hours
		$start_time = $availability[ $current_day ]['start'];
		$end_time = $availability[ $current_day ]['end'];
		
		return $current_time >= $start_time && $current_time <= $end_time;
	}

	/**
	 * Get available agents
	 *
	 * @since    1.0.0
	 * @param    array    $criteria    Filter criteria
	 * @return   array                 Available agents
	 */
	public function get_available_agents( $criteria = array() ) {
		$defaults = array(
			'skills' => array(),
			'languages' => array(),
			'entity_ids' => array(),
			'max_workload' => null,
			'include_busy' => false
		);
		
		$criteria = wp_parse_args( $criteria, $defaults );
		
		// Get all agents
		$role_manager = METS_Role_Manager::get_instance();
		$agent_roles = array( 'ticket_agent', 'senior_agent', 'ticket_manager' );
		$all_agents = array();
		
		foreach ( $agent_roles as $role ) {
			$agents = get_users( array( 'role' => $role ) );
			$all_agents = array_merge( $all_agents, $agents );
		}
		
		// Filter agents
		$available_agents = array();
		
		foreach ( $all_agents as $agent ) {
			// Check if available
			if ( ! $this->is_agent_available( $agent->ID ) ) {
				continue;
			}
			
			// Check skills
			if ( ! empty( $criteria['skills'] ) ) {
				$agent_skills = $this->get_agent_skills( $agent->ID );
				if ( ! array_intersect( $criteria['skills'], $agent_skills ) ) {
					continue;
				}
			}
			
			// Check languages
			if ( ! empty( $criteria['languages'] ) ) {
				$agent_languages = $this->get_agent_languages( $agent->ID );
				if ( ! array_intersect( $criteria['languages'], $agent_languages ) ) {
					continue;
				}
			}
			
			// Check entity assignment
			if ( ! empty( $criteria['entity_ids'] ) ) {
				$assigned_entities = $role_manager->get_user_assigned_entities( $agent->ID );
				if ( ! array_intersect( $criteria['entity_ids'], $assigned_entities ) ) {
					continue;
				}
			}
			
			// Check workload
			$workload = $role_manager->get_agent_workload( $agent->ID );
			$preferences = $this->get_agent_preferences( $agent->ID );
			
			if ( $criteria['max_workload'] !== null && $workload > $criteria['max_workload'] ) {
				continue;
			}
			
			if ( ! $criteria['include_busy'] && $workload >= $preferences['max_tickets'] ) {
				continue;
			}
			
			$available_agents[] = array(
				'id' => $agent->ID,
				'name' => $agent->display_name,
				'email' => $agent->user_email,
				'workload' => $workload,
				'capacity' => $preferences['max_tickets'],
				'skills' => $this->get_agent_skills( $agent->ID ),
				'languages' => $this->get_agent_languages( $agent->ID ),
				'status' => $preferences['status'],
				'response_time' => $preferences['response_time']
			);
		}
		
		// Sort by workload (ascending)
		usort( $available_agents, function( $a, $b ) {
			return $a['workload'] - $b['workload'];
		});
		
		return $available_agents;
	}

	/**
	 * AJAX handler to update agent availability
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_availability() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		$user_id = get_current_user_id();
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
		
		if ( ! in_array( $status, array( 'available', 'busy', 'away', 'offline' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status.', METS_TEXT_DOMAIN ) ) );
		}
		
		$preferences = $this->get_agent_preferences( $user_id );
		$preferences['status'] = $status;
		$preferences['status_message'] = $message;
		
		update_user_meta( $user_id, 'mets_agent_preferences', $preferences );
		update_user_meta( $user_id, 'mets_last_activity', current_time( 'mysql' ) );
		
		wp_send_json_success( array( 
			'message' => __( 'Availability updated successfully.', METS_TEXT_DOMAIN ),
			'status' => $status
		) );
	}

	/**
	 * AJAX handler to get agent schedule
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_schedule() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : get_current_user_id();
		
		if ( ! current_user_can( 'edit_user', $user_id ) && $user_id != get_current_user_id() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$availability = $this->get_agent_availability( $user_id );
		$preferences = $this->get_agent_preferences( $user_id );
		
		wp_send_json_success( array(
			'availability' => $availability,
			'status' => $preferences['status'],
			'status_message' => $preferences['status_message'],
			'is_available_now' => $this->is_agent_available( $user_id )
		) );
	}

	/**
	 * AJAX handler to update agent skills
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_skills() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$skills = isset( $_POST['skills'] ) ? array_map( 'sanitize_text_field', $_POST['skills'] ) : array();
		
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		update_user_meta( $user_id, 'mets_agent_skills', $skills );
		
		wp_send_json_success( array( 
			'message' => __( 'Skills updated successfully.', METS_TEXT_DOMAIN ),
			'skills' => $skills
		) );
	}

	/**
	 * Get best agent for ticket
	 *
	 * @since    1.0.0
	 * @param    array    $ticket_data    Ticket data
	 * @return   int|null                Best agent ID or null
	 */
	public function get_best_agent_for_ticket( $ticket_data ) {
		$criteria = array(
			'entity_ids' => array( $ticket_data['entity_id'] )
		);
		
		// Add skill requirements based on ticket category/tags
		if ( ! empty( $ticket_data['category'] ) ) {
			// Map categories to skills
			$category_skills = apply_filters( 'mets_category_skill_mapping', array(
				'technical' => array( 'technical', 'troubleshooting' ),
				'billing' => array( 'billing' ),
				'sales' => array( 'sales' ),
				'support' => array( 'product', 'technical' )
			) );
			
			if ( isset( $category_skills[ $ticket_data['category'] ] ) ) {
				$criteria['skills'] = $category_skills[ $ticket_data['category'] ];
			}
		}
		
		// Get available agents
		$available_agents = $this->get_available_agents( $criteria );
		
		if ( empty( $available_agents ) ) {
			// Try without skill requirements
			unset( $criteria['skills'] );
			$available_agents = $this->get_available_agents( $criteria );
		}
		
		if ( empty( $available_agents ) ) {
			return null;
		}
		
		// Return agent with lowest workload
		return $available_agents[0]['id'];
	}
}