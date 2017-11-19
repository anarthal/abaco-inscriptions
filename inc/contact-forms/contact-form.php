<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/field.php';

abstract class ABACO_ContactForm {
    public $fields, $custom_validators;
    public function __construct($fields, $validators) {
        $this->fields = $fields;
        $this->custom_validators = $validators;
    }
    public abstract function insert(array $data);
}

class ABACO_ResultHelper {
    private $m_result;
    private $m_tags = [];
    public function __construct($result, $form_tags) {
        $this->m_result = $result;
        foreach ($form_tags as $tag) {
            $this->m_tags[$tag['name']] = $tag;
        }
    }
    public function invalidate($name, $message) {
        $this->m_result->invalidate($this->m_tags[$name], $message);
    }
}

class ABACO_Submission {
    public $contact_form;
    private $m_data;
    
    public function __construct(ABACO_ContactForm $form) {
        $this->contact_form = $form;
    }
    public function data() {
        return $this->m_data;
    }
    
    public function code() {
        $res = array_map(function($field) {
            return $field->code();
        }, $this->contact_form->fields);
        return implode('<br /><br />', $res);
    }
    
    public function setup_data(array $raw_data, $result) {
        $data = [];
        $name = null;
        try {
            // Basic validation
            foreach ($this->contact_form->fields as $field) {
                if (!$field instanceof ABACO_DataField) {
                    continue;
                }
                $name = $field->name;
                $value = isset($raw_data[$name]) ? $raw_data[$name] : '';
                $data[$name] = $field->validate($value);
            }
            // Custom validation
            foreach ($this->contact_form->custom_validators as $name_ => $validator) {
                $name = $name_;
                $data = call_user_func($validator, $data);
            }
            $this->m_data = $data;
        } catch (ABACO_ValidationError $err) {
            $result->invalidate($name, $err->getMessage());
        }
    }

    public function insert() {
        $this->contact_form->insert($this->m_data);
    }
    
}

class ABACO_ContactFormManager {
    private static $m_instance;
    private $m_form_factories = [];
    private $m_submissions = [];
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
    public function get_submission($title) {
        if (isset($this->m_submissions[$title])) {
            return $this->m_submissions[$title];
        } else if (isset($this->m_form_factories[$title])) {
            $form = call_user_func($this->m_form_factories[$title]);
            $submission = new ABACO_Submission($form);
            $this->m_submissions[$title] = $submission;
            return $submission;
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
        $submission = $this->get_submission($contact_form->title());
        if ($submission) {
            $contact_form->set_properties(array(
                'form' => $submission->code()
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
        
        $abaco_submission = $this->get_submission($contact_form->title());
        if ($abaco_submission) {
            $data = $submission->get_posted_data();
            foreach ($submission->uploaded_files() as $file_key => $file) {
                $data[$file_key] = $file;
            }
            $helper = new ABACO_ResultHelper($result, $form_tags);
            $abaco_submission->setup_data($data, $helper);
        }

        return $result;
    }
    public function insertion_hook($contact_form) {
        $submission = $this->get_submission($contact_form->title());
        if ($submission) {
            $submission->insert();
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