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
        add_filter('pre_get_posts', __CLASS__ . '::pre_get_posts', 10, 1);
        add_filter('query_vars', __CLASS__ . '::query_vars', 10, 1);
        add_filter('request', __CLASS__ . '::request', 10, 1);
    }

    /**
     * 
     * 
     * @param $qv array
     * 
     * @return array
     */
    public static function query_vars($qv)
    {
        $qv[] = 'taxonomy_taxi';

        return $qv;
    }

    /**
     * Handle taxonomies where dropdown selected is View All or Show None
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
            if (isset($_GET['taxonomy_taxi'][$v->name])) {
                switch ($_GET['taxonomy_taxi'][$v->name]) {
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
     * Set the tax query for items selected 
     * Set the tax query for taxonomies sent as [None] from dropdowns
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

        // @TODO move this to static class var like show none, 
        // to avoid wrapping in isset? 
        // OR
        // remove self::$show_none and do the logic here with 0 and -1s
        if (!empty($wp_query->query['taxonomy_taxi'])) {
            $wp_query->query['taxonomy_taxi'] = array_filter(
                $wp_query->query['taxonomy_taxi'],
                function ($v) {
                    return !in_array($v, ["0", "-1"]);
                }
            );

            foreach ($wp_query->query['taxonomy_taxi'] as $k => $v) {
                $wp_query->query_vars['tax_query'][] = [
                    [
                        'taxonomy' =>  $k,
                        'field' => 'slug',
                        'terms' => $v,
                    ]
                ];
            }
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
