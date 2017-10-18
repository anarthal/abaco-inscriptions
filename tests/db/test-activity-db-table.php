<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/db/activity-db-table.php');

class ActivityDbTableTest extends WP_UnitTestCase {
    function setUp() {
        $this->act_kind = array_keys(abaco_activity_kind_options())[0];
        $this->act_req_time = array_keys(abaco_activity_requested_time_options());
        $this->act_id = $this->factory->post->create([
            'post_title' => 'title',
            'post_status' => 'publish',
            'post_content' => 'description',
            'post_type' => ABACO_ACTIVITY_POST_TYPE_NAME,
            'meta_input' => [
                'kind' => $this->act_kind,
                'duration' => 60,
                'requested_time' => $this->act_req_time,
                'participants_total' => 5,
                'participants_male' => 2,
                'participants_female' => 1,
                'observations' => 'obs',
                'participant_id' => 8,
                'allows_preinscription' => true,
                'adult_content' => false
            ]
        ]);
        $this->table = new ABACO_ActivityDbTable();
    }
    
    // Query by ID
    function test_query_by_id_record_exists_returns_it() {
        $res = $this->table->query_by_id($this->act_id, ['observations','kind']);
        $this->assertEquals('obs', $res->observations);
        $this->assertEquals($this->act_kind, $res->kind);
    }
    
    function test_query_by_id_applies_parser() {
        $res = $this->table->query_by_id($this->act_id, ['participants_total']);
        $this->assertTrue(is_int($res->participants_total));
    }
    
    function test_query_by_id_not_found_returns_null() {
        $res = $this->table->query_by_id($this->act_id + 1, ['participants_total']);
        $this->assertNull($res);
    }
    
    function test_query_by_id_only_returns_record_if_post_type_is_activity() {
        $id = $this->factory->post->create();
        $res = $this->table->query_by_id($id, ['participants_total']);
        $this->assertNull($res);
    }
    
    function test_query_by_id_returns_record_with_any_post_status() {
        $this->factory->post->create([
            'post_id' => $this->act_id,
            'post_status' => 'draft'
        ]);
        $res = $this->table->query_by_id($this->act_id, ['participants_total']);
        $this->assertFalse(is_null($res));
    }
    
    function test_query_by_id_missing_metadata_returns_null() {
        delete_post_meta($this->act_id, 'kind');
        $res = $this->table->query_by_id($this->act_id, ['kind']);
        $this->assertNull($res);
    }
}