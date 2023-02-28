<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/parser.php';

class ABACO_ParticipantParser extends ABACO_Parser {
    public function __construct() {
        parent::__construct([
            'id' => 'intval',
            'booking_days' => function($value) {
                $res = @unserialize($value);
                return is_array($res) ? $res : [];
            },
            'yes_info' => 'abaco_parse_bool',
            'birth_date' => function($value) {
                $res = date_create($value);
                return $res ? $res : null;
            },
            'contact_participant_id' => function($value) {
                return is_numeric($value) ? intval($value) : null;
            }
        ]);
    }
}

class ABACO_ParticipantDbTable {
    // Singleton management
    private static $m_instance;
    public static function get_instance() {
        if (!self::$m_instance) {
            global $wpdb;
            $parser = new ABACO_ParticipantParser();
            self::$m_instance = new self($wpdb, $parser);
        }
        return self::$m_instance;
    }
    
    private $m_db;
    private $m_parser;
    
    public function __construct($db, $parser) {
        $this->m_db = $db;
        $this->m_parser = $parser;
    }
    
    // Table name
    public function name() {
        return $this->m_db->prefix . ABACO_PARTICIPANT_TABLE_NAME;
    }
    
    // DB table create / drop
    public function create() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = $this->create_sql();
        dbDelta($sql);
    }
    public function drop() {
        $table_name = $this->name();
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $this->m_db->query($sql);
    }
    
    // Query functions
    public function query_all($fields = null, $where = '1=1') { // null => all fields
        $fields_query = self::make_fields_query($fields);
        $table = $this->name();
        $sql = "SELECT $fields_query FROM $table WHERE $where;";
        $res = $this->m_db->get_results($sql, ARRAY_A);
        if (!isset($res)) {
            wp_die('Database error');
        }
        return array_map([$this->m_parser, 'parse'], $res);
    }
    
    public function query_by_id($id_name, $id_value, $fields = null) { // null => all
        $fields_query = self::make_fields_query($fields);
        $table = $this->name();
        $sql = $this->m_db->prepare("SELECT $fields_query FROM $table WHERE `$id_name` = %s",
            $id_value);
        $res = $this->m_db->get_row($sql, OBJECT);
        return $this->m_parser->parse($res);
    }
    public function is_nif_available($nif) {
        $table = $this->name();
        $sql = $this->m_db->prepare("SELECT COUNT(*) FROM $table WHERE nif = %s", $nif);
        $res = $this->m_db->get_var($sql);
        if (!isset($res)) {
            wp_die('Database error');
        }
        return $res === '0';
    }
    public function nif_to_id($nif) {
        $table = $this->name();
        $sql = $this->m_db->prepare("SELECT id FROM $table WHERE nif = %s", $nif);
        $res = $this->m_db->get_var($sql);
        if ($res === null) {
            return null;
        } else {
            return intval($res);
        }
    }
    
    // Insert functions
    public function insert($data) {
        $data['booking_days'] = serialize($data['booking_days']);
        if(isset($data['birth_date'])) {
            $data['birth_date'] = $data['birth_date']->format('Y-m-d');
        }
        if (!$this->m_db->insert($this->name(), $data)) {
            wp_die("Database insert error");
        }
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
            `last_name` varchar(100) DEFAULT NULL,
            `alias` varchar(50) DEFAULT NULL,
            `birth_date` date DEFAULT NULL,
            `phone` varchar(50) DEFAULT NULL,
            `email` varchar(50) NOT NULL,
            `gender` ENUM ($gender_options) NOT NULL,
            `group` varchar(50) DEFAULT NULL,
            `province` ENUM ($province_options) NOT NULL,
            `city` varchar(25) NOT NULL,
            `observations` varchar(140) DEFAULT NULL,
            `booking_days` varchar(100) DEFAULT '$booking_days_default',
            `tutor_nif` varchar(20) DEFAULT NULL,
            `inscription_day` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `yes_info` TINYINT(1) DEFAULT 0,
            `contact_participant_id` INT DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY (`nif`)
        ) $charset_collate;";
    }
    
    private static function option_list($options) {
        return implode(', ', array_map(function($opt) {
            return "'" . $opt . "'";
        }, array_keys($options)));
    }
    
    private static function make_fields_query($fields) {
        if ($fields === null) {
            return '*';
        } else {
            return implode(',', array_map(function($value) {
                return '`' . $value . '`';
            }, $fields));
        }
    }
}