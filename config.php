<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// General
define('ABACO_SKIP_MAIL', true);
define('ABACO_ENABLE_CAPTCHA', false);
define('ABACO_ACTIVITY_AUTOPUBLISH', false);
define('ABACO_PARTICIPANT_TABLE_NAME', 'abaco_participants');
define('ABACO_PARTICIPANT_FORM_TITLE', 'participant');
define('ABACO_NIF_MANDATORY_AGE', 14);
define('ABACO_MINORITY_AGE', 18);
define('ABACO_COMPANY_FORM_TITLE', 'company');
define('ABACO_UPLOAD_DIR', 'abaco');
define('ABACO_ACTIVITY_FORM_TITLE', 'activity');
define('ABACO_ACTIVITY_DURATION_MIN', 1); // in half an hours
define('ABACO_ACTIVITY_DURATION_MAX', 12); // this is 6h
define('ABACO_ACTIVITY_POST_TYPE_NAME', 'abaco_activity');
define('ABACO_PREINSCRIPTION_FORM_TITLE', 'preinscription');
define('ABACO_PREINSCRIPTION_TABLE_NAME', 'abaco_preinscriptions');
define('ABACO_AGE_REFERENCE_DATE', '29-03-2018');

// Admin
define('ABACO_ADMIN_MAIN_SLUG', 'abaco');
define('ABACO_ADMIN_PARTICIPANT_SLUG', 'participants');
define('ABACO_ADMIN_COMPANY_SLUG', 'companies');
define('ABACO_ADMIN_SETTINGS_SLUG', 'abaco-settings');
define('ABACO_ADMIN_SETTINGS_SECTION', 'abaco-settings-section');
define('ABACO_SETTING_MINOR_AUTHORIZATION_URL', 'minor-authorization-url');
define('ABACO_REQUIRED_CAPABILITY', 'publish_pages');

// Booking day config
function abaco_booking_days() {
    return array(
        'THU' => __('Thursday', 'abaco'),
        'FRI' => __('Friday', 'abaco'),
        'SAT' => __('Saturday', 'abaco')
    );
}

function abaco_booking_days_select_options() {
    $res = abaco_booking_days();
    $res['NONE'] = __('None', 'abaco');
    return $res;
}


function abaco_gender_options() {
    return array(
        'MALE' => __('Male', 'abaco'),
        'FEMALE' => __('Female', 'abaco'),
        'NONBINARY' => __('Non binary', 'abaco')
    );
}

function abaco_document_type_options() {
    return array(
        'NIF' => __('NIF', 'abaco'),
        'PASSPORT' => __('Passport', 'abaco'),
        'UUID' => __('I do not have a NIF', 'abaco')
    );
}

function abaco_province_options() {
    return array(
        'OTHER' => __('Other', 'abaco'), 
        'ALBACETE' => 'Albacete', 
        'ALICANTE' => 'Alicante', 
        'ALMERIA' => 'Almería', 
        'ARABA' => 'Araba', 
        'ASTURIAS' => 'Asturias',
        'AVILA' => 'Ávila', 
        'BADAJOZ' => 'Badajoz', 
        'BALEARES' => 'Baleares', 
        'BARCELONA' => 'Barcelona', 
        'BIZKAIA' => 'Bizkaia', 
        'BURGOS' => 'Burgos', 
        'CACERES' => 'Cáceres', 
        'CADIZ' => 'Cádiz',
        'CANTABRIA' => 'Cantabria',
        'CASTELLON' => 'Castellón',
        'CEUTA' => 'Ceuta', 
        'CIUDAD_REAL' => 'Ciudad Real',
        'CORDOBA' => 'Córdoba', 
        'CUENCA' => 'Cuenca', 
        'GERONA' => 'Gerona', 
        'GRANADA' => 'Granada', 
        'GUADALAJARA' => 'Guadalajara', 
        'GIPUZKOA' => 'Gipuzkoa', 
        'HUELVA' => 'Huelva', 
        'HUESCA' => 'Huesca', 
        'JAEN' => 'Jaén', 
        'CORUNYA' => 'A Coruña',
        'RIOJA' => 'La Rioja',
        'PALMAS' => 'Las Palmas', 
        'LEON' => 'León', 
        'LLEIDA' => 'Lleida', 
        'LUGO' => 'Lugo',
        'MADRID' => 'Madrid', 
        'MALAGA' => 'Málaga', 
        'MELILLA' => 'Melilla', 
        'MURCIA' => 'Murcia', 
        'NAVARRA' => 'Navarra', 
        'ORENSE' => 'Orense', 
        'PALENCIA' => 'Palencia', 
        'PONTEVEDRA' => 'Pontevedra', 
        'SALAMANCA' => 'Salamanca',
        'TENERIFE' => 'Santa Cruz de Tenerife',
        'SEGOVIA' => 'Segovia', 
        'SEVILLA' => 'Sevilla', 
        'SORIA' => 'Soria', 
        'TARRAGONA' => 'Tarragona', 
        'TERUEL' => 'Teruel', 
        'TOLEDO' => 'Toledo', 
        'VALENCIA' => 'Valencia', 
        'VALLADOLID' => 'Valladolid',
        'ZAMORA' => 'Zamora',
        'ZARAGOZA' => 'Zaragoza'
    );
}

// Activity


