<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class ABACO_AdminView {
    public final function draw() {
        echo $this->code();
    }
    public abstract function code();
    public static function wrap($contents) {
        return '<div class="wrap">' . $contents . '</div>';
    }
}

class ABACO_AdminButtonLink extends ABACO_AdminView {
    private $m_url;
    private $m_text;
    public function __construct($url, $text) {
        $this->m_url = $url;
        $this->m_text = $text;
    }
    public function code() {
        return
        '<a href="' . esc_url($this->m_url) . '" class="button button-primary">' .
            esc_html($this->m_text) .
        '</a>';
    }
}

abstract class ABACO_AdminTable extends ABACO_AdminView {  
    public final function code() {
        return '<table class="widefat fixed">' .
            self::format_headers($this->headers()) .
            '<tbody>' .
            self::format_rows($this->rows()) .
            '</tbody></table>'; 
    }
    
    public abstract function headers();
    public abstract function rows();
    
    protected static function format_headers($headers) {
        if (empty($headers)) {
            return '';
        }
        $res = '<thead><tr>';
        foreach ($headers as $header) {
            $res .= '<th>' . self::format_cell($header) . '</th>';
        }
        $res .= '</tr></thead>';
        return $res;
    }
    
    protected static function format_rows($rows) {
        $res = '';
        $alternate = false;
        foreach ($rows as $row) {
            $alternate = !$alternate;
            $res .= '<tr ' . ($alternate ? 'class="alternate"' : '') . '>';
            foreach ($row as $field) {
                $res .= '<td>' . self::format_cell($field) . '</td>';
            }
            $res .= '</tr>';
        }
        return $res;
    }
    
    private static function format_cell($value) {
        if ($value instanceof ABACO_AdminView) {
            return $value->code();
        } else {
            return esc_html($value);
        }
    }
}

class ABACO_AdminKeyValueTable extends ABACO_AdminTable {
    private $m_data;
    // $data should be numeric array of 2 position numeric array
    public function __construct(array $data) {
        $this->m_data = $data;
    }
    public function headers() {
        return [];
    }
    public function rows() {
        return $this->m_data;
    }
}

class ABACO_AdminRowTable extends ABACO_AdminTable {
    private $m_headers;
    private $m_data;
    /*
     * $headers should be array<string, string> (key, display_name)
     * $data should be numeric array of array<string -> value>
     * $headers' keys should match the items' keys for $data
     */
    public function __construct(array $headers, array $data) {
        $this->m_headers = $headers;
        $this->m_data = $data;
    }
    public function headers() {
        return array_values($this->m_headers);
    }
    public function rows() {
        $res = [];
        $keys = array_keys($this->m_headers);
        foreach ($this->m_data as $row) {
            $processed_row = [];
            foreach ($keys as $header) {
                $processed_row[] = $row[$header];
            }
            $res[] = $processed_row;
        }
        return $res;
    }
}


class ABACO_AdminLink extends ABACO_AdminView {
    private $m_url;
    private $m_text;
    public function __construct($url, $text) {
        $this->m_url = $url;
        $this->m_text = $text;
    }
    public function code() {
        return
        '<a href="' . esc_url($this->m_url) . '">' .
            esc_html($this->m_text) .
        '</a>';
    }
}