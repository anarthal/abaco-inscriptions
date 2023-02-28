<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/generic-views.php';

class ABACO_AdminActivityController {
    public $activity_id;
    public $activity;
    public $organizer;
    
    public function __construct($act_id, ABACO_ParticipantDbTable $participant_table,
            ABACO_ActivityDbTable $activity_table,
            ABACO_PreinscriptionDbTable $preinsc_table) {
        $this->activity_id = $act_id;
        $this->activity = $activity_table->query_by_id($act_id,
            ['participant_id', 'requested_time', 'observations']);
        if ($this->activity !== null) {
            $this->organizer = $participant_table->query_by_id(
                'id',
                $this->activity->participant_id,
                ['id', 'first_name', 'last_name', 'email']
            );
            $this->preinscriptions = $preinsc_table->query_participants($act_id);
        }
    }
    
    public function has_data() {
        return $this->activity !== null;
    }
    public function has_organizer_data() {
        return $this->organizer !== null;
    }
}

class ABACO_AdminActivityView extends ABACO_AdminView {
    private $m_controller;
    public function __construct(ABACO_AdminActivityController $controller) {
        $this->m_controller = $controller;
    }
    
    public function code() {
        $res = '<h2>' . esc_html__('Other activity data', 'abaco') . '</h2>';
        if ($this->m_controller->has_data()) {
            $res .= $this->organizer_table()->code();
            if (!empty($this->m_controller->preinscriptions)) {
                $res .= '<br /><h2>' . esc_html__('Preinscriptions', 'abaco') . '</h2>';
                $res .= $this->preinscription_table()->code();
            }
        } else {
            $res .= '<p>' .
                esc_html__('No information found about this activity.', 'abaco') .
                '</p>';
        }
        return $res;
    }
    
    private function organizer_table() {
        $act = $this->m_controller->activity;
        $organizer = $this->m_controller->organizer;
        $req_time = abaco_enum_to_string(
            abaco_activity_requested_time_options(),
            $act->requested_time
        );
        $table_data = [
            [__('Requested activity time', 'abaco'), $req_time],
            [__('Observations and needs', 'abaco'), $act->observations]
        ];
        $table_data = array_merge($table_data,
            self::organizer_table_data($organizer));
        return new ABACO_AdminKeyValueTable($table_data);
    }
    
    private function preinscription_table() {
        $parts = $this->m_controller->preinscriptions;
        $table_data = array_map(function($part) {
            $name = $part['first_name'] . ' ' . $part['last_name'];
            $button =  new ABACO_AdminButtonLink(
                abaco_admin_participant_link($part['id']),
                __('See details', 'abaco')
            );
            return [$name, $button];
        }, $parts);
        return new ABACO_AdminKeyValueTable($table_data);
    }
    
    private static function organizer_table_data($organizer) {
        if ($organizer) {
            $name = $organizer->first_name . ' ' . $organizer->last_name;
            $details = new ABACO_AdminButtonLink(
                abaco_admin_participant_link($organizer->id),
                __('See details', 'abaco')
            );
            return [
                [__('Organizes', 'abaco'), $name],
                [__('Organizer email', 'abaco'), $organizer->email],
                [$details, '']
            ];
        } else {
            return [
                [__('Could not find information about organizer.', 'abaco'), '']
            ];
        }
    }
}
