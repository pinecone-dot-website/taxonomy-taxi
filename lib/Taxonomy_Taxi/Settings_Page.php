<?php

namespace Taxonomy_Taxi;

class Settings_Page {
	/**
	*	
	*	attached to `admin_menu` action
	*/
	public static function init(){
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
					self::render_post_type( $post_type->name );
				},
				'taxonomy_taxi',
				'taxonomy_taxi_settings_section'
			);
	 	}

		register_setting( 'taxonomy_taxi', 'taxonomy_taxi', __CLASS__.'::save' );

		add_options_page( 'Taxonomy Taxi', 'Taxonomy Taxi', 'manage_options', 'taxonomy_taxi', __CLASS__.'::render_settings_page' );
	}

	/**
	*
	*	@param string html
	*	@return string html
	*/
	public static function admin_footer_text( $original = '' ){
		return render( 'admin/options-general_footer', array(
			'version' => version()
		) );
	}

	/**
	*	callback for add_settings_section to render description field
	*/
	public static function description(){
		echo sprintf( 'version %s', version() );
	}

	/**
	*	show direct link to settings page in plugins list
	*	attached to `plugin_action_links` filter
	*	@param array
	*	@param string
	*	@param array
	*	@param string
	*	@return array
	*/
	public static function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ){
		if( $plugin_file == 'taxonomy-taxi/_plugin.php' && $url = menu_page_url('taxonomy_taxi', FALSE) ){
			$actions[] = sprintf( '<a href="%s">Settings</a>', $url );
		}

		return $actions;
	}

	/**
	*	render the ui for each post type row
	*	@param string
	*	@return 
	*/
	public static function render_post_type( $post_type = '' ){ 
		$taxonomies = Settings::get_all_for_post_type( $post_type );

		echo render( 'admin/options-general_post-type', array(
			'post_type' => $post_type,
			'taxonomies' => $taxonomies
		) );
	}

	/**
	*	callback for add_settings_field to render form ui
	*/
	public static function render_settings_page(){
		wp_enqueue_style( 'taxonomy-taxi', plugins_url('public/admin/options-general.css', TAXONOMY_TAXI_FILE), array(), version(), 'all' );

		wp_enqueue_script( 'taxonomy-taxi', plugins_url('public/admin/options-general.js', TAXONOMY_TAXI_FILE), array(), version(), 'all' );

		echo render( 'admin/options-general', array() );

		add_filter( 'admin_footer_text', __CLASS__.'::admin_footer_text' );
	}

	/**
	*	only save unchecked checkboxes
	*	@param array
	*	@return array
	*/
	public static function save( $form_data ){
		$post_types = get_post_types( array(
	 		'show_ui' => TRUE
	 	), 'objects' );
		
		$saved = array();

		foreach( $post_types as $post_type => $object ){
			$all = get_object_taxonomies( $post_type, 'names' );
			$user_input = isset($form_data[$post_type]) ? $form_data[$post_type] : array();

			$saved[$post_type] = array_diff( $all, $user_input );
		}

		return $saved;
	}
}