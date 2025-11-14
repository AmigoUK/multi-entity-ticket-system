<?php
/**
 * Satisfaction Survey Model
 *
 * Handles all database operations for customer satisfaction surveys
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.1
 */

/**
 * Satisfaction Survey Model Class
 *
 * This class handles all database operations for customer satisfaction surveys
 * including creation, retrieval, and analytics.
 *
 * @since      1.0.1
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Satisfaction_Survey_Model {

	/**
	 * Database table name
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $table_name    The database table name.
	 */
	private $table_name;

	/**
	 * Initialize the model
	 *
	 * @since    1.0.1
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_satisfaction_surveys';
	}

	/**
	 * Create a new satisfaction survey
	 *
	 * @since    1.0.1
	 * @param    int       $ticket_id        Ticket ID
	 * @param    string    $customer_email   Customer email
	 * @param    int       $agent_id         Agent ID (optional)
	 * @param    int       $entity_id        Entity ID
	 * @return   int|WP_Error                Survey ID on success, WP_Error on failure
	 */
	public function create( $ticket_id, $customer_email, $agent_id, $entity_id ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $ticket_id ) || empty( $customer_email ) || empty( $entity_id ) ) {
			return new WP_Error( 'missing_fields', __( 'Ticket ID, customer email, and entity ID are required.', METS_TEXT_DOMAIN ) );
		}

		// Check if survey already exists for this ticket
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->table_name} WHERE ticket_id = %d",
			$ticket_id
		) );

		if ( $existing ) {
			return new WP_Error( 'survey_exists', __( 'Survey already exists for this ticket.', METS_TEXT_DOMAIN ) );
		}

		// Generate unique token
		$token = $this->generate_unique_token();

		// Insert survey record
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'ticket_id'        => $ticket_id,
				'customer_email'   => sanitize_email( $customer_email ),
				'survey_token'     => $token,
				'agent_id'         => $agent_id,
				'entity_id'        => $entity_id,
				'survey_sent_at'   => current_time( 'mysql' ),
				'rating'           => 0, // Will be updated when submitted
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s', '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create survey.', METS_TEXT_DOMAIN ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Generate unique survey token
	 *
	 * @since    1.0.1
	 * @return   string    Unique token
	 */
	private function generate_unique_token() {
		global $wpdb;

		do {
			$token = bin2hex( random_bytes( 32 ) ); // 64 character hex string
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE survey_token = %s",
				$token
			) );
		} while ( $exists > 0 );

		return $token;
	}

	/**
	 * Get survey by token
	 *
	 * @since    1.0.1
	 * @param    string    $token    Survey token
	 * @return   object|null         Survey object or null
	 */
	public function get_by_token( $token ) {
		global $wpdb;

		$survey = $wpdb->get_row( $wpdb->prepare(
			"SELECT s.*, t.ticket_number, t.subject, t.customer_name, e.name as entity_name
			FROM {$this->table_name} s
			LEFT JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
			LEFT JOIN {$wpdb->prefix}mets_entities e ON s.entity_id = e.id
			WHERE s.survey_token = %s",
			$token
		) );

		return $survey;
	}

	/**
	 * Submit survey response
	 *
	 * @since    1.0.1
	 * @param    string    $token          Survey token
	 * @param    int       $rating         Rating (1-5)
	 * @param    string    $feedback       Optional feedback text
	 * @return   bool|WP_Error             True on success, WP_Error on failure
	 */
	public function submit_response( $token, $rating, $feedback = '' ) {
		global $wpdb;

		// Validate rating
		$rating = intval( $rating );
		if ( $rating < 1 || $rating > 5 ) {
			return new WP_Error( 'invalid_rating', __( 'Rating must be between 1 and 5.', METS_TEXT_DOMAIN ) );
		}

		// Get survey
		$survey = $this->get_by_token( $token );
		if ( ! $survey ) {
			return new WP_Error( 'survey_not_found', __( 'Survey not found.', METS_TEXT_DOMAIN ) );
		}

		// Check if already completed
		if ( ! empty( $survey->survey_completed_at ) ) {
			return new WP_Error( 'already_completed', __( 'Survey has already been completed.', METS_TEXT_DOMAIN ) );
		}

		// Update survey
		$result = $wpdb->update(
			$this->table_name,
			array(
				'rating'               => $rating,
				'feedback_text'        => sanitize_textarea_field( $feedback ),
				'survey_completed_at'  => current_time( 'mysql' ),
			),
			array( 'survey_token' => $token ),
			array( '%d', '%s', '%s' ),
			array( '%s' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to submit survey response.', METS_TEXT_DOMAIN ) );
		}

		// Trigger action for analytics/notifications
		do_action( 'mets_survey_completed', $survey->id, $rating, $feedback );

		return true;
	}

	/**
	 * Check if customer can receive survey (1 per month limit)
	 *
	 * @since    1.0.1
	 * @param    string    $customer_email    Customer email
	 * @return   bool                         True if can receive survey
	 */
	public function can_receive_survey( $customer_email ) {
		global $wpdb;

		// Check if customer received survey in last 30 days
		$recent_survey = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name}
			WHERE customer_email = %s
			AND survey_sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
			$customer_email
		) );

		return $recent_survey == 0;
	}

	/**
	 * Get entity statistics
	 *
	 * @since    1.0.1
	 * @param    int       $entity_id     Entity ID
	 * @param    string    $date_range    Date range (30days, 90days, all)
	 * @return   array                    Statistics array
	 */
	public function get_entity_stats( $entity_id, $date_range = '30days' ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $date_range );

		$stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				COUNT(*) as total_surveys,
				COUNT(CASE WHEN survey_completed_at IS NOT NULL THEN 1 END) as completed_surveys,
				AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating,
				COUNT(CASE WHEN rating = 5 THEN 1 END) as rating_5,
				COUNT(CASE WHEN rating = 4 THEN 1 END) as rating_4,
				COUNT(CASE WHEN rating = 3 THEN 1 END) as rating_3,
				COUNT(CASE WHEN rating = 2 THEN 1 END) as rating_2,
				COUNT(CASE WHEN rating = 1 THEN 1 END) as rating_1,
				COUNT(CASE WHEN feedback_text IS NOT NULL AND feedback_text != '' THEN 1 END) as with_feedback
			FROM {$this->table_name}
			WHERE entity_id = %d {$where_date}",
			$entity_id
		) );

		// Calculate response rate
		$response_rate = $stats->total_surveys > 0
			? round( ( $stats->completed_surveys / $stats->total_surveys ) * 100, 1 )
			: 0;

		return array(
			'total_surveys'      => intval( $stats->total_surveys ),
			'completed_surveys'  => intval( $stats->completed_surveys ),
			'response_rate'      => $response_rate,
			'avg_rating'         => round( floatval( $stats->avg_rating ), 2 ),
			'csat_score'         => round( floatval( $stats->avg_rating ) * 20, 1 ), // Convert to 0-100 scale
			'rating_distribution' => array(
				'5' => intval( $stats->rating_5 ),
				'4' => intval( $stats->rating_4 ),
				'3' => intval( $stats->rating_3 ),
				'2' => intval( $stats->rating_2 ),
				'1' => intval( $stats->rating_1 ),
			),
			'with_feedback'      => intval( $stats->with_feedback ),
		);
	}

	/**
	 * Get agent statistics
	 *
	 * @since    1.0.1
	 * @param    int       $agent_id      Agent ID
	 * @param    string    $date_range    Date range
	 * @return   array                    Statistics array
	 */
	public function get_agent_stats( $agent_id, $date_range = '30days' ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $date_range );

		$stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				COUNT(*) as total_surveys,
				COUNT(CASE WHEN survey_completed_at IS NOT NULL THEN 1 END) as completed_surveys,
				AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating,
				COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings,
				COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_ratings
			FROM {$this->table_name}
			WHERE agent_id = %d {$where_date}",
			$agent_id
		) );

		$response_rate = $stats->total_surveys > 0
			? round( ( $stats->completed_surveys / $stats->total_surveys ) * 100, 1 )
			: 0;

		return array(
			'total_surveys'      => intval( $stats->total_surveys ),
			'completed_surveys'  => intval( $stats->completed_surveys ),
			'response_rate'      => $response_rate,
			'avg_rating'         => round( floatval( $stats->avg_rating ), 2 ),
			'csat_score'         => round( floatval( $stats->avg_rating ) * 20, 1 ),
			'positive_ratings'   => intval( $stats->positive_ratings ),
			'negative_ratings'   => intval( $stats->negative_ratings ),
		);
	}

	/**
	 * Get recent feedback
	 *
	 * @since    1.0.1
	 * @param    int       $entity_id    Entity ID (optional)
	 * @param    int       $limit        Number of results
	 * @return   array                   Array of feedback
	 */
	public function get_recent_feedback( $entity_id = null, $limit = 10 ) {
		global $wpdb;

		$where = '';
		$params = array();

		if ( $entity_id ) {
			$where = 'WHERE s.entity_id = %d AND';
			$params[] = $entity_id;
		}

		$sql = "SELECT s.*, t.ticket_number, t.subject, t.customer_name, e.name as entity_name, u.display_name as agent_name
				FROM {$this->table_name} s
				LEFT JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
				LEFT JOIN {$wpdb->prefix}mets_entities e ON s.entity_id = e.id
				LEFT JOIN {$wpdb->users} u ON s.agent_id = u.ID
				{$where} s.feedback_text IS NOT NULL AND s.feedback_text != ''
				ORDER BY s.survey_completed_at DESC
				LIMIT %d";

		$params[] = $limit;

		$feedback = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );

		return $feedback;
	}

	/**
	 * Get surveys trend data
	 *
	 * @since    1.0.1
	 * @param    int       $entity_id    Entity ID
	 * @param    int       $days         Number of days
	 * @return   array                   Trend data
	 */
	public function get_trend_data( $entity_id, $days = 30 ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT
				DATE(survey_completed_at) as date,
				COUNT(*) as total,
				AVG(rating) as avg_rating
			FROM {$this->table_name}
			WHERE entity_id = %d
			AND survey_completed_at IS NOT NULL
			AND survey_completed_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			GROUP BY DATE(survey_completed_at)
			ORDER BY date ASC",
			$entity_id,
			$days
		);

		$results = $wpdb->get_results( $sql );

		return $results;
	}

	/**
	 * Get date WHERE clause for SQL queries
	 *
	 * @since    1.0.1
	 * @param    string   $period    Period string
	 * @return   string              WHERE clause
	 */
	private function get_date_where_clause( $period ) {
		switch ( $period ) {
			case '7days':
				return " AND survey_completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
			case '30days':
				return " AND survey_completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
			case '90days':
				return " AND survey_completed_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
			case 'all':
			default:
				return "";
		}
	}
}
