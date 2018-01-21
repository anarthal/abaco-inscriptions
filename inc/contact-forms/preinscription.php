<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/contact-form.php';

class ABACO_SlotControl {   
    public static function is_slot($activity, $slot_count, $gender) {
        $slots_male = $activity->participants_male;
        $slots_female = $activity->participants_female;
        $slots_indiff = $activity->participants_total
            - $slots_male - $slots_female;
        $parts_male = $slot_count->MALE;
        $parts_female = $slot_count->FEMALE;
        $parts_nonbinary = $slot_count->NONBINARY;
        
        $excess_male = max($parts_male - $slots_male, 0); // males that don't fit in male-specific slots
        $excess_female = max($parts_female - $slots_female, 0); // same
        
        if ($gender === 'MALE') {
            $total_slots = $slots_male + $slots_indiff;
            $total_parts = $parts_male + $excess_female + $parts_nonbinary;
        } elseif ($gender === 'FEMALE') {
            $total_slots = $slots_female + $slots_indiff;
            $total_parts = $parts_female + $excess_male + $parts_nonbinary;
        } else {
            $total_slots = $slots_indiff;
            $total_parts = $excess_male + $excess_female + $parts_nonbinary;
        }
        
        return $total_slots > $total_parts;
    }
}

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
        
        // Get the activity
        $act_id = $data['preinscription_activity'];
        $act = $this->m_activity_table->query_by_id($act_id,
            ['adult_content', 'participants_male',
             'participants_female', 'participants_total']);
        if ($act === null) {
            throw new ABACO_ValidationError(
                __('Sorry, this activity is unavailable.', 'abaco')
            );
        }
        
        // Check the participant is not already inscribed
        if ($this->m_preinscription_table->is_already_inscribed($part->id, $act_id)) {
            throw new ABACO_ValidationError(
                __('You already are inscribed to this activity.', 'abaco')
            );
        }
        
        // If participant is under age, check for adult content
        $age = abaco_compute_age($part->birth_date);
        if ($age < ABACO_MINORITY_AGE && $act->adult_content) {
            throw new ABACO_ValidationError(
                __('Minors cannot inscribe to this activity because it has adult content.', 'abaco')
            );
        }
        
        // Check for free slots
        $slots = $this->m_preinscription_table->query_slots($act_id);
        if (!ABACO_SlotControl::is_slot($act, $slots, $part->gender)) {
            throw new ABACO_ValidationError(
                __('Sorry, there are no remaining slots for this activity.', 'abaco')  
            );
        }
        
        $data['participant_id'] = $part->id;
        $data['activity_id'] = $data['preinscription_activity'];
        unset($data['nif']);
        unset($data['preinscription_activity']);
        
        return $data;
    }
    
    
    public function insert(array $data) {
        $this->m_preinscription_table->insert($data);
    }
    
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