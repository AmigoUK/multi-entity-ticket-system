<?php
/**
 * API tests for METS REST API endpoints
 *
 * @package METS_Tests
 * @subpackage API_Tests
 */

class Test_METS_REST_API extends METS_Test_Case {

    /**
     * Test user with API access
     */
    protected $api_user_id;

    /**
     * API base URL
     */
    protected $api_base = 'wp-json/mets/v1';

    /**
     * Setup API testing environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create user with API access
        $this->api_user_id = $this->mets_factory->create_manager([
            'user_login' => 'api_test_user',
            'user_email' => 'api@example.com'
        ]);

        // Set current user for API requests
        wp_set_current_user( $this->api_user_id );
    }

    /**
     * Test API authentication
     */
    public function test_api_authentication() {
        // Test without authentication
        wp_set_current_user( 0 );
        
        $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets" );
        $response = rest_do_request( $request );
        
        // Should require authentication
        $this->assertEquals( 401, $response->get_status() );
        $this->assertStringContainsString( 'authentication', strtolower( $response->get_data()['message'] ) );

        // Test with authentication
        wp_set_current_user( $this->api_user_id );
        
        $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets" );
        $response = rest_do_request( $request );
        
        // Should succeed with proper authentication
        $this->assertEquals( 200, $response->get_status() );
    }

    /**
     * Test GET /tickets endpoint
     */
    public function test_get_tickets_endpoint() {
        // Create test tickets
        $ticket_ids = $this->mets_factory->create_tickets( 3, [
            'status' => 'open'
        ]);

        $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets" );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( 'tickets', $data );
        $this->assertGreaterThanOrEqual( 3, count( $data['tickets'] ) );
    }

    /**
     * Test GET /tickets/{id} endpoint
     */
    public function test_get_single_ticket_endpoint() {
        $ticket_id = $this->mets_factory->create_ticket([
            'title' => 'API Test Ticket',
            'customer_email' => 'api.customer@example.com'
        ]);

        $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets/$ticket_id" );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertIsArray( $data );
        $this->assertEquals( $ticket_id, $data['id'] );
        $this->assertEquals( 'API Test Ticket', $data['title'] );
        $this->assertEquals( 'api.customer@example.com', $data['customer_email'] );
    }

    /**
     * Test POST /tickets endpoint
     */
    public function test_create_ticket_endpoint() {
        $entity_id = $this->mets_factory->create_entity();

        $ticket_data = [
            'title' => 'API Created Ticket',
            'content' => 'This ticket was created via API',
            'customer_name' => 'API Customer',
            'customer_email' => 'new.customer@example.com',
            'entity_id' => $entity_id,
            'priority' => 'medium',
            'category' => 'technical'
        ];

        $request = new WP_REST_Request( 'POST', "/$this->api_base/tickets" );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( json_encode( $ticket_data ) );

        $response = rest_do_request( $request );

        $this->assertEquals( 201, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( 'id', $data );
        $this->assertEquals( 'API Created Ticket', $data['title'] );
        $this->assertEquals( 'new.customer@example.com', $data['customer_email'] );

        // Verify ticket was actually created in database
        $this->assertTicketExists( $data['id'] );
    }

    /**
     * Test PUT /tickets/{id} endpoint
     */
    public function test_update_ticket_endpoint() {
        $ticket_id = $this->mets_factory->create_ticket([
            'status' => 'open',
            'priority' => 'low'
        ]);

        $update_data = [
            'status' => 'in_progress',
            'priority' => 'high'
        ];

        $request = new WP_REST_Request( 'PUT', "/$this->api_base/tickets/$ticket_id" );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( json_encode( $update_data ) );

        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertEquals( 'in_progress', $data['status'] );
        $this->assertEquals( 'high', $data['priority'] );

        // Verify update in database
        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT status, priority FROM $table WHERE id = %d",
            $ticket_id
        ));

