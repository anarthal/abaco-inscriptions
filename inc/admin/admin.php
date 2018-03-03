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
            ABACO_REQUIRED_CAPABILITY,
            ABACO_ADMIN_MAIN_SLUG,
            [$this, 'main_page']
        );
        add_submenu_page(
            ABACO_ADMIN_MAIN_SLUG,
            __('Participants', 'abaco'),
            __('Participants', 'abaco'),
            ABACO_REQUIRED_CAPABILITY,
            ABACO_ADMIN_PARTICIPANT_SLUG,
            [$this, 'participant_page']
        );
        add_submenu_page(
            ABACO_ADMIN_MAIN_SLUG,
            __('Companies', 'abaco'),
            __('Companies', 'abaco'),
            ABACO_REQUIRED_CAPABILITY,
            ABACO_ADMIN_COMPANY_SLUG,
            [$this, 'company_table']
        );
        add_submenu_page(
            ABACO_ADMIN_MAIN_SLUG,
            __('Settings', 'abaco'),
            __('Settings', 'abaco'),
            'manage_options',
            ABACO_ADMIN_SETTINGS_SLUG,
            [ABACO_SettingsPage::class, 'draw']
        );
        add_submenu_page(
            ABACO_ADMIN_MAIN_SLUG,
            __('Preinscriptions', 'abaco'),
            __('Preinscriptions', 'abaco'),
            ABACO_REQUIRED_CAPABILITY,
            ABACO_ADMIN_PREINSCRIPTION_SLUG,
            [$this, 'preinscription_page']
        );
        ABACO_SettingsPage::register_settings();
        

        add_action("load-$hook", [$this, 'export_action']);
        if (function_exists('register_field_group')) {
            register_field_group(abaco_activity_acf_config());
        }
        add_action('edit_form_advanced', [$this, 'activity_edit_custom_fields']);
    }

    public function main_page() {
        abaco_check_capability();
        require_once __DIR__ . '/export.php';
        (new ABACO_AdminExportView())->draw();
    }
    public function participant_table() {
        abaco_check_capability();
        require_once __DIR__ . '/participant-table.php';
        $part_table = abaco_participant_db_table();
        $data = new ABACO_AdminParticipantTableController($part_table);
        (new ABACO_AdminParticipantTableView($data))->draw();
    }
    public function company_table() {
        require_once __DIR__ . '/company-table.php';
        $part_table = abaco_participant_db_table();
        $data = new ABACO_AdminCompanyTableController($part_table);
        (new ABACO_AdminCompanyTableView($data))->draw();
    }
    public function participant_entry($id) {
        require_once __DIR__ . '/participant-entry.php';
        $part_table = abaco_participant_db_table();
        $data = new ABACO_AdminParticipantEntryController($id, $part_table);
        (new ABACO_AdminParticipantEntryView($data))->draw();
    }
    public function participant_page() {
        abaco_check_capability();
        $participant_id = filter_input(INPUT_GET, 'participant_id');
        $participant_nif = filter_input(INPUT_GET, 'participant_nif');
        if (isset($participant_id) && is_string($participant_id)) {
            $participant_id = intval(trim($participant_id));
            $this->participant_entry($participant_id);
        } elseif (isset($participant_nif) && is_string($participant_nif)) {
            $participant_id = abaco_participant_db_table()->nif_to_id(
                trim($participant_nif));
            if (!isset($participant_id)) {
                echo esc_html(sprintf(__('Participant with NIF "%s" not found.', 'abaco'),
                    $participant_nif));
            } else {
                $this->participant_entry($participant_id);
            }
        } else {
            $this->participant_table();
        }
    }
    public function preinscription_page() {
        abaco_check_capability();
        require_once __DIR__ . '/preinscriptions.php';
        $data = new ABACO_AdminPreinscriptionController(
            abaco_preinscription_db_table());
        (new ABACO_AdminPreinscriptionView($data))->draw();
    }
    public function activity_edit_custom_fields() {
        require_once __DIR__ . '/activity.php';
        global $post;
        $participant_table = abaco_participant_db_table();
        $activity_table = abaco_activity_db_table();
        $preinsc_table = abaco_preinscription_db_table();
        $data = new ABACO_AdminActivityController($post->ID,
            $participant_table, $activity_table, $preinsc_table);
        (new ABACO_AdminActivityView($data))->draw();
    }
    public function export_action() {
        abaco_check_capability();
        require_once __DIR__ . '/export.php';
        ABACO_ExportAction::export_action();
    }
}
