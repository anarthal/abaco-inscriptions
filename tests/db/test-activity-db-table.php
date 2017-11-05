<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

_abaco_require('inc/db/activity-db-table.php');

class ActivityDbTableTest extends WP_UnitTestCase {
   function insert_image() {
        $attachment = [
            'post_mime_type' => 'image/jpeg',
            'post_title' => basename($this->img),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        $attach_id = wp_insert_attachment($attachment, $this->img, $this->act_id);
        $attach_data = wp_generate_attachment_metadata($attach_id, $this->img);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($this->act_id, $attach_id);
    }
    function setUp() {
        parent::setUp();
        $this->act_kind = array_keys(abaco_activity_kind_options())[0];
        $this->act_req_time = array_keys(abaco_activity_requested_time_options());
        $this->img = DIR_TESTDATA . '/images/test-image.jpg';
        $this->act_meta = [
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
        ];
        $this->act_data = array_merge($this->act_meta, [
            'name_' => 'myname',
            'description' => 'desc',
            'img' => $this->img
        ]);
        $this->table = new ABACO_ActivityDbTable();
        $this->act_id = $this->factory->post->create([
            'post_title' => 'title',
            'post_status' => 'publish',
            'post_content' => 'description',
            'post_type' => ABACO_ACTIVITY_POST_TYPE_NAME,
            'meta_input' => $this->act_meta
        ]);
    }
    
    // Drop
    function test_drop_removes_activities_attachments_metas() {
        $this->insert_image();
        $attach_id = get_post_thumbnail_id($this->act_id);
        $this->table->drop();
        $this->assertNull(get_post($this->act_id));
        $this->assertNull(get_post($attach_id));
        $this->assertEquals([], get_post_meta($this->act_id));
        $this->assertEquals([], get_post_meta($attach_id));
    }
    
    function test_drop_does_not_remove_other_posts_or_metas() {
        $id = $this->factory->post->create(['title' => 'other']);
        update_post_meta($id, 'key', 'value');
        $this->table->drop();
        $post = get_post($id);
        $this->assertInstanceOf(WP_Post::class, $post);
        $value = get_post_meta($id, 'key', true);
        $this->assertEquals('value', $value);
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
       
    // Get image URL
    function test_get_image_url_returns_downloadable_image() {
        $this->insert_image();
        $url = $this->table->get_image_url($this->act_id);
        $isUrl = (bool)filter_var($url, FILTER_VALIDATE_URL);
        $this->assertTrue($isUrl);
    }
    
    function test_get_image_url_no_post_returns_null() {
        $this->insert_image();
        $url = $this->table->get_image_url($this->act_id + 1);
        $this->assertNull($url);
    }
    
    function test_get_image_url_no_image_returns_null() {
        $url = $this->table->get_image_url($this->act_id);
        $this->assertNull($url);
    }
    
    // Query all
    function test_query_all_returns_list_of_activities() {
        $this->insert_image();
        $res = $this->table->query_all();
        $this->assertEquals(1, count($res));
        $act = $res[0];
        $this->assertEquals($this->act_id, $act['id']);
        $this->assertEquals('title', $act['name_']);
        $this->assertEquals('description', $act['description']);
        $this->assertEquals($this->act_kind, $act['kind']);
        $this->assertEquals(60, $act['duration']);
        $this->assertEquals($this->act_req_time, $act['requested_time']);
        $this->assertEquals(5, $act['participants_total']);
        $this->assertEquals(2, $act['participants_male']);
        $this->assertEquals(1, $act['participants_female']);
        $this->assertEquals(8, $act['participant_id']);
        $this->assertEquals(true, $act['allows_preinscription']);
        $this->assertEquals(false, $act['adult_content']);
        $url = $this->table->get_image_url($this->act_id);
        $this->assertEquals($url, $act['img']);
    }
    
    function test_query_all_no_image_field_null() {
        $act = $this->table->query_all()[0];
        $this->assertNull($act['img']);
    }
    
    function test_query_all_only_returns_activities() {
        $this->factory->post->create(['title' => 'nonactivity']);
        $res = $this->table->query_all();
        $this->assertEquals(1, count($res));
    }
    
    /**
     * @dataProvider post_status
     */
    function test_query_all_only_returns_published_activities($status) {
        wp_update_post(['ID' => $this->act_id, 'post_status' => $status]);
        $res = $this->table->query_all();
        $this->assertEmpty($res);
    }
    
    function post_status() {
        return [
            ['draft'],
            ['thrash'],
            ['pending']
        ];
    }
    
    function test_query_all_missing_metadata_dies() {
        delete_post_meta($this->act_id, 'kind');
        $this->expectException(WPDieException::class);
        $this->table->query_all();
    }
    
    // Insert tests
    function test_insert_creates_post() {
        $id = $this->table->insert($this->act_data);
        $post = get_post($id);
        $this->assertEquals('myname', $post->post_title);
        $this->assertEquals('desc', $post->post_content);
        $this->assertEquals(ABACO_ACTIVITY_POST_TYPE_NAME, $post->post_type);
        $this->assertEquals('pending', $post->post_status);
    }
    
    // We won't check values because we would need parsing
    function test_insert_creates_post_meta() {
        $id = $this->table->insert($this->act_data);
        $meta = get_post_meta($id);
        foreach (array_keys($this->act_meta) as $key) {
            $this->assertTrue(isset($meta[$key]));
        }
    }
    
    function test_insert_image_as_thumbnail() {
        $id = $this->table->insert($this->act_data);
        $thumb_id = get_post_thumbnail_id($id);
        $path = get_attached_file($thumb_id);
        $expected_path = wp_upload_dir()['basedir'] . '/' . ABACO_UPLOAD_DIR
            . '/' . (string)$id .  '/test-image.jpg';
        $this->assertEquals($expected_path, $path);
        $this->assertEquals(IMAGETYPE_JPEG, exif_imagetype($path));
        $this->assertNotNull($this->table->get_image_url($id));
        $this->assertEquals('image/jpeg', get_post_mime_type($thumb_id));
    }
    
    function test_insert_missing_metadata_dies() {
        unset($this->act_data['kind']);
        $this->expectException(WPDieException::class);
        $this->table->insert($this->act_data);
    }
}