<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/participant.php');

class ABACO_MockParticipantTable {
    public function is_nif_available($nif) {
        return $nif !== 'existent';
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
            'birth_date' => (new DateTime())->sub(new DateInterval('P13Y'))
        ];
        $res = $this->form->validate_document_type($data);
        $this->assertEquals($data, $res);
    }
    
    function test_validate_document_type_uuid_over_nif_age_throws() {
        $data = [
            'document_type' => 'UUID',
            'birth_date' => (new DateTime())->sub(new DateInterval('P14Y'))
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
}