<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/contact-form.php');

class ABACO_MockCf7Result {
    public $invalidate_tag;
    public $invalidate_msg;
    public function invalidate($tag, $msg) {
        $this->invalidate_tag = $tag;
        $this->invalidate_msg = $msg;
    }
}

class ResultHelperTest extends PHPUnit_Framework_TestCase {
    function setUp() {
        $this->tags = [
            ['name' => 'n1'],
            ['name' => 'n2']
        ];
        $this->result = new ABACO_MockCf7Result();
        $this->helper = new ABACO_ResultHelper($this->result, $this->tags);
    }
    public function test_invalidate_existing_tag_calls_result_invalidate() {
        $this->helper->invalidate('n2', 'msg');
        $this->assertEquals($this->tags[1], $this->result->invalidate_tag);
        $this->assertEquals('msg', $this->result->invalidate_msg);
    }
}