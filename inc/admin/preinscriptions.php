<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/generic-views.php';

class ABACO_AdminPreinscriptionController {
    public $data;
    public function __construct(ABACO_PreinscriptionDbTable $pre_table) {
        // Data from DB
        $this->data = $pre_table->query_all_readable();
    }
}

class ABACO_AdminPreinscriptionView extends ABACO_AdminView {
    private $m_controller;
    public function __construct(ABACO_AdminPreinscriptionController $controller) {
        $this->m_controller = $controller;
    }
    public function code() {
        $res = '<h1>' . esc_html__('Preinscriptions', 'abaco') . '</h1><br />' .
            $this->preinscription_table()->code();
        return self::wrap($res);
    }
    
    private function preinscription_table() {
        $headers = [
            __('Activity name', 'abaco'),
            __('Participant name', 'abaco'),
            __('Participant identifier document', 'abaco'),
            __('Preinscription date', 'abaco'),
            __('Observations', 'abaco')
        ];
        $data = array_map(function($row) {
            return [
                self::activity_link($row),
                $row['first_name'] . ' ' . $row['last_name'],
                self::participant_link($row),
                $row['inscription_day'],
                $row['observations']
            ];
        }, $this->m_controller->data);
        return new ABACO_AdminRowTable($headers, $data);
    }
    
    private static function participant_link($row) {
        return new ABACO_AdminLink(
            abaco_admin_participant_link($row['participant_id']),
            $row['nif']
        );
    }
    
    private static function activity_link($row) {
        return new ABACO_AdminLink(
            get_edit_post_link($row['activity_id']),
            $row['activity_name']
        );
    }
}
