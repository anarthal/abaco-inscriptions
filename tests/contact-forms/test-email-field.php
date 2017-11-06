<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class EmailFieldTest extends PHPUnit_Framework_TestCase {
    /**
     * @dataProvider data_provider
     */
    function test_trim($input, $expected) {
        $field = new ABACO_EmailField('name', 'display', true);
        $res = unprotect($field)->m_trim($input);
        $this->assertEquals($expected, $res);
    }
    
    function data_provider() {
        return [
            ['  ', ''],
            ['invalid', ''],
            [' test@gmail.com ', 'test@gmail.com']
        ];
    }
}