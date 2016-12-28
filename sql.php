<?php

namespace Taxonomy_Taxi;

/**
*	filter for `posts_fields` to select joined taxonomy data into the main query
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_fields( $sql, &$wp_query ){
	foreach( taxonomies() as $tax ){
		$tax = esc_sql( $tax->name );
		
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
				   ) AS `{$tax}_slugs`";
	}
	 
	return $sql;
}

/**
*	filter for `posts_orderby` 
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_orderby( $sql, &$wp_query ){
	global $wpdb;
	
	if( isset($wp_query->query_vars['orderby']) && array_key_exists($wp_query->query_vars['orderby'], taxonomies()) )
		$sql = $wp_query->query_vars['orderby']."_slugs ".$wp_query->query_vars['order'];
	
	return $sql;
}

/**
*	just for debugging, view the sql query that populates the Edit table
*	@param WP_Query
*	@param string 
*	@return string
*/
function posts_request( $sql, &$wp_query ){
	return $sql;
}