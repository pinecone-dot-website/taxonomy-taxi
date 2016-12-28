<?php 

namespace Taxonomy_Taxi;

class Edit{
	/**
	*
	*/
	public static function init(){
		add_filter( 'disable_categories_dropdown', '__return_true' );
		
		add_action( 'manage_pages_custom_column', __CLASS__.'::manage_posts_custom_column', 10, 2 );
		add_action( 'manage_posts_custom_column', __CLASS__.'::manage_posts_custom_column', 10, 2 );
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
}