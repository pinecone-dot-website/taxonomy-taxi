<?php
/*
Plugin Name: Taxonomy Taxi
Plugin URI: 
Description: Show custom taxonomies in /wp-admin/edit.php automatically
Version: .52
Author: Eric Eaglstun
Author URI: 
Photo Credit: http://www.flickr.com/photos/photos_mweber/
Photo URL: http://www.flickr.com/photos/photos_mweber/540970484/
Photo License: Attribution-NonCommercial 2.0 Generic (CC BY-NC 2.0)
*/

class TaxoTaxi{
	private static $wpdb;						// pretend that $wpdb is not global
	
	private static $post_type = '';				// single custom post type we are working with
	private static $taxonomies = array();		// populated by get_object_taxonomies, 
												// with default categories and post tags removed
	
	/*
	*
	*
	*/
	public static function setup(){
		if( !is_admin() )
			return;
		
		// TODO continue if user is on the edit screen
		
		global $wpdb;
		self::$wpdb = &$wpdb;
		
		require 'taxo-taxi_walker.php';
		
		add_action( 'query_vars', 'TaxoTaxi::query_vars' );
		
		add_filter( 'manage_posts_columns', 'TaxoTaxi::manage_posts_columns' );
		add_action( 'manage_posts_custom_column', 'TaxoTaxi::manage_posts_custom_column', 10, 2 );
		
		add_filter( 'posts_fields', 'TaxoTaxi::posts_fields' );
		add_filter( 'posts_groupby', 'TaxoTaxi::posts_groupby' );
		add_filter( 'posts_join', 'TaxoTaxi::posts_join' );
		add_filter( 'posts_orderby', 'TaxoTaxi::posts_orderby' );
		add_filter( 'posts_request', 'TaxoTaxi::posts_request' );
		add_filter( 'posts_results', 'TaxoTaxi::posts_results' );
		
		add_action( 'restrict_manage_posts', 'TaxoTaxi::restrict_manage_posts' );
	}
	
	/*
	*	setup class variables as soon as posssible, once $post_type is available
	*	@param array not used
	*	@return array 
	*/
	public static function query_vars( $default ){
		global $post_type;
		self::$post_type = $post_type;
		
		self::$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		
		// don't show default taxonomies twice
		unset( self::$taxonomies['category']);
		unset( self::$taxonomies['post_tag']);
		
		return $default;
	}
	
	/*
	*	filter for `manage_posts_columns` to add columns for custom taxonomies in Edit table
	*	@param array $headings
	*	@return array $headings
	*/
	public static function manage_posts_columns( $headings ){
		// default is to show before Categories
		$keys = array_keys( $headings );
		$key = array_search('categories', $keys);
		
		// arbitary placement in table
		if( !$key )
			$key = 2;
		
		// display the extra taxonomies after standard Categories
		$a = array_slice( $headings, 0, $key );
		$b = array_map( 'TaxoTaxi::_array_map_taxonomies', self::$taxonomies );
		$c = array_slice( $headings, $key );
		
		$headings = array_merge( $a, $b, $c );
		
		return $headings;
	}
	
	/*
	*	action for `manage_posts_custom_column` to echo column data inside each table cell
	*	@param string 
	*	@param int
	*	@return NULL
	*/
	public static function manage_posts_custom_column( $column_name, $post_id ){
		global $post;
		
		if( !isset($post->$column_name) || !count($post->$column_name) )
			return print '&nbsp;';

		$links = array_map( 'TaxoTaxi::_array_map_buildLinks', $post->$column_name );
		
		// array_unique is needed because of duplicates when sorting by categories or post tags( beheader )
		echo implode( ', ', array_unique($links) );
	}
	
	/*
	*	filter for `posts_fields` to select joined taxonomy data into the main query
	*	@param string 
	*	@return string
	*/
	public static function posts_fields( $sql ){
		foreach( self::$taxonomies as $tax ){
			$tax = self::$wpdb->escape( $tax->name );
			$sql .= ", GROUP_CONCAT( DISTINCT(IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.name, NULL)) ) AS `{$tax}_names`,
					   GROUP_CONCAT( DISTINCT(IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.slug, NULL)) ) AS `{$tax}_slugs`";
		}
		
