<?php

namespace Taxonomy_Taxi;

/**
*   builds nested drop down selects in wp-admin/edit.php
*   sets value to be term slug rather than term id
*/
class Walker extends \Walker_CategoryDropdown
{
    /**
    *
    *   @param string
    *   @param WP_Term
    *   @param int
    *   @param array
    *   @param
    *   @return
    */
    public function start_el(&$output, $term, $depth = 0, $args = array(), $current_object_id = 0)
    {
        $pad = str_repeat( '&nbsp;', $depth * 2 );
        $cat_name = apply_filters( 'list_cats', $term->name, $term );
        
        if ($args['show_count']) {
            $cat_name .= '&nbsp;&nbsp;('. $term->count .')';
        }

        if (!isset($args['value'])) {
            $args['value'] = ( $term->taxonomy != 'category' ? 'slug' : 'id' );
        }

        $output .= render( 'admin/edit-option', array(
            'depth' => $depth,
            'display_name' => $pad.$cat_name,
            'selected' => $args['selected'],
            'term' => $term
        ) );
    }
}
