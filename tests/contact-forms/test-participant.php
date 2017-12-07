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
    }
    
    // validate_document_type
    function test_validate_document_type_uuid_under_nif_age_ok() {
        $data = [
            'document_type' => 'UUID',
            'birth_date' => make_birth_date(13)
        ];
        $res = $this->form->validate_document_type($data);
        $this->assertEquals($data, $res);
    }
    
    function test_validate_document_type_uuid_over_nif_age_throws() {
        $data = [
            'document_type' => 'UUID',
            'birth_date' => make_birth_date(14)
        ];
        $this->expectException(ABACO_ValidationError::class);
        $this->form->validate_document_type($data);
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
    }
}