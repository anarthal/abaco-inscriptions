<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('utility.php');

class IsValidNifTest extends PHPUnit_Framework_TestCase {
    
    /**
     * 
     * @dataProvider valid_cases
     */
    function test_valid($value) {
        $this->assertTrue(abaco_is_valid_nif($value));
    }
    
    /**
     * 
     * @dataProvider invalid_cases
     */
    function test_invalid($value) {
        $this->assertFalse(abaco_is_valid_nif($value));
    }
    
    function valid_cases() {
        return [
            ['12345678A'],
            ['00000000B'],
            ['83741029p'],
            ['A98010872'],
            ['b2342456p']
        ];
    }
    
    function invalid_cases() {
        return [
            ['8892478B'],
            ['832982890B'],
            ['123456789'],
            ['12345678_'],
            ['87139199.'],
            ['87981239?'],
            ['89232341+'],
            ['87193191-'],
            ['A8932849/'],
            ['98324u80A'],
            ['J9138L90T'],
            ['00213189Ñ'],
            ['89230910ñ'],
            ['ñ9320400q']
        ];
    }
}