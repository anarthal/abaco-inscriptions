<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class ABACO_Field {
    public $name;
    public function __construct($name) {
        $this->name = $name;
    }
    public abstract function code();
    
    public static function escape($elm) {
        return str_replace(array('[', ']', '"'), '', esc_html($elm));
    }
}

interface ABACO_Validator {
    public function validate($input);
}

abstract class ABACO_DataField extends ABACO_Field implements ABACO_Validator {
    public final function validate($input) {
        try {
            if (!isset($input)) {
                $input = '';
            }
            return $this->m_validate($input);
        } catch (Exception $ex) {
            return $ex;
        }
    }
    protected abstract function m_validate($input);
    
    // Helpers
    protected static function check_string($input) {
        if (!is_string($input)) {
            throw new Exception("Invalid type");
        }
    }
    protected static function check_array($input) {
        if (!is_array($input)) {
            throw new Exception("Invalid type");
        }
    }
}

// Generates a field entry with no value associated
class ABACO_EchoField extends ABACO_Field {
    private $m_data;
    public function __construct($name, $data) {
        parent::__construct($name);
        $this->m_data = $data;
    }
    public function code() {
        return $this->m_data;
    }
}

// Params necessary for generating the field form entry
class ABACO_FieldParams {
    public $display_name;
    public $mandatory;
    public $asterisk;
    public $cf7_options;
    public $element_id;
    public function __construct($display_name, $mandatory, array $opts) {
        $this->display_name = $display_name;
        $this->mandatory = $mandatory;
        $defaults = array(
            'asterisk' => $mandatory,
            'cf7_options' => '',
            'element_id' => ''
        );
        foreach (wp_parse_args($opts, $defaults) as $key => $value) {
            $this->$key = $value;
        }
    }
    public function tag_asterisk() {
        return $this->mandatory ? '*' : '';
    }
    public function label_asterisk() {
        return $this->asterisk ? ' <span style="color:red">[*]</span>' : '';
    }
}

// Generates a form entry between <label> elements
abstract class ABACO_LabelField extends ABACO_DataField {
    protected $params;
    public function __construct($name, $display_name, $mandatory, array $opts = []) {
        parent::__construct($name);
        $this->params = new ABACO_FieldParams($display_name,
            $mandatory, $opts);
    }
    public function code() {
        return '<label>' .
            self::escape($this->params->display_name) .
            $this->params->label_asterisk() .
            '<br />[' .
            $this->tag_type() . $this->params->tag_asterisk() .
            ' ' . $this->name .
            ' ' . $this->params->cf7_options .
            ']</label>';
    }
    abstract protected function tag_type();
}

abstract class ABACO_StringField extends ABACO_LabelField {
    protected final function m_validate($input) {
        self::check_string($input);
        $res = $this->m_trim($input);
        if ($this->params->mandatory && $res === '') {
            throw new Exception(__("This field is mandatory.", "abaco"));
        }
        return $res;
    }
    protected abstract function m_trim($input);
}

class ABACO_EmailField extends ABACO_StringField {
    protected function m_trim($input) {
        return sanitize_email($input);
    }
    protected function tag_type() {
        return 'email';
    }
}

class ABACO_TextField extends ABACO_StringField {
    private $m_nocase;
    public function __construct($name, $display_name, $mandatory,
            $nocase = false, array $opts = []) {
        parent::__construct($name, $display_name, $mandatory, $opts);
        $this->m_nocase = $nocase;
    }
    protected function m_trim($input) {
        if ($this->m_nocase) {
            $input = strtolower($input);
        }
        return sanitize_text_field($input);
    }
    protected function tag_type() {
        return 'text';
    }
}

class ABACO_FileField extends ABACO_TextField {
    protected function tag_type() {
        return 'file';
    }
}

class ABACO_TelField extends ABACO_TextField {
    protected function tag_type() {
        return 'tel';
    }
}

class ABACO_TextareaField extends ABACO_StringField {
    private $m_noshortcodes;
    public function __construct($name, $display_name, $mandatory,
            $noshortcodes = false, array $opts = []) {
        parent::__construct($name, $display_name, $mandatory, $opts);
        $this->m_noshortcodes = $noshortcodes;
    }
    protected function m_trim($input) {
        if ($this->m_noshortcodes) {
            $input = strip_shortcodes($input);
        }
        return sanitize_textarea_field($input);
    }
    public function tag_type() {
        return 'textarea';
    }
}


class ABACO_NumberField extends ABACO_LabelField {
    public function m_validate($input) {
        self::check_string($input);
        if (!is_numeric($input)) {
            throw new Exception(__("This field must be a number.", "abaco"));
        }
        return intval($input);
    }
    public function tag_type() {
        return 'number';
    }
}


class ABACO_SelectField extends ABACO_LabelField {
    private $m_select_opts;
    public function __construct($name, $display_name,
            array $select_opts, array $opts=[]) {
        parent::__construct($name, $display_name, true, $opts);
        $this->m_select_opts = $select_opts;
    }
    protected function m_validate($input) {
        self::check_string($input);
        if (!in_array($input, $this->m_select_opts, true)) {
            throw new Exception(__("An invalid option was selected.", "abaco"));
        }
        return $input;
    }
    public function tag_type() {
        return 'select';
    }
}

class ABACO_DateField extends ABACO_LabelField {
    protected function m_validate($input) {
        return new DateTime($input);
    }
    public function tag_type() {
        return 'date';
    }
}

// Checboxes
class ABACO_CheckboxField extends ABACO_DataField {
    private $m_params;
    public function __construct($name, $display_name, array $opts=[]) {
        parent::__construct($name);
        $this->m_params = new ABACO_FieldParams($display_name, false, $opts);
    }
    protected function m_validate($input) {
        self::check_array($input);
        if (empty($input) || $input[0] === "") {
            return 0;
        } else {
            return 1;
        }
    }
    public function code() {
        $elm_id = $this->m_params->element_id;
        return '<p' .
            ($elm_id !== '' ? " id=\"$elm_id\"" : '') . '>' .
            '[checkbox ' .
            $this->name . ' ' . $this->m_params->cf7_options .
            ' "' . self::escape($this->m_params->display_name) . '"] </p>';
    }
}

class ABACO_MulticheckboxField extends ABACO_DataField {
    private $m_select_opts;
    private $m_params;
    public function __construct($name, $display_name, $mandatory,
            array $select_opts, array $opts=[]) {
        parent::__construct($name);
        $this->m_select_opts = $select_opts;
        $this->m_params = new ABACO_FieldParams($display_name, $mandatory, $opts);
    }
    protected function m_validate($input) {
        self::check_array($input);
        $res = array_intersect($this->m_select_opts, $input);
        if ($this->m_params->mandatory && empty($res)) {
            throw new Exception(__('This field is mandatory.', 'abaco'));
        }
        return $res;
    }
    public function code() {
        $elm_id = $this->m_params->element_id;
        return '<p' .
            ($elm_id !== '' ? " id=\"$elm_id\"" : '') . '>' .
            self::escape($this->m_params->display_name) .
            $this->m_params->label_asterisk() .
            '<br />[checkbox' . $this->m_params->tag_asterisk() .
            ' ' . $this->name . ' ' . $this->m_params->cf7_options .
            ']</p>';
    }
}

class ABACO_SubmitField extends ABACO_Field {
    public function __construct() {
        parent::__construct('submit');
    }
    public function code() {
        return '<label>[submit "' .
            self::escape('Submit') .
            '"]</label>';
    }
}