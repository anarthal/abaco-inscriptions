<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/preinscription.php');
//_abaco_require('inc/db/preinscription-db-table.php');

class IsSlotTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->act = (object)[
            'participants_male' => 3,
            'participants_female' => 4,
            'participants_total' => 10
        ];
        $this->slots = new stdClass();
    }
    
    function do_test($gender, $expected) {
        $res = ABACO_SlotControl::is_slot($this->act, $this->slots, $gender);
        $this->assertEquals($expected, $res);
    }
    
    // Test for males
    public function test_male_everything_free_true() {
        $this->slots->MALE = 1;
        $this->slots->FEMALE = 1;
        $this->slots->NONBINARY = 1;
        $this->do_test('MALE', true);
    }
    
    public function test_male_only_male_free_true() {
        $this->slots->MALE = 2;
        $this->slots->FEMALE = 6;
        $this->slots->NONBINARY = 1;
        $this->do_test('MALE', true);
    }
    
    public function test_male_indiff_free_true() {
        $this->slots->MALE = 3;
        $this->slots->FEMALE = 5;
        $this->slots->NONBINARY = 1;
        $this->do_test('MALE', true);
    }
    
    public function test_male_no_slots_false() {
        $this->slots->MALE = 4;
        $this->slots->FEMALE = 5;
        $this->slots->NONBINARY = 1;
        $this->do_test('MALE', false);
    }
    
    public function test_male_no_male_or_indiff_slots_false() {
        $this->slots->MALE = 5;
        $this->slots->FEMALE = 1; // only female slots
        $this->slots->NONBINARY = 1;
        $this->do_test('MALE', false);
    }
    
    // Tests for female
    public function test_female_everything_free_true() {
        $this->slots->MALE = 1;
        $this->slots->FEMALE = 1;
        $this->slots->NONBINARY = 1;
        $this->do_test('FEMALE', true);
    }
    
    public function test_female_only_female_free_true() {
        $this->slots->MALE = 5;
        $this->slots->FEMALE = 3;
        $this->slots->NONBINARY = 1;
        $this->do_test('FEMALE', true);
    }
    
    public function test_female_indiff_free_true() {
        $this->slots->MALE = 1;
        $this->slots->FEMALE = 5;
        $this->slots->NONBINARY = 1;
        $this->do_test('FEMALE', true);
    }
    
    public function test_female_no_slots_false() {
        $this->slots->MALE = 4;
        $this->slots->FEMALE = 5;
        $this->slots->NONBINARY = 1;
        $this->do_test('FEMALE', false);
    }
    
    public function test_female_no_female_or_indiff_slots_false() {
        $this->slots->MALE = 1; // only male slots
        $this->slots->FEMALE = 6;
        $this->slots->NONBINARY = 1;
        $this->do_test('FEMALE', false);
    }
    
    // Tests for nonbinary
    public function test_nonbinary_everything_free_true() {
        $this->slots->MALE = 1;
        $this->slots->FEMALE = 1;
        $this->slots->NONBINARY = 1;
        $this->do_test('NONBINARY', true);
    }
    
    public function test_nonbinary_no_indiff_slots_false() {
        $this->slots->MALE = 1; // only male slots
        $this->slots->FEMALE = 1;
        $this->slots->NONBINARY = 3;
        $this->do_test('NONBINARY', false);
    }
}