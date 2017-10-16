<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class CheckboxFieldTest extends WP_UnitTestCase {

    function test_validate_not_array_returns_exception() {
        $field = new ABACO_CheckboxField('name', 'display');
        $res = $field->validate('invalid');
        $this->assertInstanceof(Exception::class, $res);
    }
    
    /**
     * @dataProvider valid_cases
     */
    function test_validate_array($input, $expected) {
        $field = new ABACO_CheckboxField('name', 'display');
        $res = $field->validate($input);
        $this->assertEquals($expected, $res);
    }
    
    function valid_cases() {
        return [
            [[], false],
            [[''], false],
            [['value'], true],
            [['', 'value'], false],
            [['value', ''], true]
        ];
    }
}