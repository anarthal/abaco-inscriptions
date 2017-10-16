<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class MulticheckboxFieldTest extends WP_UnitTestCase {

    /**
     * @dataProvider validate_cases
     */
    function test_validate($input, $expected, $mandatory = false) {
        $field = new ABACO_MulticheckboxField('name', 'display', $mandatory,
            ['opt1', 'opt2']);
        $res = $field->validate($input);
        if (is_null($expected)) {
            $this->assertInstanceof(Exception::class, $res);
        } else {
            $this->assertEquals($expected, $res);
        }
    }
    
    function validate_cases() {
        return [
            [[''], []],
            [['opt1'], ['opt1']],
            [['opt1', 'opt2'], ['opt1', 'opt2']],
            [['opt1', 'invalid'], ['opt1']],
            [['opt1'], ['opt1'], true],
            [[''], null, true],
            [['invalid'], null, true]
        ];
    }
}