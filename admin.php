<?php

namespace Taxonomy_Taxi;

/**
*	setup settings link and callbacks
*	attached to `admin_menu` action
*/
function admin_menu(){
	Settings_Page::init();
}
add_action( 'admin_menu', __NAMESPACE__.'\admin_menu' );

/**
*	called on `load-edit.php` action and from wp_ajax_inline-save
*	sets up the rest of the actions / filters
*/
function setup(){
	// fix for tag = 0 in drop down borking wp_query
	if( filter_input(INPUT_GET, 'tag') === "0" )
		unset( $_GET['tag'] );
	
	// set up post type and associated taxonomies
	switch( $GLOBALS['pagenow'] ){
		case 'upload.php':
			$post_type = 'attachment';
			break;

		default:
			$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';
			break;
	}

	Edit::init( $post_type );
	Query::init();
	Sql::init();
}
add_action( 'load-edit.php', __NAMESPACE__.'\setup' );
add_action( 'load-upload.php', __NAMESPACE__.'\setup' );

/**
*	attached to ajax for quick edit
*	subvert wp_ajax_inline_save()
*/
function inline_save(){
	require __DIR__.'/admin-ajax.php';
	wp_ajax_inline_save();
}
add_action( 'wp_ajax_inline-save', __NAMESPACE__.'\inline_save', 0 );