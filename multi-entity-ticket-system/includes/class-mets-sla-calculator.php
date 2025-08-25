<?php
/**
 * SLA Calculator
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * SLA Calculator class.
 *
 * This class handles SLA calculations including due dates, breach detection,
 * and business hours considerations.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SLA_Calculator {

	/**
	 * SLA Rule Model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SLA_Rule_Model    $sla_model    SLA rule model.
	 */
	private $sla_model;

	/**
	 * Business Hours Model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Business_Hours_Model    $business_hours_model    Business hours model.
	 */
	private $business_hours_model;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-sla-rule-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-business-hours-model.php';

		$this->sla_model = new METS_SLA_Rule_Model();
		$this->business_hours_model = new METS_Business_Hours_Model();
	}

	/**
	 * Calculate SLA due dates for a ticket
	 *
	 * @since    1.0.0
	 * @param    array    $ticket_data    Ticket data array
	 * @return   array                    Array with SLA due dates
	 */
	public function calculate_sla_dates( $ticket_data ) {
		$entity_id = $ticket_data['entity_id'];
		$priority = $ticket_data['priority'] ?? 'normal';
		$created_at = isset( $ticket_data['created_at'] ) ? 
			new DateTime( $ticket_data['created_at'] ) : 
			new DateTime();

		// Get applicable SLA rule
		$sla_rule = $this->sla_model->get_applicable_rule( $entity_id, $priority, $ticket_data );

		if ( ! $sla_rule ) {
			return array(
				'sla_rule_id' => null,
				'sla_response_due' => null,
				'sla_resolution_due' => null,
				'sla_escalation_due' => null,
			);
		}

		$response_due = null;
		$resolution_due = null;
		$escalation_due = null;

		// Calculate response due date
		if ( $sla_rule->response_time ) {
			$response_due = $this->calculate_due_date_internal(
				$created_at,
				$sla_rule->response_time,
				$entity_id,
				$sla_rule->business_hours_only ?? false
			);
		}

		// Calculate resolution due date
		if ( $sla_rule->resolution_time ) {
			$resolution_due = $this->calculate_due_date_internal(
				$created_at,
				$sla_rule->resolution_time,
				$entity_id,
				$sla_rule->business_hours_only ?? false
			);
		}

		// Calculate escalation due date
		if ( $sla_rule->escalation_time ) {
			$escalation_due = $this->calculate_due_date_internal(
				$created_at,
				$sla_rule->escalation_time,
				$entity_id,
				$sla_rule->business_hours_only ?? false
			);
		}

		return array(
			'sla_rule_id' => $sla_rule->id,
			'sla_response_due' => $response_due ? $response_due->format( 'Y-m-d H:i:s' ) : null,
			'sla_resolution_due' => $resolution_due ? $resolution_due->format( 'Y-m-d H:i:s' ) : null,
			'sla_escalation_due' => $escalation_due ? $escalation_due->format( 'Y-m-d H:i:s' ) : null,
		);
	}


	/**
	 * Check if SLA is breached
	 *
	 * @since    1.0.0
	 * @param    int         $ticket_id    Ticket ID
	 * @param    string      $sla_type     SLA type (response|resolution)
	 * @return   bool                      True if breached
	 */
	public function is_sla_breached( $ticket_id, $sla_type ) {
		global $wpdb;

		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mets_tickets WHERE id = %d",
				$ticket_id
			)
		);

		if ( ! $ticket ) {
			return false;
		}

		$current_time = new DateTime();

		switch ( $sla_type ) {
			case 'response':
				if ( $ticket->first_response_at ) {
					return false; // Already responded
				}
				return $ticket->sla_response_due && 
					   $current_time > new DateTime( $ticket->sla_response_due );

			case 'resolution':
				if ( $ticket->resolved_at ) {
					return false; // Already resolved
				}
				return $ticket->sla_resolution_due && 
					   $current_time > new DateTime( $ticket->sla_resolution_due );

			default:
				return false;
		}
	}

	/**
	 * Get SLA status for a ticket
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @return   array                SLA status information
	 */
	public function get_sla_status( $ticket_id ) {
		global $wpdb;

		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mets_tickets WHERE id = %d",
				$ticket_id
			)
		);

		if ( ! $ticket ) {
			return array(
				'has_sla' => false,
				'response_status' => 'no_sla',
				'resolution_status' => 'no_sla',
			);
		}

		$current_time = new DateTime();
		$response_status = 'no_sla';
		$resolution_status = 'no_sla';

		// Check response SLA
		if ( $ticket->sla_response_due ) {
			$response_due = new DateTime( $ticket->sla_response_due );
			
			if ( $ticket->first_response_at ) {
				$response_time = new DateTime( $ticket->first_response_at );
				$response_status = $response_time <= $response_due ? 'met' : 'breached';
			} else {
				$response_status = $current_time <= $response_due ? 'active' : 'breached';
			}
		}

		// Check resolution SLA
		if ( $ticket->sla_resolution_due ) {
			$resolution_due = new DateTime( $ticket->sla_resolution_due );
			
			if ( $ticket->resolved_at ) {
				$resolution_time = new DateTime( $ticket->resolved_at );
				$resolution_status = $resolution_time <= $resolution_due ? 'met' : 'breached';
			} else {
				$resolution_status = $current_time <= $resolution_due ? 'active' : 'breached';
			}
		}

		return array(
			'has_sla' => $ticket->sla_rule_id !== null,
			'response_status' => $response_status,
			'resolution_status' => $resolution_status,
			'response_due' => $ticket->sla_response_due,
			'resolution_due' => $ticket->sla_resolution_due,
			'escalation_due' => $ticket->sla_escalation_due,
		);
	}

	/**
	 * Get tickets approaching SLA breach
	 *
	 * @since    1.0.0
	 * @param    int    $warning_hours    Hours before breach to warn
	 * @return   array                    Array of ticket IDs
	 */
	public function get_tickets_approaching_breach( $warning_hours = 2 ) {
		global $wpdb;

		$warning_time = new DateTime();
		$warning_time->add( new DateInterval( "PT{$warning_hours}H" ) );
		$warning_timestamp = $warning_time->format( 'Y-m-d H:i:s' );

		$current_timestamp = current_time( 'mysql' );

		// Get tickets with response SLA approaching
		$response_tickets = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}mets_tickets 
				WHERE sla_response_due IS NOT NULL 
				AND sla_response_due <= %s 
				AND sla_response_due > %s
				AND first_response_at IS NULL
				AND status NOT IN ('closed', 'resolved')",
				$warning_timestamp,
				$current_timestamp
			)
		);

		// Get tickets with resolution SLA approaching
		$resolution_tickets = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}mets_tickets 
				WHERE sla_resolution_due IS NOT NULL 
				AND sla_resolution_due <= %s 
				AND sla_resolution_due > %s
				AND resolved_at IS NULL
				AND status NOT IN ('closed', 'resolved')",
				$warning_timestamp,
				$current_timestamp
			)
		);

		return array_unique( array_merge( $response_tickets, $resolution_tickets ) );
	}

	/**
	 * Get breached tickets
	 *
	 * @since    1.0.0
	 * @return   array    Array of ticket IDs
	 */
	public function get_breached_tickets() {
		global $wpdb;

		$current_timestamp = current_time( 'mysql' );

		// Get tickets with breached response SLA
		$response_breached = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}mets_tickets 
				WHERE sla_response_due IS NOT NULL 
				AND sla_response_due < %s
				AND first_response_at IS NULL
				AND status NOT IN ('closed', 'resolved')",
				$current_timestamp
			)
		);

		// Get tickets with breached resolution SLA
		$resolution_breached = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}mets_tickets 
				WHERE sla_resolution_due IS NOT NULL 
				AND sla_resolution_due < %s
				AND resolved_at IS NULL
				AND status NOT IN ('closed', 'resolved')",
				$current_timestamp
			)
		);

		return array_unique( array_merge( $response_breached, $resolution_breached ) );
	}

	/**
	 * Update SLA breach flags
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @return   bool                 True on success
	 */
	public function update_breach_flags( $ticket_id ) {
		global $wpdb;

		$response_breached = $this->is_sla_breached( $ticket_id, 'response' );
		$resolution_breached = $this->is_sla_breached( $ticket_id, 'resolution' );

		$result = $wpdb->update(
			$wpdb->prefix . 'mets_tickets',
			array(
				'sla_response_breached' => $response_breached ? 1 : 0,
				'sla_resolution_breached' => $resolution_breached ? 1 : 0,
			),
			array( 'id' => $ticket_id ),
			array( '%d', '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Calculate response time metrics
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @return   array|false          Metrics array or false on failure
	 */
	public function calculate_response_metrics( $ticket_id ) {
		global $wpdb;

		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mets_tickets WHERE id = %d",
				$ticket_id
			)
		);

		if ( ! $ticket || ! $ticket->first_response_at ) {
			return false;
		}

		$created_at = new DateTime( $ticket->created_at );
		$responded_at = new DateTime( $ticket->first_response_at );

		// Calculate total response time
		$total_minutes = $created_at->diff( $responded_at )->days * 1440 + 
						$created_at->diff( $responded_at )->h * 60 + 
						$created_at->diff( $responded_at )->i;

		// Calculate business hours response time if SLA uses business hours
		$business_minutes = 0;
		if ( $ticket->sla_rule_id ) {
			$sla_rule = $this->sla_model->get_by_id( $ticket->sla_rule_id );
			if ( $sla_rule && $sla_rule->business_hours_only ) {
				$business_hours = $this->business_hours_model->calculate_business_hours(
					$ticket->entity_id,
					$created_at,
					$responded_at
				);
				$business_minutes = $business_hours * 60;
			}
		}

		// Check if within SLA
		$within_sla = ! $ticket->sla_response_breached;
		$sla_target = null;
		if ( $ticket->sla_rule_id ) {
			$sla_rule = $this->sla_model->get_by_id( $ticket->sla_rule_id );
			if ( $sla_rule && $sla_rule->response_time_hours ) {
				$sla_target = $sla_rule->response_time_hours * 60;
			}
		}

		return array(
			'ticket_id' => $ticket_id,
			'metric_type' => 'first_response',
			'start_time' => $ticket->created_at,
			'end_time' => $ticket->first_response_at,
			'duration_minutes' => $total_minutes,
			'business_duration_minutes' => $business_minutes,
			'sla_target_minutes' => $sla_target,
			'within_sla' => $within_sla,
		);
	}

	/**
	 * Get SLA compliance rate for entity
	 *
	 * @since    1.0.0
	 * @param    int         $entity_id    Entity ID
	 * @param    DateTime    $from_date    From date
	 * @param    DateTime    $to_date      To date
	 * @return   array                     Compliance metrics
	 */
	public function get_compliance_rate( $entity_id, $from_date, $to_date ) {
		global $wpdb;

		$from_str = $from_date->format( 'Y-m-d H:i:s' );
		$to_str = $to_date->format( 'Y-m-d H:i:s' );

		// Get total tickets with SLA
		$total_tickets = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				WHERE entity_id = %d 
				AND sla_rule_id IS NOT NULL
				AND created_at BETWEEN %s AND %s",
				$entity_id,
				$from_str,
				$to_str
			)
		);

		if ( ! $total_tickets ) {
			return array(
				'total_tickets' => 0,
				'response_compliance' => 0,
				'resolution_compliance' => 0,
			);
		}

		// Get response compliance
		$response_compliant = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				WHERE entity_id = %d 
				AND sla_rule_id IS NOT NULL
				AND sla_response_breached = 0
				AND first_response_at IS NOT NULL
				AND created_at BETWEEN %s AND %s",
				$entity_id,
				$from_str,
				$to_str
			)
		);

		// Get resolution compliance
		$resolution_compliant = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				WHERE entity_id = %d 
				AND sla_rule_id IS NOT NULL
				AND sla_resolution_breached = 0
				AND resolved_at IS NOT NULL
				AND created_at BETWEEN %s AND %s",
				$entity_id,
				$from_str,
				$to_str
			)
		);

		return array(
			'total_tickets' => (int) $total_tickets,
			'response_compliance' => $total_tickets > 0 ? round( ( $response_compliant / $total_tickets ) * 100, 2 ) : 0,
			'resolution_compliance' => $total_tickets > 0 ? round( ( $resolution_compliant / $total_tickets ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Get applicable SLA rule for a ticket
	 *
	 * @since    1.0.0
	 * @param    array    $ticket_data    Ticket data array
	 * @return   object|null              SLA rule object or null
	 */
	public function get_applicable_sla( $ticket_data ) {
		$entity_id = $ticket_data['entity_id'] ?? null;
		$priority = $ticket_data['priority'] ?? 'normal';
		
		return $this->sla_model->get_applicable_rule( $entity_id, $priority, $ticket_data );
	}

	/**
	 * Calculate due date considering business hours (public method for monitor)
	 *
	 * @since    1.0.0
	 * @param    string      $start_date           Start date string
	 * @param    int         $hours                Hours to add
	 * @param    bool        $business_hours_only  Consider business hours only
	 * @param    int         $entity_id            Entity ID
	 * @return   string                            Due date string
	 */
	public function calculate_due_date( $start_date, $hours, $business_hours_only = false, $entity_id = null ) {
		$start_datetime = is_string( $start_date ) ? new DateTime( $start_date ) : $start_date;
		$due_date = $this->calculate_due_date_internal( $start_datetime, $hours, $entity_id, $business_hours_only );
		return $due_date ? $due_date->format( 'Y-m-d H:i:s' ) : null;
	}

	/**
	 * Internal due date calculation method
	 *
	 * @since    1.0.0
	 * @param    DateTime    $start_date           Start date
	 * @param    int         $hours                Hours to add
	 * @param    int         $entity_id            Entity ID
	 * @param    bool        $business_hours_only  Consider business hours only
	 * @return   DateTime                          Due date
	 */
	private function calculate_due_date_internal( $start_date, $hours, $entity_id, $business_hours_only ) {
		if ( ! $business_hours_only ) {
			// Simple calendar hours calculation
			$due_date = clone $start_date;
			$due_date->add( new DateInterval( "PT{$hours}H" ) );
			return $due_date;
		}

		// Business hours calculation
		return $this->business_hours_model->add_business_hours( $entity_id, $start_date, $hours );
	}
}