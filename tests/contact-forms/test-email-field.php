<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class EmailFieldTest extends WP_UnitTestCase {
    function test_empty_mandatory_validation_fails() {
        $field = new ABACO_EmailField('name', 'display', true);
        $res = $field->validate('   ');
        $this->assertInstanceOf(Exception::class, $res);
    }
    function test_invalid_mandatory_validation_fails() {
        $field = new ABACO_EmailField('name', 'display', true);
        $res = $field->validate('invalid');
        $this->assertInstanceOf(Exception::class, $res);
    }
}