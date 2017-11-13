<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

interface ABACO_ContactForm {
    public function code();
    public function setup_data(array $data);
    public function validate($result, $form_tags);
    public function insert();
}

class ABACO_ContactFormManager {
    private static $m_instance;
    private $m_form_factories = [];
    private $m_forms = [];
    private $m_selects = [];
    
    // Construction & singleton management
    public static function get_instance() {
        if (!self::$m_instance) {
            self::$m_instance = new self();
        }
        return self::$m_instance;
    }
    public function __construct() {
        $this->register_hooks();
    }
    
    // Accessors
    public function add_form($title, callable $form_factory) {
        $this->m_form_factories[$title] = $form_factory;
    }
    public function get_form($title) {
        if (isset($this->m_forms[$title])) {
            return $this->m_forms[$title];
        } else if (isset($this->m_form_factories[$title])) {
            $form = call_user_func($this->m_form_factories[$title]);
            $this->m_forms[$title] = $form;
            return $form;
        } else {
            return null;
        }
    }
    public function add_selects($selects) {
        $this->m_selects = array_merge($this->m_selects, $selects);
    }
    public function get_select($name) {
        if (!isset($this->m_selects[$name])) {
            return null;
        }
        $values = $this->m_selects[$name];
        return call_user_func($values);
    }
    
    // Hooks
    public function form_code_hook($contact_form) {
        $abaco_contact_form = $this->get_form($contact_form->title());
        if ($abaco_contact_form) {
            $contact_form->set_properties(array(
                'form' => $abaco_contact_form->code()
            ));
        }
    }
    public function select_hook($form_tag) {
        $name = $form_tag['name'];
        $values = $this->get_select($name);
        if (isset($values)) {
            $form_tag['values'] = array_keys($values);
            $form_tag['labels'] = array_values($values);
        }
        return $form_tag;
    }
    public function validation_hook($result, $form_tags) {
        $submission = WPCF7_Submission::get_instance();
        $contact_form = WPCF7_ContactForm::get_current();

        if (!$submission || !$contact_form) {
            wp_die("Validation invoked in wrong context");
        }
        $abaco_contact_form = $this->get_form($contact_form->title());
        if (!$abaco_contact_form) {
            return $result;
        }
        $data = $submission->get_posted_data();
        foreach ($submission->uploaded_files() as $file_key => $file) {
            $data[$file_key] = $file;
        }
        $abaco_contact_form->setup_data($data);
        $abaco_contact_form->validate($result, $form_tags);

        return $result;
    }
    public function insertion_hook($contact_form) {
        $abaco_contact_form = $this->get_form($contact_form->title());
        if ($abaco_contact_form) {
            $abaco_contact_form->insert();
        }
    }
    
    // Hook registration
    public function register_hooks() {
        add_action('wpcf7_contact_form', array($this, 'form_code_hook'));
        add_filter('wpcf7_form_tag', array($this, 'select_hook'), 10, 1);
        add_filter('wpcf7_validate', array($this, 'validation_hook'), 10, 2);
        add_action('wpcf7_before_send_mail', array($this, 'insertion_hook'));
    }
}

// A generic base class for submissions
abstract class ABACO_ContactFormImpl implements ABACO_ContactForm {
    public $data = array(); // actual semi-processed data
    private $m_custom_validators = array(); // array field_name -> function(value)
    private $m_fields; // Array name->Field; must match what is received in POST
    
    // Construction and form declaration
    public function __construct($fields) {
        $this->m_fields = $fields;
    }
    public final function code() {
        $res = array();
        foreach ($this->m_fields as $field) {
            $res[] = $field->code();
        }
        return implode('<br /><br />', $res);
    }
    
    // Data setup 
    public final function setup_data(array $data) {
        foreach ($this->m_fields as $field) {
            if ($field instanceof ABACO_DataField) {
                $name = $field->name;
                $this->data[$name] = $field->validate($data[$name]);
            }
        }
    }
    
    // Validation
    protected function add_validator($field_name, $callable) {
        $this->m_custom_validators[$field_name] = $callable;
    }
    public final function validate($result, $form_tags) {
        $valid = $this->basic_validation($result, $form_tags);
        if ($valid) {
            $this->custom_validation($result, $form_tags);
        }
    }
    protected function basic_validation($result, $form_tags) {
        $valid = true;
        foreach ($form_tags as $form_tag) {
            $name = $form_tag['name'];
            if (!isset($this->data[$name])) { // a tag we have no interest in
                continue;
            }
            $value = $this->data[$name];
            if ($value instanceof Exception) { // Basic validation failed
                $result->invalidate($form_tag, $value->getMessage());
                $valid = false;
                continue;
            }
        }
        return $valid;
    }
    protected function custom_validation($result, $form_tags) {
        foreach ($form_tags as $form_tag) {
            $name = $form_tag['name'];
            if (!isset($this->data[$name])) { // a tag we have no interest in
                continue;
            }
            $value = $this->data[$name];
            if (!isset($this->m_custom_validators[$name])) {
                continue;
            }
            try {
                call_user_func($this->m_custom_validators[$name], $value);
            } catch (Exception $ex) {
                $result->invalidate($form_tag, $ex->getMessage());
            }
        }
    }
}
