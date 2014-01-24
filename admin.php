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
	
	// set up post type and associated taxonomies
	$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';
	$tax = get_object_taxonomies( $post_type, 'objects' );
	
	// don't show default taxonomies twice
	unset( $tax['category'] );
	unset( $tax['post_tag'] );
	
	taxonomies( $tax );
	
	// filters and actions
	add_filter( 'manage_edit-'.$post_type.'_sortable_columns', __NAMESPACE__.'\register_sortable_columns' );
	add_filter( 'manage_posts_columns', __NAMESPACE__.'\manage_posts_columns' );
	
	add_action( 'manage_pages_custom_column', __NAMESPACE__.'\manage_posts_custom_column', 10, 2 );
	add_action( 'manage_posts_custom_column', __NAMESPACE__.'\manage_posts_custom_column', 10, 2 );
	
	add_filter( 'posts_fields', __NAMESPACE__.'\posts_fields', 10, 2 );
	add_filter( 'posts_groupby', __NAMESPACE__.'\posts_groupby', 10, 2 );
	add_filter( 'posts_join', __NAMESPACE__.'\posts_join', 10, 2 );
	
	add_filter( 'posts_request', __NAMESPACE__.'\posts_request' );
	add_filter( 'posts_results', __NAMESPACE__.'\posts_results' );

	add_filter( 'request', __NAMESPACE__.'\request' );	
	add_action( 'restrict_manage_posts', __NAMESPACE__.'\restrict_manage_posts' );
}

/*
*	array map callback to build the link in the Edit table
*	@param array
*	@return string
*/
function array_map_build_links( $array ){
	return '<a href="?post_type='.$array['post_type'].'&'.$array['taxonomy'].'='.$array['slug'].'">'.$array['name'].'</a>';
}
	
/*
*	array map callback
*	@param object
*	@return string
*/
function array_map_taxonomies( $object ){
	return $object->labels->name;
}

/*
*	attached to ajax for quick edit
*	subvert wp_ajax_inline_save()
*/
function inline_save(){
	setup();
	
	check_ajax_referer( 'inlineeditnonce', '_inline_edit' );

	if( !isset($_POST['post_ID']) || ! ( $post_id = (int) $_POST['post_ID'] ) )
		wp_die();

	if( 'page' == $_POST['post_type'] ){
		if( !current_user_can( 'edit_page', $post_id ) )
			wp_die( __( 'You are not allowed to edit this page.' ) );
	} else {
		if( !current_user_can( 'edit_post', $post_id ) )
			wp_die( __( 'You are not allowed to edit this post.' ) );
	}

	if( $last = wp_check_post_lock( $post_id) ){
		$last_user = get_userdata( $last );
		$last_user_name = $last_user ? $last_user->display_name : __( 'Someone' );
		printf( $_POST['post_type'] == 'page' ? __( 'Saving is disabled: %s is currently editing this page.' ) : __( 'Saving is disabled: %s is currently editing this post.' ),	esc_html( $last_user_name ) );
		wp_die();
	}

	$data = &$_POST;

	$post = get_post( $post_id, ARRAY_A );
	$post = wp_slash($post); //since it is from db

	$data['content'] = $post['post_content'];
	$data['excerpt'] = $post['post_excerpt'];

	// rename
	$data['user_ID'] = get_current_user_id();

	if( isset($data['post_parent']) )
		$data['parent_id'] = $data['post_parent'];

	// status
	if( isset($data['keep_private']) && 'private' == $data['keep_private'] )
		$data['post_status'] = 'private';
	else
		$data['post_status'] = $data['_status'];

	if( empty($data['comment_status']) )
		$data['comment_status'] = 'closed';
		
	if( empty($data['ping_status']) )
		$data['ping_status'] = 'closed';

	// Hack: wp_unique_post_slug() doesn't work for drafts, so we will fake that our post is published.
	if( !empty( $data['post_name'] ) && in_array( $post['post_status'], array( 'draft', 'pending')) ){
		$post['post_status'] = 'publish';
		$data['post_name'] = wp_unique_post_slug( $data['post_name'], 
												  $post['ID'], 
												  $post['post_status'], 
												  $post['post_type'], 
												  $post['post_parent'] );
	}

	// update the post
	edit_post();
	
	$post_id = $_POST['post_ID'];
	$post_type = $_POST['post_type']; 
	
 	$posts = get_posts( array('p' => $post_id, 
 							  'post_type' => $post_type, 
 							  'post_status' => 'any', 
 							  'suppress_filters' => FALSE, 
 							  'posts_per_page' => 1) ); 
 	
 	if( !isset($posts[0]) ) 
 		return;
 	
 	$level = 0;
 	$parent = $posts[0]->post_parent;

	while( $parent > 0 ){
		$parent_post = get_post( $parent );
		$parent = $parent_post->post_parent;
		$level++;
	}
							  
 	$wp_list_table = _get_list_table( 'WP_Posts_List_Table', array('screen' => $_POST['screen']) ); 
 	$wp_list_table->display_rows( array($posts[0]), $level ); 
 	
 	die();
}

/*
*	attached to `manage_posts_columns` filter
*	adds columns for custom taxonomies in Edit table
*	@param array $headings
*	@return array $headings
*/
function manage_posts_columns( $headings ){
	// default is to show before Categories
	$keys = array_keys( $headings );
	$key = array_search( 'categories', $keys );
	
	// arbitary placement in table
	if( !$key )
		$key = 2;
	
	// display the extra taxonomies after standard Categories
	$a = array_slice( $headings, 0, $key );
	$b = array_map( __NAMESPACE__.'\array_map_taxonomies', taxonomies() );
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
	
	if( !isset($post->$column_name) || !count($post->$column_name) )
		return print '&nbsp;';

	$links = array_map( __NAMESPACE__.'\array_map_build_links', $post->$column_name );
	
	// array_unique is needed because of duplicates when sorting by categories or post tags( beheader )
	echo implode( ', ', array_unique($links) );
}

/*
*	just for debugging, view the sql query that populates the Edit table
*	@param string 
*	@return string
*/
function posts_request( $sql ){
	return $sql;
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
		$html = wp_dropdown_categories( array(
			'echo' => 0,
			'hierarchical' => TRUE,
			'name' => $taxonomy,
			'selected' => isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : FALSE,
			'show_option_all' => 'View All '.$props->labels->all_items,
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