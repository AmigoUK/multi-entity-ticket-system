<?php
/**
 * SLA Rule Model
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * SLA Rule Model class.
 *
 * This class handles all CRUD operations for SLA rules.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SLA_Rule_Model {

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
		$this->table_name = $wpdb->prefix . 'mets_sla_rules';
	}

	/**
	 * Create a new SLA rule
	 *
	 * @since    1.0.0
	 * @param    array    $data    SLA rule data
	 * @return   int|false         Rule ID on success, false on failure
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'entity_id' => null,
			'name' => '',
			'priority' => 'normal',
			'response_time_hours' => null,
			'resolution_time_hours' => null,
			'escalation_time_hours' => null,
			'business_hours_only' => 0,
			'conditions' => '',
			'is_active' => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		// Validate required fields
		if ( empty( $data['name'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'entity_id' => $data['entity_id'],
				'name' => $data['name'],
				'priority' => $data['priority'],
				'response_time' => $data['response_time_hours'],
				'resolution_time' => $data['resolution_time_hours'],
				'escalation_time' => $data['escalation_time_hours'],
				'is_active' => $data['is_active'],
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%d' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Update an SLA rule
	 *
	 * @since    1.0.0
	 * @param    int      $rule_id    Rule ID
	 * @param    array    $data       Updated data
	 * @return   bool                 True on success, false on failure
	 */
	public function update( $rule_id, $data ) {
		global $wpdb;

		$update_data = array();
		$update_format = array();

		$allowed_fields = array(
			'name' => '%s',
			'priority' => '%s',
			'response_time_hours' => '%d',
			'resolution_time_hours' => '%d',
			'escalation_time_hours' => '%d',
			'business_hours_only' => '%d',
			'conditions' => '%s',
			'is_active' => '%d',
		);

		foreach ( $allowed_fields as $field => $format ) {
			if ( array_key_exists( $field, $data ) ) {
				if ( $field === 'conditions' && is_array( $data[ $field ] ) ) {
					$update_data[ $field ] = json_encode( $data[ $field ] );
				} else {
					$update_data[ $field ] = $data[ $field ];
				}
				$update_format[] = $format;
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $rule_id ),
			$update_format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get SLA rule by ID
	 *
	 * @since    1.0.0
	 * @param    int    $rule_id    Rule ID
	 * @return   object|null         Rule object or null if not found
	 */
	public function get_by_id( $rule_id ) {
		global $wpdb;

		$rule = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$rule_id
			)
		);

		if ( $rule && ! empty( $rule->conditions ) ) {
			$rule->conditions = json_decode( $rule->conditions, true );
		}

		return $rule;
	}

	/**
	 * Get SLA rules by entity
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id    Entity ID
	 * @param    bool     $active_only  Get only active rules
	 * @return   array                  Array of rule objects
	 */
	public function get_by_entity( $entity_id, $active_only = true ) {
		global $wpdb;

		$where_clause = "WHERE entity_id = %d";
		$params = array( $entity_id );

		if ( $active_only ) {
			$where_clause .= " AND is_active = 1";
		}

		$rules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} {$where_clause} ORDER BY priority DESC, created_at ASC",
				$params
			)
		);

		// Decode conditions for each rule
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule->conditions ) ) {
				$rule->conditions = json_decode( $rule->conditions, true );
			}
		}

		return $rules;
	}

	/**
	 * Get applicable SLA rule for a ticket
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id    Entity ID
	 * @param    string   $priority     Ticket priority
	 * @param    array    $ticket_data  Additional ticket data for conditions
	 * @return   object|null             Applicable SLA rule or null
	 */
	public function get_applicable_rule( $entity_id, $priority, $ticket_data = array() ) {
		global $wpdb;

		// First, try to find entity-specific rules
		$rules = $this->get_by_entity( $entity_id, true );

		foreach ( $rules as $rule ) {
			// Check if rule applies to this priority
			if ( $rule->priority === $priority || $rule->priority === 'all' ) {
				// Check additional conditions if they exist
				if ( $this->rule_conditions_match( $rule, $ticket_data ) ) {
					return $rule;
				}
			}
		}

		// If no entity-specific rule found, try global rules (entity_id IS NULL)
		$global_rules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE entity_id IS NULL AND is_active = 1
				AND (priority = %s OR priority = 'all')
				ORDER BY priority DESC, created_at ASC",
				$priority
			)
		);

		foreach ( $global_rules as $rule ) {
			if ( ! empty( $rule->conditions ) ) {
				$rule->conditions = json_decode( $rule->conditions, true );
			}

			// Check additional conditions if they exist
			if ( $this->rule_conditions_match( $rule, $ticket_data ) ) {
				return $rule;
			}
		}

		// Fall back to default rule for entity
		$default_rule = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE entity_id = %d AND priority = 'default' AND is_active = 1 
				ORDER BY created_at ASC LIMIT 1",
				$entity_id
			)
		);

		if ( $default_rule && ! empty( $default_rule->conditions ) ) {
			$default_rule->conditions = json_decode( $default_rule->conditions, true );
		}

		// If no entity default, try global default
		if ( ! $default_rule ) {
			$default_rule = $wpdb->get_row(
				"SELECT * FROM {$this->table_name} 
				WHERE entity_id IS NULL AND priority = 'default' AND is_active = 1 
				ORDER BY created_at ASC LIMIT 1"
			);

			if ( $default_rule && ! empty( $default_rule->conditions ) ) {
				$default_rule->conditions = json_decode( $default_rule->conditions, true );
			}
		}

		return $default_rule;
	}

	/**
	 * Check if rule conditions match ticket data
	 *
	 * @since    1.0.0
	 * @param    object   $rule         SLA rule object
	 * @param    array    $ticket_data  Ticket data
	 * @return   bool                   True if conditions match
	 */
	private function rule_conditions_match( $rule, $ticket_data ) {
		// If no conditions, rule applies
		if ( empty( $rule->conditions ) || ! is_array( $rule->conditions ) ) {
			return true;
		}

		foreach ( $rule->conditions as $condition ) {
			if ( ! $this->single_condition_matches( $condition, $ticket_data ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a single condition matches
	 *
	 * @since    1.0.0
	 * @param    array   $condition     Condition array
	 * @param    array   $ticket_data   Ticket data
	 * @return   bool                   True if condition matches
	 */
	private function single_condition_matches( $condition, $ticket_data ) {
		$field = $condition['field'] ?? '';
		$operator = $condition['operator'] ?? '=';
		$value = $condition['value'] ?? '';

		if ( ! isset( $ticket_data[ $field ] ) ) {
			return false;
		}

		$ticket_value = $ticket_data[ $field ];

		switch ( $operator ) {
			case '=':
				return $ticket_value == $value;
			case '!=':
				return $ticket_value != $value;
			case 'in':
				return in_array( $ticket_value, (array) $value );
			case 'not_in':
				return ! in_array( $ticket_value, (array) $value );
			case 'contains':
				return strpos( $ticket_value, $value ) !== false;
			default:
				return false;
		}
	}

	/**
	 * Delete SLA rule
	 *
	 * @since    1.0.0
	 * @param    int    $rule_id    Rule ID
	 * @return   bool               True on success, false on failure
	 */
	public function delete( $rule_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $rule_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get all SLA rules with pagination
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array with 'rules' and 'total' keys
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => 20,
			'page' => 1,
			'orderby' => 'created_at',
			'order' => 'DESC',
			'entity_id' => null,
			'is_active' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$where_values = array();

		if ( ! is_null( $args['entity_id'] ) ) {
			$where_clauses[] = 'entity_id = %d';
			$where_values[] = $args['entity_id'];
		}

		if ( ! is_null( $args['is_active'] ) ) {
			$where_clauses[] = 'is_active = %d';
			$where_values[] = $args['is_active'];
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Get total count
		$total_query = "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}";
		if ( ! empty( $where_values ) ) {
			$total = $wpdb->get_var( $wpdb->prepare( $total_query, $where_values ) );
		} else {
			$total = $wpdb->get_var( $total_query );
		}

		// Get rules
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		$order_sql = sprintf( 'ORDER BY %s %s', $args['orderby'], $args['order'] );
		$limit_sql = sprintf( 'LIMIT %d OFFSET %d', $args['per_page'], $offset );

		$rules_query = "SELECT * FROM {$this->table_name} {$where_sql} {$order_sql} {$limit_sql}";
		
		if ( ! empty( $where_values ) ) {
			$rules = $wpdb->get_results( $wpdb->prepare( $rules_query, $where_values ) );
		} else {
			$rules = $wpdb->get_results( $rules_query );
		}

		// Decode conditions for each rule
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule->conditions ) ) {
				$rule->conditions = json_decode( $rule->conditions, true );
			}
		}

		return array(
			'rules' => $rules,
			'total' => (int) $total,
		);
	}
}