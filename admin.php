<?php

namespace taxonomytaxi;

add_action( 'wp_ajax_inline-save', __NAMESPACE__.'\inline_save', 0 );
add_action( 'load-edit.php', __NAMESPACE__.'\TaxoTaxi::setup' );

/*
*
*/	
class TaxoTaxi{
	private static $wpdb;						// pretend that $wpdb is not global
	
	private static $post_type = '';				// single custom post type we are working with
	private static $taxonomies = array();		// the taxonomies associated with current post type
												// populated by get_object_taxonomies, 
												// with default categories and post tags removed
	
	/*
	*	called on `load-edit.php` action
	*	sets up class variables and the rest of the actions / filters
	*/
	public static function setup(){
		global $wpdb;
		self::$wpdb = &$wpdb;
		
		require __DIR__.'/lib/walker-taxo-taxi.php';
		
		add_filter( 'query', __NAMESPACE__.'\TaxoTaxi::query_vars' );
		
		self::$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';
		
		add_filter( 'manage_edit-'.self::$post_type.'_sortable_columns', __NAMESPACE__.'\TaxoTaxi::register_sortable_columns' );
		add_filter( 'manage_posts_columns', __NAMESPACE__.'\TaxoTaxi::manage_posts_columns' );
		
		add_action( 'manage_pages_custom_column', __NAMESPACE__.'\TaxoTaxi::manage_posts_custom_column', 10, 2 );
		add_action( 'manage_posts_custom_column', __NAMESPACE__.'\TaxoTaxi::manage_posts_custom_column', 10, 2 );
		
		add_filter( 'posts_fields', __NAMESPACE__.'\TaxoTaxi::posts_fields' );
		add_filter( 'posts_groupby', __NAMESPACE__.'\TaxoTaxi::posts_groupby' );
		add_filter( 'posts_join', __NAMESPACE__.'\TaxoTaxi::posts_join' );
		add_filter( 'posts_request', __NAMESPACE__.'\posts_request' );
		add_filter( 'posts_results', __NAMESPACE__.'\TaxoTaxi::posts_results' );

		add_filter( 'request', __NAMESPACE__.'\request' );	
		add_action( 'restrict_manage_posts', __NAMESPACE__.'\TaxoTaxi::restrict_manage_posts' );
	}
	
	/*
	*	setup class variables as soon as posssible, once $post_type is available
	*	attached to `query` filter
	*	@param array not used
	*	@return array 
	*/
	public static function query_vars( $default ){
		self::$taxonomies = get_object_taxonomies( self::$post_type, 'objects' );
		
		// don't show default taxonomies twice
		unset( self::$taxonomies['category'] );
		unset( self::$taxonomies['post_tag'] );
		
		return $default;
	}
	
	/*
	*	attached to `manage_posts_columns` filter
	*	adds columns for custom taxonomies in Edit table
	*	@param array $headings
	*	@return array $headings
	*/
	public static function manage_posts_columns( $headings ){
		// default is to show before Categories
		$keys = array_keys( $headings );
		$key = array_search( 'categories', $keys );
		
		// arbitary placement in table
		if( !$key )
			$key = 2;
		
		// display the extra taxonomies after standard Categories
		$a = array_slice( $headings, 0, $key );
		$b = array_map( __NAMESPACE__.'\array_map_taxonomies', self::$taxonomies );
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
	public static function manage_posts_custom_column( $column_name, $post_id ){
		global $post;
		
		if( !isset($post->$column_name) || !count($post->$column_name) )
			return print '&nbsp;';

		$links = array_map( __NAMESPACE__.'\array_map_build_links', $post->$column_name );
		
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
	*	filter for `posts_results` to parse taxonomy data from each $post into array for later display 
	*	@param array WP_Post
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
	
	/*
	*	register custom taxonomies for sortable columns
	*	@param array
	*	@return array
	*/
	public static function register_sortable_columns( $columns ){
		$keys = array_keys( self::$taxonomies );
		$keys = array_combine( $keys, $keys );
		//var_dump( $keys ); die();
		
		$columns = array_merge( $columns, $keys ); 
		
		
		return $columns;
	}
	
	/*
	*	action for `restrict_manage_posts` 
	*	to display drop down selects for custom taxonomies
	*/
	public static function restrict_manage_posts(){
		foreach( self::$taxonomies as $taxonomy => $props ){
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
*
*/
function get_taxonomies(){
	static $taxonomies = NULL;
	
	if( !$taxonomies ){
	
	}
	
	return $taxonomies;
}

/*
*	attached to ajax for quick edit
*	subvert wp_ajax_inline_save()
*/
function inline_save(){
	TaxoTaxi::setup();
	
	check_ajax_referer( 'inlineeditnonce', '_inline_edit' );

	if ( ! isset($_POST['post_ID']) || ! ( $post_ID = (int) $_POST['post_ID'] ) )
		wp_die();

	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_ID ) )
			wp_die( __( 'You are not allowed to edit this page.' ) );
	} else {
		if ( ! current_user_can( 'edit_post', $post_ID ) )
			wp_die( __( 'You are not allowed to edit this post.' ) );
	}

	if ( $last = wp_check_post_lock( $post_ID ) ) {
		$last_user = get_userdata( $last );
		$last_user_name = $last_user ? $last_user->display_name : __( 'Someone' );
		printf( $_POST['post_type'] == 'page' ? __( 'Saving is disabled: %s is currently editing this page.' ) : __( 'Saving is disabled: %s is currently editing this post.' ),	esc_html( $last_user_name ) );
		wp_die();
	}

	$data = &$_POST;

	$post = get_post( $post_ID, ARRAY_A );
	$post = wp_slash($post); //since it is from db

	$data['content'] = $post['post_content'];
	$data['excerpt'] = $post['post_excerpt'];

	// rename
	$data['user_ID'] = get_current_user_id();

	if ( isset($data['post_parent']) )
		$data['parent_id'] = $data['post_parent'];

	// status
	if ( isset($data['keep_private']) && 'private' == $data['keep_private'] )
		$data['post_status'] = 'private';
	else
		$data['post_status'] = $data['_status'];

	if ( empty($data['comment_status']) )
		$data['comment_status'] = 'closed';
	if ( empty($data['ping_status']) )
		$data['ping_status'] = 'closed';

	// Hack: wp_unique_post_slug() doesn't work for drafts, so we will fake that our post is published.
	if ( ! empty( $data['post_name'] ) && in_array( $post['post_status'], array( 'draft', 'pending' ) ) ) {
		$post['post_status'] = 'publish';
		$data['post_name'] = wp_unique_post_slug( $data['post_name'], $post['ID'], $post['post_status'], $post['post_type'], $post['post_parent'] );
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