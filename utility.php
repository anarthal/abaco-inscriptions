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