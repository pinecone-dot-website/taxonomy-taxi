<?php

namespace Taxonomy_Taxi;

class Query
{
    protected static $show_none = [];

    /**
     *
     */
    public static function init()
    {
        add_filter('request', __CLASS__ . '::request', 10, 1);
        add_filter('pre_get_posts', __CLASS__ . '::pre_get_posts', 10, 1);
    }

    /**
     * Handle taxonomies selected View All or Show None
     * parsed in pre_get_posts()
     * 
     * @param $qv array
     * 
     * @return array
     */
    public static function request($qv)
    {
        $tax = get_taxonomies([], 'objects');

        foreach ($tax as $v) {
            if (isset($qv[$v->query_var])) {
                switch ($qv[$v->query_var]) {
                    case '-1':
                        // posts with no terms in taxonomy - [ No {$term->name} ]
                        self::$show_none[] = $v->name;
                    case '0':
                        // fix bug in tag = 0 in drop down borking wp_query
                        unset($qv[$v->query_var]);

                        break;
                }
            }
        }

        return $qv;
    }

    /**
     * Handle the taxonomies sent as [None] from request()
     * 
     * @param $wp_query WP_Query
     * 
     * @return WP_Query
     */
    public static function pre_get_posts($wp_query)
    {
        if (!isset($wp_query->query_vars['tax_query'])) {
            $wp_query->query_vars['tax_query'] = [];
        }

        foreach (self::$show_none as $taxonomy) {
            $wp_query->query_vars['tax_query'][] = [
                [
                    'operator' => 'NOT EXISTS',
                    'taxonomy' => $taxonomy,
                ]
            ];
        }

        if (count($wp_query->query_vars['tax_query']) > 1 && !isset($wp_query->query_vars['tax_query']['relation'])) {
            $wp_query->query_vars['tax_query']['relation'] = 'AND';
        }

        // 'id=>parent' or 'ids' bypasses `posts_results` filter
        unset($wp_query->query_vars['fields']);

        return $wp_query;
    }
}
