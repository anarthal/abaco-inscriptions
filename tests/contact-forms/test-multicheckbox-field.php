<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class MulticheckboxFieldTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider validate_cases
     */
    function test_validate($input, $expected, $mandatory = false) {
        $field = new ABACO_MulticheckboxField('name', 'display', $mandatory,
            ['opt1', 'opt2']);
        if (is_null($expected)) {
            $this->expectException(ABACO_ValidationError::class);
            $field->validate($input);
        } else {
            $res = $field->validate($input);
            $this->assertEquals($expected, $res);
        }
    }
    
    function validate_cases() {
        return [
            [[''], []],
            [['opt1'], ['opt1']],
            [['opt1', 'opt2'], ['opt1', 'opt2']],
            [['opt2'], ['opt2']],
            [['opt1', 'invalid'], ['opt1']],
            [['invalid', 'opt1'], ['opt1']],
            [['invalid', 'opt2'], ['opt2']],
            [['opt1'], ['opt1'], true],
            [[''], null, true],
            [['invalid'], null, true]
        ];
    }
    
    /**
     * @dataProvider code_cases
     */
    function test_code($display, $mandatory, $opts, $expected) {
        $field = new ABACO_MulticheckboxField('name', $display, $mandatory,
            ['opt1', 'opt2'], $opts);
        $this->assertEquals($expected, $field->code());
    }
    
    function code_cases() {
        return [
            ['display', false, [], '<p>display<br />[checkbox name]</p>'],
            ['display', true, ['asterisk' => false],
                '<p>display<br />[checkbox* name]</p>'],
            ['display', false, ['asterisk' => true],
                '<p>display ' . ABACO_FieldParams::label_asterisk_html() . 
                '<br />[checkbox name]</p>'],
            ['display', false, ['cf7_options' => 'opts'],
                '<p>display<br />[checkbox name opts]</p>'],
            ['display', false, ['element_id' => 'test'],
                '<p id="test">display<br />[checkbox name]</p>'],
            ['<script>display', false, [],
                '<p>&lt;script&gt;display<br />[checkbox name]</p>'],
            ['[display]', false, [], '<p>display<br />[checkbox name]</p>'],
        ];
    }
}