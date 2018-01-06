<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/participant.php');

function make_birth_date($age) {
    return (new DateTime())->sub(new DateInterval("P${age}Y"));
}

function make_birth_date_string($age) {
    return make_birth_date($age)->format('Y-m-d');
}

class ABACO_MockParticipantTable {
    public function is_nif_available($nif) {
        return $nif !== 'existent';
    }
    public function query_by_id($id_type, $id, $fields) {
        if ($id_type !== 'nif') {
            return null;
        }
        if ($id === 'under_age') {
            return (object)[
                'birth_date' => make_birth_date(17)->format('Y-m-d'),
                'booking_days' => ['THU', 'FRI']
            ];
        } else if ($id === 'over_age') {
            return (object)[
                'birth_date' => make_birth_date(19)->format('Y-m-d'),
                'booking_days' => ['THU', 'FRI']
            ];
        }
        return null;
    }
}

class ParticipantTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->table = new ABACO_MockParticipantTable();
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
    
    function expect_invalid($field_name) {
        $this->result->expects($this->once())
                     ->method('invalidate')
                     ->with($this->equalTo($field_name), $this->anything());
    }
    function do_test() {
        $this->sub->setup_data($this->input, $this->result);
    }
    
    function do_test_invalid($field_name) {
        $this->expect_invalid($field_name);
        $this->do_test();
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
    
    // validate_document_type
    /*function test_validate_document_type_uuid_under_nif_age_ok() {
        $data = [
            'document_type' => 'UUID',
            'birth_date' => make_birth_date(13)
        ];
        $res = $this->form->validate_document_type($data);
        $this->assertEquals($data, $res);
    }
    

    
    function test_validate_document_type_nif_ok() {
        $data = ['document_type' => 'NIF'];
        $res = $this->form->validate_document_type($data);
        $this->assertEquals($data, $res);
    }
    
    function test_validate_document_type_passport_ok() {
        $data = ['document_type' => 'NIF'];
        $res = $this->form->validate_document_type($data);
        $this->assertEquals($data, $res);
    }
    
    // validate_nif
    function test_validate_nif_does_not_exist_ok() {
        $data = [
            'document_type' => 'NIF',
            'nif' => 'non_existent'
        ];
        $res = $this->form->validate_nif($data);
        $this->assertEquals($data, $res);
    }
    
    function test_validate_nif_exists_throw() {
        $data = [
            'document_type' => 'NIF',
            'nif' => 'existent'
        ];
        $this->expectException(ABACO_ValidationError::class);
        $this->form->validate_nif($data);
    }
    
    function test_validate_nif_uuid_generates() {
        $data = ['document_type' => 'UUID'];
        $res = $this->form->validate_nif($data);
        $this->assertNotEmpty($res['nif']); // an UUID was generated
        $this->assertEquals(2, count($res)); // no additional fields
        $this->assertEquals('UUID', $res['document_type']); // unmodified
    }
    
    function test_validate_nif_not_uuid_missing_nif_throws() {
        $data = ['document_type' => 'NIF', 'nif' => ''];
        $this->expectException(ABACO_ValidationError::class);
        $this->form->validate_nif($data);
    }
    
    // validate_tutor
    function test_validate_tutor_over_age_ok() {
        $data = [
            'birth_date' => make_birth_date(18),
            'tutor_nif' => ''
        ];
        $res = $this->form->validate_tutor($data);
        $this->assertEquals($res, $data);
        $mitest = ['hola' => null];
        $this->assertNull($mitest['hola']);
    }
    
    function test_validate_tutor_under_age_tutor_nif_empty_throws() {
        $data = [
            'birth_date' => make_birth_date(17),
            'tutor_nif' => ''
        ];
        $this->expectException(ABACO_ValidationError::class);
        $this->form->validate_tutor($data);
    }
    
    function test_validate_tutor_under_age_tutor_does_not_exist_throws() {
        $data = [
            'birth_date' => make_birth_date(17),
            'tutor_nif' => 'non_existent'
        ];
        $this->expectException(ABACO_ValidationError::class);
        $this->form->validate_tutor($data);
    }
    
    function test_validate_tutor_under_age_tutor_under_age_throws() {
        $data = [
            'birth_date' => make_birth_date(17),
            'tutor_nif' => 'under_age'
        ];
        $this->expectException(ABACO_ValidationError::class);
        $this->form->validate_tutor($data);
    }
    
    function test_validate_tutor_under_age_booking_day_mismatch_throws() {
        $data = [
            'birth_date' => make_birth_date(17),
            'tutor_nif' => 'under_age',
            'booking_days' => ['THU', 'SAT']
        ];
        $this->expectException(ABACO_ValidationError::class);
        $this->form->validate_tutor($data);
    }
    
    function test_validate_tutor_under_age_ok() {
        $data = [
            'birth_date' => make_birth_date(17),
            'tutor_nif' => 'over_age',
            'booking_days' => ['THU']
        ];
        $res = $this->form->validate_tutor($data);
        $this->assertEquals($data, $res);
    }*/
    
    // insert: better tested in system tests
}