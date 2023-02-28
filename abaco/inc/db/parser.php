<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ABACO_Parser {
    private $m_parsers;
    public function __construct(array $parsers) {
        $this->m_parsers = $parsers;
    }
    public function parse($record) {
        $is_object = is_object($record);
        $is_array = is_array($record);
        foreach ($this->m_parsers as $field => $parser) {
            if ($is_object && isset($record->$field)) {
                $record->$field = call_user_func($parser, $record->$field);
            } elseif ($is_array && isset($record[$field])) {
                $record[$field] = call_user_func($parser, $record[$field]);
            }
        }
        return $record;
    }
}