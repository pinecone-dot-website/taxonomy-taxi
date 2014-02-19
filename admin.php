<?php

namespace taxonomytaxi;

add_action( 'wp_ajax_inline-save', __NAMESPACE__.'\inline_save', 0 );
add_action( 'load-edit.php', __NAMESPACE__.'\setup' );

/*
*	called on `load-edit.php` action
*	sets up class variables and the rest of the actions / filters
*/
function setup(){
	require __DIR__.'/lib/walker-taxo-taxi.php';
	require __DIR__.'/sql.php';
	
	// fix for tag = 0 in drop down borking wp_query
	if( isset($_GET['tag']) && $_GET['tag'] === "0" )
		unset( $_GET['tag'] );
		
	// set up post type and associated taxonomies
	$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';
	$tax = get_object_taxonomies( $post_type, 'objects' );
	
	taxonomies( $tax );
	
	// filters and actions
	add_filter( 'manage_edit-'.$post_type.'_sortable_columns', __NAMESPACE__.'\register_sortable_columns', 10, 1 );
	add_filter( 'manage_posts_columns', __NAMESPACE__.'\manage_posts_columns', 10, 1 );
	
	add_action( 'manage_pages_custom_column', __NAMESPACE__.'\manage_posts_custom_column', 10, 2 );
	add_action( 'manage_posts_custom_column', __NAMESPACE__.'\manage_posts_custom_column', 10, 2 );
	
	add_filter( 'posts_fields', __NAMESPACE__.'\posts_fields', 10, 2 );
	add_filter( 'posts_groupby', __NAMESPACE__.'\posts_groupby', 10, 2 );
	add_filter( 'posts_join', __NAMESPACE__.'\posts_join', 10, 2 );
	
	add_filter( 'posts_request', __NAMESPACE__.'\posts_request', 10, 1 );
	add_filter( 'posts_results', __NAMESPACE__.'\posts_results', 10, 1 );

	add_filter( 'request', __NAMESPACE__.'\request', 10, 1 );	
	add_action( 'restrict_manage_posts', __NAMESPACE__.'\restrict_manage_posts', 10, 1 );
}

/*
*	attached to ajax for quick edit
*	subvert wp_ajax_inline_save()
*/
function inline_save(){
	require __DIR__.'/admin-ajax.php';
	wp_ajax_inline_save();
}

/*
*	attached to `manage_posts_columns` filter
*	adds columns for custom taxonomies in Edit table
*	@param array $headings
*	@return array $headings
*/
function manage_posts_columns( $headings ){
	//  arbitary placement in table if it cant replace categories
	$keys = array_keys( $headings );
	$key = array_search( 'categories', $keys );
	if( !$key )
		$key = 3;
		
	// going to replace stock columns with sortable ones
	unset( $headings['categories'] );
	unset( $headings['tags'] );
	
	$a = array_slice( $headings, 0, $key );
	$b = array_map( function($taxonomy){
		return $taxonomy->labels->name;
	}, taxonomies() );
	$c = array_slice( $headings, $key );
	
	$headings = array_merge( $a, $b, $c );
	
	return $headings;
}

/*
*	attached to `manage_posts_custom_column` action
*	echos column data inside each table cell
*	@param string 
*	@param int
*	@return NULL
*/
function manage_posts_custom_column( $column_name, $post_id ){
	global $post;
	
	if( !isset($post->taxonomy_taxi[$column_name]) || !count($post->taxonomy_taxi[$column_name]) )
		return print '&nbsp;';
	
	$links = array_map( function($column){
		return '<a href="?post_type='.$column['post_type'].'&amp;'.$column['taxonomy'].'='.$column['slug'].'">'.$column['name'].'</a>';
	}, $post->taxonomy_taxi[$column_name] );

	echo implode( ', ', $links );
}

/*
*	filter for `posts_results` to parse taxonomy data from each $post into array for later display 
*	@param array WP_Post
*	@return array
*/
function posts_results( $posts ){
	foreach( $posts as &$post ){		
		$taxonomies = array();
		
		foreach( taxonomies() as $tax ){
			$tax_name = esc_sql( $tax->name );
			
			$col = $tax_name.'_slugs';
			$slugs = explode( ',', $post->$col );
			
			$col = $tax_name.'_names';
			$names = explode( ',', $post->$col );
			
			$objects = array_fill( 0, count($names), 0 );
			array_walk( $objects, function( &$v, $k ) use( $names, $slugs, $post, $tax_name ){
				$v = array(
					'name' => $names[$k],
					'post_type' => $post->post_type,
					'slug' => $slugs[$k], 
					'taxonomy' => $tax_name
				);
			});
			
			$taxonomies[$tax_name] = $objects;
		}
		
		$props = array_merge( $post->to_array(), array('taxonomy_taxi' => $taxonomies) );
		$post = new \WP_Post( (object) $props );
	}
	
	return $posts;
}

/*
*	fix bug in setting post_format query varaible
*	wp-includes/post.php function _post_format_request()
*		$tax = get_taxonomy( 'post_format' );
*		$qvs['post_type'] = $tax->object_type;
*		sets global $post_type to an array
*	@param array
*	@return array
*/
function request( $qvs ){
	if( isset($qvs['post_type']) && is_array($qvs['post_type']) )
		$qvs['post_type'] = $qvs['post_type'][0];
		
	return $qvs;
}

/*
*	register custom taxonomies for sortable columns
*	@param array
*	@return array
*/
function register_sortable_columns( $columns ){
	$keys = array_keys( taxonomies() );
	$keys = array_combine( $keys, $keys );
	
	$columns = array_merge( $columns, $keys ); 
	
	return $columns;
}

/*
*	action for `restrict_manage_posts` 
*	to display drop down selects for custom taxonomies
*/
function restrict_manage_posts(){
	foreach( taxonomies() as $taxonomy => $props ){
		if( $taxonomy == 'category' )
			continue;
				
		$html = wp_dropdown_categories( array(
			'echo' => 0,
			'hierarchical' => TRUE,
			'name' => $props->query_var,
			'selected' => isset( $_GET[$props->query_var] ) ? $_GET[$props->query_var] : FALSE,
			'show_option_all' => 'View '.$props->labels->all_items,
			'taxonomy' => $taxonomy,
			'walker' => new Walker_Taxo_Taxi
		) );
		
		echo $html;
	}
}

/*
*
*	@param
*	@return array
*/
function taxonomies( $tax = NULL ){
	static $taxonomies = NULL;
	
	if( !is_null($tax) )
		$taxonomies = $tax;
		
	return $taxonomies;
}