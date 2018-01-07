<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/activity.php');
_abaco_require('inc/db/participant-db-table.php');
_abaco_require('inc/db/activity-db-table.php');

class ActivityTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->part_table = $this->createMock(ABACO_ParticipantDbTable::class);
        $this->part_table->method('nif_to_id')
                         ->will($this->returnCallback(function($value) {
                             return $value === 'notfound' ? null : 10;
                          }));
        $this->act_table = $this->createMock(ABACO_ActivityDbTable::class);
        $this->form = new ABACO_ActivityForm(
            $this->part_table, $this->act_table);
        $this->sub = new ABACO_Submission($this->form);
        $this->result = $this->getMockBuilder(ABACO_InvalidatableResult::class)
                             ->setMethods(['invalidate'])
                             ->getMock();
        $this->input = [
            'name_' => 'myname',
            'kind' => 'LIVE_ROLE',
            'description' => 'mydesc',
            'img' => 'myfile',
            'duration' => '60',
            'requested_time' => ['THU', 'SAT'],
            'participants_total' => '6',
            'participants_male' => '3',
            'participants_female' => '2',
            'observations' => 'myobs',
            'organizer_nif' => '123',
            'allows_preinscription' => ['allows_preinscription'],
            'adult_content' => []
        ];
    }
    
    function do_test_invalid($field_name) {
        $this->result->expects($this->once())
                     ->method('invalidate')
                     ->with($this->equalTo($field_name), $this->anything());
        $this->sub->setup_data($this->input, $this->result);
    }
    
    function do_test_valid($expected) {
        $this->sub->setup_data($this->input, $this->result);
        $this->assertEquals($expected, $this->sub->data());
    }
    
    // Invalid name
    function test_missing_name_invalid() {
        $this->input['name_'] = '';
        $this->do_test_invalid('name_');
    }
    
    // Invalid kind
    function test_invalid_kind_invalid() {
        $this->input['kind'] = 'invalid';
        $this->do_test_invalid('kind');
    }
    
    // Invalid description
    function test_missing_description_invalid() {
        $this->input['description'] = '';
        $this->do_test_invalid('description');
    }
    
    // Invalid img
    function test_missing_img_invalid() {
        $this->input['img'] = '';
        $this->do_test_invalid('img');
    }
    
    // Invalid duration
    function test_invalid_duration_invalid() {
        $this->input['duration'] = 'invalid';
        $this->do_test_invalid('duration');
    }
    
    // Invalid requested time
    function test_invalid_requested_time_type_invalid() {
        $this->input['requested_time'] = 'invalid';
        $this->do_test_invalid('requested_time');
    }
    
    function test_empty_requested_time_type_invalid() {
        $this->input['requested_time'] = [];
        $this->do_test_invalid('requested_time');
    }
    
    // Invalid participants.
    // Checks for negative numbers are performed in CF7
    function test_missing_participants_male_invalid() {
        $this->input['participants_male'] = '';
        $this->do_test_invalid('participants_male');
    }
    
    function test_nonnumber_participants_male_invalid() {
        $this->input['participants_male'] = 'invalid';
        $this->do_test_invalid('participants_male');
    }
    
    function test_missing_participants_female_invalid() {
        $this->input['participants_female'] = '';
        $this->do_test_invalid('participants_female');
    }
    
    function test_nonnumber_participants_female_invalid() {
        $this->input['participants_female'] = 'invalid';
        $this->do_test_invalid('participants_female');
    }
    
    function test_missing_participants_total_invalid() {
        $this->input['participants_total'] = '';
        $this->do_test_invalid('participants_total');
    }
    
    function test_nonnumber_participants_total_invalid() {
        $this->input['participants_total'] = 'invalid';
        $this->do_test_invalid('participants_total');
    }
    
    function test_negative_participants_indifferent_invalid() {
        $this->input['participants_total'] = '3';
        $this->do_test_invalid('participants_total');
    }
    
    // Invalid NIF
    function test_missing_organizer_nif_invalid() {
        $this->input['organizer_nif'] = '';
        $this->do_test_invalid('organizer_nif');
    }
    
    function test_organizer_nif_not_found_invalid() {
        $this->input['organizer_nif'] = 'notfound';
        $this->do_test_invalid('organizer_nif');
    }
    
    // Valid cases
    function test_trivial_valid() {
        $expected = [
            'name_' => 'myname',
            'kind' => 'LIVE_ROLE',
            'description' => 'mydesc',
            'img' => 'myfile',
            'duration' => '60',
            'requested_time' => ['THU', 'SAT'],
            'participants_total' => 6,
            'participants_male' => 3,
            'participants_female' => 2,
            'observations' => 'myobs',
            'organizer_nif' => '123',
            'participant_id' => 10,
            'allows_preinscription' => true,
            'adult_content' => false
        ];
        $this->do_test_valid($expected);
    }
}