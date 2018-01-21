<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ABACO_PreinscriptionDbTable {
    // Singleton management
    private static $m_instance;
    public static function get_instance() {
        if (!self::$m_instance) {
            global $wpdb;
            self::$m_instance = new self($wpdb);
        }
        return self::$m_instance;
    }
    
    private $m_db;
    
    public function __construct($db) {
        $this->m_db = $db;
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