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
    
   /**
     * @dataProvider code_cases
     */
    function test_code($display, $opts, $expected) {
        $field = new ABACO_CheckboxField('name', $display, $opts);
        $this->assertEquals($expected, $field->code());
    }
    
    function code_cases() {
        return [
            ['display', [], '<p>[checkbox name "display"]</p>'],
            ['display', ['cf7_options' => 'opts'],
                '<p>[checkbox name opts "display"]</p>'],
            ['display', ['element_id' => 'test'],
                '<p id="test">[checkbox name "display"]</p>'],
            ['display"inject', [], '<p>[checkbox name "display&quot;inject"]</p>'],
            ['display<p>[]', [], '<p>[checkbox name "display&lt;p&gt;"]</p>']
        ];
    }
}