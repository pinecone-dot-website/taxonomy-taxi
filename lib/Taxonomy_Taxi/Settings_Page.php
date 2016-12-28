<?php

namespace Taxonomy_Taxi;

class Settings_Page {
	/**
	*
	*/
	public static function init(){
		self::register_page();
		add_options_page( 'Taxonomy Taxi', 'Taxonomy Taxi', 'manage_options', 'taxonomy_taxi', __CLASS__.'::view' );
	}

	/**
	*
	*/
	public static function register_page(){
		add_settings_section(
			'taxonomy_taxi_settings_section',
			'Taxonomy Taxi',
			__CLASS__.'::description',
			'taxonomy_taxi'
		);
	 	
	 	$post_types = get_post_types( array(
	 		'show_ui' => TRUE
	 	), 'objects' );

	 	foreach( $post_types as $post_type ){
		 	add_settings_field(
				'taxonomy_taxi_setting_name-'.$post_type->name,
				$post_type->labels->name,
				function() use($post_type){
					self::post_type( $post_type->name );
				},
				'taxonomy_taxi',
				'taxonomy_taxi_settings_section'
			);
	 	}

	 	register_setting( 'taxonomy_taxi', 'taxonomy_taxi', __CLASS__.'::save' );
	}

	/**
	*	callback for add_settings_section to render description field
	*/
	public static function description(){
		echo sprintf( 'version %s', version() );
	}

	/**
	*	render the ui for each post type row
	*	@param string
	*	@return 
	*/
	public static function post_type( $post_type = '' ){ 
		$taxonomies = Settings::get_all_for_post_type( $post_type );

		echo render( 'admin/options-general_post-type', array(
			'post_type' => $post_type,
			'taxonomies' => $taxonomies
		) );
	}

	/**
	*	only save unchecked checkboxes
	*	@param array
	*	@return array
	*/
	public static function save( $form_data ){
		foreach( $form_data as $post_type => &$options ){
			$all = get_object_taxonomies( $post_type, 'names' );

			$options = array_diff( $all, $options );
		}

		return $form_data;
	}

	/**
	*	callback for add_settings_field to render form ui
	*/
	public static function view(){
		echo render( 'admin/options-general', array() );
	}
}