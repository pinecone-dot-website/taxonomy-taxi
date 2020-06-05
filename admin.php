<?php

namespace Taxonomy_Taxi;

/**
 *	setup settings link and callbacks
 */
add_action('admin_menu', __NAMESPACE__ . '\Settings_Page::init');

/**
 * Called on `load-edit.php` action and from wp_ajax_inline-save
 * sets up the rest of the actions / filters
 */
function setup()
{
	// set up post type and associated taxonomies
	switch ($GLOBALS['pagenow']) {
		case 'upload.php':
			$post_type = 'attachment';
			break;

		default:
			$post_type = isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : 'post';
			break;
	}

	Edit::init($post_type);
	Query::init();
	Sql::init();
}
add_action('load-edit.php', __NAMESPACE__ . '\setup');
add_action('load-upload.php', __NAMESPACE__ . '\setup');

/**
 * Show direct link to settings page in plugins list
 */
add_filter('plugin_action_links', __NAMESPACE__ . '\Settings_Page::plugin_action_links', 10, 4);

/**
 * Attached to ajax for quick edit
 * Subvert wp_ajax_inline_save()
 */
add_action('wp_ajax_inline-save', __NAMESPACE__ . '\WP_Ajax::inline_save', 0);
