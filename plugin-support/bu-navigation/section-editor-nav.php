<?php

// Only add filters for section editors
if ( Secdor\Edit_User::get_current()->is_allowed() ) {

	add_action( 'bu_nav_tree_enqeueue_scripts', 'buse_bu_navigation_scripts' );
	add_filter( 'bu_nav_tree_script_context', 'buse_bu_navigation_script_context' );

	// Add custom filter to add editable status fields to post objects
	add_filter( 'bu_nav_tree_view_filter_posts', 'buse_bu_navigation_filter_posts' );
	add_filter( 'bu_nav_tree_view_format_post_bu_navman', 'buse_bu_navigation_format_post', 10, 3 );
	add_filter( 'bu_nav_tree_view_format_post_nav_metabox', 'buse_bu_navigation_format_post', 10, 3 );

	// Add custom fields
	add_filter( 'bu_navigation_filter_fields', 'buse_bu_navigation_filter_fields' );

}

function buse_bu_navigation_scripts() {

	$screen = get_current_screen();
	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	$suffix = "";

	wp_enqueue_script( 'section-editor-nav', plugins_url( 'js/section-editor-nav' . $suffix . '.js', SECDOR_PLUGIN_ENTRYPOINT ), array( 'bu-navigation' ), Secdor\Section_Editing_Plugin::VERSION, true );

	if ( function_exists( 'bu_navigation_supported_post_types' ) ) {
		if ( 'post' == $screen->base && in_array( $screen->post_type, bu_navigation_supported_post_types() ) ) {
			wp_enqueue_script( 'section-editor-nav-metabox', plugins_url( 'js/section-editor-nav-metabox' . $suffix . '.js', SECDOR_PLUGIN_ENTRYPOINT ), array( 'bu-navigation-metabox' ), Secdor\Section_Editing_Plugin::VERSION, true );
		}
	}
}

function buse_bu_navigation_script_context( $config ) {
	$config['isSectionEditor'] = true;
	return $config;
}

/**
 * Filters pages during bu_navigation_get_pages to add section group related meta data -- can_edit and can_remove
 *
 * @global type $wpdb
 * @param type $posts
 * @return type
 */
function buse_bu_navigation_filter_posts( $posts ) {
	global $wpdb;

	$current_user = get_current_user_id();
	$groups = Secdor\Edit_Groups::get_instance();
	$section_groups = array_keys(
		$groups->find_groups_for_user( $current_user )
	);

	if ( ( is_array( $posts ) ) && ( count( $posts ) > 0 ) ) {

		// Section editors with no groups have all posts denied
		if ( is_array( $section_groups ) && ! empty( $section_groups ) ) {

			/* Gather all group post meta in one shot */
			$ids = array_keys( $posts );

			// Sanitize the list of IDs for direct use in a query.
			$ids = implode( ',', array_map( 'intval', $ids ) );

			// Sanitize the list of groups for direct use in a query.
			$section_groups_values = implode( ',', array_map( 'intval', $section_groups ) );

			$group_meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$ids}) AND meta_value IN ({$section_groups_values})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					Secdor\Edit_Group::META_KEY
				)
				, OBJECT_K
			); // get results as objects in an array keyed on post_id

			if ( ! is_array( $group_meta ) ) {
				$group_meta = array();
			}

			// Append permissions to post object
			foreach ( $posts as $post ) {

				$post->can_edit = false;
				$post->can_remove = false;

				if ( array_key_exists( $post->ID, $group_meta ) ) {
					$perm = $group_meta[ $post->ID ];

					if ( in_array( $perm->meta_value, $section_groups ) ) {
						$post->can_edit = true;
						$post->can_remove = true;
					}
				}

				// Hierarchical perm editors ignore draft/pending, allowed by default
				if ( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
					$post->can_edit = true;
					$post->can_remove = ( $post->post_author == $current_user );
				}
			}
		} else {

			foreach ( $posts as $post ) {

				$post->can_edit = false;
				$post->can_remove = false;

				// Hierarchical perm editors ignore draft/pending, allowed by default
				if ( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
					$post->can_edit = true;
					$post->can_remove = ($post->post_author == $current_user);
				}
			}
		}
	}

	return $posts;

}

function buse_bu_navigation_format_post( $p, $post, $has_children ) {

	$p['metadata']['post']['post_meta']['canEdit'] = $post->can_edit;
	$p['metadata']['post']['post_meta']['canRemove'] = $post->can_remove;

	if ( ! isset( $p['attr']['class'] ) ) {
		$p['attr']['class'] = '';
	}

	if ( ! $post->can_edit ) {
		$p['attr']['class'] = ' denied';
	}

	return $p;

}

function buse_bu_navigation_filter_fields( $fields ) {

	if ( ! in_array( 'post_author', $fields ) ) {
		$fields[] = 'post_author';
	}

	return $fields;

}
