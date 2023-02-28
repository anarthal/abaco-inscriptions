<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/generic-views.php';

class ABACO_AdminParticipantEntryController {
    private $m_id;
    private $m_data;
    public function __construct($id, ABACO_ParticipantDbTable $participant_table) {
        $this->m_id = intval($id);
        if (!$this->m_id) {
            return;
        }
        $data = $participant_table->query_by_id('id', $id);
        if ($data !== null) {
            $data->gender = abaco_enum_to_string(abaco_gender_options(), $data->gender);
            $data->province = abaco_enum_to_string(abaco_province_options(), $data->province);
            $data->document_type = abaco_enum_to_string(abaco_document_type_options(), $data->document_type);
            $data->booking_days = abaco_enum_to_string(abaco_booking_days(), $data->booking_days);
            $data->yes_info = ($data->yes_info ? __('Yes', 'abaco') : __('No', 'abaco'));
            if ($data->birth_date !== null) {
                $data->birth_date = $data->birth_date->format('Y-m-d');
            } else {
                $data->birth_date = __('No birth date available.');
            }
        }
        $this->m_data = $data;
    }
    public function data() {
        return $this->m_data;
    }
    public function id() {
        return $this->m_id;
    }
    public function has_data() {
        return $this->m_data !== null;
    }
}

class ABACO_AdminParticipantEntryView extends ABACO_AdminView {
    private $m_controller;
    public function __construct(ABACO_AdminParticipantEntryController $controller) {
        $this->m_controller = $controller;
    }
    public function code() {
        if ($this->m_controller->has_data()) {
            $res = $this->table()->code();
        } else {
            $res = esc_html(sprintf(
                __('No resuls for participant with ID %d', 'abaco'),
                $this->m_controller->id()
            ));
        }
        return self::wrap($res);
    }
    
    private function table() {
        $data = $this->m_controller->data();
        $fields = isset($data->contact_participant_id) ?
            self::company_fields($data) :
            self::physical_participant_fields($data);
        return new ABACO_AdminKeyValueTable($fields);
    }
    
    private static function physical_participant_fields($data) {
        return [
            [__('Participant type'), __('Physical participant', 'abaco')],
            [__('First name', 'abaco'), $data->first_name],
            [__('Last name', 'abaco'), $data->last_name],
            [__('Alias', 'abaco'), $data->alias],
            [__('Birth date', 'abaco'), $data->birth_date],
            [__('Document type', 'abaco'), $data->document_type],
            [__('Identifier document', 'abaco'), $data->nif],
            [__('Gender', 'abaco'), $data->gender],
            [__('Phone', 'abaco'), $data->phone],
            [__('Email', 'abaco'), $data->email],
            [__('Group', 'abaco'), $data->group],
            [__('Province', 'abaco'), $data->province],
            [__('City', 'abaco'), $data->city],
            [__('Observations', 'abaco'), $data->observations],
            [__('Booking days', 'abaco'), $data->booking_days],
            [__('Tutor\'s identifier document', 'abaco'), $data->tutor_nif],
            [__('Participant wishes to receive spam from ABACO', 'abaco'), $data->yes_info]
        ];
    }
    
    private static function company_fields($data) {
        return [
            [__('Participant type'), __('Company', 'abaco')],
            [__('Company name', 'abaco'), $data->first_name],
            [__('NIF', 'abaco'), $data->nif],
            [__('Phone', 'abaco'), $data->phone],
            [__('Email', 'abaco'), $data->email],
            [__('Province', 'abaco'), $data->province],
            [__('City', 'abaco'), $data->city],
            [__('Observations', 'abaco'), $data->observations],
            [__('Participant wishes to receive spam from ABACO', 'abaco'), $data->yes_info],
            [__('Contact person'), self::participant_button(
                $data->contact_participant_id, __('See contact person', 'abaco'))]
        ];
    }
    
    private static function participant_button($id, $text) {
        return new ABACO_AdminButtonLink(
            abaco_admin_participant_link($id), $text);
    }
}