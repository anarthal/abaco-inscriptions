<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class ABACO_SettingsPage {
    public static function draw() {
?>
    <div class="wrap">
        <h2><?php echo esc_html__('Settings', 'abaco'); ?></h2>
        <form action="options.php" method="POST">
            <?php
            settings_fields(ABACO_ADMIN_SETTINGS_SECTION);
            do_settings_sections(ABACO_ADMIN_SETTINGS_SLUG);
            submit_button();
            ?>
        </form>
    </div>
<?php
    }
    
    public static function register_settings() {
        add_settings_section(
            ABACO_ADMIN_SETTINGS_SECTION,
            '', // no title
            null, // no help text
            ABACO_ADMIN_SETTINGS_SLUG
        );
        add_settings_field(
            ABACO_SETTING_MINOR_AUTHORIZATION_URL,
            __('Minor authorization URL', 'abaco'),
            [self::class, 'minor_authorization_url'],
            ABACO_ADMIN_SETTINGS_SLUG,
            ABACO_ADMIN_SETTINGS_SECTION
        );
        register_setting(
            ABACO_ADMIN_SETTINGS_SECTION,
            ABACO_SETTING_MINOR_AUTHORIZATION_URL
        );
    }
    
    public static function minor_authorization_url() {
        $value = get_option(ABACO_SETTING_MINOR_AUTHORIZATION_URL);
        echo '<input type="text" name="' . ABACO_SETTING_MINOR_AUTHORIZATION_URL
            . '" value="' . esc_attr($value) . '" /> - <a href="'
            . esc_url($value) . '">' . esc_html__('Link', 'abaco') . '</a>';
    }
}