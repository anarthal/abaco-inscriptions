<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ABACO_ActivityDbTable {
    
    // Singleton management
    private static $m_instance;
    public static function get_instance() {
        if (self::$m_instance === null) {
            self::$m_instance = new self();
        }
        return self::$m_instance;
    }

    // Warning: only metadata fields may be queried using this function
    public function query_by_id($id, $fields) {
        $meta = get_post_meta($id);
        $res = new stdClass();
        try {
            foreach ($fields as $field) {
                if (!isset($meta[$field])) {
                    throw new Exception('Missing activity field');
                }
                $res->$field = $meta[$field][0];
            }
            return $this->parse($res);
        } catch (Exception $ex) {
            return null;
        }
    }

    public function get_image_url($id) {
        $thumb_id_str = get_post_thumbnail_id($id);
        if ($thumb_id_str === '') {
            return null;
        }
        $thumbnail_id = intval($thumb_id_str);
        $img = wp_get_attachment_url($thumbnail_id);
        if ($img === false) {
            return null;
        }
        return $img;
    }

    // Gets all fields for all published activities; normally, for export action
    public function query_all() {
        $metas = $this->get_all_meta_data();
        $activities_bare = $this->get_all_bare();
        $activities = [];
        foreach ($activities_bare as $act) {
            $id = intval($act['id']);
            $act['id'] = $id;
            $act['img'] = $this->get_image_url($id);
            $activities[$id] = $act;
        }
        $res = array_values(self::merge_metadata($activities, $metas));
        return array_map(function($act) {
            return $this->parse($act);
        }, $res);
    }
    
    public function query_preinscription_activities() {
        global $wpdb;
        $post_type = ABACO_ACTIVITY_POST_TYPE_NAME;
        $meta_table = $wpdb->prefix . 'postmeta';
        $post_table = $wpdb->prefix . 'posts';
        $sql = "SELECT posts.ID AS 'id', 
            posts.post_title as 'name_'
            FROM $meta_table meta INNER JOIN $post_table posts ON meta.post_id = posts.ID
            WHERE posts.post_type = '$post_type'
            AND meta.meta_key = 'allows_preinscription'
            AND meta.meta_value = '1'
            AND posts.post_status = 'publish';";
        $res = $wpdb->get_results($sql, ARRAY_A);
        if ($res === null) {
            wp_die('Database error.');
        }
        return $res;
    }

    // Insertion
    public function insert($data) {
        $post_id = wp_insert_post(array(
            'post_title' => $data['name_'], // already sanitized
            'post_content' => $data['description'], // already sanitized
            'post_status' => (ABACO_ACTIVITY_AUTOPUBLISH ? 'publish' : 'pending'),
            'post_type' => ABACO_ACTIVITY_POST_TYPE_NAME,
            'meta_input' => self::generate_insert_meta_data($data)
        ));
        if (is_wp_error($post_id)) {
            wp_die("Error inserting activity");
        }
        try {
            $this->upload_img($post_id, $data['img']);
        } catch (Exception $ex) {
            wp_delete_post($post_id, true);
            wp_die($ex->getMessage());
        }
        return $post_id;
    }
    
    // Drop (for uninstall): this will delete all activities and images
    public function drop() {
        $ids_clause = implode(', ', $this->get_post_ids_to_remove());
        global $wpdb;
        $post_table = $wpdb->prefix . 'posts';
        $meta_table = $wpdb->prefix . 'postmeta';
        $sql = "DELETE posts, meta FROM $post_table posts " .
            "LEFT JOIN $meta_table meta ON (posts.ID = meta.post_id) " .
            "WHERE posts.ID IN ($ids_clause);";
        $wpdb->query($sql);
        wp_cache_flush();
    }
    
    public function remove_upload_dir() {
        WP_Filesystem();
        global $wp_filesystem;
        $wp_filesystem->rmdir(abaco_full_upload_dir(), true);
    }

    // Parsing
    private $m_parser;
    protected function parse($record) {
        if (!$this->m_parser) {
            require_once __DIR__ . '/parser.php';
            $this->m_parser = new ABACO_Parser([
                'duration' => 'intval',
                'participants_total' => 'intval',
                'participants_male' => 'intval',
                'participants_female' => 'intval',
                'duration' => 'intval',
                'participant_id' => [__CLASS__, 'parse_participant_id'],
                'requested_time' => 'abaco_parse_array',
                'allows_preinscription' => 'abaco_parse_bool',
                'adult_content' => 'abaco_parse_bool'
            ]);
        }
        return $this->m_parser->parse($record);
    }
    public static function parse_participant_id($value) {
        $res = intval($value);
        if ($res === 0) {
            throw new Exception('Bad participant id');
        }
        return $res;
    }
    
    // Drop helpers
    protected function get_post_ids_to_remove() {
        $acts_query = new WP_Query([
            'post_type' => ABACO_ACTIVITY_POST_TYPE_NAME,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => get_post_stati()
        ]);
        $imgs_query = new WP_Query([
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_parent__in' => $acts_query->posts,
            'fields' => 'ids',
            'post_status' => get_post_stati()
        ]);
        return array_merge($acts_query->posts, $imgs_query->posts);
    }

    // Export query helpers
    protected function get_all_meta_data() {
        global $wpdb;

        $meta_keys = implode(', ', array_map(function($elm) {
                return "'" . $elm . "'";
            }, ABACO_ACTIVITY_META_FIELDS));
        $post_type = ABACO_ACTIVITY_POST_TYPE_NAME;
        $meta_table = $wpdb->prefix . 'postmeta';
        $post_table = $wpdb->prefix . 'posts';
        $sql = "SELECT meta.meta_key AS 'key',
            meta.meta_value AS 'value',
            meta.post_id AS 'post_id'
            FROM $meta_table meta INNER JOIN $post_table posts ON meta.post_id = posts.ID
            WHERE posts.post_type = '$post_type'
                AND meta.meta_key IN ($meta_keys)
                AND posts.post_status = 'publish';";
        $res = $wpdb->get_results($sql, OBJECT);
        if ($res === null) {
            wp_die('Database error.');
        }
        return $res;
    }

    protected function get_all_bare() {
        global $wpdb;
        $post_type = ABACO_ACTIVITY_POST_TYPE_NAME;
        $post_table = $wpdb->prefix . 'posts';
        $sql = "SELECT ID AS id,
                post_title AS name_,
                post_content AS description
                FROM $post_table
                WHERE post_type = '$post_type'
                AND post_status = 'publish';";
        $activities = $wpdb->get_results($sql, ARRAY_A);
        if ($activities === null) {
            wp_die('Database error.');
        }
        return $activities;
    }

    protected static function merge_metadata($activities, $metas) {
        foreach ($metas as $meta) {
            $activities[$meta->post_id][$meta->key] = $meta->value;
        }
        foreach ($activities as $activity) {
            foreach (ABACO_ACTIVITY_META_FIELDS as $key) {
                if (!isset($activity[$key])) {
                    $title = $activity['name_'];
                    wp_die("Activity $title: missing meta: $key");
                }
            }
        }
        return $activities;
    }

    // Insertion helpers
    private static function generate_insert_meta_data($data) {
        $res = [];
        foreach (ABACO_ACTIVITY_META_FIELDS as $field) {
            if (!isset($data[$field])) {
                wp_die('Activity DB insert: missing metadata field');
            }
            $res[$field] = $data[$field];
        }
        return $res;
    }

    protected function upload_img($post_id, $img) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $fname = sanitize_file_name(basename($img));
        $destination_dir = abaco_full_upload_dir() . '/' . $post_id;
        $fname_full = $destination_dir . '/' . $fname;
        if (!wp_mkdir_p($destination_dir)) {
            throw new Exception("Error creating upload dir");
        }
        if (!copy($img, $fname_full)) {
            throw new Exception("Error copying file");
        }
        $wp_filetype = wp_check_filetype($fname);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $fname,
            'post_content' => wp_strip_all_tags(strip_shortcodes(
                    __('This image was uploaded via the ABACO plugin, by a user.', 'abaco'))),
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $fname_full, $post_id, true);
        if (is_wp_error($attach_id)) {
            throw new Exception("Error inserting image attachment");
        }
        if (!set_post_thumbnail($post_id, $attach_id)) {
            throw new Exception("Error setting thumbnail");
        }
    }
}

function abaco_preinscription_activities() {
    static $res = null;
    if ($res === null) {
        $table = abaco_activity_db_table();
        $res = $table->query_preinscription_activities();
    }
    return $res;
}

function abaco_preinscription_activities_select_options() {
    $acts = abaco_preinscription_activities();
    $res = [];
    foreach ($acts as $act) {
        $res[$act['id']] = $act['name_'];
    }
    return $res;
}

function abaco_preinscription_activities_keys() {
    $acts = abaco_preinscription_activities();
    return array_map(function($act) {
        return (string)$act['id'];
    }, $acts);
}
