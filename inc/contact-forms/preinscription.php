<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/contact-form.php';

class ABACO_PreinscriptionForm extends ABACO_ContactForm {
    private $m_participant_table;
    private $m_activity_table;
    private $m_preinscription_table;
    
    public function __construct(ABACO_ParticipantDbTable $participant_table,
            ABACO_ActivityDbTable $activity_table,
            ABACO_PreinscriptionDbTable $preinscription_table) {
        $validators = [
            'nif' => [$this, 'validate_nif']
        ];
        parent::__construct(self::make_field_list(), $validators);
        $this->m_participant_table = $participant_table;
        $this->m_activity_table = $activity_table;
        $this->m_preinscription_table = $preinscription_table;
    }
    
    public function validate_nif($data) {
        $nif = $data['nif'];
        $part = $this->m_participant_table->query_by_id('nif', $nif,
            ['id', 'birth_date', 'gender']);
        
        // Check it actually exists
        if ($part === null) {
            throw new ABACO_ValidationError(
                __('You must be inscribed before pre-inscribing to activities.','abaco')
            );
        }
        
        // Check it is a physical participant
        if ($part->birth_date === null) {
            throw new ABACO_ValidationError(
                __('Only physical participants can preinscribe to activities.', 'abaco')
            );
        }
        
        /*$act_id = $data['preinscription_activity'];
        $act = $this->m_activity_table->query_by_id($act_id,
            ['allows_preinscription', 'adult_content']);
        
        // Check activity actually allows preinscription
        if ($act === null || !$act->allows_preinscription) {
            throw new ABACO_ValidationError(
                __('This activity does not allow preinscription.', 'abaco')
            );
        }*/
        
        // If participant is under age, check for adult content
        $age = abaco_compute_age($part->birth_date);
        if ($age < ABACO_MINORITY_AGE && $act->adult_content) {
            throw new ABACO_ValidationError(
                __('Minors cannot inscribe to this activity because it has adult content.', 'abaco')
            );
        }
        
        // TODO: check for already inscribed and free slots
        
        $data['participant_id'] = $part->id;
        $data['activity_id'] = $data['preinscription_activity'];
        unset($data['nif']);
        unset($data['preinscription_activity']);
        
        return $data;
    }
    
    
    public function insert(array $data) {
        $this->m_preinscription_table->insert($data);
    }
    
    // Helpers
    private static function make_field_list() {
        return [
            new ABACO_TextField('nif', __('NIF or passport', 'abaco'), true, true),
            new ABACO_SelectField('preinscription_activity', __('Activity', 'abaco'), abaco_preinscription_activities_keys()),
            new ABACO_TextareaField('observations', __('Observations', 'abaco'), false),
            new ABACO_CaptchaField(),
            new ABACO_SubmitField()
        ];
    }
}