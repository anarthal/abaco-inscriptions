<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class TextareaFieldTest extends WP_UnitTestCase {
    /**
     * @dataProvider data_provider
     */
    function test_trim($field, $input, $expected) {
        $res = unprotect($field)->m_trim($input);
        $this->assertEquals($expected, $res);
    }
    
    function data_provider() {
        return [
            [new ABACO_TextareaField('name', 'display', true), ' test ', 'test'],
            [new ABACO_TextareaField('name', 'display', true),
                '<script>a</script>content', 'content'],
            [new ABACO_TextareaField('name', 'display', true, false),
                'test[gallery]', 'test[gallery]'],
            [new ABACO_TextareaField('name', 'display', true, true),
                'test[gallery]', 'test']
        ];
    }
}