function abaco_activity_custom_post_config() {
    return array(
        'labels' => array(
            'name' => __('Activities', 'abaco'),
            'singular_name' => __('Activity', 'abaco'),
            'add_new_item' => __('Add new activity', 'abaco'),
            'edit_item' => __('Edit activity', 'abaco'),
            'new_item' => __('New activity', 'abaco'),
            'view_item' => __('View activity', 'abaco'),
            'view_items' => __('View activities', 'abaco'),
            'search_items' => __('Search activities', 'abaco'),
            'not_found' => __('No activities found', 'abaco'),
            'not_found_in_trash' => __('No activities found in thrash', 'abaco'),
            'archives' => __('Activity list', 'abaco')
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
            'post-formats'
        ),
        'show_in_menu' => 'abaco'
    );
}

function abaco_activity_kind_options() {
    return array(
        'BOARD_ROLE' => __('Tabletop role game', 'abaco'),
        'LIVE_ROLE' => __('Live action role playing game', 'abaco'),
        'STRATEGY' => __('Strategy game', 'abaco'),
        'BOARD_GAMES' => __('Board games', 'abaco'),
        'OUTDOOR' => __('Outdoor activity', 'abaco'),
        'SOFTCOMBAT' => __('Soft Combat', 'abaco'),
        'CONTEST' => __('Contest', 'abaco'),
        'WORKSHOP' => __('Workshop', 'abaco'),
        'CARDS' => __('Card game', 'abaco'),
        'TOURNAMENT' => __('Tournament', 'abaco'),
        'CONFERENCE' => __('Conference', 'abaco'),
        'OTHER' => __('Other', 'abaco')
    );
}

function abaco_activity_duration_options() {
    $res = array();
    for ($i = ABACO_ACTIVITY_DURATION_MIN; $i <= ABACO_ACTIVITY_DURATION_MAX; ++$i) {
        $hours = floor($i / 2);
        $mins = ($i % 2) ? '30' : '00';
        $res[(string)($i * 30)] = "$hours:$mins h";
    }
    return $res;
}

function abaco_activity_duration_keys() {
    $res = array();
    for ($i = ABACO_ACTIVITY_DURATION_MIN; $i <= ABACO_ACTIVITY_DURATION_MAX; ++$i) {
        $res[] = (string) (30*$i);
    }
    return $res;
}

function abaco_activity_requested_time_options() {
    return array(
        'THU' => __('Thursday', 'abaco'),
        'FRI' => __('Friday', 'abaco'),
        'SAT' => __('Saturday', 'abaco'),
        'SUN' => __('Sunday', 'abaco')
    );
}

const ABACO_ACTIVITY_META_FIELDS = array(
    'kind',
    'duration',
    'requested_time',
    'participants_total',
    'participants_male',
    'participants_female',
    'observations',
    'participant_id',
    'allows_preinscription',
    'adult_content'
);

// Error messages
function abaco_negative_participants_message() {
    return __("Indifferent number of participants cannot be negative!", "abaco");
}

// ACF activity fields
function abaco_activity_acf_config() {
    return array(
        'id' => 'acf_activity_fields',
        'title' => 'activity_fields',
        'fields' => array(
            array(
                'key' => 'abaco_activity_kind',
                'label' => __('Activity kind', 'abaco'),
                'name' => 'kind',
                'type' => 'select',
                'required' => 1,
                'choices' => abaco_activity_kind_options(),
                'allow_null' => 0,
                'multiple' => 0,
            ),
            array(
                'key' => 'abaco_activity_duration',
                'label' => __('Activity approximate duration', 'abaco'),
                'name' => 'duration',
                'type' => 'select',
                'required' => 1,
                'choices' => abaco_activity_duration_options(),
                'allow_null' => 0,
                'multiple' => 0
            ),
            array(
                'key' => 'abaco_activity_total_participants',
                'label' => __('Total number of participants', 'abaco'),
                'name' => 'participants_total',
                'type' => 'number',
                'required' => 1,
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'min' => 0,
                'max' => '',
                'step' => '',
            ),
            array(
                'key' => 'abaco_activity_male_participants',
                'label' => __('Number of male participants', 'abaco'),
                'name' => 'participants_male',
                'type' => 'number',
                'required' => 1,
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'min' => 0,
                'max' => '',
                'step' => '',
            ),
            array(
                'key' => 'abaco_activity_female_participants',
                'label' => __('Number of female participants', 'abaco'),
                'name' => 'participants_female',
                'type' => 'number',
                'required' => 1,
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'min' => 0,
                'max' => '',
                'step' => '',
            ),
            array(
                'key' => 'abaco_activity_allows_preinscription',
                'label' => __('Allows web preinscription', 'abaco'),
                'name' => 'allows_preinscription',
                'type' => 'true_false',
                'message' => '',
                'default_value' => 0,
            ),
            array(
                'key' => 'abaco_activity_adult_content',
                'label' => __('This activity has adult content', 'abaco'),
                'name' => 'adult_content',
                'type' => 'true_false',
                'message' => '',
                'default_value' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'abaco_activity',
                    'order_no' => 0,
                    'group_no' => 0,
                ),
            ),
        ),
        'options' => array(
            'position' => 'normal',
            'layout' => 'no_box',
            'hide_on_screen' => array(
            ),
        ),
        'menu_order' => 0,
    );
}