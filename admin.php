<?php

namespace Taxonomy_Taxi;

/*
*	setup page for dbug settings
*	add link to settings page under 'Settings' admin sidebar
*	update settings from $_POST
*	attached to `admin_menu` action
*/
function admin_menu(){
	add_options_page( 'Taxonomy Taxi', 'Taxonomy Taxi', 'manage_options', 'taxonomy_taxi', __NAMESPACE__.'\Settings_Page::view' );
}
add_action( 'admin_menu', __NAMESPACE__.'\admin_menu' );

add_action( 'admin_init', __NAMESPACE__.'\Settings_Page::register_page' );

/**
*	called on `load-edit.php` action
*	sets up the rest of the actions / filters
*/
function setup(){
	// fix for tag = 0 in drop down borking wp_query
	if( filter_input(INPUT_GET, 'tag') === "0" )
		unset( $_GET['tag'] );
		
	// set up post type and associated taxonomies
	$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';

	add_filter( 'request', __NAMESPACE__.'\Query::request' );
	
	add_filter( 'pre_get_posts', __NAMESPACE__.'\Query::pre_get_posts', 10, 1 );
	
	add_filter( 'posts_results', __NAMESPACE__.'\posts_results', 10, 1 );

	add_filter( 'request', __NAMESPACE__.'\request', 10, 1 );	
	add_action( 'restrict_manage_posts', __NAMESPACE__.'\restrict_manage_posts', 10, 1 );

	Edit::init( $post_type );
	Sql::init();
}
add_action( 'load-edit.php', __NAMESPACE__.'\setup' );

/**
*	attached to ajax for quick edit
*	subvert wp_ajax_inline_save()
*/
function inline_save(){
	require __DIR__.'/admin-ajax.php';
	wp_ajax_inline_save();
}
add_action( 'wp_ajax_inline-save', __NAMESPACE__.'\inline_save', 0 );

/**
*	filter for `posts_results` to parse taxonomy data from each $post into array for later display 
*	@param array WP_Post
*	@return array
*/
function posts_results( $posts ){
	// assigning to &$post was not working on wpengine...
	foreach( $posts as $k=>$post ){		
		$taxonomies = array();
		
		foreach( Edit::get_taxonomies() as $tax ){
			$tax_name = esc_sql( $tax->name );
			
			$col = $tax_name.'_slugs';
			$slugs = explode( ',', $post->$col );
			
			$col = $tax_name.'_names';
			$names = explode( ',', $post->$col );
			
			$objects = array_fill( 0, count($names), 0 );
			array_walk( $objects, function( &$v, $k ) use( $names, $slugs, $post, $tax_name ){
				switch( $tax_name ){
					case 'category':
						$tax_name = 'category_name';
						break;
						
					case 'post_tag':
						$tax_name = 'tag';
						break;
				}
						
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
		
		$posts[$k] = new \WP_Post( (object) $props );
	}
		
	return $posts;
}

/**
*	fix bug in setting post_format query varaible
*	wp-includes/post.php function _post_format_request()
*		$tax = get_taxonomy( 'post_format' );
*		$qvs['post_type'] = $tax->object_type;
*		sets global $post_type to an array
*	attached to `request` filter
*	@param array
*	@return array
*/
function request( $qvs ){
	if( isset($qvs['post_type']) && is_array($qvs['post_type']) )
		$qvs['post_type'] = $qvs['post_type'][0];
		
	return $qvs;
}

/**
*	action for `restrict_manage_posts` 
*	to display drop down selects for custom taxonomies
*/
function restrict_manage_posts(){
	foreach( Edit::get_taxonomies() as $taxonomy => $props ){
		$label = array_filter( array(
			$props->labels->all_items, 
			$props->name
		) );
			
		$html = wp_dropdown_categories( array(
			'echo' => 0,
			'hide_empty' => TRUE,
			'hide_if_empty' => TRUE,
			'hierarchical' => TRUE,
			'name' => $props->query_var,
			'selected' => isset( $_GET[$props->query_var] ) ? $_GET[$props->query_var] : FALSE,
			'show_option_all' => 'View '.reset($label),
			'show_option_none' => '[None]',
			'taxonomy' => $taxonomy,
			'walker' => new Walker_Taxo_Taxi
		) );
		
		echo $html;
	}
}

/**
*	set and get custom taxonomies for edit screen
*	@param array
*	@return array
*/
function taxonomies( $tax = NULL ){
	static $taxonomies = NULL;
	
	if( !is_null($tax) )
		$taxonomies = $tax;
		
	return $taxonomies;
}
