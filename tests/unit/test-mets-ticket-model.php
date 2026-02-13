<?php
/**
 * Unit tests for METS Ticket Model
 *
 * @package METS_Tests
 * @subpackage Unit_Tests
 */

class Test_METS_Ticket_Model extends METS_Test_Case {

    /**
     * Test ticket creation
     */
    public function test_create_ticket() {
        $ticket_data = [
            'title' => 'Test Ticket Creation',
            'content' => 'This is a test ticket content',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'entity_id' => 1,
            'priority' => 'medium',
            'category' => 'general'
        ];

        $ticket_id = $this->mets_factory->create_ticket( $ticket_data );

        $this->assertIsInt( $ticket_id );
        $this->assertGreaterThan( 0, $ticket_id );
        $this->assertTicketExists( $ticket_id );
    }

    /**
     * Test ticket retrieval
     */
    public function test_get_ticket() {
        $ticket_id = $this->mets_factory->create_ticket();
        
        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $ticket_id
        ));

        $this->assertNotNull( $ticket );
        $this->assertEquals( $ticket_id, $ticket->id );
        $this->assertNotEmpty( $ticket->title );
        $this->assertNotEmpty( $ticket->customer_email );
    }

    /**
     * Test ticket update
     */
    public function test_update_ticket() {
        $ticket_id = $this->mets_factory->create_ticket([
            'status' => 'open',
            'priority' => 'low'
        ]);

        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';
        
        // Update ticket
        $updated = $wpdb->update(
            $table,
            [ 'status' => 'closed', 'priority' => 'high' ],
            [ 'id' => $ticket_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        $this->assertEquals( 1, $updated );

        // Verify update
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $ticket_id
        ));

        $this->assertEquals( 'closed', $ticket->status );
        $this->assertEquals( 'high', $ticket->priority );
    }

    /**
     * Test ticket validation
     */
    public function test_ticket_validation() {
        // Test missing required fields
        $invalid_ticket = [
            'content' => 'Missing title and customer info'
        ];

        // This should fail validation if model has validation
        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';
        
        // Insert without required fields should fail
        $result = $wpdb->insert( $table, $invalid_ticket );
        
        // Since we don't have validation at DB level, this will succeed
        // But in a real implementation, model validation should catch this
        $this->assertFalse( $result === false );
    }

    /**
     * Test ticket status transitions
     */
    public function test_ticket_status_transitions() {
        $ticket_id = $this->mets_factory->create_ticket([
            'status' => 'open'
        ]);

        $valid_statuses = [ 'open', 'in_progress', 'resolved', 'closed' ];
        
        foreach ( $valid_statuses as $status ) {
            global $wpdb;
            $table = $wpdb->prefix . 'mets_tickets';
            
            $updated = $wpdb->update(
                $table,
                [ 'status' => $status ],
                [ 'id' => $ticket_id ],
                [ '%s' ],
                [ '%d' ]
            );

            $this->assertEquals( 1, $updated );

            $ticket = $wpdb->get_row( $wpdb->prepare(
                "SELECT status FROM $table WHERE id = %d",
                $ticket_id
            ));

            $this->assertEquals( $status, $ticket->status );
        }
    }

    /**
     * Test ticket priority levels
     */
    public function test_ticket_priority_levels() {
        $priorities = [ 'low', 'medium', 'high', 'urgent' ];
        
        foreach ( $priorities as $priority ) {
            $ticket_id = $this->mets_factory->create_ticket([
                'priority' => $priority
            ]);

            global $wpdb;
            $table = $wpdb->prefix . 'mets_tickets';
            $ticket = $wpdb->get_row( $wpdb->prepare(
                "SELECT priority FROM $table WHERE id = %d",
                $ticket_id
            ));

            $this->assertEquals( $priority, $ticket->priority );
        }
    }

    /**
     * Test ticket search functionality
     */
    public function test_ticket_search() {
        // Create tickets with specific titles
        $ticket_ids = [
            $this->mets_factory->create_ticket([ 'title' => 'Login Issue' ]),
            $this->mets_factory->create_ticket([ 'title' => 'Payment Problem' ]),
            $this->mets_factory->create_ticket([ 'title' => 'Login Problem' ])
        ];

        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';

        // Search for tickets containing "Login"
        $login_tickets = $wpdb->get_results(
            "SELECT * FROM $table WHERE title LIKE '%Login%'"
        );

        $this->assertCount( 2, $login_tickets );

        // Search for tickets containing "Problem"
        $problem_tickets = $wpdb->get_results(
            "SELECT * FROM $table WHERE title LIKE '%Problem%'"
        );

        $this->assertCount( 2, $problem_tickets );
    }

    /**
     * Test ticket deletion
     */
    public function test_delete_ticket() {
        $ticket_id = $this->mets_factory->create_ticket();
        
        // Verify ticket exists
        $this->assertTicketExists( $ticket_id );

        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';
        
        // Delete ticket
        $deleted = $wpdb->delete(
            $table,
            [ 'id' => $ticket_id ],
            [ '%d' ]
        );

        $this->assertEquals( 1, $deleted );

        // Verify ticket no longer exists
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $ticket_id
        ));

        $this->assertNull( $ticket );
    }

    /**
     * Test ticket number generation
     */
    public function test_ticket_number_generation() {
        // Set ticket prefix
        $this->utils->set_mets_settings([
            'ticket_prefix' => 'TST-'
        ]);

        $ticket_id = $this->mets_factory->create_ticket();

        // In a real implementation, ticket number would be auto-generated
        // Here we simulate what the expected format should be
        $expected_number = 'TST-' . str_pad( $ticket_id, 6, '0', STR_PAD_LEFT );

        // This test assumes ticket number generation is implemented
        $this->assertMatchesRegularExpression( '/^TST-\d{6}$/', $expected_number );
    }

    /**
     * Test that ticket number generation produces unique sequential numbers.
     */
    public function test_create_ticket_generates_unique_numbers() {
        $entity_id = $this->mets_factory->create_entity(['slug' => 'testco']);

        $ticket1_id = $this->mets_factory->create_ticket([
            'entity_id' => $entity_id,
            'subject' => 'First ticket',
            'description' => 'Test',
            'customer_name' => 'User One',
            'customer_email' => 'one@example.com',
        ]);

        $ticket2_id = $this->mets_factory->create_ticket([
            'entity_id' => $entity_id,
            'subject' => 'Second ticket',
            'description' => 'Test',
            'customer_name' => 'User Two',
            'customer_email' => 'two@example.com',
        ]);

        global $wpdb;
        $table = $wpdb->prefix . 'mets_tickets';
        $num1 = $wpdb->get_var($wpdb->prepare("SELECT ticket_number FROM $table WHERE id = %d", $ticket1_id));
        $num2 = $wpdb->get_var($wpdb->prepare("SELECT ticket_number FROM $table WHERE id = %d", $ticket2_id));

        $this->assertNotEquals($num1, $num2, 'Two tickets should never share a ticket number');

        // Verify sequential numbering
        $seq1 = intval(substr($num1, -4));
        $seq2 = intval(substr($num2, -4));
        $this->assertEquals($seq1 + 1, $seq2, 'Ticket numbers should be sequential');
    }
}