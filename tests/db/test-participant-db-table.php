<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/db/participant-db-table.php');

// Parser test
class ParticipantParser extends PHPUnit_Framework_TestCase {
    function setUp() {
        $this->parser = new ABACO_ParticipantParser();
    }
    
    function test_birth_date_null_returns_null() {
        $data = ['birth_date' => null];
        $res = $this->parser->parse($data);
        $expected = ['birth_date' => null];
        $this->assertEquals($expected, $res);
    }
    
    function test_birth_date_invalid_returns_null() {
        $data = ['birth_date' => 'invalid'];
        $res = $this->parser->parse($data);
        $expected = ['birth_date' => null];
        $this->assertEquals($expected, $res);
    }
    
    function test_booking_days_invalid_returns_empty() {
        $data = ['booking_days' => 'invalid'];
        $res = $this->parser->parse($data);
        $expected = ['booking_days' => []];
        $this->assertEquals($expected, $res);
    }
    
    function test_booking_days_not_array_returns_empty() {
        $data = ['booking_days' => serialize(78)];
        $res = $this->parser->parse($data);
        $expected = ['booking_days' => []];
        $this->assertEquals($expected, $res);
    }
    
    function test_contact_participant_id_null_returns_null() {
        $data = ['contact_participant_id' => null];
        $res = $this->parser->parse($data);
        $expected = ['contact_participant_id' => null];
        $this->assertEquals($expected, $res);
    }
    
    function test_contact_participant_id_not_int_returns_null() {
        $data = ['contact_participant_id' => 'invalid'];
        $res = $this->parser->parse($data);
        $expected = ['contact_participant_id' => null];
        $this->assertEquals($expected, $res);
    }
    
    function test_contact_participant_int_returns_int() {
        $data = ['contact_participant_id' => '123'];
        $res = $this->parser->parse($data);
        $expected = ['contact_participant_id' => 123];
        $this->assertEquals($expected, $res);
    }
}

// Test with real DB
class ParticipantDbTableTest extends WP_UnitTestCase {
    function setUp() {
        parent::setUp();
        global $wpdb;
        $this->db = $wpdb;
        $this->parser = new ABACO_ParticipantParser();
        $this->table = new ABACO_ParticipantDbTable($this->db, $this->parser);
        $this->table->create();
    }
    
    function tearDown() {
        $this->table->drop();
        parent::tearDown();
    }
    
    function get_raw_record() {
        return [
            'nif' => '123',
            'document_type' => 'PASSPORT',
            'first_name' => 'myname',
            'last_name' => 'mylastname',
            'alias' => 'myalias',
            'birth_date' => '2010-10-20',
            'phone' => '901020',
            'email' => 'test@gmail.com',
            'gender' => 'MALE',
            'group' => 'mygroup',
            'province' => 'NAVARRA',
            'city' => 'mycity',
            'observations' => 'myobs',
            'booking_days' => serialize(['THU', 'SAT']),
            'tutor_nif' => '456',
            'yes_info' => 0
        ];
    }
    
    function get_parsed_record() {
        return [
            'nif' => '123',
            'document_type' => 'PASSPORT',
            'first_name' => 'myname',
            'last_name' => 'mylastname',
            'alias' => 'myalias',
            'birth_date' => new DateTime('2010-10-20'),
            'phone' => '901020',
            'email' => 'test@gmail.com',
            'gender' => 'MALE',
            'group' => 'mygroup',
            'province' => 'NAVARRA',
            'city' => 'mycity',
            'observations' => 'myobs',
            'booking_days' => ['THU', 'SAT'],
            'tutor_nif' => '456',
            'yes_info' => false
        ];
    }
    
    function insertParticipant($data) {
        $this->db->insert($this->table->name(), $data);
    }
    
    // Query all
    function test_query_all_no_data_returns_empty_array() {
        $res = $this->table->query_all();
        $this->assertEquals($res, []);
    }
    
