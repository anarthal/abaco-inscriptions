<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

// Mock implementation for ABACO_DataField
class ABACO_MockDataField extends ABACO_DataField {
    protected function m_validate($input) {
        return trim($input);
    }
    public function code() {
        return '';
    }
}

class DataFieldValidateTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->field = new ABACO_MockDataField('myname');
    }
    function test_null_value_calls_m_validate_empty_string() {
        $res = $this->field->validate(null);
        $this->assertEquals($res, '');
    }
    function test_not_null_calls_m_validate() {
        $res = $this->field->validate('  test ');
        $this->assertEquals($res, 'test');
    }
}