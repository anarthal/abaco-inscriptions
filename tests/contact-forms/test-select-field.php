<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class SelectFieldTest extends WP_UnitTestCase {
    function test_validate_valid_option_returns_same() {
        $field = new ABACO_SelectField('name', 'display', ['opt1', 'opt2']);
        $res = $field->validate('opt1');
        $this->assertEquals('opt1', $res);
    }
    function test_validate_invalid_option_returns_exception() {
        $field = new ABACO_SelectField('name', 'display', ['opt1', 'opt2']);
        $res = $field->validate('other');
        $this->assertInstanceof(Exception::class, $res);
    }
}