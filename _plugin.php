<?php
/*
Plugin Name:	Taxonomy Taxi
Plugin URI: 	http://wordpress.org/plugins/taxonomy-taxi/
Description:	Show custom taxonomies in /wp-admin/edit.php automatically
Version: 		.9.9
Author: 		postpostmodern, pinecone-dot-website
Author URI: 	http://rack.and.pinecone.website
Photo Credit:	http://www.flickr.com/photos/photos_mweber/
Photo URL:		http://www.flickr.com/photos/photos_mweber/540970484/
Photo License:	Attribution-NonCommercial 2.0 Generic (CC BY-NC 2.0)
*/

register_activation_hook( __FILE__, create_function("", '$ver = "5.3"; if( version_compare(phpversion(), $ver, "<") ) die( "This plugin requires PHP version $ver or greater be installed." );') );

require __DIR__.'/index.php';