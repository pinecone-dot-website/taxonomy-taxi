<?php

namespace Taxonomy_Taxi;

class WP_Ajax
{
	/**
	 * Copied from wp_ajax_inline_save()
	 *
	 */
	public static function inline_save()
	{
		// Taxonomy Taxi
		setup();

		// Stock WP 5.4.1
		global $mode;

		check_ajax_referer('inlineeditnonce', '_inline_edit');

		if (!isset($_POST['post_ID']) || !(int) $_POST['post_ID']) {
			wp_die();
		}

		$post_ID = (int) $_POST['post_ID'];

		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_ID)) {
				wp_die(__('Sorry, you are not allowed to edit this page.'));
			}
		} else {
			if (!current_user_can('edit_post', $post_ID)) {
				wp_die(__('Sorry, you are not allowed to edit this post.'));
			}
		}

		$last = wp_check_post_lock($post_ID);
		if ($last) {
			$last_user      = get_userdata($last);
			$last_user_name = $last_user ? $last_user->display_name : __('Someone');

			/* translators: %s: User's display name. */
			$msg_template = __('Saving is disabled: %s is currently editing this post.');

			if ('page' === $_POST['post_type']) {
				/* translators: %s: User's display name. */
				$msg_template = __('Saving is disabled: %s is currently editing this page.');
			}

			printf($msg_template, esc_html($last_user_name));
			wp_die();
		}

		$data = &$_POST;

		$post = get_post($post_ID, ARRAY_A);

		// Since it's coming from the database.
		$post = wp_slash($post);

		$data['content'] = $post['post_content'];
		$data['excerpt'] = $post['post_excerpt'];

		// Rename.
		$data['user_ID'] = get_current_user_id();

		if (isset($data['post_parent'])) {
			$data['parent_id'] = $data['post_parent'];
		}

		// Status.
		if (isset($data['keep_private']) && 'private' == $data['keep_private']) {
			$data['visibility']  = 'private';
			$data['post_status'] = 'private';
		} else {
			$data['post_status'] = $data['_status'];
		}

		if (empty($data['comment_status'])) {
			$data['comment_status'] = 'closed';
		}

		if (empty($data['ping_status'])) {
			$data['ping_status'] = 'closed';
		}

		// Exclude terms from taxonomies that are not supposed to appear in Quick Edit.
		if (!empty($data['tax_input'])) {
			foreach ($data['tax_input'] as $taxonomy => $terms) {
				$tax_object = get_taxonomy($taxonomy);
				/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
				if (!apply_filters('quick_edit_show_taxonomy', $tax_object->show_in_quick_edit, $taxonomy, $post['post_type'])) {
					unset($data['tax_input'][$taxonomy]);
				}
			}
		}

		// Hack: wp_unique_post_slug() doesn't work for drafts, so we will fake that our post is published.
		if (!empty($data['post_name']) && in_array($post['post_status'], array('draft', 'pending'))) {
			$post['post_status'] = 'publish';
			$data['post_name']   = wp_unique_post_slug($data['post_name'], $post['ID'], $post['post_status'], $post['post_type'], $post['post_parent']);
		}

		// Update the post.
		edit_post();

		$wp_list_table = _get_list_table('WP_Posts_List_Table', array('screen' => $_POST['screen']));

		$mode = 'excerpt' === $_POST['post_view'] ? 'excerpt' : 'list';

		$level = 0;
		if (is_post_type_hierarchical($wp_list_table->screen->post_type)) {
			$request_post = array(get_post($_POST['post_ID']));
			$parent       = $request_post[0]->post_parent;

			while ($parent > 0) {
				$parent_post = get_post($parent);
				$parent      = $parent_post->post_parent;
				$level++;
			}
		}

		// Taxonomy Taxi again! 
		$posts = get_posts(
			[
				'p' => $_POST['post_ID'],
				'post_type' => $_POST['post_type'],
				'post_status' => 'any',
				'suppress_filters' => false,
				'posts_per_page' => 1,
			]
		);

		$wp_list_table->display_rows($posts, $level);

		// Thank u next
		wp_die();
	}
}
