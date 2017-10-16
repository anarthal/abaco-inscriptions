<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class EmailFieldTest extends WP_UnitTestCase {
    function test_whitespace_trim_returns_empty() {
        $field = new ABACO_EmailField('name', 'display', true);
        $res = unprotect($field)->m_trim('   ');
        $this->assertEquals('', $res);
    }
    function test_invalid_trim_returns_empty() {
        $field = new ABACO_EmailField('name', 'display', true);
        $res = unprotect($field)->m_trim('invalid');
        $this->assertEquals($res, '');
    }
}