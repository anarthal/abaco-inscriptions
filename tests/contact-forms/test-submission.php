<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');
_abaco_require('inc/contact-forms/contact-form.php');

class ABACO_SubmissionMockContactForm extends ABACO_ContactForm {
    public $inserted_data;
    public function __construct() {
        $fields = [
            new ABACO_TextField('myfield', 'display', true),
            new ABACO_EchoField('echo', 'hola')
        ];
        $validators = [];
        parent::__construct($fields, $validators);
    }
    public function insert(array $data) {
        $this->inserted_data = $data;
    }
}

class ABACO_MockInvalidable {
    public $name, $msg;
    public function invalidate($name, $message) {
        $this->name = $name;
        $this->msg = $message;
    }
}

class SubmissionTest extends PHPUnit_Framework_TestCase {
    function setUp() {
        $this->form = new ABACO_SubmissionMockContactForm();
        $this->sub = new ABACO_Submission($this->form);
        $this->inv = new ABACO_MockInvalidable();
    }
    
    // code
    function test_code_joins_field_codes() {
        $res = $this->sub->code();
        $expected = $this->form->fields[0]->code() . '<br /><br />' .
            $this->form->fields[1]->code();
        $this->assertEquals($expected, $res);
    }
    
    // setup_data
    function test_setup_data_processes_data_and_sets_it() {
        $data = ['myfield' => '  value  '];
        $this->sub->setup_data($data, null); // nothing should be invalidated
        $this->assertEquals(['myfield' => 'value'], $this->sub->data());
    }
    
    function test_setup_data_unknown_values_eliminates_them() {
        $data = [
            'myfield' => '  value  ',
            'othefield' => 'othervalue'
        ];
        $this->sub->setup_data($data, null); // nothing should be invalidated
        $this->assertEquals(['myfield' => 'value'], $this->sub->data());
    }
    
    function test_setup_data_basic_validation_failed_invalidates() {
        $data = ['myfield' => '    '];
        $this->sub->setup_data($data, $this->inv);
        $this->assertEquals('myfield', $this->inv->name);
        $this->assertNotEmpty($this->inv->msg); // something like 'field mandatory'
    }
    
    function test_setup_data_basic_validation_failed_custom_validation_not_triggered() {
        $triggered = false;
        $this->form->custom_validators = [
            'myfield' => function($data) use (&$triggered) { $triggered = true; }
        ];
        $data = ['myfield' => '  '];
        $this->sub->setup_data($data, $this->inv);
        $this->assertFalse($triggered);
    }
    
    function test_setup_data_advanced_validation_failed_invalidates() {
        $this->form->custom_validators = [
            'myfield' => function($data) { throw new ABACO_ValidationError('a'); }
        ];
        $data = ['myfield' => 'value'];
        $this->sub->setup_data($data, $this->inv);
        $this->assertEquals('myfield', $this->inv->name);
        $this->assertEquals('a', $this->inv->msg);
    }
    
    function test_setup_data_advanced_validation_can_modify_data() {
        $this->form->custom_validators = [
            'myfield' => function($data) { $data['myfield'] = 'a'; return $data; }
        ];
        $data = ['myfield' => 'value'];
        $this->sub->setup_data($data, null);
        $this->assertEquals(['myfield' => 'a'], $this->sub->data());
    }
    
    // insert
    function test_insert_delegates() {
        $data = ['myfield' => 'value'];
        $this->sub->setup_data($data, null);
        $this->sub->insert();
        $this->assertEquals($data, $this->form->inserted_data);
    }
}