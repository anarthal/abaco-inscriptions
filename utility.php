<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function abaco_admin_participant_link($id) {
    return 'admin.php?' . http_build_query(array(
            'page' => ABACO_ADMIN_PARTICIPANT_SLUG,
            'participant_id' => $id
    ));
}

function abaco_enum_to_string($enum, $value) {
    if (is_string($value)) {
        return (isset($enum[$value]) ? $enum[$value] : '?');
    } elseif (is_array($value)) {
        $res = array_map(function($elm) use ($enum) {
            return abaco_enum_to_string($enum, $elm);
        }, $value);
        return implode(', ', $res);
    }
    throw new Exception(__FUNCTION__ . ': unknown type');
}

function abaco_participant_db_table() {
    require_once __DIR__ . '/inc/db/participant-db-table.php';
    return ABACO_ParticipantDbTable::get_instance();
}

function abaco_activity_db_table() {
    require_once __DIR__ . '/inc/db/activity-db-table.php';
    return ABACO_ActivityDbTable::get_instance();
}

function abaco_preinscription_db_table() {
    require_once __DIR__ . '/inc/db/preinscription-db-table.php';
    return ABACO_PreinscriptionDbTable::get_instance();
}

function abaco_parse_bool($value) {
    return (bool)$value;
}

// unserializes array and performs error checking
function abaco_parse_array($value) {
    $res = @unserialize($value);
    if (!is_array($res)) {
        throw new InvalidArgumentException('abaco_parse_array');
    }
    return $res;
}

function abaco_full_upload_dir() {
    return wp_upload_dir()['basedir'] . '/' . ABACO_UPLOAD_DIR;
}

function abaco_compute_age($birth) {
    $ref = new DateTime(ABACO_AGE_REFERENCE_DATE);
    return $ref->diff($birth)->y;
}

function abaco_check_capability($cap = ABACO_REQUIRED_CAPABILITY) {
    if (!current_user_can($cap)) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
}

function abaco_is_number($c) {
    return ord($c) >= ord('0') && ord($c) <= ord('9');
}

function abaco_is_letter($c) {
    $ord = ord($c);
    return $ord >= ord('A') && $ord <= ord('Z'); // no Ñ in NIF
}

function abaco_is_valid_nif($value) {
    if (!is_string($value) || strlen($value) !== 9) {
        return false;
    }
    
    // Characters 1-7 should be numbers
    $value_upper = strtoupper($value);
    for ($i = 1; $i < 8; ++$i) {
        if (!abaco_is_number($value_upper[$i])) {
            return false;
        }
    }
    
    // DNI => NNNNNNNNL
    // Other nifs => LNNNNNNNX
    $first_number = abaco_is_number($value_upper[0]);
    $first_letter = abaco_is_letter($value_upper[0]);
    $last_number = abaco_is_number($value_upper[8]);
    $last_letter = abaco_is_letter($value_upper[8]);
    return
        ($first_number && $last_letter) || // DNI
        ($first_letter && ($last_letter || $last_number)); // other NIFs
    
}