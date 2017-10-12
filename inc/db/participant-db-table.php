<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ABACO_ParticipantDbTable {
    // Singleton management
    private static $m_instance;
    public static function get_instance() {
        if (!self::$m_instance) {
            self::$m_instance = new self();
        }
        return self::$m_instance;
    }
    
    // Table name
    public function name() {
        global $wpdb;
        return $wpdb->prefix . ABACO_PARTICIPANT_TABLE_NAME;
    }
    
    // DB table create / drop
    public function create() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = $this->create_sql();
        dbDelta($sql);
    }
    public function drop() {
        global $wpdb;
        $table_name = $this->name();
        $sql = "DROP TABLE $table_name IF EXISTS;";
        $wpdb->query($sql);
    }
    
    // Query functions
    public function query_all($fields) { // fields must be string
        global $wpdb;
        $table = $this->name();
        $sql = "SELECT $fields FROM $table;";
        $res = $wpdb->get_results($sql, ARRAY_A);
        if (!isset($res)) {
            wp_die('Database error');
        }
        for ($i = 0; $i != count($res); ++$i) {
            $res[$i] = $this->parse($res[$i]);
        }
        return $res;
    }
    public function query_by_id($id_name, $id_value, $fields = '*') {
        global $wpdb;
        $table = $this->name();
        $sql = $wpdb->prepare("SELECT $fields FROM $table WHERE $id_name = %s",
            $id_value);
        $res = $wpdb->get_row($sql, OBJECT);
        return $this->parse($res);
    }
    public function is_nif_available($nif) {
        global $wpdb;
        $table = $this->name();
        $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE nif = %s", $nif);
        $res = $wpdb->get_var($sql);
        if (!isset($res)) {
            wp_die('Database error');
        }
        return $res === '0';
    }
    public function nif_to_id($nif) {
        global $wpdb;
        $table = $this->name();
        $sql = $wpdb->prepare("SELECT id FROM $table WHERE nif = %s", $nif);
        $res = $wpdb->get_var($sql);
        if ($res === null) {
            return null;
        } else {
            return intval($res);
        }
    }
    
    // Insert functions
    public function insert($data) {
        global $wpdb;
        $data['booking_days'] = serialize($data['booking_days']);
        if (!$wpdb->insert($this->name(), $data)) {
            wp_die("Database insert error");
        }
    }
    
    // Parse functions
    private $m_parser;
    protected function parse($record) {
        if (!$this->m_parser) {
            require_once __DIR__ . '/parser.php';
            $this->m_parser = new ABACO_Parser([
                'id' => 'intval',
                'booking_days' => 'abaco_parse_array',
                'yes_info' => 'abaco_parse_bool'
            ]);
        }
        return $this->m_parser->parse($record);
    }
    
    // Helpers
    private function create_sql() {
        global $wpdb;
        $table_name = $this->name();
        $document_type_options = self::option_list(abaco_document_type_options());
        $gender_options = self::option_list(abaco_gender_options());
        $province_options = self::option_list(abaco_province_options());
        $booking_days_default = serialize([]);
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE $table_name (
            `id` INT NOT NULL AUTO_INCREMENT,
            `nif` varchar(20) NOT NULL,
            `document_type` ENUM ($document_type_options) NOT NULL,
            `first_name` varchar(50) NOT NULL,
            `last_name` varchar(100) DEFAULT '',
            `alias` varchar(50) DEFAULT '',
            `birth_date` date DEFAULT NULL,
            `phone` varchar(50) DEFAULT '',
            `email` varchar(50) NOT NULL,
            `gender` ENUM ($gender_options) NOT NULL,
            `group` varchar(50) DEFAULT '',
            `province` ENUM ($province_options) NOT NULL,
            `city` varchar(25) NOT NULL,
            `observations` varchar(140),
            `booking_days` varchar(100) DEFAULT '$booking_days_default',
            `tutor_nif` varchar(20) DEFAULT NULL,
            `inscription_day` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `yes_info` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY (`nif`)
        ) $charset_collate;";
    }
    
    private static function option_list($options) {
        return implode(', ', array_map(function($opt) {
            return "'" . $opt . "'";
        }, array_keys($options)));
    }
}