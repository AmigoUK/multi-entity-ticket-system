<?php
/**
 * Test factory for creating METS test data
 *
 * @package METS_Tests
 */

class METS_Test_Factory {

    /**
     * Create a test ticket
     *
     * @param array $args Ticket arguments
     * @return int Ticket ID
     */
    public function create_ticket( $args = [] ) {
        global $wpdb;
        
        $defaults = [
            'title' => 'Test Ticket ' . time(),
            'content' => 'This is a test ticket content',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'entity_id' => 1,
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'general',
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        ];
        
        $ticket_data = wp_parse_args( $args, $defaults );
        
        $table = $wpdb->prefix . 'mets_tickets';
        $wpdb->insert( $table, $ticket_data );
        
        return $wpdb->insert_id;
    }

    /**
     * Create a test ticket reply
     *
     * @param int $ticket_id Ticket ID
     * @param array $args Reply arguments
     * @return int Reply ID
     */
    public function create_ticket_reply( $ticket_id, $args = [] ) {
        global $wpdb;
        
        $defaults = [
            'ticket_id' => $ticket_id,
            'content' => 'This is a test reply',
            'author_name' => 'Agent Smith',
            'author_email' => 'agent@example.com',
            'author_type' => 'agent',
            'is_private' => 0,
            'created_at' => current_time( 'mysql' )
        ];
        
        $reply_data = wp_parse_args( $args, $defaults );
        
        $table = $wpdb->prefix . 'mets_ticket_replies';
        $wpdb->insert( $table, $reply_data );
        
        return $wpdb->insert_id;
    }

    /**
     * Create a test entity
     *
     * @param array $args Entity arguments
     * @return int Entity ID
     */
    public function create_entity( $args = [] ) {
        global $wpdb;
        
        $defaults = [
            'name' => 'Test Entity ' . time(),
            'description' => 'Test entity description',
            'status' => 'active',
            'parent_id' => 0,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        ];
        
        $entity_data = wp_parse_args( $args, $defaults );
        
        $table = $wpdb->prefix . 'mets_entities';
        $wpdb->insert( $table, $entity_data );
        
        return $wpdb->insert_id;
    }

    /**
     * Create a test attachment
     *
     * @param int $ticket_id Ticket ID
     * @param array $args Attachment arguments
     * @return int Attachment ID
     */
    public function create_attachment( $ticket_id, $args = [] ) {
        global $wpdb;
        
        // Create a test file
        $upload_dir = wp_upload_dir();
        $test_dir = $upload_dir['basedir'] . '/mets-tests';
        
        if ( ! is_dir( $test_dir ) ) {
            wp_mkdir_p( $test_dir );
        }
        
        $filename = 'test-file-' . time() . '.txt';
        $file_path = $test_dir . '/' . $filename;
        file_put_contents( $file_path, 'Test file content' );
        
        $defaults = [
            'ticket_id' => $ticket_id,
            'filename' => $filename,
            'file_path' => $file_path,
            'file_size' => filesize( $file_path ),
            'mime_type' => 'text/plain',
            'uploaded_by' => 'test@example.com',
            'created_at' => current_time( 'mysql' )
        ];
        
        $attachment_data = wp_parse_args( $args, $defaults );
        
        $table = $wpdb->prefix . 'mets_attachments';
        $wpdb->insert( $table, $attachment_data );
        
        return $wpdb->insert_id;
    }

    /**
     * Create a test KB article
     *
     * @param array $args Article arguments
     * @return int Article ID
     */
    public function create_kb_article( $args = [] ) {
        global $wpdb;
        
        $defaults = [
            'title' => 'Test KB Article ' . time(),
            'content' => 'This is test KB article content',
            'category_id' => 1,
            'status' => 'published',
            'author_id' => 1,
            'view_count' => 0,
            'helpful_count' => 0,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        ];
        
        $article_data = wp_parse_args( $args, $defaults );
        
        $table = $wpdb->prefix . 'mets_kb_articles';
        $wpdb->insert( $table, $article_data );
        
        return $wpdb->insert_id;
    }

    /**
     * Create multiple test tickets
     *
     * @param int $count Number of tickets to create
     * @param array $args Common arguments for all tickets
     * @return array Array of ticket IDs
     */
    public function create_tickets( $count, $args = [] ) {
        $ticket_ids = [];
        
        for ( $i = 1; $i <= $count; $i++ ) {
            $ticket_args = array_merge( $args, [
                'title' => "Test Ticket $i",
                'customer_email' => "customer$i@example.com"
            ]);
            
            $ticket_ids[] = $this->create_ticket( $ticket_args );
        }
        
        return $ticket_ids;
    }

    /**
     * Create a test WordPress user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public function create_user( $args = [] ) {
        $defaults = [
            'user_login' => 'testuser' . time(),
            'user_email' => 'testuser' . time() . '@example.com',
            'user_pass' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'subscriber'
        ];
        
        $user_data = wp_parse_args( $args, $defaults );
        
        return wp_insert_user( $user_data );
    }

    /**
     * Create test METS agent user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public function create_agent( $args = [] ) {
        $defaults = [
            'role' => 'mets_agent'
        ];
        
        $user_args = array_merge( $args, $defaults );
        return $this->create_user( $user_args );
    }

    /**
     * Create test METS manager user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public function create_manager( $args = [] ) {
        $defaults = [
            'role' => 'mets_manager'
        ];
        
        $user_args = array_merge( $args, $defaults );
        return $this->create_user( $user_args );
    }
}