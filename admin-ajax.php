<?php

namespace Taxonomy_Taxi;

/*
*
*
*/
function wp_ajax_inline_save(){
	setup();
	
	check_ajax_referer( 'inlineeditnonce', '_inline_edit' );

	if( !isset($_POST['post_ID']) || !($post_id = (int) $_POST['post_ID']) )
		wp_die();

	if( 'page' == $_POST['post_type'] ){
		if( !current_user_can( 'edit_page', $post_id ) )
			wp_die( __( 'You are not allowed to edit this page.') );
	} else {
		if( !current_user_can( 'edit_post', $post_id ) )
			wp_die( __( 'You are not allowed to edit this post.') );
	}

	if( $last = wp_check_post_lock($post_id) ){
		$last_user = get_userdata( $last );
		$last_user_name = $last_user ? $last_user->display_name : __( 'Someone' );
		printf( $_POST['post_type'] == 'page' ? __( 'Saving is disabled: %s is currently editing this page.' ) : __( 'Saving is disabled: %s is currently editing this post.' ),	esc_html( $last_user_name) );
		wp_die();
	}

	$data = &$_POST;

	$post = get_post( $post_id, ARRAY_A );
	$post = wp_slash( $post ); //since it is from db

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
	if( !empty($data['post_name'] ) && in_array($post['post_status'], array('draft', 'pending')) ){
		$post['post_status'] = 'publish';
		$data['post_name'] = wp_unique_post_slug( $data['post_name'], 
												  $post['ID'], 
												  $post['post_status'], 
												  $post['post_type'], 
												  $post['post_parent'] );
	}

	// update the post
	edit_post();
	
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