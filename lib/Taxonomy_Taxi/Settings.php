<?php

namespace Taxonomy_Taxi;

class Settings
{
    /**
     * Save user settings to prevent multiple calls to get_option
     * 
     * @var array
     */
    protected static $settings = [];

    /**
     * Gets the taxonomies for post type which are marked active from settings page
     * 
     * @param $post_type string
     * 
     * @return array
     */
    public static function get_active_for_post_type($post_type = '')
    {
        $active = array_filter(
            array_map(
                function ($tax) {
                    return $tax->checked ? $tax : false;
                },
                self::get_all_for_post_type($post_type)
            )
        );

        return $active;
    }

    /**
     * Gets all taxonomies for post type, and marks whether it is active from settings page
     * 
     * @param $post_type string
     * 
     * @return array
     */
    public static function get_all_for_post_type($post_type = '')
    {
        if (!isset(self::$settings[$post_type])) {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            $saved = self::get_saved($post_type);

            $checked = array_keys(array_diff_key($taxonomies, array_flip($saved)));

            $settings = [];
            foreach ($taxonomies as $tax => $props) {
                $view_all = array_filter(
                    [
                        $props->labels->all_items,
                        $props->name,
                    ]
                );

                $settings[$tax] = (object) [
                    'checked' => in_array($tax, $checked),
                    'label' => $props->label,
                    'query_var' => $props->query_var,
                    'name' => $tax,
                    'view_all' => reset($view_all),
                ];
            }

            self::$settings[$post_type] = $settings;
        }

        return self::$settings[$post_type];
    }

    /**
     * Gets the saved setting for post type - taxonomies not to display
     * 
     * @param $post_type string
     * 
     * @return array
     */
    protected static function get_saved($post_type = '')
    {
        $option = get_option('taxonomy_taxi');

        return isset($option[$post_type]) ? $option[$post_type] : [];
    }
}
