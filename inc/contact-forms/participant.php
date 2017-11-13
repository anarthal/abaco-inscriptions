<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/contact-form.php';
require_once __DIR__ . '/field.php';

class ABACO_ParticipantForm extends ABACO_ContactFormImpl {
    private $m_participant_table;
  
    // Construction
    public function __construct($participant_table) {
        parent::__construct(self::make_field_list());
        $this->add_validator('document_type', array($this, 'validate_document_type'));
        $this->add_validator('nif', array($this, 'validate_nif'));
        $this->add_validator('tutor_nif', array($this, 'validate_tutor'));
        $this->m_participant_table = $participant_table;
    }
    
    // Accessors
    public function birth_date() {
        return $this->data['birth_date'];
    }
    public function age() {
        return self::compute_age($this->birth_date());
    }
    public function document_type() {
        return $this->data['document_type'];
    }
    public function nif() {
        return $this->data['nif'];
    }
    public function set_nif($value) {
        $this->data['nif'] = $value;
    }
    public function booking_days() {
        return $this->data['booking_days'];
    }
    
    // Validate functions
    protected function validate_document_type($value) {
        if ($value === 'UUID' && $this->age() >= ABACO_NIF_MANDATORY_AGE) {
            throw new Exception(__("You are eager enough to have a NIF.", 'abaco'));
        }
    }
    protected function validate_nif($nif) {
        if ($this->document_type() === 'UUID') {
            $this->set_nif(uniqid());
            return;
        }
        if ($nif === '') {
            throw new Exception(__('This field is mandatory.', 'abaco'));
        }
        if (!$this->m_participant_table->is_nif_available($nif)) {
            throw new Exception(__('This document has already been registered.',
                'abaco'));
        }
    }
    protected function validate_tutor($tutor_nif) {
        // Check if it's minor
        if ($this->age() >= ABACO_MINORITY_AGE) {
            return;
        }
        
        // Check field is set
        if ($tutor_nif === "") {
            throw new Exception(
                __("This field is required because you are under age.", 'abaco'));
        }
        
        // Get info about tutor
        $tutor = $this->m_participant_table->query_by_id('nif', $tutor_nif,
            'birth_date, booking_days');
        if ($tutor === null) {
            $msg = __("Your tutor must be inscribed in the event. Please check her document is correct.", 'abaco');
            throw new Exception($msg);
        }

        // Check if tutor is over age
        $tutor_age = self::compute_age(new DateTime($tutor->birth_date));
        if ($tutor_age < ABACO_MINORITY_AGE) {
            throw new Exception(__('Your tutor must be an adult.', 'abaco'));
        }

        // Check booking days for tutor
        if (!self::booking_days_include($this->booking_days(), $tutor->booking_days)) {
            throw new Exception(
                __('Your tutor must stay at least the same days as you.', 'abaco'));
        }
    }
    
    // Insertion functions
    public function insert() {
        $data = $this->data;
        $data['birth_date'] = $data['birth_date']->format('Y-m-d');
        if ($data['tutor_nif'] === '') {
            unset($data['tutor_nif']);
        }
        $this->m_participant_table->insert($data);
    }
    
    // Helpers
    public static function compute_age($birth) {
        $now = new DateTime();
        return $now->diff($birth)->y;
    }
    
    public static function booking_days_include($this_days, $other_days) {
        foreach ($this_days as $day) {
            if (!in_array($day, $other_days)) {
                return false;
            }
        }
        return true;
    }
    
    private static function make_field_list() {
        return array(
            new ABACO_TextField('first_name', __('First name', 'abaco'), true),
            new ABACO_TextField('last_name', __('Last name', 'abaco'), true),
            new ABACO_TextField('alias', __('Alias', 'abaco'), false),
            new ABACO_DateField('birth_date', __('Birth date (aaaa-mm-dd)', 'abaco'), true, ['cf7_options' => 'max:2017-01-01 id:booking-birth-date']),
            new ABACO_SelectField('document_type', __('Identifier document type', 'abaco'), array_keys(abaco_document_type_options()), ['cf7_options' => 'id:document-type']),
            new ABACO_TextField('nif', __('Identifier document', 'abaco'), false, true, ['cf7_options' => 'id:nif', 'asterisk' => true]),
            new ABACO_SelectField('gender', __('Gender', 'abaco'), array_keys(abaco_gender_options())),
            new ABACO_TelField('phone', __('Phone', 'abaco'), false),
            new ABACO_EmailField('email', __('Email', 'abaco'), true),
            new ABACO_TextField('group', __('Group or association', 'abaco'), false, true),
            new ABACO_SelectField('province', __('Province', 'abaco'), array_keys(abaco_province_options())),
            new ABACO_TextField('city', __('City', 'abaco'), true, true),
            new ABACO_TextareaField('observations', __('Observations', 'abaco'), false),
            new ABACO_MulticheckboxField('booking_days', __('Nights you want to stay', 'abaco'), false, array_keys(abaco_booking_days_select_options()), ['element_id' => 'booking-days-container']),
            new ABACO_EchoField('tutor_nif_before', self::tutor_nif_before_html()),
            new ABACO_TextField('tutor_nif', __('Your tutor\'s identity document'), false, true, ['asterisk' => true]),
            new ABACO_EchoField('tutor_nif_after', '<hr /></div>'),
            new ABACO_CheckboxField('yes_info', __('I want to receive information about other activities organized by ABACO.', 'abaco'), ['cf7_options' => 'default:1']),
            new ABACO_SubmitField()
        );
    }
        
    private static function tutor_nif_before_html() {
        return '<div id="booking-under-age">' .
            '<hr />' .
            '<p>' . ABACO_Field::escape(__('We have detected you are a minor.
               Please introduce here your tutor data.
               Your tutor must already be inscribed and stay the same nights as you, at least.
               You must bring us --LINK HERE-- this authorization signed by your tutor.'), 'abaco') .
            '</p><br /> ';
    }
}
