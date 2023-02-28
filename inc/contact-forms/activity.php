<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/contact-form.php';

class ABACO_ActivityForm extends ABACO_ContactForm {
    private $m_participant_table;
    private $m_activity_table;
    
    public function __construct($participant_table, $activity_table) {
        $validators = [
            'organizer_nif' => [$this, 'validate_nif']
        ];
        parent::__construct(self::make_field_list(), $validators);
        $this->m_participant_table = $participant_table;
        $this->m_activity_table = $activity_table;
    }
    
    // Validation
    public function validate_nif($data) {
        $nif = $data['organizer_nif'];
        $id = $this->m_participant_table->nif_to_id($nif);
        if ($id === null) {
            throw new ABACO_ValidationError(
                __('You must be inscribed before registering activities.', 'abaco')
            );
        }
        $data['participant_id'] = $id;
        return $data;
    }
    
    // Insertion
    public function insert(array $data) {
        $data['participants_male'] = 0;
        $data['participants_female'] = 0;
        $data['allows_preinscription'] = false;
        $this->m_activity_table->insert($data);
    }
    
    // Helpers
    private static function heading() {
        return '<p>Los datos que aquí introduzcas serán publicados en nuestra web.</p>';
    }

    private static function heading_private_data() {
        return '<p>&nbsp;</p><p>Necesitamos algunos datos extra. <strong>Estos datos son sólo visibles por el personal de Abaco, y no se harán públicos en la web</strong>.</p>';
    }

    private static function acceptance_privacy() {
        return '<p> [acceptance acceptance-privacy] He leído y acepto la <a href="https://www.abacobilbao.org/politica-privacidad-activities/">política de privacidad</a>. <span style="color:red">[*]</span> </p>';
    }

    private static function acceptance_entry() {
        return '<p> [acceptance acceptance] ' . ABACO_Field::escape(
            __('The activity organizer compromises to organize the above activity and is responsible for making adequate use of the facilities where it takes place and for keeping the organization informed of any incidence', 'abaco')
        ) . '<span style="color:red">[*]</span> </p>';
    }

    private static function make_field_list() {
        return array(
            // Fields that will be public
            new ABACO_EchoField('heading', self::heading()),
            new ABACO_TextField('name_', __('Activity name', 'abaco'), true),
            new ABACO_SelectField('kind', __('Activity kind', 'abaco'), array_keys(abaco_activity_kind_options())),
            new ABACO_TextareaField('description', __('Description to be published on our website', 'abaco'), true, true, ['cf7_options' => 'maxlength:500']),
            new ABACO_FileField('img', __('Image to be published on our website', 'abaco'), true, false, ['cf7_options' => 'limit:1mb filetypes:gif|png|jpg|jpeg']),
            new ABACO_SelectField('duration', __('Activity approximate duration', 'abaco'), abaco_activity_duration_keys()),
            new ABACO_NumberField('participants_total', __('Total number of participants', 'abaco'), true, ['cf7_options' => 'id:activity-participants-total min:1']),
            new ABACO_CheckboxField('adult_content', __('This activity has adult content', 'abaco')),

            // Fields that we'll keep private
            new ABACO_EchoField('heading_private', self::heading_private_data()),
            new ABACO_TextField('organizer_nif', __('Organizer NIF or passport', 'abaco'), true, true),
            new ABACO_MulticheckboxField('requested_time', __('Days you may organize the activity (subject to availability)', 'abaco'), true, array_keys(abaco_activity_requested_time_options())),
            new ABACO_TextareaField('observations', __('Observations and needs (space, materials...)', 'abaco'), true, false, ['cf7_options' => 'maxlength:300']),
            new ABACO_EchoField('acceptance', self::acceptance_entry()),
            new ABACO_EchoField('acceptance-privacy', self::acceptance_privacy()),
            new ABACO_CaptchaField(),
            new ABACO_SubmitField()
        );
    }
}
