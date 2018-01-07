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
            'nif' => [$this, 'validate_nif']
        ];
        parent::__construct(self::make_field_list(), $validators);
        $this->m_participant_table = $participant_table;
    }
    
    public function validate_nif($data) {
        $nif = $data['nif'];
        if (!$this->m_participant_table->is_nif_available($nif)) {
            throw new ABACO_ValidationError(
                __('This NIF has already been registered.','abaco')
            );
        }
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
            new ABACO_TextField('first_name', __('Company name', 'abaco'), true),
            new ABACO_TextField('nif', __('NIF', 'abaco'), true, true),
            new ABACO_TelField('phone', __('Phone', 'abaco'), true),
            new ABACO_EmailField('email', __('Email', 'abaco'), true),
            new ABACO_SelectField('province', __('Province', 'abaco'), array_keys(abaco_province_options())),
            new ABACO_TextField('city', __('City', 'abaco'), true, true),
            new ABACO_TextareaField('observations', __('Observations', 'abaco'), false),
            new ABACO_CheckboxField('yes_info', __('I want to receive information about other activities organized by ABACO.', 'abaco'), ['cf7_options' => 'default:1']),
            new ABACO_SubmitField()
        );
    }
}