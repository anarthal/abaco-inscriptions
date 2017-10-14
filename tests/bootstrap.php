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