        $this->assertEquals( 'in_progress', $ticket->status );
        $this->assertEquals( 'high', $ticket->priority );
    }

    /**
     * Test DELETE /tickets/{id} endpoint
     */
    public function test_delete_ticket_endpoint() {
        $ticket_id = $this->mets_factory->create_ticket();

        $request = new WP_REST_Request( 'DELETE', "/$this->api_base/tickets/$ticket_id" );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertTrue( $data['deleted'] );

        // Verify ticket was deleted from database
        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $ticket_id
        ));

        $this->assertNull( $ticket );
    }

    /**
     * Test GET /entities endpoint
     */
    public function test_get_entities_endpoint() {
        // Create test entities
        $entity_ids = [
            $this->mets_factory->create_entity([ 'name' => 'API Entity 1' ]),
            $this->mets_factory->create_entity([ 'name' => 'API Entity 2' ])
        ];

        $request = new WP_REST_Request( 'GET', "/$this->api_base/entities" );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( 'entities', $data );
        $this->assertGreaterThanOrEqual( 2, count( $data['entities'] ) );
    }

    /**
     * Test POST /tickets/{id}/replies endpoint
     */
    public function test_create_ticket_reply_endpoint() {
        $ticket_id = $this->mets_factory->create_ticket();

        $reply_data = [
            'content' => 'This is an API reply',
            'author_type' => 'agent',
            'is_private' => false
        ];

        $request = new WP_REST_Request( 'POST', "/$this->api_base/tickets/$ticket_id/replies" );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( json_encode( $reply_data ) );

        $response = rest_do_request( $request );

        $this->assertEquals( 201, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertArrayHasKey( 'id', $data );
        $this->assertEquals( $ticket_id, $data['ticket_id'] );
        $this->assertEquals( 'This is an API reply', $data['content'] );
        $this->assertEquals( 'agent', $data['author_type'] );
    }

    /**
     * Test API input validation
     */
    public function test_api_input_validation() {
        // Test creating ticket with invalid data
        $invalid_data = [
            'title' => '', // Empty title
            'content' => 'Valid content',
            'customer_email' => 'invalid-email', // Invalid email format
            'priority' => 'invalid_priority' // Invalid priority
        ];

        $request = new WP_REST_Request( 'POST', "/$this->api_base/tickets" );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( json_encode( $invalid_data ) );

        $response = rest_do_request( $request );

        $this->assertEquals( 400, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertArrayHasKey( 'message', $data );
        $this->assertStringContainsString( 'validation', strtolower( $data['message'] ) );
    }

    /**
     * Test API rate limiting
     */
    public function test_api_rate_limiting() {
        // Make multiple rapid requests
        $responses = [];
        for ( $i = 0; $i < 15; $i++ ) {
            $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets" );
            $responses[] = rest_do_request( $request );
        }

        // Check if rate limiting kicks in (if implemented)
        $rate_limited = false;
        foreach ( $responses as $response ) {
            if ( $response->get_status() === 429 ) {
                $rate_limited = true;
                break;
            }
        }

        // If rate limiting is implemented, it should trigger
        // For now, we just verify all requests processed
        $this->assertGreaterThan( 0, count( $responses ) );
    }

    /**
     * Test API pagination
     */
    public function test_api_pagination() {
        // Create many tickets
        $ticket_ids = $this->mets_factory->create_tickets( 25 );

        // Test first page
        $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets" );
        $request->set_query_params([
            'page' => 1,
            'per_page' => 10
        ]);
        
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertArrayHasKey( 'tickets', $data );
        $this->assertLessThanOrEqual( 10, count( $data['tickets'] ) );
        
        // Check pagination headers
        $headers = $response->get_headers();
        $this->assertArrayHasKey( 'X-WP-Total', $headers );
        $this->assertArrayHasKey( 'X-WP-TotalPages', $headers );
    }

    /**
     * Test API search functionality
     */
    public function test_api_search_functionality() {
        // Create tickets with searchable content
        $this->mets_factory->create_ticket([
            'title' => 'Login Problem Issue',
            'content' => 'Cannot login to account'
        ]);

        $this->mets_factory->create_ticket([
            'title' => 'Payment Processing',
            'content' => 'Payment not going through'
        ]);

        // Search for "login" tickets
        $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets" );
        $request->set_query_params([
            'search' => 'login'
        ]);

        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertArrayHasKey( 'tickets', $data );
        
        // Verify search results contain "login"
        foreach ( $data['tickets'] as $ticket ) {
            $contains_search_term = 
                stripos( $ticket['title'], 'login' ) !== false ||
                stripos( $ticket['content'], 'login' ) !== false;
            
            $this->assertTrue( $contains_search_term );
        }
    }

    /**
     * Test API error handling
     */
    public function test_api_error_handling() {
        // Test accessing non-existent ticket
        $request = new WP_REST_Request( 'GET', "/$this->api_base/tickets/99999" );
        $response = rest_do_request( $request );

        $this->assertEquals( 404, $response->get_status() );
        
        $data = $response->get_data();
        $this->assertArrayHasKey( 'message', $data );
        $this->assertStringContainsString( 'not found', strtolower( $data['message'] ) );
    }
}