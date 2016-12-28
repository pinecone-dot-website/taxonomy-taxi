<?php 

namespace Taxonomy_Taxi;

class Sql{
	public static function init(){
		add_filter( 'posts_groupby', __CLASS__.'::posts_groupby', 10, 2 );
		add_filter( 'posts_join', __CLASS__.'::posts_join', 10, 2 );
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
}