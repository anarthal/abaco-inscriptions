<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class FieldEscapeTest extends WP_UnitTestCase {
    function test_escape_removes_cf7_control_characters() {
        $unescaped = 'pre[inject]"attack';
        $res = ABACO_Field::escape($unescaped);
        $this->assertFalse(strpos($res, '['));
        $this->assertFalse(strpos($res, ']'));
        $this->assertFalse(strpos($res, '"'));
        $this->assertFalse(count($res) === 0);
    }
}