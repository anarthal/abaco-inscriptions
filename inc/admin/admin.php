<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/settings.php';

class ABACO_Admin {  
    public function register_hooks() {
        $hook = add_menu_page(
            __('ABACO Events', 'abaco'),
            __('ABACO Events', 'abaco'),
            'manage_options',
            ABACO_ADMIN_MAIN_SLUG,
            [$this, 'main_page']
        );
        add_submenu_page(
            ABACO_ADMIN_MAIN_SLUG,
            __('Participants', 'abaco'),
            __('Participants', 'abaco'),
            'manage_options',
            ABACO_ADMIN_PARTICIPANT_SLUG,
            [$this, 'participant_page']
        );
        add_submenu_page(
            ABACO_ADMIN_MAIN_SLUG,
            __('Settings', 'abaco'),
            __('Settings', 'abaco'),
            'manage_options',
            ABACO_ADMIN_SETTINGS_SLUG,
            [ABACO_SettingsPage::class, 'draw']
        );
        ABACO_SettingsPage::register_settings();
        

        add_action("load-$hook", [$this, 'export_action']);
        if (function_exists('register_field_group')) {
            register_field_group(abaco_activity_acf_config());
        }
        add_action('edit_form_advanced', [$this, 'activity_edit_custom_fields']);
    }

    public function main_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        require_once __DIR__ . '/export.php';
        (new ABACO_AdminExportView())->draw();
    }
    public function participant_table() {
        require_once __DIR__ . '/participant-table.php';
        $part_table = abaco_participant_db_table();
        $data = new ABACO_AdminParticipantTableController($part_table);
        (new ABACO_AdminParticipantTableView($data))->draw();
    }
    public function participant_entry($id) {
        require_once __DIR__ . '/participant-entry.php';
        $part_table = abaco_participant_db_table();
        $data = new ABACO_AdminParticipantEntryController($id, $part_table);
        (new ABACO_AdminParticipantEntryView($data))->draw();
    }
    public function participant_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        $participant_id = filter_input(INPUT_GET, 'participant_id');
        if (!isset($participant_id) ||
            !is_string($participant_id) ||
            trim($participant_id) === '') {
            $this->participant_table();
        } else {
            $participant_id = trim($participant_id);
            $this->participant_entry($participant_id);
        }
    }
    public function activity_edit_custom_fields() {
        require_once __DIR__ . '/activity.php';
        global $post;
        $participant_table = abaco_participant_db_table();
        $activity_table = abaco_activity_db_table();
        $data = new ABACO_AdminActivityController($post->ID,
            $participant_table, $activity_table);
        (new ABACO_AdminActivityView($data))->draw();
    }
    public function export_action() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        $action = filter_input(INPUT_GET, 'export');
        if ($action !== 'json') {
            return;
        }
        $nonce = filter_input(INPUT_GET, '_abaco_nonce');
        if (!isset($nonce) || !wp_verify_nonce($nonce, 'abaco')) {
            wp_die('Invalid nonce');
        }
        require_once __DIR__ . '/export.php';
        $part_table = abaco_participant_db_table();
        $act_table = abaco_activity_db_table();
        $data = new ABACO_AdminExportData($part_table, $act_table);
        abaco_admin_export($data);
    }
}
