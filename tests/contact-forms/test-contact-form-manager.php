<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/contact-form.php');

class ABACO_MockContactForm extends ABACO_ContactForm {
    public $insert_data;
    public function __construct() {
        parent::__construct([], []);
    }
    public function insert(array $data) {
        $this->insert_data = $data;
    }
}

class ABACO_MockCf7Form {
    public $props;
    public function __construct($title) {
        $this->m_title = $title;
    }
    public function set_properties(array $props) {
        $this->props = $props;
    }
    public function title() {
        return $this->m_title;
    }
}

class ContactFormManagerTest extends PHPUnit_Framework_TestCase {
    function setUp() {
        $this->manager = new ABACO_ContactFormManager();
        $this->manager->add_form('registered',
            function() { return new ABACO_MockContactForm(); });
        $this->manager->add_selects(['myselect' => function() {
            return ['k1' => 'v1', 'k2' => 'v2'];
        }]);
    }
    
    // get_submission
    function test_get_submission_registered_returns_new() {
        $res = $this->manager->get_submission('registered');
        $this->assertInstanceOf(ABACO_Submission::class, $res);
        $this->assertInstanceOf(ABACO_MockContactForm::class, $res->contact_form);
    }
    
    function test_get_submission_cached_returns_existing() {
        $form0 = $this->manager->get_submission('registered');
        $form1 = $this->manager->get_submission('registered');
        $this->assertEquals($form0, $form1);
    }
    
    function test_get_submission_not_registered_returns_null() {
        $form = $this->manager->get_submission('other');
        $this->assertNull($form);
    }
    
    // get_select
    function test_get_select_registered_calls_and_returns() {
        $select = $this->manager->get_select('myselect');
        $expected = ['k1' => 'v1', 'k2' => 'v2'];
        $this->assertEquals($expected, $select);
    }
    
    function test_get_select_not_registered_returns_null() {
        $select = $this->manager->get_select('non_existent');
        $this->assertNull($select);
    }
       
    // select_hook
    function test_select_hook_registered_sets_values_and_labels() {
        $mock_select = ['name' => 'myselect'];
        $res = $this->manager->select_hook($mock_select);
        $this->assertEquals('myselect', $res['name']);
        $this->assertEquals(['k1', 'k2'], $res['values']);
        $this->assertEquals(['v1', 'v2'], $res['labels']);
    }
    
    function test_select_hook_not_registered_does_nothing() {
        $mock_select = ['name' => 'other'];
        $res = $this->manager->select_hook($mock_select);
        $this->assertEquals($mock_select, $res);
    }
    
    // code_hook
    /*function test_form_code_hook_registered_sets_code() {
        $cf7_form = new ABACO_MockCf7Form('registered');
        $this->manager->form_code_hook($cf7_form);
        $this->assertEquals(['form' => 'code'], $cf7_form->props);
    }
    
    function test_form_code_hook_not_registered_does_nothing() {
        $cf7_form = new ABACO_MockCf7Form('other');
        $this->manager->form_code_hook($cf7_form);
        $this->assertNull($cf7_form->props);
    }*/
}