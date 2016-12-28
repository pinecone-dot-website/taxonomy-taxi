<?php 

namespace Taxonomy_Taxi;

class Sql{
	public static function init(){
		add_filter( 'posts_fields', __CLASS__.'::posts_fields', 10, 2 );
		add_filter( 'posts_groupby', __CLASS__.'::posts_groupby', 10, 2 );
		add_filter( 'posts_join', __CLASS__.'::posts_join', 10, 2 );
		add_filter( 'posts_orderby', __CLASS__.'::posts_orderby', 10, 2 );

		add_filter( 'posts_request', __CLASS__.'::posts_request', 10, 2 );
	}

	/**
	*	filter for `posts_fields` to select joined taxonomy data into the main query
	*	@param string 
	*	@param WP_Query
	*	@return string
	*/
	public static function posts_fields( $sql, &$wp_query ){
		foreach( Edit::get_taxonomies() as $tax ){
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
	*	filter for `posts_groupby` to group query by post id
	*	@param string 
	*	@param WP_Query
	*	@return string
	*/
	public static function posts_groupby( $sql, &$wp_query ){
		global $wpdb;

		if( $wp_query->is_main_query() ){
			$sql = $wpdb->posts.".ID";
		}
		
		return $sql;
	}

	/**
	*	filter for `posts_join` to join taxonomy data into the main query
	*	@param string 
	*	@param WP_Query
	*	@return string
	*/
	public static function posts_join( $sql, &$wp_query ){
		global $wpdb;

		if( $wp_query->is_main_query() ){
			$sql .= " LEFT JOIN ".$wpdb->term_relationships." TR_AUTO 
						ON ".$wpdb->posts.".ID = TR_AUTO.object_id
					  LEFT JOIN ".$wpdb->term_taxonomy." TX_AUTO 
					  	ON TR_AUTO.term_taxonomy_id = TX_AUTO.term_taxonomy_id 
					  LEFT JOIN ".$wpdb->terms." T_AUTO 
					  	ON TX_AUTO.term_id = T_AUTO.term_id ";
		}	

		return $sql;
	}

	/**
	*	filter for `posts_orderby` 
	*	@param string 
	*	@param WP_Query
	*	@return string
	*/
	public static function posts_orderby( $sql, &$wp_query ){
		global $wpdb;
		
		if( isset($wp_query->query_vars['orderby']) && array_key_exists($wp_query->query_vars['orderby'], Edit::get_taxonomies()) )
			$sql = $wp_query->query_vars['orderby']."_slugs ".$wp_query->query_vars['order'];
		
		return $sql;
	}


	/**
	*	just for debugging, view the sql query that populates the Edit table
	*	@param WP_Query
	*	@param string 
	*	@return string
	*/
	public static function posts_request( $sql, &$wp_query ){
		return $sql;
	}
}