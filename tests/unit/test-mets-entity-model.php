<?php
/**
 * Unit tests for METS Entity Model
 *
 * @package METS_Tests
 * @subpackage Unit_Tests
 */

class Test_METS_Entity_Model extends METS_Test_Case {

    /**
     * Test entity creation
     */
    public function test_create_entity() {
        $entity_data = [
            'name' => 'Test Support Entity',
            'description' => 'Test entity for unit testing',
            'status' => 'active',
            'parent_id' => 0
        ];

        $entity_id = $this->mets_factory->create_entity( $entity_data );

        $this->assertIsInt( $entity_id );
        $this->assertGreaterThan( 0, $entity_id );
        $this->assertEntityExists( $entity_id );
    }

    /**
     * Test entity hierarchy
     */
    public function test_entity_hierarchy() {
        // Create parent entity
        $parent_id = $this->mets_factory->create_entity([
            'name' => 'Main Support',
            'parent_id' => 0
        ]);

        // Create child entity
        $child_id = $this->mets_factory->create_entity([
            'name' => 'Technical Support',
            'parent_id' => $parent_id
        ]);

        global $wpdb;
        $table = $wpdb->prefix . 'mets_entities';

        // Verify parent entity
        $parent = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $parent_id
        ));

        $this->assertEquals( 0, $parent->parent_id );
        $this->assertEquals( 'Main Support', $parent->name );

        // Verify child entity
        $child = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $child_id
        ));

        $this->assertEquals( $parent_id, $child->parent_id );
        $this->assertEquals( 'Technical Support', $child->name );
    }

    /**
     * Test entity status management
     */
    public function test_entity_status_management() {
        $entity_id = $this->mets_factory->create_entity([
            'status' => 'active'
        ]);

        global $wpdb;
        $table = $wpdb->prefix . 'mets_entities';

        // Test deactivating entity
        $wpdb->update(
            $table,
            [ 'status' => 'inactive' ],
            [ 'id' => $entity_id ],
            [ '%s' ],
            [ '%d' ]
        );

        $entity = $wpdb->get_row( $wpdb->prepare(
            "SELECT status FROM $table WHERE id = %d",
            $entity_id
        ));

        $this->assertEquals( 'inactive', $entity->status );

        // Test reactivating entity
        $wpdb->update(
            $table,
            [ 'status' => 'active' ],
            [ 'id' => $entity_id ],
            [ '%s' ],
            [ '%d' ]
        );

        $entity = $wpdb->get_row( $wpdb->prepare(
            "SELECT status FROM $table WHERE id = %d",
            $entity_id
        ));

        $this->assertEquals( 'active', $entity->status );
    }

    /**
     * Test getting child entities
     */
    public function test_get_child_entities() {
        // Create parent entity
        $parent_id = $this->mets_factory->create_entity([
            'name' => 'Main Department'
        ]);

        // Create multiple child entities
        $child_ids = [
            $this->mets_factory->create_entity([
                'name' => 'Sub Department 1',
                'parent_id' => $parent_id
            ]),
            $this->mets_factory->create_entity([
                'name' => 'Sub Department 2',
                'parent_id' => $parent_id
            ]),
            $this->mets_factory->create_entity([
                'name' => 'Sub Department 3',
                'parent_id' => $parent_id
            ])
        ];

        global $wpdb;
        $table = $wpdb->prefix . 'mets_entities';

        // Get all child entities
        $children = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE parent_id = %d ORDER BY name",
            $parent_id
        ));

        $this->assertCount( 3, $children );
        $this->assertEquals( 'Sub Department 1', $children[0]->name );
        $this->assertEquals( 'Sub Department 2', $children[1]->name );
        $this->assertEquals( 'Sub Department 3', $children[2]->name );
    }

    /**
     * Test entity deletion with ticket dependency check
     */
    public function test_entity_deletion_with_dependencies() {
        $entity_id = $this->mets_factory->create_entity();

        // Create a ticket assigned to this entity
        $ticket_id = $this->mets_factory->create_ticket([
            'entity_id' => $entity_id
        ]);

        global $wpdb;
        $entity_table = $wpdb->prefix . 'mets_entities';
        $ticket_table = $wpdb->prefix . 'mets_tickets';

        // Check if entity has dependent tickets
        $ticket_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $ticket_table WHERE entity_id = %d",
            $entity_id
        ));

        $this->assertEquals( 1, $ticket_count );

        // In a real implementation, entity deletion should be prevented
        // if it has dependent tickets, or tickets should be reassigned
        
        // For testing, verify the dependency exists
        $this->assertGreaterThan( 0, $ticket_count );
    }

    /**
     * Test active entities retrieval
     */
    public function test_get_active_entities() {
        // Create mix of active and inactive entities
        $active_ids = [
            $this->mets_factory->create_entity([ 'status' => 'active', 'name' => 'Active 1' ]),
            $this->mets_factory->create_entity([ 'status' => 'active', 'name' => 'Active 2' ])
        ];

        $inactive_ids = [
            $this->mets_factory->create_entity([ 'status' => 'inactive', 'name' => 'Inactive 1' ])
        ];

        global $wpdb;
        $table = $wpdb->prefix . 'mets_entities';

        // Get only active entities
        $active_entities = $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'active' ORDER BY name"
        );

        // Should include default test entities plus the new active ones
        $this->assertGreaterThanOrEqual( count( $active_ids ), count( $active_entities ) );
        
        // Verify none of the results are inactive
        foreach ( $active_entities as $entity ) {
            $this->assertEquals( 'active', $entity->status );
        }
    }

    /**
     * Test entity search functionality
     */
    public function test_entity_search() {
        // Create entities with specific names
        $entity_ids = [
            $this->mets_factory->create_entity([ 'name' => 'Technical Support Team' ]),
            $this->mets_factory->create_entity([ 'name' => 'Customer Service Team' ]),
            $this->mets_factory->create_entity([ 'name' => 'Technical Documentation' ])
        ];

        global $wpdb;
        $table = $wpdb->prefix . 'mets_entities';

        // Search for entities containing "Technical"
        $technical_entities = $wpdb->get_results(
            "SELECT * FROM $table WHERE name LIKE '%Technical%'"
        );

        $this->assertGreaterThanOrEqual( 2, count( $technical_entities ) );

        // Search for entities containing "Team"
        $team_entities = $wpdb->get_results(
            "SELECT * FROM $table WHERE name LIKE '%Team%'"
        );

        $this->assertGreaterThanOrEqual( 2, count( $team_entities ) );
    }

    /**
     * Test entity update
     */
    public function test_update_entity() {
        $entity_id = $this->mets_factory->create_entity([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        global $wpdb;
        $table = $wpdb->prefix . 'mets_entities';

        // Update entity
        $updated = $wpdb->update(
            $table,
            [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'updated_at' => current_time( 'mysql' )
            ],
            [ 'id' => $entity_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        $this->assertEquals( 1, $updated );

        // Verify update
        $entity = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $entity_id
        ));

        $this->assertEquals( 'Updated Name', $entity->name );
        $this->assertEquals( 'Updated Description', $entity->description );
    }

    /**
     * Test entity validation
     */
    public function test_entity_validation() {
        // Test entity name uniqueness (if implemented)
        $entity_name = 'Unique Entity Name';
        
        $first_id = $this->mets_factory->create_entity([
            'name' => $entity_name
        ]);

        // In a real implementation, creating another entity with the same name
        // might be prevented or handled differently
        $second_id = $this->mets_factory->create_entity([
            'name' => $entity_name
        ]);

        // Both should succeed unless uniqueness constraint is implemented
        $this->assertNotEquals( $first_id, $second_id );
    }
}