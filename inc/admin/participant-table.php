<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/generic-views.php';

class ABACO_AdminParticipantTableController {
    public $data;
    public function __construct(ABACO_ParticipantDbTable $part_table) {
        $this->data = $part_table->query_all(
            'id, nif, first_name, last_name, alias, email, booking_days');
    }
    public function booking_data_per_day() {
        $res = [];
        foreach (array_keys(abaco_booking_days()) as $day) {
            $res[$day] = self::count_participants_per_day($this->data, $day);
        }
        return $res;
    }
    
    // Helpers
    private static function count_participants_per_day($parts, $day) {
        $res = 0;
        foreach ($parts as $part) {
            if (in_array($day, $part['booking_days'])) {
                ++$res;
            }
        }
        return $res;
    }
}

class ABACO_AdminParticipantTableView extends ABACO_AdminView {
    private $m_controller;
    public function __construct(ABACO_AdminParticipantTableController $controller) {
        $this->m_controller = $controller;
    }
    public function code() {
        $booking_table = $this->booking_table();
        $part_table = $this->part_table();
        $res = '<div style="width:50%">' . $booking_table->code() .
            '</div><br />' .
            $part_table->code();
        return self::wrap($res);
    }
    
    private function booking_table() {
        $res = [
            [__('Total participants', 'abaco'), count($this->m_controller->data)]
        ];
        $per_day = $this->m_controller->booking_data_per_day();
        $day_map = abaco_booking_days();
        foreach ($per_day as $day => $number) {
            $day_name = abaco_enum_to_string($day_map, $day);
            $res[] = [
                sprintf(__('Participants staying on %s', 'abaco'), $day_name),
                $number
            ];
        }
        return new ABACO_AdminKeyValueTable($res);
    }
    private function part_table() {
        $data = $this->m_controller->data;
        for ($i = 0; $i != count($data); ++$i) {
            $data[$i]['_details'] = new ABACO_AdminButtonLink(
                abaco_admin_participant_link($data[$i]['id']),
                __('See details', 'abaco')
            );
        }
        $headers = [
            'nif' => __('Identifier document', 'abaco'),
            'first_name' => __('First name', 'abaco'),
            'last_name' => __('Last name', 'abaco'),
            'alias' => __('Alias', 'abaco'),
            'email' => __('Email', 'abaco'),
            '_details' => ''
        ];
        return new ABACO_AdminRowTable($headers, $data);
    }
}

