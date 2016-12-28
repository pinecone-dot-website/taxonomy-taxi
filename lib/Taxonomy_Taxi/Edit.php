<?php 

namespace Taxonomy_Taxi;

class Edit{
	protected static $post_type = '';
	protected static $taxonomies = array();

	/**
	*
	*	@param string
	*/
	public static function init( $post_type = '' ){
		self::$post_type = $post_type;
		self::set_taxonomies( $post_type );

		add_filter( 'disable_categories_dropdown', '__return_true' );
		
		// edit.php columns
		add_filter( 'manage_edit-'.$post_type.'_sortable_columns', __CLASS__.'::register_sortable_columns', 10, 1 );	
		add_filter( 'manage_pages_columns', __CLASS__.'::manage_posts_columns', 10, 1 );
		add_filter( 'manage_posts_columns', __CLASS__.'::manage_posts_columns', 10, 1 );
		add_action( 'manage_pages_custom_column', __CLASS__.'::manage_posts_custom_column', 10, 2 );
		add_action( 'manage_posts_custom_column', __CLASS__.'::manage_posts_custom_column', 10, 2 );
	}

	/**
	*	attached to `manage_posts_columns` filter
	*	adds columns for custom taxonomies in Edit table
	*	@param array $headings
	*	@return array $headings
	*/
	public static function manage_posts_columns( $headings ){
		//  arbitary placement in table if it cant replace categories
		$keys = array_keys( $headings );
		$key = array_search( 'categories', $keys );

		if( !$key )
			$key = max( 1, count($keys) );
			
		// going to replace stock columns with sortable ones
		unset( $headings['categories'] );
		unset( $headings['tags'] );
		
		$a = array_slice( $headings, 0, $key );
		$b = array_map( function($tax){
			return $tax->label;
		}, Settings::get_active_for_post_type(self::$post_type) );
		
		$c = array_slice( $headings, $key );
		
		$headings = array_merge( $a, $b, $c );
	
		return $headings;
	}

	/**
	*	attached to `manage_posts_custom_column` action
	*	echos column data inside each table cell
	*	@param string 
	*	@param int
	*	@return NULL
	*/
	public static function manage_posts_custom_column( $column_name, $post_id ){
		global $post;
		
		if( !isset($post->taxonomy_taxi[$column_name]) || !count($post->taxonomy_taxi[$column_name]) )
			return print '&nbsp;';
		
		$links = array_map( function($column){
			return sprintf( '<a href="?post_type=%s&amp;%s=%s">%s</a>', 
				$column['post_type'],
				$column['taxonomy'],
				$column['slug'],
				$column['name'] 
			);
		}, $post->taxonomy_taxi[$column_name] );

		echo implode( ', ', $links );
	}

	/**
	*	register custom taxonomies for sortable columns
	*	@param array
	*	@return array
	*/
	public static function register_sortable_columns( $columns ){
		$keys = array_map( function($tax){
			return $tax->name;
		}, Settings::get_active_for_post_type(self::$post_type) );

		if( count($keys) ){
			$keys = array_combine( $keys, $keys );
			$columns = array_merge( $columns, $keys ); 
		}

		return $columns;
	}

	public static function get_taxonomies(){
		return self::$taxonomies;
	}

	/**
	*
	*/
	protected static function set_taxonomies( $post_type ){
		self::$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	}
}