<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/parser.php';

class ABACO_PreinscriptionParser extends ABACO_Parser {
    public function __construct() {
        parent::__construct([
            'id' => 'intval',
            'activity_id' => 'intval',
            'participant_id' => 'intval'
        ]);
    }
}

class ABACO_PreinscriptionDbTable {
    // Singleton management
    private static $m_instance;
    public static function get_instance() {
        if (!self::$m_instance) {
            global $wpdb;
            self::$m_instance = new self($wpdb, new ABACO_PreinscriptionParser());
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
        return $this->m_db->prefix . ABACO_PREINSCRIPTION_TABLE_NAME;
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
    
    // Query
    public function query_all() {
        $table_name = $this->name();
        $sql = "SELECT * FROM $table_name";
        $res = $this->m_db->get_results($sql, ARRAY_A);
        if ($res === null) {
            wp_die('Database error');
        }
        return array_map([$this->m_parser, 'parse'], $res);
    }
    
    public function query_all_readable() {
        $pre_table = $this->name();
        $part_table = $this->m_db->prefix . ABACO_PARTICIPANT_TABLE_NAME;
        $posts_table = $this->m_db->prefix . 'posts';
        $sql = "SELECT part.id AS participant_id,
                part.first_name AS first_name,
                part.last_name AS last_name,
                part.nif AS nif,
                act.ID AS activity_id,
                act.post_title AS activity_name,
                pre.inscription_day AS inscription_day,
                pre.observations AS observations
                FROM $pre_table pre
                INNER JOIN $part_table part ON part.id = pre.participant_id
                INNER JOIN $posts_table act ON act.ID = pre.activity_id
                ORDER BY pre.inscription_day;";
        $res = $this->m_db->get_results($sql, ARRAY_A);
        if ($res === null) {
            wp_die('Database error');
        }
        return $res;
    }
    
    public function query_participants($act_id) {
        $table = $this->name();
        $part_table = $this->m_db->prefix . ABACO_PARTICIPANT_TABLE_NAME;
        $sql = $this->m_db->prepare(
                "SELECT part.first_name AS first_name, part.last_name as last_name,
                    part.id AS id
                 FROM $table pre
                 INNER JOIN $part_table part ON pre.participant_id = part.id
                 WHERE pre.activity_id = %d", $act_id);
        $res = $this->m_db->get_results($sql, ARRAY_A);
        if ($res === null) {
            wp_die('Database error');
        }
        return $res;
    }
    
    public function query_slots($act_id) {
        $table = $this->name();
        $part_table = $this->m_db->prefix . ABACO_PARTICIPANT_TABLE_NAME;
        $sql = $this->m_db->prepare(
                "SELECT part.gender AS gender, COUNT(*) AS `count`
                 FROM $table pre
                 INNER JOIN $part_table part ON pre.participant_id = part.id
                 WHERE pre.activity_id = %d
                 GROUP BY part.gender", $act_id);
        $counts = $this->m_db->get_results($sql, OBJECT);
        if ($counts === null) {
            wp_die('Database error');
        }
        $res = new stdClass();
        foreach ($counts as $count) {
            $gender = $count->gender;
            $res->$gender = intval($count->count);
        }
        foreach (array_keys(abaco_gender_options()) as $gender) {
            if (!isset($res->$gender)) {
                $res->$gender = 0;
            }
        }
        return $res;
    }
    
    public function is_already_inscribed($part_id, $act_id) {
        $table = $this->name();
        $sql = $this->m_db->prepare("SELECT COUNT(*) FROM $table WHERE 
            participant_id=%d AND activity_id=%d", $part_id, $act_id);
        $res = $this->m_db->get_var($sql);
        if ($res === null) {
            wp_die('Database error');
        }
        return $res !== '0';
    }
    
    // Insert
    public function insert(array $data) {
        if (!$this->m_db->insert($this->name(), $data)) {
            wp_die("Database insert error");
        }
    }
    
    // Helpers
    protected function create_sql() {
        $part_table = $this->m_db->prefix . ABACO_PARTICIPANT_TABLE_NAME;
        $post_table = $this->m_db->prefix . 'posts';
        $table_name = $this->name();
        $charset_collate = $this->m_db->get_charset_collate();
        return "CREATE TABLE $table_name (
            `id` INT NOT NULL AUTO_INCREMENT,
            `participant_id` INT NOT NULL,
            `activity_id` BIGINT UNSIGNED NOT NULL,
            `observations` VARCHAR(140) DEFAULT NULL,
            `inscription_day` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`participant_id`) REFERENCES $part_table(id),
            FOREIGN KEY (`activity_id`) REFERENCES $post_table(ID)
        ) $charset_collate;";
    }
    
}