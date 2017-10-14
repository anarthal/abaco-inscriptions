<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

class FieldParamsTest extends WP_UnitTestCase {
    function test_constructor_options_takes_defaults_if_not_given() {
        $res = new ABACO_FieldParams('name', true, ['cf7_options' => 'test']);
        $this->assertEquals($res->display_name, 'name');
        $this->assertEquals($res->mandatory, true);
        $this->assertEquals($res->cf7_options, 'test');
        $this->assertEquals($res->asterisk, true);
        $this->assertEquals($res->element_id, '');
    }
    function test_constructor_asterisk_default_to_mandatory() {
        $res = new ABACO_FieldParams('name', false, []);
        $this->assertEquals($res->asterisk, false);
    }
    function test_tag_asterisk_returns_asterisk_if_mandatory() {
        $res = new ABACO_FieldParams('name', true, ['asterisk' => false]);
        $this->assertEquals($res->tag_asterisk(), '*');
        $res->asterisk = true;
        $res->mandatory = false;
        $this->assertEquals($res->tag_asterisk(), '');
    }
    function test_label_asterisk_returns_html_if_asterisk() {
        $res = new ABACO_FieldParams('name', true, ['asterisk' => false]);
        $this->assertEquals($res->label_asterisk(), '');
        $res->asterisk = true;
        $this->assertTrue(count($res->label_asterisk()) !== 0);
    }
}