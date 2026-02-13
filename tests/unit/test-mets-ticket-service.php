<?php
/**
 * Unit tests for METS Ticket Service
 *
 * @package METS_Tests
 * @subpackage Unit_Tests
 */

class Test_METS_Ticket_Service extends METS_Test_Case {

	public function test_create_ticket_returns_id() {
		require_once METS_PLUGIN_PATH . 'includes/services/class-mets-ticket-service.php';
		$service = new METS_Ticket_Service();

		$entity_id = $this->mets_factory->create_entity( array( 'slug' => 'svc-test' ) );

		$result = $service->create_ticket( array(
			'entity_id'      => $entity_id,
			'subject'        => 'Service layer test',
			'description'    => 'Testing the service layer',
			'customer_name'  => 'Test User',
			'customer_email' => 'test@example.com',
		) );

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}

	public function test_update_ticket_tracks_changes() {
		require_once METS_PLUGIN_PATH . 'includes/services/class-mets-ticket-service.php';
		$service = new METS_Ticket_Service();

		$ticket_id = $this->mets_factory->create_ticket( array( 'status' => 'new' ) );
		$result = $service->update_ticket_properties( $ticket_id, array(
			'status' => 'open',
		) );

		$this->assertArrayHasKey( 'changes', $result );
	}
}
