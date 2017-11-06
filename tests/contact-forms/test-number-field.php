<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class NumberFieldTest extends PHPUnit_Framework_TestCase {
 
    /**
     * @dataProvider validation_cases
     * Set $expected to null to indicate we expect validation to fail
     */
    function test_validate($input, $expected) {
        $field = new ABACO_NumberField('name', 'display', true);
        $res = $field->validate($input);
        if (is_null($expected)) {
            $this->assertInstanceof(Exception::class, $res);
        } else {
            $this->assertEquals($expected, $res);
        }
    }
    
    function validation_cases() {
        return [
            [[], null],
            ['5908invalid', null],
            ['0', 0],
            ['89', 89]
        ];
    }

}