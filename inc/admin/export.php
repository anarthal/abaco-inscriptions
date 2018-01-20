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
        $items = [
            __('Participant data.', 'abaco'),
            __('Activity data. Images will be exported as hyperlinks.', 'abaco'),
            __('Enumerations.', 'abaco')
        ];
        $res = '<div class="card">' .
            '<h2>' . esc_html__('Export', 'abaco') . '</h2>' .
            '<p>' . esc_html__('The following data will be exported in JSON format:',
                'abaco') . '</p>';
        foreach ($items as $item) {
            $res .= '<li>' . esc_html($item) . '</li>';
        }
        $res .= '<br />' . $this->export_button()->code() . '</div>';
        return $res;
    }
    
    protected function export_url() {
        return 'admin.php?' . http_build_query([
            'page' => ABACO_ADMIN_MAIN_SLUG,
            'export' => 'json',
            self::NONCE_NAME => wp_create_nonce('abaco')
        ]);
    }
    
    protected function export_button() {
        return new ABACO_AdminButtonLink(
            $this->export_url(),
            __('Export as JSON', 'abaco')
        );
    }
}

class ABACO_AdminExportData {
    public $data;
    public function __construct(ABACO_ParticipantDbTable $part_table,
            ABACO_ActivityDbTable $act_table) {
        $participant_data = $part_table->query_all();
        $activity_data = $act_table->query_all();
        $this->data = [
            'participants' => $participant_data,
            'activities' => $activity_data,
            'booking_days' => array_keys(abaco_booking_days()),
            'genders' => array_keys(abaco_gender_options()),
            'document_types' => array_keys(abaco_document_type_options()),
            'provinces' => array_keys(abaco_province_options()),
            'activity_kinds' => array_keys(abaco_activity_kind_options()),
            'requested_times' => array_keys(abaco_activity_requested_time_options())
        ];
    }
}

function abaco_admin_export(ABACO_AdminExportData $data) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=export.json');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    echo json_encode($data);
    exit;
}
