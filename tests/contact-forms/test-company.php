<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/company.php');
_abaco_require('inc/db/participant-db-table.php');

class CompanyTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->table = $this->createMock(ABACO_ParticipantDbTable::class);
        $this->table->method('is_nif_available')
                    ->will($this->returnCallback(function($value) {
                        return $value !== 'existent';
                    }));
        $this->form = new ABACO_CompanyForm($this->table);
        $this->sub = new ABACO_Submission($this->form);
        $this->result = $this->getMockBuilder(ABACO_InvalidatableResult::class)
                             ->setMethods(['invalidate'])
                             ->getMock();
        $this->input = [
            'first_name' => 'my_first_name',
            'nif' => '123',
            'phone' => '670',
            'email' => 'test@test.com',
            'province' => 'NAVARRA',
            'city' => 'Pamplona',
            'observations' => 'myobs',
            'yes_info' => ['yes_info']
        ];
    }
    
    function do_test_invalid($field_name) {
        $this->result->expects($this->once())
                     ->method('invalidate')
                     ->with($this->equalTo($field_name), $this->anything());
        $this->sub->setup_data($this->input, $this->result);
    }
    
    // Invalid first_name
    function test_missing_first_name_invalid() {
        $this->input['first_name'] = '';
        $this->do_test_invalid('first_name');
    }
    
    // Invalid NIF
    function test_missing_nif_invalid() {
        $this->input['nif'] = '';
        $this->do_test_invalid('nif');
    }
    
    function test_existent_nif_invalid() {
        $this->input['nif'] = 'existent';
        $this->do_test_invalid('nif');
    }
    
    // Invalid phone
    function test_missing_phone_invalid() {
        $this->input['phone'] = '';
        $this->do_test_invalid('phone');
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
    
    function test_trivial_valid() {
        $this->sub->setup_data($this->input, $this->result);
        $expected = [
            'first_name' => 'my_first_name',
            'nif' => '123',
            'phone' => '670',
            'email' => 'test@test.com',
            'province' => 'NAVARRA',
            'city' => 'pamplona',
            'observations' => 'myobs',
            'yes_info' => true
        ];
        $this->assertEquals($expected, $this->sub->data());
    }
    
    function test_optional_fields_missing_valid() {
        $data = [
            'first_name' => 'my_first_name',
            'nif' => '123',
            'phone' => '670',
            'email' => 'test@test.com',
            'province' => 'NAVARRA',
            'city' => 'Pamplona',
            'observations' => '',
            'yes_info' => []
        ];
        $expected = [
            'first_name' => 'my_first_name',
            'nif' => '123',
            'phone' => '670',
            'email' => 'test@test.com',
            'province' => 'NAVARRA',
            'city' => 'pamplona',
            'observations' => '',
            'yes_info' => false
        ];
        $this->sub->setup_data($data, $this->result);
        $this->assertEquals($expected, $this->sub->data());
    }
}