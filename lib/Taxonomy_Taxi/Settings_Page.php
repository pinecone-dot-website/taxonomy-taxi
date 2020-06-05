<?php

namespace Taxonomy_Taxi;

/**
 * 
 */
class Settings_Page
{
    /**
     * Attached to `admin_menu` action
     */
    public static function init()
    {
        add_settings_section(
            'taxonomy_taxi_settings_section',
            '', // subhead
            __CLASS__ . '::description',
            'taxonomy_taxi'
        );

        $post_types = get_post_types(
            [
                'show_ui' => true,
            ],
            'objects'
        );

        foreach ($post_types as $post_type) {
            add_settings_field(
                'taxonomy_taxi_setting_name-' . $post_type->name,
                $post_type->labels->name,
                function () use ($post_type) {
                    self::render_post_type($post_type->name);
                },
                'taxonomy_taxi',
                'taxonomy_taxi_settings_section'
            );
        }

        register_setting('taxonomy_taxi', 'taxonomy_taxi', __CLASS__ . '::save');

        add_options_page(
            'Taxonomy Taxi',
            'Taxonomy Taxi',
            'manage_options',
            'taxonomy_taxi',
            __CLASS__ . '::render_settings_page'
        );
    }

    /**
     *
     * @param $original string html
     * 
     * @return string html
     */
    public static function admin_footer_text($original = '')
    {
        return render(
            'admin/options-general_footer',
            [
                'version' => version(),
            ]
        );
    }

    /**
     * Callback for add_settings_section to render description field
     * 
     * @return void
     */
    public static function description()
    {
        echo sprintf('<pre>%s</pre>', version());
    }

    /**
     * Show direct link to settings page in plugins list
     * attached to `plugin_action_links` filter
     * 
     * @param $actions     array
     * @param $plugin_file string
     * @param $plugin_data array
     * @param $context     string
     * 
     * @return array
     */
    public static function plugin_action_links($actions, $plugin_file, $plugin_data, $context)
    {
        if ($plugin_file == 'taxonomy-taxi/_plugin.php' && $url = menu_page_url('taxonomy_taxi', false)) {
            $actions[] = sprintf('<a href="%s">Settings</a>', $url);
        }

        return $actions;
    }

    /**
     * Render the ui for each post type row
     * 
     * @param $post_type string
     * 
     * @return
     */
    public static function render_post_type($post_type = '')
    {
        $taxonomies = Settings::get_all_for_post_type($post_type);

        echo render(
            'admin/options-general_post-type',
            [
                'post_type' => $post_type,
                'taxonomies' => $taxonomies,
            ]
        );
    }

    /**
     * Callback for add_settings_field to render form ui
     */
    public static function render_settings_page()
    {
        wp_enqueue_style('taxonomy-taxi', plugins_url('public/admin/options-general.css', TAXONOMY_TAXI_FILE), [], version(), 'all');

        wp_enqueue_script('taxonomy-taxi', plugins_url('public/admin/options-general.js', TAXONOMY_TAXI_FILE), [], version(), 'all');

        echo render('admin/options-general', []);

        add_filter('admin_footer_text', __CLASS__ . '::admin_footer_text');
    }

    /**
     * Only save unchecked checkboxes
     * 
     * @param $form_data array
     * 
     * @return array
     */
    public static function save($form_data)
    {
        $post_types = get_post_types(array(
            'show_ui' => true
        ), 'objects');

        $saved = [];

        foreach ($post_types as $post_type => $object) {
            $all = get_object_taxonomies($post_type, 'names');
            $user_input = isset($form_data[$post_type]) ? $form_data[$post_type] : [];

            $saved[$post_type] = array_diff($all, $user_input);
        }

        // fix saving the options when there is no option saved in the db yet
        // i have no idea why this works
        // @TODO make this not suck
        add_filter(
            "pre_update_option_taxonomy_taxi",
            function ($value, $old_value, $option) use ($form_data) {
                if ($old_value === false) {
                    $value = $form_data;
                }

                return $value;
            },
            10,
            3
        );

        return $saved;
    }
}
