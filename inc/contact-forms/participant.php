<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/contact-form.php';

class ABACO_ParticipantForm extends ABACO_ContactForm {
    private $m_participant_table;
  
    // Construction
    public function __construct($participant_table) {
        $validators = [
            'document_type' => [$this, 'validate_document_type'],
            'nif' => [$this, 'validate_nif'],
            'tutor_nif' => [$this, 'validate_tutor']
        ];
        parent::__construct(
            self::make_field_list(),
            $validators
        );
        $this->m_participant_table = $participant_table;
    }
    
    // Validate functions
    public function validate_document_type($data) {
        $doctype = $data['document_type'];
        if ($doctype === 'UUID' &&
            self::compute_age($data['birth_date']) >= ABACO_NIF_MANDATORY_AGE) {
            throw new ABACO_ValidationError(
                __('You are eager enough to have a NIF.', 'abaco')
            );
        }
        return $data;
    }
    public function validate_nif($data) {
        // If we have UUID (minor without NIF), generate UUID and return
        $doctype = $data['document_type'];
        if ($doctype === 'UUID') {
            $data['nif'] = uniqid();
            return $data;
        }
        
        $nif = $data['nif'];
        
        // NIF is not actually mandatory because of the UUID possibility
        if ($nif === '') {
            throw new ABACO_ValidationError(
                __('This field is mandatory.', 'abaco')
            );
        }
        
        // Check if already registered
        if (!$this->m_participant_table->is_nif_available($nif)) {
            throw new ABACO_ValidationError(
                __('This document has already been registered.', 'abaco')
            );
        }
        return $data;
    }
    public function validate_tutor($data) {
        // Check if it's minor
        $age = self::compute_age($data['birth_date']);
        if ($age >= ABACO_MINORITY_AGE) {
            return $data;
        }
        
        // Check field is set
        $tutor_nif = $data['tutor_nif'];
        if ($tutor_nif === "") {
            throw new ABACO_ValidationError(
                __('This field is required because you are under age.', 'abaco')
            );
        }
        
        // Get info about tutor
        $tutor = $this->m_participant_table->query_by_id('nif', $tutor_nif,
            ['birth_date', 'booking_days']);
        if ($tutor === null || $tutor->birth_date === null) {
            throw new ABACO_ValidationError(
                __('Your tutor must be inscribed in the event. Please check her document is correct.', 'abaco')
            );
        }

        // Check if tutor is over age
        $tutor_age = self::compute_age($tutor->birth_date);
        if ($tutor_age < ABACO_MINORITY_AGE) {
            throw new ABACO_ValidationError(
                __('Your tutor must be an adult.', 'abaco')
            );
        }

        // Check booking days for tutor
        if (!self::booking_days_include($data['booking_days'], $tutor->booking_days)) {
            throw new ABACO_ValidationError(
                __('Your tutor must stay at least the same days as you.', 'abaco')
            );
        }
        
        return $data;
    }
    
    // Insertion functions
    public function insert(array $data) {
        $fields_to_clear = ['alias', 'phone', 'group',
            'observations', 'tutor_nif'];
        foreach ($fields_to_clear as $field) {
            if ($data[$field] == null) {
                unset($data[$field]);
            }
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
        return [
            new ABACO_TextField('first_name', __('First name', 'abaco'), true),
            new ABACO_TextField('last_name', __('Last name', 'abaco'), true),
            new ABACO_TextField('alias', __('Alias', 'abaco'), false),
            new ABACO_DateField('birth_date', __('Birth date (aaaa-mm-dd)', 'abaco'), true, ['cf7_options' => 'id:booking-birth-date']),
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
        ];
    }
        
    private static function tutor_nif_before_html() {
        return '<div id="booking-under-age">' .
            '<hr />' .
            '<p>' . ABACO_Field::escape(__('We have detected you are a minor.
               Please introduce here your tutor data.
               Your tutor must already be inscribed and stay the same nights as you, at least.
               You must bring us the authorization below signed by your tutor.'), 'abaco') .
            '</p><p><a href="'
            . esc_url(self::get_minor_authorization()) . '">Download authorization (PDF)</a></p><br /> ';
    }
    
    private static function get_minor_authorization() {
        return get_option(ABACO_SETTING_MINOR_AUTHORIZATION_URL, '/404');
    }
}
