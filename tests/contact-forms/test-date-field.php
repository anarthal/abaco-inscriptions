<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class DateFieldTest extends PHPUnit_Framework_TestCase {
    function test_validate_valid_returns_date() {
        $field = new ABACO_DateField('name', 'disp', true);
        $res = $field->validate('1990-10-20');
        $this->assertEquals(new DateTime('1990-10-20'), $res);
    }
    
    /**
     * @dataProvider invalid_cases
     */
    function test_validate_invalid_throws_validation_error($value) {
        $field = new ABACO_DateField('name', 'disp', true);
        $this->expectException(ABACO_ValidationError::class);
        $field->validate($value);
    }
    
    function invalid_cases() {
        return [
            [''],
            ['jhbdasjbk']
        ];
    }
}