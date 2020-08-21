<?php
namespace Secdor;

use \WP_User;
use \WP_Query;

class Group_Permissions {

	const META_KEY = '_bu_section_group';

	/**
	 * Allows developers to opt-out for section editing feature
	 */
	public static function get_supported_post_types( $output = 'objects' ) {

		$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
		$supported_post_types = array();

		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'section-editing' ) ) {

				switch ( $output ) {

					case 'names':
						$supported_post_types[] = $post_type->name;
						break;

					case 'objects': default:
						$supported_post_types[] = $post_type;
						break;
				}
			}
		}

		return $supported_post_types;

	}

	/**
	 * Relocated from Secdor\Section_Capabilities in classes.capabilities.php
	 */
	public static function can_edit_section( WP_User $user, $post_id ) {

		$user_id = $user->ID;

		if ( $user_id == 0 ) {
			return false;
		}
		if ( $post_id == 0 ) {
			return false;
		}

		// Get all groups for this user
		$edit_groups_o = Edit_Groups::get_instance();
		$groups = $edit_groups_o->find_groups_for_user( $user_id );

		if ( empty( $groups ) ) {
			return false;
		}

		foreach ( $groups as $key => $group ) {

			// This group is good, bail here
			if ( self::group_can_edit( $group->id, $post_id ) ) {
				return true;
			}
		}

		// User is section editor, but not allowed for this section
		return false;

	}

	/**
	 * Update permissions for a group
	 *
	 * @param int   $group_id ID of group to modify ACL for
	 * @param array $permissions Permissions, as an associative array indexed by post type
	 */
	public static function update_group_permissions( $group_id, $permissions ) {
		global $wpdb;

		if ( ! is_array( $permissions ) ) {
			return false;
		}

		foreach ( $permissions as $post_type => $ids_by_status ) {

			if ( ! is_array( $ids_by_status ) ) {
				error_log( "Unexpected value found while updating permissions: $ids_by_status" );
				continue;
			}

			// Incoming allowed posts
			$allowed_ids = isset( $ids_by_status['allowed'] ) ? $ids_by_status['allowed'] : array();

			if ( ! empty( $allowed_ids ) ) {

				// Make sure we don't add allowed meta twice
				$previously_allowed = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} WHERE post_id IN (%s) AND meta_key = %s AND meta_value = %s",
						implode( ',', $allowed_ids ),
						self::META_KEY,
						$group_id
					)
				 );
				$additions = array_merge( array_diff( $allowed_ids, $previously_allowed ) );

				foreach ( $additions as $post_id ) {

					add_post_meta( $post_id, self::META_KEY, $group_id );
				}
			}

			// Incoming restricted posts
			$denied_ids = isset( $ids_by_status['denied'] ) ? $ids_by_status['denied'] : array();

			if ( ! empty( $denied_ids ) ) {

				// Sanitize the list of IDs for direct use in the query.
				$denied_ids = implode( ',', array_map( 'intval', $denied_ids ) );

				// Select meta_id's for removal based on incoming posts
				$denied_meta_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id IN ({$denied_ids}) AND meta_key = %s AND meta_value = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						self::META_KEY,
						$group_id
					)
				 );

				// Bulk deletion
				if ( ! empty( $denied_meta_ids ) ) {

					// Sanitize the list of IDs for direct use in the query.
					$denied_meta_ids = implode( ',', array_map( 'intval', $denied_meta_ids ) );

					// Remove allowed status in one query
					$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_id IN ({$denied_meta_ids})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

					// Purge cache
					foreach ( $denied_ids as $post_id ) {
						wp_cache_delete( $post_id, 'post_meta' );
					}
				}
			}
		}

	}

	public static function delete_group_permissions( $group_id ) {

		$supported_post_types = self::get_supported_post_types( 'names' );

		$meta_query = array(
			'key' => self::META_KEY,
			'value' => $group_id,
			'compare' => 'LIKE',
			);

		$args = array(
			'post_type' => $supported_post_types,
			'meta_query' => array( $meta_query ),
			'posts_per_page' => -1,
			'fields' => 'ids',
			);

		$query = new WP_Query( $args );

		foreach ( $query->posts as $post_id ) {
			delete_post_meta( $post_id, self::META_KEY, $group_id );
		}

	}

	/**
	 * Can this group edit a particular post
	 */
	public static function group_can_edit( $group_id, $post_id, $ignore = '' ) {

		if ( 'ignore_global' !== $ignore ) {
			$groups = Edit_Groups::get_instance();

			if ( $groups->post_is_globally_editable_by_group( $post_id, $group_id ) ) {
				return true;
			}
		}

		$allowed_groups = get_post_meta( $post_id, self::META_KEY );

		return ( is_array( $allowed_groups ) && in_array( $group_id, $allowed_groups ) ) ? true : false;

	}

	/**
	 * Query for all posts that have section editing permissions assigned for this group
	 *
	 * @uses WP_Query
	 *
	 * @param array $args an optional array of WP_Query arguments, will override defaults
	 * @return array an array of posts that have section editing permissions for this group
	 */
	public static function get_allowed_posts_for_group( $group_id, $args = array() ) {

		$defaults = array(
			'post_type' => 'page',
			'meta_key' => self::META_KEY,
			'meta_value' => $group_id,
			'posts_per_page' => -1,
			);

		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );

		return $query->posts;
	}
}
