<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// All these actions will enhance the activity show when using the Nirvana theme.
// They will have no effect when using ther themes
class ABACO_NirvanaActivity {
    // Add featured image to activity single view
    public static function show_img($content) {
        global $post;
        $img_html = '';
        if ($post->post_type === ABACO_ACTIVITY_POST_TYPE_NAME &&
            function_exists('nirvana_set_featured_thumb')) {
            ob_start();
            nirvana_set_featured_thumb();
            $img_html = ob_get_clean();
        }
        return $img_html . $content;
    }
    
    // Removes date & author from the displayed activity meta
    public static function remove_unwanted_meta_data() {
        global $nirvanas;
        global $post;
        if ($post->post_type === ABACO_ACTIVITY_POST_TYPE_NAME) {
            $nirvanas['nirvana_single_show']['date'] = false;
            $nirvanas['nirvana_single_show']['author'] = false;
            $nirvanas['nirvana_blog_show']['date'] = false;
            $nirvanas['nirvana_blog_show']['author'] = false;
        }
    }
    
    // Displays activity metadata
    public static function display_meta_data() {
        global $post;
        $fields = ['kind', 'participants_total', 'adult_content', 'duration'];
        $act = abaco_activity_db_table()->query_by_id($post->ID, $fields);
        if ($act === null) {
            return;
        }
        $formatted_meta = self::format_meta_data($act);
        echo self::view_meta_data($formatted_meta);
    }
    
    // Registers the above functions as hooks
    public static function register_hooks() {
        add_filter('the_content', [__CLASS__, 'show_img']);
        add_action('cryout_before_content_hook',
            [__CLASS__, 'remove_unwanted_meta_data']);
        add_action('cryout_post_meta_hook', 
            [__CLASS__, 'display_meta_data'], 20);
    }
    
    // Given an activity, transform it into displayable contents
    // We return array<array<text, icon_name>>
    protected static function format_meta_data($activity) {
        $kind = abaco_enum_to_string(abaco_activity_kind_options(),
            $activity->kind);
        $participants = sprintf(__('%s participants', 'abaco'),
            $activity->participants_total);
        $duration = sprintf(__('Duration: %s', 'abaco'),
            abaco_enum_to_string(abaco_activity_duration_options(),
                (string)$activity->duration));
        $res = [
            [$kind, 'tag'],
            [$participants, 'author'],
            [$duration, 'time']
        ];
        if ($activity->adult_content) {
            $res[] = [__('Adult content', 'abaco'), 'warning'];
        }
        return $res;
    }
    
    // Transforms an array of fomatted metadata (see above function)
    // into HTML
    protected static function view_meta_data($data) {
        $res = '';
        foreach ($data as $row) {
            $res .= '<span><i class="crycon-' . $row[1] .
                ' crycon-metas"></i>' .
                esc_html($row[0]) . '</span>';
        }
        return $res;
    }
}



