<?php

namespace taxonomytaxi;

/*
*	filter for `posts_fields` to select joined taxonomy data into the main query
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_fields( $sql, &$wp_query ){
	foreach( taxonomies() as $tax ){
		$tax = esc_sql( $tax->name );
		
		$sql .= ", GROUP_CONCAT( DISTINCT(IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.name, NULL)) 
						ORDER BY T_AUTO.name ASC ) 
						AS `{$tax}_names`,
				   GROUP_CONCAT( DISTINCT(IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.slug, NULL)) 
				   		ORDER BY T_AUTO.name ASC ) 
				   		AS `{$tax}_slugs`";
	}
	
	// @TODO: this should be unnecessary with the above sql.  
	// refactor TaxoTaxi::posts_results to not need this 
	$sql .= ", GROUP_CONCAT( (TX_AUTO.taxonomy) ORDER BY T_AUTO.name ASC ) AS `concat_taxonomy`
			 , GROUP_CONCAT( (T_AUTO.slug) ORDER BY T_AUTO.name ASC ) AS `concat_slug`
			 , GROUP_CONCAT( (T_AUTO.name) ORDER BY T_AUTO.name ASC ) AS `concat_name`";
			 
	return $sql;
}

/*
*	filter for `posts_groupby` to group query by post id
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_groupby( $sql, &$wp_query ){
	global $wpdb;
	$sql = $wpdb->posts.".ID";
	
	return $sql;
}

/*
*	filter for `posts_join` to join taxonomy data into the main query
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_join( $sql, &$wp_query ){
	global $wpdb;
	$sql .= " LEFT JOIN ".$wpdb->term_relationships." TR_AUTO 
				ON ".$wpdb->posts.".ID = TR_AUTO.object_id
			  LEFT JOIN ".$wpdb->term_taxonomy." TX_AUTO 
			  	ON TR_AUTO.term_taxonomy_id = TX_AUTO.term_taxonomy_id 
			  	AND TX_AUTO.taxonomy NOT IN( 'category', 'post_tag' )
			  LEFT JOIN ".$wpdb->terms." T_AUTO 
			  	ON TX_AUTO.term_id = T_AUTO.term_id ";
			  	
	return $sql;
}

/*
*	filter for `posts_results` to parse taxonomy data from each $post into array for later display 
*	@param array WP_Post
*	@return array
*/
function posts_results( $posts ){
	foreach( $posts as &$post ){
		// TODO: refactor this to not need the extra sql fields in TaxoTaxi::posts_fields
		
		// if this is NULL, then no custom taxonomies were found for the post
		if( !$post->concat_taxonomy )
			continue;
			
		// get the unique taxonomies, use as array keys with empty array as value
		$order = explode( ',', $post->concat_taxonomy );
		
		$keys = array_keys( array_flip($order) );
		$taxonomies = array_fill_keys( $keys, array() );
		
		$slugs = explode( ',', $post->concat_slug );
		$names = explode( ',', $post->concat_name );
	
		foreach( $names as $k=>$name ){
			// there seems to be a problem with the length limit from group_concat
			// refactoring this method as above should get rid of the problem
			if( isset($order[$k]) )
				$taxonomies[ $order[$k] ][] = array( 'name' => $name,
													 'post_type' => $post->post_type,
													 'slug' => $slugs[$k], 
													 'taxonomy' => $order[$k] );
		}
		//var_dump($taxonomies);die();
		$props = array_merge( $post->to_array(), $taxonomies );
		$post = new \WP_Post( (object) $props );
	}
	
	return $posts;
}