<?php

namespace Taxonomy_Taxi;

/**
 * Should only be used on wp-admin/edit.php and wp-admin/upload.php
 */
class Edit
{
    /**
     * The post type the editor is currently viewing
     * 
     * @var string
     */
    protected static $post_type = '';

    /**
     * Cache taxonomies associated with the post type.
     * Key - post type, Value - get_object_taxonomies() as objects
     * 
     * @var array
     */
    protected static $taxonomies = [];

    /**
     *
     * @param $post_type string
     */
    public static function init($post_type = '')
    {
        self::$post_type = $post_type;
        self::set_taxonomies($post_type);

        add_filter('disable_categories_dropdown', '__return_true');
        add_action('restrict_manage_posts', __CLASS__ . '::restrict_manage_posts', 10, 2);

        // edit.php and upload.php columns, not set on quick edit ajax
        $screen = get_current_screen();
        if ($screen) {
            add_filter('manage_' . $screen->id . '_sortable_columns', __CLASS__ . '::register_sortable_columns', 10, 1);
        }

        add_filter('manage_media_columns', __CLASS__ . '::manage_posts_columns', 10, 1);
        add_filter('manage_' . $post_type . '_posts_columns', __CLASS__ . '::manage_posts_columns', 10, 1);

        add_action('manage_media_custom_column', __CLASS__ . '::manage_posts_custom_column', 10, 2);
        add_action('manage_' . $post_type . '_posts_custom_column', __CLASS__ . '::manage_posts_custom_column', 10, 2);

        add_filter('request', __CLASS__ . '::request', 10, 1);
    }

    /**
     * attached to `manage_{$post_type}_posts_columns` and `manage_media_columns` filters
     * adds columns for custom taxonomies in Edit table
     * 
     * @param $headings array 
     * 
     * @return array
     */
    public static function manage_posts_columns($headings)
    {
        $keys = array_keys($headings);

        // first try to put custom columns starting at categories placement
        $key = array_search('categories', $keys);

        // if that doesnt work put before post comments
        if (!$key) {
            $key = array_search('comments', $keys);
        }

        // if that doesnt work put before date
        if (!$key) {
            $key = array_search('date', $keys) ? array_search('date', $keys) : false;
        }

        //  arbitary placement in table if it cant find category, comments, or date
        if (!$key) {
            $key = max(1, count($keys));
        }

        // going to replace stock columns with sortable ones
        unset($headings['categories']);
        unset($headings['tags']);

        $a = array_slice($headings, 0, $key);
        $b = array_map(
            function ($tax) {
                return $tax->label;
            },
            Settings::get_active_for_post_type(self::$post_type)
        );

        $c = array_slice($headings, $key);

        $headings = array_merge($a, $b, $c);

        return $headings;
    }

    /**
     * Attached to `manage_{$post_type}_posts_custom_column` and `manage_media_custom_column` actions
     * echos column data inside each table cell
     * 
     * @param $column_name string
     * @param $post_id int
     * 
     * @return NULL
     */
    public static function manage_posts_custom_column($column_name, $post_id)
    {
        global $post;

        if (!isset($post->taxonomy_taxi[$column_name]) || !count($post->taxonomy_taxi[$column_name])) {
            return print '&nbsp;';
        }

        $links = array_map(
            function ($column) {
                return sprintf(
                    '<a href="?post_type=%s&amp;%s=%s">%s</a>',
                    $column['post_type'],
                    sprintf('taxonomy_taxi[%s]', $column['taxonomy']),
                    $column['slug'],
                    $column['name']
                );
            },
            $post->taxonomy_taxi[$column_name]
        );

        echo implode(', ', $links);
    }

    /**
     * Register custom taxonomies for sortable columns
     * 
     * @param $columns array
     * 
     * @return array
     */
    public static function register_sortable_columns($columns)
    {
        $keys = array_map(
            function ($tax) {
                return $tax->name;
            },
            Settings::get_active_for_post_type(self::$post_type)
        );

        if (count($keys)) {
            $keys = array_combine($keys, $keys);
            $columns = array_merge($columns, $keys);
        }

        return $columns;
    }

    /**
     * fix bug in setting post_format query varaible
     * wp-includes/post.php function _post_format_request()
     *   $tax = get_taxonomy( 'post_format' );
     *   $qvs['post_type'] = $tax->object_type;
     *   sets global $post_type to an array
     * attached to `request` filter
     * 
     * @param $qvs array
     * 
     * @return array
     */
    public static function request($qvs)
    {
        if (isset($qvs['post_type']) && is_array($qvs['post_type'])) {
            $qvs['post_type'] = $qvs['post_type'][0];
        }

        return $qvs;
    }

    /**
     * Action for `restrict_manage_posts` to display drop down selects 
     * for custom taxonomies on /wp-admin/edit.php
     * 
     * @param $post_type string
     * @param $which     string
     * 
     * @return void
     */
    public static function restrict_manage_posts($post_type = '', $which = 'top')
    {
        $active_columns = Settings::get_active_for_post_type($post_type);

        foreach ($active_columns as $taxonomy => $props) {
            $query_var = sprintf('taxonomy_taxi[%s]', $props->name);

            $html = wp_dropdown_categories(
                [
                    'echo' => 0,
                    'hide_empty' => true,
                    'hide_if_empty' => false,
                    'hierarchical' => true,
                    'name' => $query_var,
                    'orderby' => 'name',
                    'selected' => isset($_GET['taxonomy_taxi'][$props->name]) ? $_GET['taxonomy_taxi'][$props->name] : false,
                    //'show_count' => TRUE,
                    'show_option_all' => 'View ' . $props->view_all,
                    'show_option_none' => sprintf('[ No %s ]', $props->label),
                    'taxonomy' => $taxonomy,
                    'walker' => new Walker,
                ]
            );

            echo $html;
        }
    }

    /**
     * Get the taxonomies associated with the post type
     * 
     * @param $post_type string
     * 
     * @return array
     */
    public static function get_taxonomies($post_type)
    {
        if (!isset(self::$taxonomies[$post_type])) {
            self::set_taxonomies($post_type);
        }

        return self::$taxonomies[$post_type];
    }

    /**
     * Store the taxonomies for the post type
     * for improved performace 
     * 
     * @param $post_type string
     * 
     * @return void
     */
    protected static function set_taxonomies($post_type)
    {
        self::$taxonomies[$post_type] = get_object_taxonomies($post_type, 'objects');
    }
}
