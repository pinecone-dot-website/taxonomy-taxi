<?php

namespace Taxonomy_Taxi;

class Settings_Page {
	/**
	*
	*/
	public static function register(){
		add_settings_section(
			'taxonomy_taxi_settings_section',
			'Taxonomy Taxi',
			__CLASS__.'::description',
			'taxonomy_taxi'
		);
	 	
	 	// Add the field with the names and function to use for our new
	 	// settings, put it in our new section
	 	add_settings_field(
			'taxonomy_taxi_setting_name',
			'Example setting Name',
			'',
			'taxonomy_taxi',
			'taxonomy_taxi_settings_section'
		);
	}

	/**
	*
	*/
	public static function description(){
		echo  sprintf( 'version %s', version() );
	}

	/**
	*
	*/
	public static function view(){
		echo render( 'admin/options-general', array() );
	}


}