		// TODO: this should be unnecessary with the above sql.  
		// refactor TaxoTaxi::posts_results to not need this 
		$sql .= ", GROUP_CONCAT( (TX_AUTO.taxonomy) ) AS `concat_taxonomy`
				 , GROUP_CONCAT( (T_AUTO.slug) ) AS `concat_slug`
				 , GROUP_CONCAT( (T_AUTO.name) ) AS `concat_name`";
		return $sql;
	}
	
	/*
	*	filter for `posts_groupby` to group query by post id
	*	@param string 
	*	@return string
	*/
	public static function posts_groupby( $sql ){
		$sql = self::$wpdb->posts.".ID";
		return $sql;
	}
	
	/*
	*	filter for `posts_join` to join taxonomy data into the main query
	*	@param string 
	*	@return string
	*/
	public static function posts_join( $sql ){
		$sql .= " LEFT JOIN ".self::$wpdb->term_relationships." TR_AUTO 
					ON ".self::$wpdb->posts.".ID = TR_AUTO.object_id
				  LEFT JOIN ".self::$wpdb->term_taxonomy." TX_AUTO 
				  	ON TR_AUTO.term_taxonomy_id = TX_AUTO.term_taxonomy_id 
				  	AND TX_AUTO.taxonomy NOT IN( 'category', 'post_tag' )
				  LEFT JOIN ".self::$wpdb->terms." T_AUTO 
				  	ON TX_AUTO.term_id = T_AUTO.term_id ";
		return $sql;
	}
	
	/*
	*	handy if you have Behaeder installed
	*	@param string 
	*	@return string
	*/
	public static function posts_orderby( $sql ){
		if( !isset($_GET['orderby']) || !array_key_exists($_GET['orderby'], self::$taxonomies) )
			return $sql;
		
		$order = isset( $_GET['order'] ) && $_GET['order'] == 'asc' ? 'asc' : 'desc';
		
		$sql = "`{$_GET['orderby']}_names` $order ";
		return $sql;
	}
	
	/*
	*	just for debugging, view the sql query that populates the Edit table
	*	@param string 
	*	@return string
	*/
	public static function posts_request( $sql ){
		//dbug( $sql );
		return $sql;
	}
	
	/*
	*	filter for `posts_results` to parse taxonomy daya from each $post into array for later display 
	*	@param array
	*	@return array
	*/
	public static function posts_results( $posts ){
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
		
			foreach( $names as $k=>$name){
				// there seems to be a problem with the length limit from group_concat
				// refactoring this method as above should get rid of the problem
				if( isset($order[$k]) )
					$taxonomies[ $order[$k] ][] = array( 'name' => $name, 
														 'slug' => $slugs[$k], 
														 'taxonomy' => $order[$k] );
			}
			
			
			
			$post = (object) array_merge( (array) $post, $taxonomies );
		}
		
		return $posts;
	}
	
	/*
	*	action for `restrict_manage_posts` 
	*	to display drop down selects for custom taxonomies
	*/
	public static function restrict_manage_posts(){
		$html = '';
		
		$Walker = new Walker_Taxo_Taxi;
		
		foreach( self::$taxonomies as $taxonomy ){
			$selected = isset( $_GET[$taxonomy->name] ) ? $_GET[$taxonomy->name] : FALSE;
			$sql = self::$wpdb->prepare( "SELECT T.*, TX.parent, IF( T.slug = %s, 'selected=\"selected\"', '' ) AS `selected` 
										  FROM ".self::$wpdb->terms." T
										  LEFT JOIN ".self::$wpdb->term_taxonomy." TX ON T.term_id = TX.term_id
										  WHERE TX.taxonomy = %s
										  ORDER BY T.name ASC", $selected, $taxonomy->name );
			$cats = self::$wpdb->get_results( $sql );

			$html .= '<select name="'.$taxonomy->name.'">';
			$html .= '<option value="">View all '.strtolower( $taxonomy->labels->name ).'</option>';
			$html .= $Walker->walk( $cats, 20 );
			$html .= '</select>';
		}
		
		echo $html;
	}
	
	/*
	*	array map callback to build the link in the Edit table
	*	@param array
	*	@return string
	*/
	public static function _array_map_buildLinks( $array ){
		return '<a href="?post_type=blog&'.$array['taxonomy'].'='.$array['slug'].'">'.$array['name'].'</a>';
	}
	
	/*
	*	array map callback
	*	@param object
	*	@return string
	*/
	public static function _array_map_taxonomies( $object ){
		return $object->labels->name;
	}
}

add_action( 'init', 'TaxoTaxi::setup' );
