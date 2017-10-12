<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/contact-form.php';
require_once __DIR__ . '/field.php';

class ABACO_ActivityForm extends ABACO_ContactFormImpl {
    private $m_participant_table;
    private $m_activity_table;
    
    public function selects() {
        return array(
            'kind' => abaco_activity_kind_options(),
            'duration' => abaco_activity_duration_options(),
            'requested_time' => abaco_activity_requested_time_options()
        );
    }
    
    public function __construct($participant_table, $activity_table) {
        parent::__construct(self::make_field_list());
        $this->add_validator('organizer_nif', array($this, 'validate_nif'));
        $this->add_validator(
            'participants_total',
            array($this, 'validate_participant_number')
        );
        $this->m_participant_table = $participant_table;
        $this->m_activity_table = $activity_table;
    } 
    
    // Validation
    protected function validate_nif($value) {
        $id = $this->m_participant_table->nif_to_id($value);
        if ($id === null) {
            throw new Exception(__("You must be inscribed before registering activities.", "abaco"));
        }
        $this->data['participant_id'] = $id;
    }
    protected function validate_participant_number($total) {
        $males = $this->data['participants_male'];
        $females = $this->data['participants_female'];
        $indifferent = $total - $males - $females;
        if ($indifferent < 0) {
            throw new Exception(self::negative_participants_message());
        }
    }
    
    // Insertion
    public function insert() {
        $this->m_activity_table->insert($this->data);
    }
    
    // Helpers
    private static function participants_indifferent_html() {
        return '<p>' .
            ABACO_Field::escape(__(
                'Number of gender-indifferent participants:', 'abaco')) .
            ' <span id="activity-participants-indifferent">0</span></p>' .
            '<p id="activity-error" style="color: red"></p>';
    }
    private static function acceptance_entry() {
        return '<p> [acceptance acceptance] ' . ABACO_Field::escape(
            __('The activity organizer compromises to organize the above activity and is responsible for making adequate use of the facilities where it takes place and for keeping the organization informed of any incidence', 'abaco')
        ) . '<span style="color:red">[*]</span> </p>';
    }
    private static function make_field_list() {
        return array(
            new ABACO_TextField('name_', __('Activity name', 'abaco'), true),
            new ABACO_SelectField('kind', __('Activity kind', 'abaco'), array_keys(abaco_activity_kind_options())),
            new ABACO_TextareaField('description', __('Description to be published on our website', 'abaco'), true, true, ['cf7_options' => 'maxlength:500']),
            new ABACO_FileField('img', __('Image to be published on our website', 'abaco'), true, ['cf7_options' => 'limit:1mb filetypes:gif|png|jpg|jpeg']),
            new ABACO_SelectField('duration', __('Activity approximate duration', 'abaco'), abaco_activity_duration_keys()),
            new ABACO_MulticheckboxField('requested_time', __('Days you may organize the activity (subject to availability)', 'abaco'), true, array_keys(abaco_activity_requested_time_options())),
            new ABACO_NumberField('participants_total', __('Total number of participants', 'abaco'), true, ['cf7_options' => 'id:activity-participants-total min:1']),
            new ABACO_NumberField('participants_male', __('Number of male participants', 'abaco'), true, ['cf7_options' => 'id:activity-participants-male min:0']),
            new ABACO_NumberField('participants_female', __('Number of female participants', 'abaco'), true, ['cf7_options' => 'id:activity-participants-female min:0']),
            new ABACO_EchoField('participants_indifferent', self::participants_indifferent_html()),
            new ABACO_TextareaField('observations', __('Observations and needs (space, materials...)', 'abaco'), true, false, ['cf7_options' => 'maxlength:300']),
            new ABACO_TextField('organizer_nif', __('Organizer NIF or passport', 'abaco'), true, true),
            new ABACO_EchoField('acceptance', self::acceptance_entry()),
            new ABACO_CheckboxField('allows_preinscription', __('Allows web preinscription', 'abaco')),
            new ABACO_CheckboxField('adult_content', __('This activity has adult content', 'abaco')),
            new ABACO_SubmitField()
        );
    }
}
