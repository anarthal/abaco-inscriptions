<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

// Mock class for StringField
class ABACO_MockStringField extends ABACO_StringField {
    protected function m_trim($input) {
        return trim($input);
    }
    protected function tag_type() {
        return 'tag_test';
    }
}

class StringFieldValidateTest extends PHPUnit_Framework_TestCase {
    function test_no_string_validate_throws_validation_error() {
        $field = new ABACO_MockStringField('name', 'display', true);
        $this->expectException(ABACO_ValidationError::class);
        $field->validate(['hola', 'mundo']);
    }
    function test_mandatory_mtrim_result_empty_validation_fails() {
        $field = new ABACO_MockStringField('name', 'display', true);
        $this->expectException(ABACO_ValidationError::class);
        $res = $field->validate('     ');
    }
    function test_mandatory_mtrim_result_not_empty_returns_result() {
        $field = new ABACO_MockStringField('name', 'display', true);
        $res = $field->validate(' content');
        $this->assertEquals('content', $res);
    }
    function test_not_mandatory_mtrim_result_empty_returns_result() {
        $field = new ABACO_MockStringField('name', 'display', false);
        $res = $field->validate('     ');
        $this->assertEquals('', $res);
    }
}
