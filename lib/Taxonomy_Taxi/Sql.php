<?php

namespace Taxonomy_Taxi;

class Sql
{
    /**
     * 
     */
    public static function init()
    {
        add_filter('posts_fields', __CLASS__ . '::posts_fields', 10, 2);
        add_filter('posts_groupby', __CLASS__ . '::posts_groupby', 10, 2);
        add_filter('posts_join', __CLASS__ . '::posts_join', 10, 2);
        add_filter('posts_orderby', __CLASS__ . '::posts_orderby', 10, 2);

        add_filter('posts_results', __CLASS__ . '::posts_results', 10, 1);

        add_filter('posts_request', __CLASS__ . '::posts_request', 10, 2);
    }

    /**
     * Filter for `posts_fields` to select joined taxonomy data into the main query
     * 
     * @param $sql      string
     * @param $wp_query WP_Query
     * 
     * @return string
     */
    public static function posts_fields($sql, $wp_query)
    {
        foreach (Edit::get_taxonomies($wp_query->query_vars['post_type']) as $tax) {
            $tax = esc_sql($tax->name);

            $sql .= ", GROUP_CONCAT( 
							DISTINCT(
								IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.name, NULL)
							) 
							ORDER BY T_AUTO.name ASC 
					   ) AS `{$tax}_names`,
					   GROUP_CONCAT( 
					   		DISTINCT(
					   			IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.slug, NULL)
					   		) 
					   		ORDER BY T_AUTO.name ASC 
					   ) AS `{$tax}_slugs` /* Taxonomy_Taxi posts_fields {$tax} */";
        }

        return $sql;
    }

    /**
     * Filter for `posts_groupby` to group query by post id
     * 
     * @param $sql      string
     * @param $wp_query WP_Query
     * 
     * @return string
     */
    public static function posts_groupby($sql, $wp_query)
    {
        global $wpdb;

        $sql = $wpdb->posts . ".ID /* Taxonomy_Taxi posts_groupby */";

        return $sql;
    }

    /**
     * Filter for `posts_join` to join taxonomy data into the main query
     * 
     * @param $sql      string
     * @param $wp_query WP_Query
     * 
     * @return string
     */
    public static function posts_join($sql, $wp_query)
    {
        global $wpdb;

        $sql .= " /* Taxonomy_Taxi posts_join start */
                  LEFT JOIN " . $wpdb->term_relationships . " TR_AUTO 
					ON " . $wpdb->posts . ".ID = TR_AUTO.object_id
				  LEFT JOIN " . $wpdb->term_taxonomy . " TX_AUTO 
				  	ON TR_AUTO.term_taxonomy_id = TX_AUTO.term_taxonomy_id 
				  LEFT JOIN " . $wpdb->terms . " T_AUTO 
                      ON TX_AUTO.term_id = T_AUTO.term_id 
                  /* Taxonomy_Taxi posts_join end */";

        return $sql;
    }

    /**
     * Filter for `posts_orderby`
     * 
     * @param $sql      string
     * @param $wp_query WP_Query
     * 
     * @return string
     */
    public static function posts_orderby($sql, $wp_query)
    {
        if (isset($wp_query->query_vars['orderby']) && array_key_exists($wp_query->query_vars['orderby'], Edit::get_taxonomies($wp_query->query_vars['post_type']))) {
            $sql = $wp_query->query_vars['orderby'] . "_slugs " . $wp_query->query_vars['order'] . " /* Taxonomy_Taxi posts_orderby */";
        }

        return $sql;
    }

    /**
     * Filter for `posts_results` to parse taxonomy data from 
     * each $post into array for later display
     * 
     * @param $posts array WP_Post[]
     * 
     * @return array
     */
    public static function posts_results($posts)
    {
        // assigning to &$post was not working on wpengine...
        foreach ($posts as $k => $post) {
            $taxonomies = [];

            foreach (Edit::get_taxonomies($post->post_type) as $tax) {
                $tax_name = esc_sql($tax->name);

                $col = $tax_name . '_slugs';
                $slugs = explode(',', $post->$col);

                $col = $tax_name . '_names';
                $names = explode(',', $post->$col);

                $objects = array_fill(0, count($names), 0);

                array_walk(
                    $objects,
                    function (&$v, $k) use ($names, $slugs, $post, $tax_name) {
                        $v = [
                            'name' => $names[$k],
                            'post_type' => $post->post_type,
                            'slug' => $slugs[$k],
                            'taxonomy' => $tax_name,
                        ];
                    }
                );

                $taxonomies[$tax_name] = $objects;
            }

            $props = array_merge(
                $post->to_array(),
                ['taxonomy_taxi' => $taxonomies,]
            );
            $posts[$k] = new \WP_Post((object) $props);

            wp_cache_set($post->ID, $posts[$k], 'posts');
        }

        return $posts;
    }

    /**
     * Just for debugging, view the sql query that populates the Edit table
     * 
     * @param $sql      string
     * @param $wp_query WP_Query
     * 
     * @return string
     */
    public static function posts_request($sql, $wp_query)
    {
        return $sql;
    }
}