    function test_query_all_returns_all_records_and_parses() {
        $data1 = $this->get_raw_record();
        $this->insertParticipant($data1);
        $data2 = $data1;
        $data2['nif'] = '456';
        $this->insertParticipant($data2);
        $res = $this->table->query_all();
        $this->assertEquals(2, count($res));
        $expected1 = $this->get_parsed_record();
        $this->assertArraySubset($expected1, $res[0]);
        $expected2 = $expected1;
        $expected2['nif'] = '456';
        $this->assertArraySubset($expected2, $res[1]);
    }
    
    function test_query_all_with_fields_returns_only_specified_fields() {
        $this->insertParticipant($this->get_raw_record());
        $res = $this->table->query_all(['nif', 'booking_days']);
        $expected = [
            'nif' => '123',
            'booking_days' => ['THU', 'SAT']
        ];
        $this->assertEquals($expected, $res[0]);
    }
    
    function test_query_all_with_fields_all_work() {
        // Prior implementation produced trouble with specific fields
        $data = $this->get_raw_record();
        $this->insertParticipant($data);
        $res = $this->table->query_all(array_keys($data));
        $this->assertEquals($this->get_parsed_record(), $res[0]);
    }
   
    // Query by ID
    function test_query_by_id_nif_existent_returns_record_and_parses() {
        $data = $this->get_raw_record();
        $this->insertParticipant($data);
        $data['nif'] = '456';
        $this->insertParticipant($data);
        $res = $this->table->query_by_id('nif', '123');
        $this->assertTrue(is_object($res));
        $this->assertArraySubset($this->get_parsed_record(), (array)$res);
    }
    
    function test_query_by_id_numeric_id_existent_returns_record_and_parses() {
        $data = $this->get_raw_record();
        $this->insertParticipant($data);
        $data['nif'] = '456';
        $this->insertParticipant($data);
        $res = $this->table->query_by_id('id', 1);
        $this->assertArraySubset($this->get_parsed_record(), (array)$res);
    }
    
    function test_query_by_id_with_fields_returns_only_these_fields() {
        $this->insertParticipant($this->get_raw_record());
        $res = $this->table->query_by_id('nif', '123', ['nif', 'group']);
        $expected = (object)[
            'nif' => '123',
            'group' => 'mygroup'
        ];
        $this->assertEquals($expected, $res);
    }
    
    function test_query_by_id_with_fields_all_work() {
        // Prior implementation produced trouble with specific fields
        $data = $this->get_raw_record();
        $this->insertParticipant($data);
        $res = $this->table->query_by_id('nif', '123', array_keys($data));
        $this->assertEquals((object)$this->get_parsed_record(), $res);
    }
    
    function test_query_by_id_not_found_returns_null() {
        $res = $this->table->query_by_id('nif', 'non_existent');
        $this->assertNull($res);
    }
    
    // is_nif_available
    function test_is_nif_available_already_inscribed_returns_false() {
        $this->insertParticipant($this->get_raw_record());
        $res = $this->table->is_nif_available('123');
        $this->assertFalse($res);
    }
    
    function test_is_nif_available_not_inscribed_returns_true() {
        $res = $this->table->is_nif_available('123');
        $this->assertTrue($res);
    }
    
    // nif to id
    function test_nif_to_id_existing_nif_returns_id() {
        $this->insertParticipant($this->get_raw_record());
        $res = $this->table->nif_to_id('123');
        $this->assertEquals(1, $res);
    }
    
    function test_nif_to_id_non_existent_returns_null() {
        $res = $this->table->nif_to_id('non_existent');
        $this->assertNull($res);
    }
    
    // insert
    function test_insert_queries_return_inserted_record() {
        $data = $this->get_parsed_record();
        $this->table->insert($data);
        $res = $this->table->query_by_id('nif', '123', array_keys($data));
        $this->assertEquals((object)$data, $res);
    }
}