<?php
/**
 * Business Hours Model
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Business Hours Model class.
 *
 * This class handles all CRUD operations for business hours configuration.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models  
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Business_Hours_Model {

	/**
	 * Table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The table name.
	 */
	private $table_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_business_hours';
	}

	/**
	 * Create business hours entry
	 *
	 * @since    1.0.0
	 * @param    array    $data    Business hours data
	 * @return   int|false         Entry ID on success, false on failure
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'entity_id' => null,
			'day_of_week' => 0,
			'start_time' => '09:00:00',
			'end_time' => '17:00:00',
			'is_active' => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		// Note: entity_id can be null for global business hours

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'entity_id' => $data['entity_id'],
				'day_of_week' => $data['day_of_week'],
				'start_time' => $data['start_time'],
				'end_time' => $data['end_time'],
				'is_active' => $data['is_active'],
			),
			array( '%d', '%d', '%s', '%s', '%d' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Update business hours entry
	 *
	 * @since    1.0.0
	 * @param    int      $id      Entry ID
	 * @param    array    $data    Updated data
	 * @return   bool              True on success, false on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$update_format = array();

		$allowed_fields = array(
			'day_of_week' => '%d',
			'start_time' => '%s',
			'end_time' => '%s',
			'is_active' => '%d',
		);

		foreach ( $allowed_fields as $field => $format ) {
			if ( array_key_exists( $field, $data ) ) {
				$update_data[ $field ] = $data[ $field ];
				$update_format[] = $format;
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $id ),
			$update_format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get business hours by entity
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id    Entity ID
	 * @param    bool     $active_only  Get only active hours
	 * @return   array                  Array of business hours objects
	 */
	public function get_by_entity( $entity_id, $active_only = true ) {
		global $wpdb;

		$where_clause = "WHERE entity_id = %d";
		$params = array( $entity_id );

		if ( $active_only ) {
			$where_clause .= " AND is_active = 1";
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} {$where_clause} ORDER BY day_of_week ASC",
				$params
			)
		);
	}

	/**
	 * Get business hours for a specific day
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id     Entity ID
	 * @param    int      $day_of_week   Day of week (0 = Sunday, 6 = Saturday)
	 * @return   object|null              Business hours object or null
	 */
	public function get_for_day( $entity_id, $day_of_week ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE entity_id = %d AND day_of_week = %d AND is_active = 1",
				$entity_id,
				$day_of_week
			)
		);
	}

	/**
	 * Set business hours for an entity (bulk update)
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id    Entity ID
	 * @param    array    $hours_data   Array of business hours data
	 * @return   bool                   True on success, false on failure
	 */
	public function set_entity_hours( $entity_id, $hours_data ) {
		global $wpdb;

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Delete existing hours for this entity
			$wpdb->delete(
				$this->table_name,
				array( 'entity_id' => $entity_id ),
				array( '%d' )
			);

			// Insert new hours
			foreach ( $hours_data as $day => $hours ) {
				if ( isset( $hours['is_active'] ) && $hours['is_active'] ) {
					$result = $this->create( array(
						'entity_id' => $entity_id,
						'day_of_week' => $day,
						'start_time' => $hours['start_time'],
						'end_time' => $hours['end_time'],
						'is_active' => 1,
					) );

					if ( $result === false ) {
						throw new Exception( 'Failed to create business hours entry' );
					}
				}
			}

			$wpdb->query( 'COMMIT' );
			return true;

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
	}

	/**
	 * Check if a given time is within business hours
	 *
	 * @since    1.0.0
	 * @param    int         $entity_id    Entity ID
	 * @param    DateTime    $datetime     DateTime to check
	 * @return   bool                      True if within business hours
	 */
	public function is_business_time( $entity_id, $datetime ) {
		$day_of_week = (int) $datetime->format( 'w' ); // 0 = Sunday
		$time = $datetime->format( 'H:i:s' );

		$hours = $this->get_for_day( $entity_id, $day_of_week );

		if ( ! $hours ) {
			return false;
		}

		return $time >= $hours->start_time && $time <= $hours->end_time;
	}

	/**
	 * Calculate business hours between two dates
	 *
	 * @since    1.0.0
	 * @param    int         $entity_id     Entity ID
	 * @param    DateTime    $start_date    Start date
	 * @param    DateTime    $end_date      End date
	 * @return   float                      Business hours as decimal
	 */
	public function calculate_business_hours( $entity_id, $start_date, $end_date ) {
		$business_hours = 0;
		$current_date = clone $start_date;

		while ( $current_date <= $end_date ) {
			$day_of_week = (int) $current_date->format( 'w' );
			$hours = $this->get_for_day( $entity_id, $day_of_week );

			if ( $hours ) {
				$day_start = clone $current_date;
				$day_start->setTime( 
					...explode( ':', $hours->start_time ) 
				);

				$day_end = clone $current_date;
				$day_end->setTime( 
					...explode( ':', $hours->end_time ) 
				);

				// Adjust for start and end times
				$period_start = max( $start_date, $day_start );
				$period_end = min( $end_date, $day_end );

				if ( $period_start < $period_end ) {
					$interval = $period_start->diff( $period_end );
					$hours_diff = $interval->h + ( $interval->i / 60 ) + ( $interval->s / 3600 );
					$business_hours += $hours_diff;
				}
			}

			$current_date->add( new DateInterval( 'P1D' ) );
		}

		return $business_hours;
	}

	/**
	 * Get next business day
	 *
	 * @since    1.0.0
	 * @param    int         $entity_id    Entity ID
	 * @param    DateTime    $from_date    Starting date
	 * @return   DateTime                  Next business day
	 */
	public function get_next_business_day( $entity_id, $from_date ) {
		$next_date = clone $from_date;
		$next_date->add( new DateInterval( 'P1D' ) );

		$attempts = 0;
		while ( $attempts < 14 ) { // Prevent infinite loop
			$day_of_week = (int) $next_date->format( 'w' );
			$hours = $this->get_for_day( $entity_id, $day_of_week );

			if ( $hours ) {
				return $next_date;
			}

			$next_date->add( new DateInterval( 'P1D' ) );
			$attempts++;
		}

		return $next_date; // Fallback
	}

	/**
	 * Add business hours to a date
	 *
	 * @since    1.0.0
	 * @param    int         $entity_id       Entity ID  
	 * @param    DateTime    $start_date      Starting date
	 * @param    float       $hours_to_add    Hours to add
	 * @return   DateTime                     Resulting date
	 */
	public function add_business_hours( $entity_id, $start_date, $hours_to_add ) {
		$current_date = clone $start_date;
		$remaining_hours = $hours_to_add;

		$attempts = 0;
		while ( $remaining_hours > 0 && $attempts < 100 ) { // Prevent infinite loop
			$day_of_week = (int) $current_date->format( 'w' );
			$business_hours = $this->get_for_day( $entity_id, $day_of_week );

			if ( $business_hours ) {
				$day_start = clone $current_date;
				$day_start->setTime( 
					...explode( ':', $business_hours->start_time ) 
				);

				$day_end = clone $current_date;
				$day_end->setTime( 
					...explode( ':', $business_hours->end_time ) 
				);

				// Calculate hours available in this day
				$available_start = max( $current_date, $day_start );
				$available_hours = $day_end->diff( $available_start )->h + 
								 ( $day_end->diff( $available_start )->i / 60 );

				if ( $remaining_hours <= $available_hours ) {
					// We can finish today
					$minutes_to_add = $remaining_hours * 60;
					$current_date->add( new DateInterval( "PT{$minutes_to_add}M" ) );
					$remaining_hours = 0;
				} else {
					// Move to next business day
					$remaining_hours -= $available_hours;
					$current_date = $this->get_next_business_day( $entity_id, $current_date );
					$current_date->setTime( 
						...explode( ':', $business_hours->start_time ) 
					);
				}
			} else {
				// No business hours for this day, move to next day
				$current_date = $this->get_next_business_day( $entity_id, $current_date );
			}

			$attempts++;
		}

		return $current_date;
	}

	/**
	 * Delete business hours entry
	 *
	 * @since    1.0.0
	 * @param    int    $id    Entry ID
	 * @return   bool          True on success, false on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get global business hours (entity_id = NULL)
	 *
	 * @since    1.0.0
	 * @param    bool     $active_only  Get only active hours
	 * @return   array                  Array of business hours objects
	 */
	public function get_global_hours( $active_only = false ) {
		global $wpdb;

		$where_clause = "WHERE entity_id IS NULL";
		$params = array();

		if ( $active_only ) {
			$where_clause .= " AND is_active = 1";
		}

		return $wpdb->get_results(
			"SELECT * FROM {$this->table_name} {$where_clause} ORDER BY day_of_week ASC"
		);
	}

	/**
	 * Clear business hours for an entity
	 *
	 * @since    1.0.0
	 * @param    int|null $entity_id    Entity ID or null for global
	 * @return   bool                  True on success, false on failure
	 */
	public function clear_entity_hours( $entity_id ) {
		global $wpdb;

		if ( is_null( $entity_id ) ) {
			$result = $wpdb->query(
				"DELETE FROM {$this->table_name} WHERE entity_id IS NULL"
			);
		} else {
			$result = $wpdb->delete(
				$this->table_name,
				array( 'entity_id' => $entity_id ),
				array( '%d' )
			);
		}

		return $result !== false;
	}

	/**
	 * Get default business hours template
	 *
	 * @since    1.0.0
	 * @return   array    Default business hours structure
	 */
	public function get_default_hours() {
		return array(
			0 => array( 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => false ), // Sunday
			1 => array( 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true ),  // Monday
			2 => array( 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true ),  // Tuesday
			3 => array( 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true ),  // Wednesday
			4 => array( 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true ),  // Thursday
			5 => array( 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true ),  // Friday
			6 => array( 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => false ), // Saturday
		);
	}
}