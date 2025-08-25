<?php
/**
 * File upload handler class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * File upload handler class.
 *
 * This class handles file uploads, validation, and storage for the ticket system.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_File_Handler {

	/**
	 * Attachment model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Attachment_Model    $attachment_model    The attachment model instance.
	 */
	private $attachment_model;

	/**
	 * File upload settings
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $upload_settings    The upload settings.
	 */
	private $upload_settings;

	/**
	 * Initialize the file handler
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-attachment-model.php';
		$this->attachment_model = new METS_Attachment_Model();
		
		// Get upload settings
		$this->upload_settings = get_option( 'mets_file_upload_settings', array(
			'max_files'     => 10,
			'max_file_size' => 20971520, // 20MB
			'allowed_types' => array( 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip' ),
		) );
	}

	/**
	 * Handle file upload
	 *
	 * @since    1.0.0
	 * @param    array    $files         $_FILES array for the upload field
	 * @param    int      $ticket_id     Ticket ID
	 * @param    int      $reply_id      Reply ID (optional)
	 * @return   array                   Array of results (success/error for each file)
	 */
	public function handle_upload( $files, $ticket_id, $reply_id = null ) {
		$results = array();

		// Handle single file upload
		if ( ! is_array( $files['name'] ) ) {
			$file_data = array(
				'name'     => $files['name'],
				'type'     => $files['type'],
				'tmp_name' => $files['tmp_name'],
				'error'    => $files['error'],
				'size'     => $files['size'],
			);
			$results[] = $this->process_single_file( $file_data, $ticket_id, $reply_id );
		} else {
			// Handle multiple file uploads
			$file_count = count( $files['name'] );
			
			// Check file count limit
			if ( $file_count > $this->upload_settings['max_files'] ) {
				return array(
					'success' => false,
					'message' => sprintf(
						__( 'Too many files. Maximum allowed: %d', METS_TEXT_DOMAIN ),
						$this->upload_settings['max_files']
					)
				);
			}

			for ( $i = 0; $i < $file_count; $i++ ) {
				$file_data = array(
					'name'     => $files['name'][ $i ],
					'type'     => $files['type'][ $i ],
					'tmp_name' => $files['tmp_name'][ $i ],
					'error'    => $files['error'][ $i ],
					'size'     => $files['size'][ $i ],
				);
				$results[] = $this->process_single_file( $file_data, $ticket_id, $reply_id );
			}
		}

		return $results;
	}

	/**
	 * Process a single file upload
	 *
	 * @since    1.0.0
	 * @param    array    $file_data    Single file data
	 * @param    int      $ticket_id    Ticket ID
	 * @param    int      $reply_id     Reply ID (optional)
	 * @return   array                  Result array
	 */
	private function process_single_file( $file_data, $ticket_id, $reply_id = null ) {
		// Check for upload errors
		if ( $file_data['error'] !== UPLOAD_ERR_OK ) {
			return array(
				'success' => false,
				'message' => $this->get_upload_error_message( $file_data['error'] ),
				'file'    => $file_data['name']
			);
		}

		// Validate file
		$validation = $this->validate_file( $file_data );
		if ( ! $validation['valid'] ) {
			return array(
				'success' => false,
				'message' => $validation['message'],
				'file'    => $file_data['name']
			);
		}

		// Upload file
		$upload_result = $this->upload_file( $file_data, $ticket_id );
		if ( is_wp_error( $upload_result ) ) {
			return array(
				'success' => false,
				'message' => $upload_result->get_error_message(),
				'file'    => $file_data['name']
			);
		}

		// Save to database
		$attachment_data = array(
			'ticket_id'   => $ticket_id,
			'reply_id'    => $reply_id,
			'file_name'   => $upload_result['filename'],
			'file_type'   => $this->get_file_extension( $file_data['name'] ),
			'file_size'   => $file_data['size'],
			'file_url'    => $upload_result['url'],
			'uploaded_by' => get_current_user_id(),
		);

		$attachment_id = $this->attachment_model->create( $attachment_data );
		if ( is_wp_error( $attachment_id ) ) {
			// Log the error for debugging
			error_log( 'METS File Upload Error - Database Save Failed: ' . $attachment_id->get_error_message() . ' | File: ' . $file_data['name'] . ' | Attachment Data: ' . print_r( $attachment_data, true ) );
			
			// Delete uploaded file if database save failed
			$this->delete_uploaded_file( $upload_result['file'] );
			
			return array(
				'success' => false,
				'message' => $attachment_id->get_error_message(),
				'file'    => $file_data['name']
			);
		}

		return array(
			'success'       => true,
			'message'       => __( 'File uploaded successfully.', METS_TEXT_DOMAIN ),
			'file'          => $file_data['name'],
			'attachment_id' => $attachment_id,
			'file_url'      => $upload_result['url']
		);
	}

	/**
	 * Validate uploaded file
	 *
	 * @since    1.0.0
	 * @param    array    $file_data    File data
	 * @return   array                  Validation result
	 */
	private function validate_file( $file_data ) {
		// Check file size
		if ( $file_data['size'] > $this->upload_settings['max_file_size'] ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					__( 'File size too large. Maximum allowed: %s', METS_TEXT_DOMAIN ),
					$this->format_file_size( $this->upload_settings['max_file_size'] )
				)
			);
		}

		// Check file type
		$file_extension = $this->get_file_extension( $file_data['name'] );
		if ( ! in_array( $file_extension, $this->upload_settings['allowed_types'] ) ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					__( 'File type not allowed. Allowed types: %s', METS_TEXT_DOMAIN ),
					implode( ', ', $this->upload_settings['allowed_types'] )
				)
			);
		}

		// Validate file content (security check)
		if ( ! $this->validate_file_content( $file_data['tmp_name'], $file_extension ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'File content validation failed. The file may be corrupted or contain malicious code.', METS_TEXT_DOMAIN )
			);
		}

		return array(
			'valid'   => true,
			'message' => ''
		);
	}

	/**
	 * Validate file content for security
	 *
	 * @since    1.0.0
	 * @param    string    $file_path      Temporary file path
	 * @param    string    $file_extension File extension
	 * @return   bool                      True if valid, false otherwise
	 */
	private function validate_file_content( $file_path, $file_extension ) {
		// Get file MIME type
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file_path );
		finfo_close( $finfo );

		// Define allowed MIME types for each extension
		$allowed_mimes = array(
			'jpg'  => array( 'image/jpeg' ),
			'jpeg' => array( 'image/jpeg' ),
			'png'  => array( 'image/png' ),
			'gif'  => array( 'image/gif' ),
			'pdf'  => array( 'application/pdf' ),
			'txt'  => array( 'text/plain' ),
			'doc'  => array( 'application/msword' ),
			'docx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
			'zip'  => array( 'application/zip' ),
		);

		// Check if MIME type matches extension
		if ( isset( $allowed_mimes[ $file_extension ] ) ) {
			return in_array( $mime_type, $allowed_mimes[ $file_extension ] );
		}

		// For other file types, just check that it's not executable
		$dangerous_mimes = array(
			'application/x-executable',
			'application/x-msdownload',
			'application/x-msdos-program',
			'application/x-httpd-php',
		);

		return ! in_array( $mime_type, $dangerous_mimes );
	}

	/**
	 * Upload file to WordPress uploads directory
	 *
	 * @since    1.0.0
	 * @param    array    $file_data    File data
	 * @param    int      $ticket_id    Ticket ID
	 * @return   array|WP_Error        Upload result or error
	 */
	private function upload_file( $file_data, $ticket_id ) {
		// Create upload directory structure
		$upload_dir = wp_upload_dir();
		$mets_dir = $upload_dir['basedir'] . '/mets-attachments/' . date( 'Y/m' );
		
		if ( ! wp_mkdir_p( $mets_dir ) ) {
			return new WP_Error( 'upload_dir_error', __( 'Failed to create upload directory.', METS_TEXT_DOMAIN ) );
		}

		// Generate unique filename
		$filename = $this->generate_unique_filename( $file_data['name'], $mets_dir );
		$file_path = $mets_dir . '/' . $filename;

		// Move uploaded file
		if ( ! move_uploaded_file( $file_data['tmp_name'], $file_path ) ) {
			error_log( 'METS File Upload Error - Failed to move file: ' . $file_data['name'] . ' | Source: ' . $file_data['tmp_name'] . ' | Destination: ' . $file_path );
			return new WP_Error( 'upload_error', __( 'Failed to move uploaded file.', METS_TEXT_DOMAIN ) );
		}

		// Set file permissions
		chmod( $file_path, 0644 );

		// Generate file URL
		$file_url = $upload_dir['baseurl'] . '/mets-attachments/' . date( 'Y/m' ) . '/' . $filename;

		return array(
			'file'     => $file_path,
			'url'      => $file_url,
			'filename' => $filename
		);
	}

	/**
	 * Generate unique filename
	 *
	 * @since    1.0.0
	 * @param    string    $original_filename    Original filename
	 * @param    string    $directory           Target directory
	 * @return   string                         Unique filename
	 */
	private function generate_unique_filename( $original_filename, $directory ) {
		$filename = sanitize_file_name( $original_filename );
		$name = pathinfo( $filename, PATHINFO_FILENAME );
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );

		// Truncate filename if too long to fit in database (varchar 255)
		// Reserve space for directory path, extension, counter, and safety margin
		$max_name_length = 200; // Safe limit to prevent database issues
		
		if ( strlen( $name ) > $max_name_length ) {
			// Keep the last part of the filename for identification
			$name = substr( $name, -$max_name_length );
			// Add hash of original name for uniqueness
			$name = substr( md5( $original_filename ), 0, 8 ) . '_' . $name;
		}

		$filename = $name . '.' . $extension;

		$counter = 1;
		while ( file_exists( $directory . '/' . $filename ) ) {
			$numbered_name = $name . '-' . $counter;
			$filename = $numbered_name . '.' . $extension;
			$counter++;
		}

		return $filename;
	}

	/**
	 * Delete uploaded file
	 *
	 * @since    1.0.0
	 * @param    string    $file_path    File path
	 * @return   bool                    True on success, false on failure
	 */
	private function delete_uploaded_file( $file_path ) {
		if ( file_exists( $file_path ) ) {
			return unlink( $file_path );
		}
		return false;
	}

	/**
	 * Get file extension from filename
	 *
	 * @since    1.0.0
	 * @param    string    $filename    File name
	 * @return   string                 File extension (lowercase)
	 */
	private function get_file_extension( $filename ) {
		return strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	}

	/**
	 * Format file size for display
	 *
	 * @since    1.0.0
	 * @param    int       $bytes    File size in bytes
	 * @return   string              Formatted file size
	 */
	private function format_file_size( $bytes ) {
		if ( $bytes == 0 ) return '0 B';
		$sizes = array( 'B', 'KB', 'MB', 'GB' );
		$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
		return sprintf( "%.1f %s", $bytes / pow( 1024, $factor ), $sizes[ $factor ] );
	}

	/**
	 * Get upload error message
	 *
	 * @since    1.0.0
	 * @param    int       $error_code    PHP upload error code
	 * @return   string                   Error message
	 */
	private function get_upload_error_message( $error_code ) {
		$messages = array(
			UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_EXTENSION  => __( 'A PHP extension stopped the file upload.', METS_TEXT_DOMAIN ),
		);

		return isset( $messages[ $error_code ] ) ? $messages[ $error_code ] : __( 'Unknown upload error.', METS_TEXT_DOMAIN );
	}

	/**
	 * Get upload settings
	 *
	 * @since    1.0.0
	 * @return   array    Upload settings
	 */
	public function get_upload_settings() {
		return $this->upload_settings;
	}

	/**
	 * Get attachment model
	 *
	 * @since    1.0.0
	 * @return   METS_Attachment_Model    Attachment model instance
	 */
	public function get_attachment_model() {
		return $this->attachment_model;
	}
}