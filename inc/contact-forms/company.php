<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/contact-form.php';

class ABACO_CompanyForm extends ABACO_ContactForm {
    private $m_participant_table;
    
    public function __construct(ABACO_ParticipantDbTable $participant_table) {
        $validators = [
            'nif' => [$this, 'validate_nif'],
            'contact_nif' => [$this, 'validate_contact_nif']
        ];
        parent::__construct(self::make_field_list(), $validators);
        $this->m_participant_table = $participant_table;
    }
    
    public function validate_nif($data) {
        $nif = $data['nif'];
        // Check for an actual NIF
        if (!abaco_is_valid_nif($nif)) {
            throw new ABACO_ValidationError(
                __('Invalid NIF.', 'abaco')
            );
        }
        
        if (!$this->m_participant_table->is_nif_available($nif)) {
            throw new ABACO_ValidationError(
                __('This NIF has already been registered.','abaco')
            );
        }

        return $data;
    }
    
    public function validate_contact_nif($data) {
        $nif = $data['contact_nif'];
        $id = $this->m_participant_table->nif_to_id($nif);
        if ($id === null) {
            throw new ABACO_ValidationError(
                __('You must be inscribed as a regular participant first.', 'abaco')
            );
        }
        $data['contact_participant_id'] = $id;
        unset($data['contact_nif']);
        return $data;
    }
    
    public function insert(array $data) {
        $data['document_type'] = 'NIF';
        $data['gender'] = 'NONBINARY';
        $data['booking_days'] = [];
        if ($data['observations'] === '') {
            unset($data['observations']);
        }
        $this->m_participant_table->insert($data);
    }
    
    // Helpers
    private static function make_field_list() {
        return array(
            new ABACO_TextField('first_name', __('Company name', 'abaco'), true, false, ['cf7_options' => 'maxlength:50']),
            new ABACO_TextField('nif', __('NIF', 'abaco'), true, true, ['cf7_options' => 'maxlength:20']),
            new ABACO_TextField('contact_nif', __('Contact person\'s NIF (you must be inscribed as a regular participant first)', 'abaco'), true, true),
            new ABACO_TelField('phone', __('Phone', 'abaco'), true, false, ['cf7_options' => 'maxlength:50']),
            new ABACO_EmailField('email', __('Email', 'abaco'), true, ['cf7_options' => 'maxlength:50']),
            new ABACO_SelectField('province', __('Province', 'abaco'), array_keys(abaco_province_options())),
            new ABACO_TextField('city', __('City', 'abaco'), true, true, ['cf7_options' => 'maxlength:25']),
            new ABACO_TextareaField('observations', __('Observations', 'abaco'), false, false, ['cf7_options' => 'maxlength:140']),
            new ABACO_CheckboxField('yes_info', __('I want to receive information about other activities organized by ABACO.', 'abaco'), ['cf7_options' => 'default:1']),
            new ABACO_CaptchaField(),
            new ABACO_SubmitField()
        );
    }
}