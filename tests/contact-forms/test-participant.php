<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/participant.php');
_abaco_require('inc/db/participant-db-table.php');

function make_birth_date($age) {
    $res = (new DateTime())->sub(new DateInterval("P${age}Y"));
    return new DateTime($res->format('Y-01-01'));
}

function make_birth_date_string($age) {
    return make_birth_date($age)->format('Y-m-d');
}

class ParticipantTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->table = $this->createMock(ABACO_ParticipantDbTable::class);
        $this->table->method('is_nif_available')
                    ->will($this->returnCallback(function($value) {
                        return $value !== 'existent';
                    }));
        $this->form = new ABACO_ParticipantForm($this->table);
        $this->sub = new ABACO_Submission($this->form);
        $this->result = $this->getMockBuilder(ABACO_InvalidatableResult::class)
                             ->setMethods(['invalidate'])
                             ->getMock();
        $this->input = [
            'first_name' => 'my_first_name',
            'last_name' => 'my_last_name',
            'alias' => 'my_alias',
            'birth_date' => make_birth_date_string(19),
            'document_type' => 'NIF',
            'nif' => '123',
            'gender' => 'MALE',
            'phone' => '670',
            'email' => 'test@test.com',
            'group' => 'mygroup',
            'province' => 'NAVARRA',
            'city' => 'Pamplona',
            'observations' => 'myobs',
            'booking_days' => ['THU', 'FRI'],
            'tutor_nif' => '',
            'yes_info' => ['yes_info']
        ];
    }
    
    function get_expected() {
        return [
            'first_name' => 'my_first_name',
            'last_name' => 'my_last_name',
            'alias' => 'my_alias',
            'birth_date' => make_birth_date(19),
            'document_type' => 'NIF',
            'nif' => '123',
            'gender' => 'MALE',
            'phone' => '670',
            'email' => 'test@test.com',
            'group' => 'mygroup',
            'province' => 'NAVARRA',
            'city' => 'pamplona',
            'observations' => 'myobs',
            'booking_days' => ['THU', 'FRI'],
            'tutor_nif' => '',
            'yes_info' => true
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
    
    // Invalid first_name
    function test_missing_first_name_invalid() {
        $this->input['first_name'] = '';
        $this->do_test_invalid('first_name');
    }
    
    // Invalid last_name
    function test_missing_last_name_invalid() {
        $this->input['last_name'] = '';
        $this->do_test_invalid('last_name');
    }
    
    // Invalid birth_date
    function test_missing_birth_date_invalid() {
        $this->input['birth_date'] = '';
        $this->do_test_invalid('birth_date');
    }
    
    function test_invalid_birth_date_invalid() {
        $this->input['birth_date'] = 'invalid';
        $this->do_test_invalid('birth_date');
    }
    
    // Invalid document_type
    function test_invalid_document_type_invalid() {
        $this->input['document_type'] = 'invalid';
        $this->do_test_invalid('document_type');
    }
    
    function test_document_type_uuid_age_over_nif_age_invalid() {
        $this->input['document_type'] = 'UUID';
        $this->input['birth_date'] = make_birth_date_string(ABACO_NIF_MANDATORY_AGE);
        $this->do_test_invalid('document_type');
    }
    
    // Invalid nif
    function test_missing_nif_document_type_nif_invalid() {
        $this->input['document_type'] = 'NIF';
        $this->input['nif'] = '';
        $this->do_test_invalid('nif');
    }
    
    function test_missing_nif_document_type_passport_invalid() {
        $this->input['document_type'] = 'PASSPORT';
        $this->input['nif'] = '';
        $this->do_test_invalid('nif');
    }
    
    function test_existing_nif_invalid() {
        $this->input['nif'] = 'existent';
        $this->do_test_invalid('nif');
    }
    
    // Invalid gender
    function test_invalid_gender_invalid() {
        $this->input['gender'] = 'invalid';
        $this->do_test_invalid('gender');
    }
    
    // Invalid email
   function test_missing_email_invalid() {
        $this->input['email'] = '';
        $this->do_test_invalid('email');
    }
    function test_invalid_email_invalid() {
        $this->input['email'] = 'invalid';
        $this->do_test_invalid('email');
    }
    
    // Invalid province
    function test_invalid_province_invalid() {
        $this->input['province'] = 'invalid';
        $this->do_test_invalid('province');
    }
    
    // Invalid city
    function test_missing_city_invalid() {
        $this->input['city'] = '';
        $this->do_test_invalid('city');
    }
    
    // Invalid tutor_nif
    function test_minor_missing_tutor_nif_invalid() {
        $this->input['birth_date'] = make_birth_date_string(17);
        $this->input['tutor_nif'] = '';
        $this->do_test_invalid('tutor_nif');
    }
    
    function test_minor_tutor_does_not_exist_invalid() {
        $this->input['birth_date'] = make_birth_date_string(17);
        $this->input['booking_days'] = [];
        $this->input['tutor_nif'] = 'tutor';
        $this->table->method('query_by_id')->willReturn(null);
        $this->do_test_invalid('tutor_nif');
    }
    
    function test_minor_tutor_under_age_invalid() {
        $this->input['birth_date'] = make_birth_date_string(17);
        $this->input['booking_days'] = []; // not interfere with current test
        $this->input['tutor_nif'] = 'tutor';
        $this->table->method('query_by_id')->willReturn((object)[
            'birth_date' => make_birth_date(17),
            'booking_days' => ['THU', 'FRI']
        ]);
        $this->do_test_invalid('tutor_nif');
    }
    
    function test_minor_tutor_booking_days_mismatch_invalid() {
        $this->input['birth_date'] = make_birth_date_string(17);
        $this->input['booking_days'] = ['THU', 'FRI'];
        $this->input['tutor_nif'] = 'tutor';
        $this->table->method('query_by_id')->willReturn((object)[
            'birth_date' => make_birth_date(17),
            'booking_days' => ['FRI', 'SAT']
        ]);
        $this->do_test_invalid('tutor_nif');
    }
    
    function test_minor_tutor_nif_null_birth_date_invalid() {
        // This will happen if tutor_nif points to a company
        $this->input['birth_date'] = make_birth_date_string(17);
        $this->input['booking_days'] = [];
        $this->input['tutor_nif'] = 'tutor';
        $this->table->method('query_by_id')->willReturn((object)[
            'birth_date' => null,
            'booking_days' => ['FRI', 'SAT']
        ]);
        $this->do_test_invalid('tutor_nif');
    }
    
    // Valid cases
    function test_trivial_valid() {
        $expected = $this->get_expected();
        $this->do_test_valid($expected);
    }
    
    function test_document_type_passport_nif_present_valid() {
        $this->input['document_type'] = 'PASSPORT';
        $expected = $this->get_expected();
        $expected['document_type'] = 'PASSPORT';
        $this->do_test_valid($expected);
    }
    
    function test_minor_valid() {
        $this->input['birth_date'] = make_birth_date_string(ABACO_MINORITY_AGE - 3);
        $this->input['tutor_nif'] = '678';
        $this->input['booking_days'] = ['SAT'];
        $expected = $this->get_expected();
        $expected['birth_date'] = make_birth_date(ABACO_MINORITY_AGE - 3);
        $expected['tutor_nif'] = '678';
        $expected['booking_days'] = ['SAT'];
        $this->table->method('query_by_id')->willReturn((object)[
            'birth_date' => make_birth_date(19),
            'booking_days' => ['FRI', 'SAT']
        ]);
        $this->do_test_valid($expected);
    }
    
    function test_nif_uuid_valid() {
        $this->input['document_type'] = 'UUID';
        $this->input['nif'] = '';
        $this->input['birth_date'] = make_birth_date_string(ABACO_NIF_MANDATORY_AGE - 3);
        $this->input['tutor_nif'] = '678';
        $this->input['booking_days'] = ['SAT'];
        $expected = $this->get_expected();
        $expected['document_type'] = 'UUID';
        $expected['birth_date'] = make_birth_date(ABACO_NIF_MANDATORY_AGE - 3);
        $expected['tutor_nif'] = '678';
        $expected['booking_days'] = ['SAT'];
        unset($expected['nif']);
        $this->table->method('query_by_id')->willReturn((object)[
            'birth_date' => make_birth_date(19),
            'booking_days' => ['FRI', 'SAT']
        ]);
        $this->sub->setup_data($this->input, $this->result);
        $data = $this->sub->data();
        $this->assertNotEmpty($data['nif']);
        unset($data['nif']);
        $this->assertEquals($expected, $data);
    }
}

class ParticipantInsertTest extends PHPUnit_Framework_TestCase {
    function setUp() {
        $this->table = $this->getMockBuilder(ABACO_ParticipantDbTable::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['insert'])
                            ->getMock();
        $this->form = new ABACO_ParticipantForm($this->table);
    }
    
    function test_all_fields_not_empty_forwards_to_table_insert() {
        $data = [
            'first_name' => 'my_first_name',
            'last_name' => 'my_last_name',
            'alias' => 'my_alias',
            'birth_date' => make_birth_date_string(19),
            'document_type' => 'NIF',
            'nif' => '123',
            'gender' => 'MALE',
            'phone' => '670',
            'email' => 'test@test.com',
            'group' => 'mygroup',
            'province' => 'NAVARRA',
            'city' => 'Pamplona',
            'observations' => 'myobs',
            'booking_days' => ['THU', 'FRI'],
            'tutor_nif' => '900',
            'yes_info' => ['yes_info']
        ];
        $this->table->expects($this->once())
             ->method('insert')
             ->with($this->equalTo($data));
        $this->form->insert($data);
    }
    
    function test_empty_optional_fields_unsets_them() {
        $data = [
            'first_name' => 'my_first_name',
            'last_name' => 'my_last_name',
            'alias' => '',
            'birth_date' => make_birth_date_string(19),
            'document_type' => 'NIF',
            'nif' => '123',
            'gender' => 'MALE',
            'phone' => '',
            'email' => 'test@test.com',
            'group' => '',
            'province' => 'NAVARRA',
            'city' => 'Pamplona',
            'observations' => '',
            'booking_days' => ['THU', 'FRI'],
            'tutor_nif' => '',
            'yes_info' => ['yes_info']
        ];
        $expected = [
            'first_name' => 'my_first_name',
            'last_name' => 'my_last_name',
            'birth_date' => make_birth_date_string(19),
            'document_type' => 'NIF',
            'nif' => '123',
            'gender' => 'MALE',
            'email' => 'test@test.com',
            'province' => 'NAVARRA',
            'city' => 'Pamplona',
            'booking_days' => ['THU', 'FRI'],
            'yes_info' => ['yes_info']
        ];
        $this->table->expects($this->once())
             ->method('insert')
             ->with($this->equalTo($expected));
        $this->form->insert($data);
    }
}