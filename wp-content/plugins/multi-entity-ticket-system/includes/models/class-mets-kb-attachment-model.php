<?php
/**
 * Knowledgebase Attachment Model
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Knowledgebase Attachment Model class.
 *
 * This class handles all CRUD operations for knowledgebase file attachments.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Attachment_Model {

	/**
	 * Table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The table name.
	 */
	private $table_name;

	/**
	 * Allowed file types
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $allowed_types    Allowed MIME types.
	 */
	private $allowed_types;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_kb_attachments';
		
		// Define allowed file types based on requirements
		$this->allowed_types = array(
			// PDF
			'application/pdf',
			// Word documents
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			// OpenDocument
			'application/vnd.oasis.opendocument.text',
			'application/vnd.oasis.opendocument.spreadsheet',
			// Excel
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			// Images
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/gif',
			'image/webp'
		);
	}

	/**
	 * Upload and create attachment record
	 *
	 * @since    1.0.0
	 * @param    int      $article_id    Article ID
	 * @param    array    $file          $_FILES array element
	 * @return   int|WP_Error            Attachment ID on success, WP_Error on failure
	 */
	public function upload_attachment( $article_id, $file ) {
		// Validate file
		$validation = $this->validate_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Handle file upload
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$upload_overrides = array(
			'test_form' => false,
			'mimes' => $this->get_allowed_mimes()
		);

		$uploaded_file = wp_handle_upload( $file, $upload_overrides );

		if ( isset( $uploaded_file['error'] ) ) {
			return new WP_Error( 'upload_error', $uploaded_file['error'] );
		}

		// Generate file hash for duplicate detection
		$file_hash = hash_file( 'sha256', $uploaded_file['file'] );

		// Check for existing file with same hash for this article
		$existing = $this->get_by_hash( $file_hash, $article_id );
		if ( $existing ) {
			// Remove the uploaded file since we already have it
			wp_delete_file( $uploaded_file['file'] );
			return new WP_Error( 'duplicate_file', __( 'This file has already been uploaded to this article.', METS_TEXT_DOMAIN ) );
		}

		// Create attachment record
		$attachment_id = $this->create( array(
			'article_id' => $article_id,
			'filename' => basename( $uploaded_file['file'] ),
			'original_filename' => $file['name'],
			'file_path' => $uploaded_file['file'],
			'file_size' => filesize( $uploaded_file['file'] ),
			'mime_type' => $uploaded_file['type'],
			'file_hash' => $file_hash,
			'uploaded_by' => get_current_user_id()
		) );

		if ( ! $attachment_id ) {
			// Clean up uploaded file if database insert failed
			wp_delete_file( $uploaded_file['file'] );
			return new WP_Error( 'db_error', __( 'Failed to save attachment information to database.', METS_TEXT_DOMAIN ) );
		}

		return $attachment_id;
	}

	/**
	 * Create attachment record
	 *
	 * @since    1.0.0
	 * @param    array    $data    Attachment data
	 * @return   int|false         Attachment ID on success, false on failure
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'article_id' => 0,
			'filename' => '',
			'original_filename' => '',
			'file_path' => '',
			'file_size' => 0,
			'mime_type' => '',
			'file_hash' => '',
			'uploaded_by' => get_current_user_id()
		);

		$data = wp_parse_args( $data, $defaults );

		// Validate required fields
		if ( empty( $data['article_id'] ) || empty( $data['filename'] ) || empty( $data['file_path'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'article_id' => $data['article_id'],
				'filename' => $data['filename'],
				'original_filename' => $data['original_filename'],
				'file_path' => $data['file_path'],
				'file_size' => $data['file_size'],
				'mime_type' => $data['mime_type'],
				'file_hash' => $data['file_hash'],
				'uploaded_by' => $data['uploaded_by']
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d' )
		);

		return $result !== false ? $wpdb->insert_id : false;
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

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, u.display_name as uploaded_by_name
				FROM {$this->table_name} a
				LEFT JOIN {$wpdb->prefix}users u ON a.uploaded_by = u.ID
				WHERE a.id = %d",
				$id
			)
		);
	}

	/**
	 * Get attachments for an article
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID
	 * @return   array                 Array of attachments
	 */
	public function get_by_article( $article_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, u.display_name as uploaded_by_name
				FROM {$this->table_name} a
				LEFT JOIN {$wpdb->prefix}users u ON a.uploaded_by = u.ID
				WHERE a.article_id = %d
				ORDER BY a.created_at ASC",
				$article_id
			)
		);
	}

	/**
	 * Get attachment by file hash
	 *
	 * @since    1.0.0
	 * @param    string   $hash        File hash
	 * @param    int      $article_id  Article ID (optional)
	 * @return   object|null           Attachment object or null if not found
	 */
	public function get_by_hash( $hash, $article_id = null ) {
		global $wpdb;

		$where_conditions = array( "file_hash = %s" );
		$params = array( $hash );

		if ( ! is_null( $article_id ) ) {
			$where_conditions[] = "article_id = %d";
			$params[] = $article_id;
		}

		$where_clause = implode( ' AND ', $where_conditions );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE {$where_clause}",
				$params
			)
		);
	}

	/**
	 * Delete attachment
	 *
	 * @since    1.0.0
	 * @param    int     $id           Attachment ID
	 * @param    bool    $delete_file  Whether to delete the physical file
	 * @return   bool                  True on success, false on failure
	 */
	public function delete( $id, $delete_file = true ) {
		global $wpdb;

		// Get attachment info before deletion
		$attachment = $this->get( $id );
		if ( ! $attachment ) {
			return false;
		}

		// Delete database record
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		// Delete physical file if requested and database deletion was successful
		if ( $result !== false && $delete_file && file_exists( $attachment->file_path ) ) {
			wp_delete_file( $attachment->file_path );
		}

		return $result !== false;
	}

	/**
	 * Increment download count
	 *
	 * @since    1.0.0
	 * @param    int    $id    Attachment ID
	 * @return   bool          True on success, false on failure
	 */
	public function increment_download_count( $id ) {
		global $wpdb;

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET download_count = download_count + 1 WHERE id = %d",
				$id
			)
		);

		return $result !== false;
	}

	/**
	 * Validate uploaded file
	 *
	 * @since    1.0.0
	 * @param    array    $file    $_FILES array element
	 * @return   bool|WP_Error     True if valid, WP_Error if invalid
	 */
	private function validate_file( $file ) {
		// Check for upload errors
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', METS_TEXT_DOMAIN ),
				UPLOAD_ERR_FORM_SIZE => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', METS_TEXT_DOMAIN ),
				UPLOAD_ERR_PARTIAL => __( 'The uploaded file was only partially uploaded.', METS_TEXT_DOMAIN ),
				UPLOAD_ERR_NO_FILE => __( 'No file was uploaded.', METS_TEXT_DOMAIN ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.', METS_TEXT_DOMAIN ),
				UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', METS_TEXT_DOMAIN ),
				UPLOAD_ERR_EXTENSION => __( 'A PHP extension stopped the file upload.', METS_TEXT_DOMAIN )
			);

			$error_message = isset( $error_messages[ $file['error'] ] ) ? 
				$error_messages[ $file['error'] ] : 
				__( 'Unknown upload error.', METS_TEXT_DOMAIN );

			return new WP_Error( 'upload_error', $error_message );
		}

		// Check file size (max 10MB)
		$max_size = 10 * 1024 * 1024; // 10MB
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds maximum allowed size of 10MB.', METS_TEXT_DOMAIN ) );
		}

		// Check MIME type
		$file_type = wp_check_filetype( $file['name'] );
		if ( ! in_array( $file_type['type'], $this->allowed_types ) ) {
			return new WP_Error( 
				'invalid_file_type', 
				sprintf( 
					__( 'File type "%s" is not allowed. Allowed types: PDF, Word, OpenDocument, Excel, Images.', METS_TEXT_DOMAIN ),
					$file_type['type'] 
				) 
			);
		}

		return true;
	}

	/**
	 * Get allowed MIME types for wp_handle_upload
	 *
	 * @since    1.0.0
	 * @return   array    Array of allowed MIME types
	 */
	private function get_allowed_mimes() {
		return array(
			'pdf' => 'application/pdf',
			'doc' => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
			'xls' => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp'
		);
	}

	/**
	 * Get attachment download URL
	 *
	 * @since    1.0.0
	 * @param    int    $id    Attachment ID
	 * @return   string        Download URL
	 */
	public function get_download_url( $id ) {
		return admin_url( 'admin-ajax.php?action=mets_kb_download_attachment&attachment_id=' . $id . '&nonce=' . wp_create_nonce( 'mets_kb_download_' . $id ) );
	}

	/**
	 * Get formatted file size
	 *
	 * @since    1.0.0
	 * @param    int    $bytes    File size in bytes
	 * @return   string           Formatted file size
	 */
	public function format_file_size( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );
		return round( $bytes, 2 ) . ' ' . $units[ $pow ];
	}

	/**
	 * Get file icon class based on MIME type
	 *
	 * @since    1.0.0
	 * @param    string   $mime_type    MIME type
	 * @return   string                 CSS class for file icon
	 */
	public function get_file_icon_class( $mime_type ) {
		$icon_map = array(
			'application/pdf' => 'dashicons-pdf',
			'application/msword' => 'dashicons-text-page',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'dashicons-text-page',
			'application/vnd.oasis.opendocument.text' => 'dashicons-text-page',
			'application/vnd.ms-excel' => 'dashicons-media-spreadsheet',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'dashicons-media-spreadsheet',
			'application/vnd.oasis.opendocument.spreadsheet' => 'dashicons-media-spreadsheet',
			'image/jpeg' => 'dashicons-format-image',
			'image/jpg' => 'dashicons-format-image',
			'image/png' => 'dashicons-format-image',
			'image/gif' => 'dashicons-format-image',
			'image/webp' => 'dashicons-format-image'
		);

		return isset( $icon_map[ $mime_type ] ) ? $icon_map[ $mime_type ] : 'dashicons-media-default';
	}

	/**
	 * Clean up orphaned attachments (files without article associations)
	 *
	 * @since    1.0.0
	 * @return   int    Number of files cleaned up
	 */
	public function cleanup_orphaned_attachments() {
		global $wpdb;

		// Get attachments that don't have associated articles
		$orphaned = $wpdb->get_results(
			"SELECT a.* FROM {$this->table_name} a
			LEFT JOIN {$wpdb->prefix}mets_kb_articles ar ON a.article_id = ar.id
			WHERE ar.id IS NULL"
		);

		$cleaned = 0;
		foreach ( $orphaned as $attachment ) {
			if ( $this->delete( $attachment->id, true ) ) {
				$cleaned++;
			}
		}

		return $cleaned;
	}
}