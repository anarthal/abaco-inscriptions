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
        if ($input === 'throw') {
            throw new InvalidArgumentException();
        } else {
            return trim($input);
        }
    }
    public function code() {
        return '';
    }
}

class DataFieldValidateTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->field = new ABACO_MockDataField('myname');
    }
    function test_invalid_value_returns_exception() {
        $res = $this->field->validate('throw');
        $this->assertInstanceOf(InvalidArgumentException::class, $res);
    }
    function test_null_value_returns_empty_string() {
        $res = $this->field->validate(null);
        $this->assertEquals($res, '');
    }
    function test_valid_returns_m_validate_value() {
        $res = $this->field->validate('  test ');
        $this->assertEquals($res, 'test');
    }
}