<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/contact-forms/field.php');

// Mock field for LabelField
class ABACO_MockLabelField extends ABACO_LabelField {
    public function tag_type() {
        return 'test_tag';
    }
    public function m_validate($input) {
        return $input;
    }
}

class LabelFieldCodeTest extends WP_UnitTestCase {
    function match($code) {
        $pat = '@<label>(.*)<br />\[([a-zA-Z0-9_]+)(\*)? ([a-zA-Z0-9_]+) ?(.*)?\]</label>@';
        $matches = [];
        preg_match($pat, $code, $matches);
        if (empty($matches)) {
            return false;
        }
        $res = new stdClass();
        $fields = ['display_name', 'tag', 'tag_asterisk', 'name', 'options'];
        for ($i = 0; $i != count($fields); ++$i) {
            $field = $fields[$i];
            $res->$field = $matches[$i+1];
        }
        return $res;
    }
    
    /**
     * @dataProvider data_provider
     */
    public function test_generates_cf7_code($field, $expected) {
        $res = $this->match($field->code());
        $this->assertEquals($res->display_name, $expected[0]);
        $this->assertEquals($res->tag, $expected[1]);
        $this->assertEquals($res->tag_asterisk, $expected[2]);
        $this->assertEquals($res->name, $expected[3]);
        $this->assertEquals($res->options, $expected[4]);
    }
    
    public function data_provider() {
        /*return [
            [new ABACO_MockLabelField('name', 'DÍSPLAY name', false), ['DÍSPLAY name', 'test_tag', '', 'name', '']]
        ];*/
        return [
            [
                new ABACO_MockLabelField('name', 'DÍSPLAY name', false),
                ['DÍSPLAY name', 'test_tag', '', 'name', '']
            ],
            [
                new ABACO_MockLabelField('name', 'display', true, ['asterisk' => false]),
                ['display', 'test_tag', '*', 'name', '']
            ],
            [
                new ABACO_MockLabelField('name', 'display', false, ['asterisk' => true]),
                ['display ' . ABACO_FieldParams::label_asterisk_html(),
                    'test_tag', '', 'name', '']
            ],
            [
                new ABACO_MockLabelField('name', 'display', false, ['cf7_options' => 'a:b c:d e']),
                ['display', 'test_tag', '', 'name', 'a:b c:d e']
            ],
            [
                new ABACO_MockLabelField('name', '<script>inject[code]</script>', false),
                [esc_html('<script>injectcode</script>'), 'test_tag', '', 'name', '']
            ]
        ];
    }
 
}