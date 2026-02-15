<?php
/**
 * Attachment model class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Attachment model class.
 *
 * This class handles all database operations for attachments including
 * CRUD operations and file management.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Attachment_Model {

	/**
	 * Database table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The database table name.
	 */
	private $table_name;

	/**
	 * Initialize the model
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_attachments';
	}

	/**
	 * Create a new attachment
	 *
	 * @since    1.0.0
	 * @param    array    $data    Attachment data
	 * @return   int|WP_Error      Attachment ID on success, WP_Error on failure
	 */
	public function create( $data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $data['ticket_id'] ) || empty( $data['file_name'] ) || empty( $data['file_url'] ) ) {
			return new WP_Error( 'missing_data', __( 'Ticket ID, file name, and file URL are required.', METS_TEXT_DOMAIN ) );
		}

		// Validate filename length to prevent database errors
		if ( strlen( $data['file_name'] ) > 255 ) {
			return new WP_Error( 'filename_too_long', __( 'File name is too long for database storage.', METS_TEXT_DOMAIN ) );
		}

		// Prepare data for insertion
		$insert_data = array(
			'ticket_id'   => intval( $data['ticket_id'] ),
			'file_name'   => sanitize_text_field( $data['file_name'] ),
			'file_type'   => ! empty( $data['file_type'] ) ? sanitize_text_field( $data['file_type'] ) : '',
			'file_size'   => ! empty( $data['file_size'] ) ? intval( $data['file_size'] ) : 0,
			'file_url'    => esc_url( $data['file_url'] ),
			'created_at'  => current_time( 'mysql' ),
		);

		$format = array( '%d', '%s', '%s', '%d', '%s', '%s' );

		// Only include reply_id when set — wpdb converts null + %d to 0, breaking IS NULL queries
		if ( ! empty( $data['reply_id'] ) ) {
			$insert_data['reply_id'] = intval( $data['reply_id'] );
			$format[] = '%d';
		}

		// Only include uploaded_by when set — FK constraint rejects 0, use NULL for guest uploads
		$uploader_id = ! empty( $data['uploaded_by'] ) ? intval( $data['uploaded_by'] ) : get_current_user_id();
		if ( $uploader_id > 0 ) {
			$insert_data['uploaded_by'] = $uploader_id;
			$format[] = '%d';
		}

		$result = $wpdb->insert( $this->table_name, $insert_data, $format );

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create attachment.', METS_TEXT_DOMAIN ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get attachment by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Attachment ID
	 * @return   object|null   Attachment object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$id
		) );
	}

	/**
	 * Get attachments by ticket ID
	 *
	 * @since    1.0.0
	 * @param    int     $ticket_id    Ticket ID
	 * @param    int     $reply_id     Reply ID (optional)
	 * @return   array                 Array of attachment objects
	 */
	public function get_by_ticket( $ticket_id, $reply_id = null ) {
		global $wpdb;

		$where_clause = 'WHERE ticket_id = %d';
		$where_values = array( $ticket_id );

		if ( $reply_id !== null ) {
			$where_clause .= ' AND reply_id = %d';
			$where_values[] = $reply_id;
		} else {
			$where_clause .= ' AND reply_id IS NULL';
		}

		$sql = "SELECT a.*, u.display_name as uploaded_by_name 
		        FROM {$this->table_name} a 
		        LEFT JOIN {$wpdb->users} u ON a.uploaded_by = u.ID 
		        {$where_clause} 
		        ORDER BY a.created_at ASC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $where_values ) );
	}

	/**
	 * Get attachments by reply ID
	 *
	 * @since    1.0.0
	 * @param    int     $reply_id    Reply ID
	 * @return   array                Array of attachment objects
	 */
	public function get_by_reply( $reply_id ) {
		global $wpdb;

		$sql = "SELECT a.*, u.display_name as uploaded_by_name 
		        FROM {$this->table_name} a 
		        LEFT JOIN {$wpdb->users} u ON a.uploaded_by = u.ID 
		        WHERE a.reply_id = %d 
		        ORDER BY a.created_at ASC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $reply_id ) );
	}

	/**
	 * Delete attachment
	 *
	 * @since    1.0.0
	 * @param    int    $id    Attachment ID
	 * @return   bool|WP_Error  True on success, WP_Error on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		// Get attachment info before deleting
		$attachment = $this->get( $id );
		if ( ! $attachment ) {
			return new WP_Error( 'attachment_not_found', __( 'Attachment not found.', METS_TEXT_DOMAIN ) );
		}

		// Delete the database record
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to delete attachment.', METS_TEXT_DOMAIN ) );
		}

		// Try to delete the physical file
		$this->delete_file( $attachment->file_url );

		return true;
	}

	/**
	 * Delete physical file
	 *
	 * @since    1.0.0
	 * @param    string    $file_url    File URL
	 * @return   bool                   True on success, false on failure
	 */
	private function delete_file( $file_url ) {
		// Convert URL to file path
		$upload_dir = wp_upload_dir();
		$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );
		
		if ( file_exists( $file_path ) ) {
			return unlink( $file_path );
		}
		
		return false;
	}

	/**
	 * Get attachment count by ticket
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @return   int                  Number of attachments
	 */
	public function get_count_by_ticket( $ticket_id ) {
		global $wpdb;

		return intval( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE ticket_id = %d",
			$ticket_id
		) ) );
	}

	/**
	 * Get file extension from filename
	 *
	 * @since    1.0.0
	 * @param    string    $filename    File name
	 * @return   string                 File extension
	 */
	public function get_file_extension( $filename ) {
		return strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	}

	/**
	 * Format file size for display
	 *
	 * @since    1.0.0
	 * @param    int       $bytes    File size in bytes
	 * @return   string              Formatted file size
	 */
	public function format_file_size( $bytes ) {
		if ( $bytes == 0 ) {
			return '0 B';
		}

		$sizes = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );

		return sprintf( "%.1f %s", $bytes / pow( 1024, $factor ), $sizes[ $factor ] );
	}

	/**
	 * Get file type icon class
	 *
	 * @since    1.0.0
	 * @param    string    $file_type    File type/extension
	 * @return   string                  CSS class for file icon
	 */
	public function get_file_icon_class( $file_type ) {
		$icons = array(
			'pdf'  => 'mets-icon-pdf',
			'doc'  => 'mets-icon-doc',
			'docx' => 'mets-icon-doc',
			'xls'  => 'mets-icon-excel',
			'xlsx' => 'mets-icon-excel',
			'ppt'  => 'mets-icon-ppt',
			'pptx' => 'mets-icon-ppt',
			'txt'  => 'mets-icon-text',
			'zip'  => 'mets-icon-zip',
			'rar'  => 'mets-icon-zip',
			'jpg'  => 'mets-icon-image',
			'jpeg' => 'mets-icon-image',
			'png'  => 'mets-icon-image',
			'gif'  => 'mets-icon-image',
			'svg'  => 'mets-icon-image',
		);

		return isset( $icons[ $file_type ] ) ? $icons[ $file_type ] : 'mets-icon-file';
	}
}