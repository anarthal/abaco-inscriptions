<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/generic-views.php';

class ABACO_AdminExportView extends ABACO_AdminView {
    const NONCE_NAME = '_abaco_nonce';
    public function code() {
        return self::csv_card() . self::json_card();
    }
    
    protected static function export_button($export_type, $text) {
        $url = 'admin.php?' . http_build_query([
            'page' => ABACO_ADMIN_MAIN_SLUG,
            'export' => $export_type,
            self::NONCE_NAME => wp_create_nonce('abaco')
        ]);
        return new ABACO_AdminButtonLink($url, $text);
    }
    
    protected static function json_card() {
        $items = [
            __('Participant data.', 'abaco'),
            __('Activity data. Images will be exported as hyperlinks.', 'abaco'),
            __('Enumerations.', 'abaco')
        ];
        $button = self::export_button('json', __('Export as JSON', 'abaco'));
        $res = '<div class="card">' .
            '<h2>' . esc_html__('Export (JSON)', 'abaco') . '</h2>' .
            '<p>' . esc_html__('The following data will be exported in JSON format:',
                'abaco') . '</p>';
        foreach ($items as $item) {
            $res .= '<li>' . esc_html($item) . '</li>';
        }
        $res .= '<br />' . $button->code() . '</div>';
        return $res;
    }
    
    protected static function csv_card() {
        $button_participants = self::export_button(
            'participants-csv',
            __('Export participants (physical ones and companies)', 'abaco')
        );
        return '<div class="card">' .
            '<h2>' . esc_html__('Export (CSV) - Compatible with Microsoft Excel', 'abaco') .
            '</h2><p>' . $button_participants->code() . '<p><p>' .
            '</p></div>';
    }
}

class ABACO_ExportAction {
    public static function export_action() {
        $action = filter_input(INPUT_GET, 'export');
        if ($action === 'json') {
            self::do_json_export();
        } elseif($action === 'participants-csv') {
            self::do_participant_csv_export();
        }
    }
    
    public static function do_json_export() {
        self::verify_nonce();
        $part_table = abaco_participant_db_table();
        $act_table = abaco_activity_db_table();
        $preinsc_table = abaco_preinscription_db_table();
        $data = self::get_json_data($part_table, $act_table, $preinsc_table);
        self::do_export(json_encode($data), 'application/json', 'export.json');
    }
    
    public static function do_participant_csv_export() {
        self::verify_nonce();
        $part_table = abaco_participant_db_table();
        $data = self::get_participants_csv_data($part_table);
        self::do_export($data, 'text/csv', 'participants.csv');
    }
    
    // Implementation - generic
    private static function verify_nonce() {
        $nonce = filter_input(INPUT_GET, ABACO_AdminExportView::NONCE_NAME);
        if (!isset($nonce) || !wp_verify_nonce($nonce, 'abaco')) {
            wp_die('Invalid nonce');
        }
    }
    
    private static function do_export($serialized_data, $content_type, $fname) {
        header('Content-Description: File Transfer');
        header("Content-Type: $content_type; charset=utf-8");
        header("Content-Disposition: attachment; filename=$fname");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        echo $serialized_data;
        exit;
    }
    
    // Implementation - JSON
    private static function get_json_data(ABACO_ParticipantDbTable $part_table,
                ABACO_ActivityDbTable $act_table,
                ABACO_PreinscriptionDbTable $preinsc_table) {
        $participant_data = $part_table->query_all();
        $activity_data = $act_table->query_all();
        $preinsc_data = $preinsc_table->query_all();
        return ['data' => [
            'participants' => $participant_data,
            'activities' => $activity_data,
            'preinscriptions' => $preinsc_data,
            'booking_days' => array_keys(abaco_booking_days()),
            'genders' => array_keys(abaco_gender_options()),
            'document_types' => array_keys(abaco_document_type_options()),
            'provinces' => array_keys(abaco_province_options()),
            'activity_kinds' => array_keys(abaco_activity_kind_options()),
            'requested_times' => array_keys(abaco_activity_requested_time_options())
        ]];
    }
    
    // Implementation - CSV
    private static function format_csv(array $fields, array $data) {
        $csv = fopen('php://temp', 'r+');
        if (!$csv) {
            wp_die('Error opening temporary CSV file');
        }
        fputcsv($csv, $fields, ';');
        foreach ($data as $line) {
            fputcsv($csv, $line, ';');
        }
        rewind($csv);
        return stream_get_contents($csv);
    }
    
    private static function get_participants_csv_data(
            ABACO_ParticipantDbTable $part_table) {
        $data = $part_table->query_all();
        if (empty($data)) {
            return '';
        }
        $fields = array_keys($data[0]);
        $data_values = array_map(function($record) {
            if (isset($record['birth_date'])) {
                $record['birth_date'] = $record['birth_date']->format('Y-m-d');
            }
            $record['booking_days'] = implode(', ', $record['booking_days']);
            return array_values($record);
        }, $data);
        return self::format_csv($fields, $data_values);
    }
}
