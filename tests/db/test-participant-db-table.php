<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/db/participant-db-table.php');

class ParticipantDbTableTest extends WP_UnitTestCase {
    function setUp() {
        parent::setUp();
        $this->table = new ABACO_ParticipantDbTable();
        $this->table->create();
        global $wpdb;
        $this->db = $wpdb;
    }
    
    function tearDown() {
        $this->table->drop();
        parent::tearDown();
    }
    
    function insertParticipant($data) {
        $this->db->insert($this->table->name(), $data);
    }
    
    // Query all
    function test_query_all_no_data_returns_empty_array() {
        $res = $this->table->query_all();
        $this->assertEquals($res, []);
    }
    
    function test_query_all_returns_all_records() {
        $this->insertParticipant(['nif' => '123', 'document_type' => 'UUID']);
        $this->insertParticipant(['nif' => '456', 'document_type' => 'NIF']);
        $res = $this->table->query_all();
        $this->assertEquals(2, count($res));
        $this->assertEquals($res[0]['nif'], '123');
        $this->assertEquals($res[0]['document_type'], 'UUID');
        $this->assertEquals($res[1]['nif'], '456');
        $this->assertEquals($res[1]['document_type'], 'NIF');
    }
    
    function test_query_all_with_fields_returns_only_specified_fields() {
        $this->insertParticipant(['nif' => '123', 'document_type' => 'UUID']);
        $res = $this->table->query_all('nif, document_type');
        $this->assertEquals(count($res[0]), 2);
        $this->assertEquals($res[0]['nif'], '123');
        $this->assertEquals($res[0]['document_type'], 'UUID');
    }
    
    function test_query_all_applies_parser() {
        $day = array_keys(abaco_booking_days())[0];
        $this->insertParticipant([
            'nif' => '123',
            'booking_days' => serialize([$day]),
            'yes_info' => false,
            'birth_date' => '2010-10-20'
        ]);
        $res = $this->table->query_all('id, booking_days, yes_info,birth_date')[0];
        $this->assertTrue(is_int($res['id']));
        $this->assertEquals([$day], $res['booking_days']);
        $this->assertTrue(is_bool($res['yes_info']));
        $this->assertEquals($res['birth_date'], new DateTime('2010-10-20'));
    }
    
    // Query by ID
    function test_query_by_id_nif_existent_returns_record() {
        $this->insertParticipant(['nif' => '456']);
        $this->insertParticipant(['nif' => '123']);
        $res = $this->table->query_by_id('nif', '123');
        $this->assertEquals('123', $res->nif);
        return $res;
    }
    
    function test_query_by_id_numeric_id_existent_returns_record() {
        $this->insertParticipant(['nif' => '123']);
        $res = $this->table->query_by_id('id', 1);
        $this->assertEquals('123', $res->nif);
        return $res;
    }
    
    /**
     * @depends test_query_by_id_numeric_id_existent_returns_record
     */
    function test_query_by_id_applies_parser($record) {
        $this->assertTrue(is_int($record->id));
    }
    
    function test_query_by_id_specified_fields_returns_only_these_fields() {
        $this->insertParticipant(['nif' => '123', 'first_name' => 'test']);
        $res = $this->table->query_by_id('nif', '123', 'nif, first_name');
        $this->assertTrue(isset($res->nif));
        $this->assertTrue(isset($res->first_name));
        $this->assertFalse(isset($res->id));
    }
    
    function test_query_by_id_not_found_returns_null() {
        $res = $this->table->query_by_id('nif', 'non_existent');
        $this->assertNull($res);
    }
    
    // is_nif_available
    function test_is_nif_available_already_inscribed_returns_false() {
        $this->insertParticipant(['nif' => '123']);
        $res = $this->table->is_nif_available('123');
        $this->assertFalse($res);
    }
    
    function test_is_nif_available_not_inscribed_returns_true() {
        $res = $this->table->is_nif_available('123');
        $this->assertTrue($res);
    }
    
    // nif to id
    function test_nif_to_id_existing_nif_returns_id() {
        $this->insertParticipant(['nif' => '123']);
        $res = $this->table->nif_to_id('123');
        $this->assertEquals(1, $res);
    }
    
    function test_nif_to_id_non_existent_returns_null() {
        $res = $this->table->nif_to_id('non_existent');
        $this->assertNull($res);
    }
    
    // insert
    function test_insert_serializes_booking_days() {
        $data = ['nif' => '123', 'booking_days' => []];
        $this->table->insert($data);
        $res = $this->table->query_by_id('nif', '123', 'booking_days');
        $this->assertTrue(is_array($res->booking_days));
    }
}