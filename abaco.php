<?php
/*
 * Plugin name: ABACO Inscriptions
 * Description: Manage ABACO inscriptions.
 * Author: AnarThal
 * Version: 0.0.0
 * Text Domain: abaco
 * Domain Path: /languages
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utility.php';
require_once __DIR__ . '/inc/contact-forms/contact-form.php';

// Activation
function abaco_activate_plugin () {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        wp_die(__('Contact Form 7 plugin is needed to run this plugin.', 'abaco'));
    }
    abaco_participant_db_table()->create();
}
register_activation_hook(__FILE__, 'abaco_activate_plugin');

// Init
add_action('init', function() {
   register_post_type(ABACO_ACTIVITY_POST_TYPE_NAME,
        abaco_activity_custom_post_config());
});
add_filter('wpcf7_skip_mail', function() {
   return ABACO_SKIP_MAIL;
});

// Translation & contact forms
add_action('plugins_loaded', function() {
    load_plugin_textdomain('abaco', false,
        basename(dirname(__FILE__)) . '/languages/');   
    $manager = ABACO_ContactFormManager::get_instance();
    $manager->add_form(
        ABACO_PARTICIPANT_FORM_TITLE,
        function() {
            require_once __DIR__ . '/inc/contact-forms/participant.php';
            return new ABACO_ParticipantForm(abaco_participant_db_table());
        }
    );
    $manager->add_form(
        ABACO_COMPANY_FORM_TITLE,
        function() {
            require_once __DIR__ . '/inc/contact-forms/company.php';
            return new ABACO_CompanyForm(abaco_participant_db_table());
        }
    );
    $manager->add_form(
        ABACO_ACTIVITY_FORM_TITLE,
        function() {
            require_once __DIR__ . '/inc/contact-forms/activity.php';
            return new ABACO_ActivityForm(abaco_participant_db_table(),
                abaco_activity_db_table());
        }
    );
});

// Admin panels
function abaco_register_menus() {
    require_once __DIR__ . '/inc/admin/admin.php';
    $admin = new ABACO_Admin();
    $admin->register_hooks();
}
add_action('admin_menu', 'abaco_register_menus', 9);

// Client scripts and styles
add_action('wpcf7_enqueue_scripts', function() {
    // Script dependencies
    wp_enqueue_script(
        'moment',
        plugin_dir_url(__FILE__) . 'js/moment.min.js'
    );
    wp_enqueue_script(
        'abaco-jquery-ui',
        plugin_dir_url(__FILE__) . 'js/jquery-ui/jquery-ui.min.js',
        array('jquery')
    );
    
    // Our script
    wp_register_script(
        'abaco_client_validation',
        plugin_dir_url(__FILE__) . 'js/client_validation.js',
        array('jquery', 'abaco-jquery-ui', 'moment')
    );
    $params = array(
        'totalParticipantsNegative' => esc_html(
                abaco_negative_participants_message()),
        'minorityAge' => ABACO_MINORITY_AGE
    );
    wp_localize_script(
        'abaco_client_validation',
        'abacoClientValidationParams',
        $params
    );
    wp_enqueue_script('abaco_client_validation');
    
    // Styles
    wp_enqueue_style(
        'jquery-ui-css',
        plugin_dir_url(__FILE__) . 'js/jquery-ui/jquery-ui.min.css'
    );
    wp_enqueue_style(
        'abaco-css',
        plugin_dir_url(__FILE__) . 'css/abaco.css'
    );
});

add_action('wp_head', function() {
    require_once __DIR__ . '/inc/frontend/activity.php';
    ABACO_NirvanaActivity::register_hooks();
});

