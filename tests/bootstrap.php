<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Abaco
 */

define('_ABACO_PLUGIN_DIR', dirname(dirname(__FILE__)));
function _abaco_require($file) {
    require_once _ABACO_PLUGIN_DIR . '/' . $file;
}

/*function invoke_method($obj, $method, $args) {
    $field = new ABACO_EmailField('name', 'display', true);
    $class = new ReflectionClass('ABACO_EmailField');
    $method = $class->getMethod('m_trim');
    $method->setAccessible(true);
    $res = $method->invokeArgs($field, ['invalid']);
    $this->assertEquals($res, '');
}*/
class _Unprotecter {
    public function __construct($obj) {
        $this->obj = $obj;
    }
    public function __call($name, $args) {
        $method = new ReflectionMethod(get_class($this->obj), $name);
        $method->setAccessible(true);
        try {
            return $method->invokeArgs($this->obj, $args);
        } finally {
            $method->setAccessible(false);
        }
        
    }
}
function unprotect($obj) {
    return new _Unprotecter($obj);
}

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    _abaco_require('abaco.php');
    //require dirname(dirname(dirname(__FILE__))) . '/contact-form-7/wp-contact-form-7.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
