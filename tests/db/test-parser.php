<?php
/**
 * Class SampleTest
 *
 * @package Abaco
 */

_abaco_require('inc/db/parser.php');

class ParserTest extends WP_UnitTestCase {
    function setUp() {
        $this->functors = [
            'f1' => 'intval',
            'f2' => function($elm) { return $elm + 3; }
        ];
        $this->parser = new ABACO_Parser($this->functors);
    }
    
    private function array_to_object($record) {
        $res = new stdClass();
        foreach ($record as $key => $value) {
            $res->$key = $value;
        }
        return $res;
    }

    /**
     * @dataProvider record_provider
     */
    function test_applies_functors_on_fields_array($record, $expected) {
        $res = $this->parser->parse($record);
        $this->assertEquals($res, $expected);
    }
    
    /**
     * @dataProvider record_provider
     */
    function test_applies_functors_on_fields_object($record, $expected) {
        $res = $this->parser->parse($this->array_to_object($record));
        $this->assertEquals($res, $this->array_to_object($expected));
    }
    
    function record_provider() {
        return [
            [
                ['f1' => '8',
                 'f2' => 10],
                ['f1' => 8,
                 'f2' => 13]
            ],
            [
                ['f1' => '8',
                 'f3' => 'hola'],
                ['f1' => 8,
                 'f3' => 'hola'],
            ],
        ];
    }
}
