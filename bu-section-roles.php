<?php

/**
 * Temporary class until a good solution for creating roles / assigning
 * capabilities is arrived upon
 */
class BU_Section_Editing_Roles {

	// need to figure out the *best* way to create roles
	static public function maybe_create() {

		$role = get_role('administrator');

		if(empty($role)) {

			add_role('administrator', 'Administrator');
			include( ABSPATH . '/wp-admin/includes/schema.php');// hack to add all roles if they were deleted.
			populate_roles();

		}

		$role = get_role( 'lead_editor' );

		if(empty($role)) {
			add_role('lead_editor', 'Lead Editor');
		}

		$role = get_role('lead_editor');

		$role->add_cap('manage_training_manager');
		$role->add_cap('upload_files');
		$role->add_cap('edit_posts');
		$role->add_cap('read');
		$role->add_cap('delete_posts');

		$role->add_cap('moderate_comments');
		$role->add_cap('manage_categories');
		$role->add_cap('manage_links');
		$role->add_cap('upload_files');
		$role->add_cap('import');
		$role->add_cap('unfiltered_html');
		$role->add_cap('edit_posts');
		$role->add_cap('edit_others_posts');
		$role->add_cap('edit_published_posts');
		$role->add_cap('publish_posts');
		$role->add_cap('edit_pages');
		$role->add_cap('read');
		$role->add_cap('level_10');
		$role->add_cap('level_9');
		$role->add_cap('level_8');
		$role->add_cap('level_7');
		$role->add_cap('level_6');
		$role->add_cap('level_5');
		$role->add_cap('level_4');
		$role->add_cap('level_3');
		$role->add_cap('level_2');
		$role->add_cap('level_1');
		$role->add_cap('level_0');

		$role->add_cap('edit_others_pages');
		$role->add_cap('edit_published_pages');
		$role->add_cap('publish_pages');
		$role->add_cap('delete_pages');
		$role->add_cap('delete_others_pages');
		$role->add_cap('delete_published_pages');
		$role->add_cap('delete_posts');
		$role->add_cap('delete_others_posts');
		$role->add_cap('delete_published_posts');
		$role->add_cap('delete_private_posts');
		$role->add_cap('edit_private_posts');
		$role->add_cap('read_private_posts');
		$role->add_cap('delete_private_pages');
		$role->add_cap('edit_private_pages');
		$role->add_cap('read_private_pages');
		$role->add_cap('read_private_posts');
		$role->add_cap('read_private_pages');
		$role->add_cap('unfiltered_html');


		/** Temporary **/
		$role = get_role('section_editor');

		if(empty($role)) {
			add_role('section_editor', 'Section Editor');
		}

		$role = get_role('section_editor');

		$role->add_cap('manage_training_manager');
		$role->add_cap('upload_files');

		$role->add_cap('read');
		$role->add_cap('edit_pages');
		$role->add_cap('edit_others_pages');
		// the following roles are overriden by the section editor functionality
		$role->add_cap('edit_published_pages');
		$role->add_cap('publish_pages');
		$role->add_cap('delete_others_pages');
		$role->add_cap('delete_published_pages');
		$role->add_cap('delete_pages');


		$role->add_cap('edit_posts');
		$role->add_cap('edit_others_posts');
		$role->add_cap('edit_published_posts');
		$role->add_cap('publish_posts');
		$role->add_cap('delete_posts');
		$role->add_cap('delete_others_posts');
		$role->add_cap('delete_published_posts');

		$role->add_cap('moderate_comments');
		$role->add_cap('manage_categories');
		$role->add_cap('manage_links');
		$role->add_cap('upload_files');
		$role->add_cap('level_7');
		$role->add_cap('level_6');
		$role->add_cap('level_5');
		$role->add_cap('level_4');
		$role->add_cap('level_3');
		$role->add_cap('level_2');
		$role->add_cap('level_1');
		$role->add_cap('level_0');
		$role->add_cap('edit_private_posts');
		$role->add_cap('read_private_posts');
		$role->add_cap('edit_private_pages');
		$role->add_cap('read_private_pages');

		$role->add_cap('unfiltered_html');

		/** Temporary **/
		$role = get_role('contributor');

		if(empty($role)) {
			add_role('contributor', 'Contributor');
		}

		$role = get_role('contributor');

		$role->add_cap('manage_training_manager');
		$role->add_cap('upload_files');

		$role->add_cap('read');
		$role->add_cap('edit_pages');

		$role->add_cap('unfiltered_html');

	}

	/**
	 * This filter is only needed for BU installations where the bu_user_management plugin is active
	 *
	 * Hopefully this will not be required some day soon
	 */
	static function bu_allowed_roles( $roles ) {

		if( ! array_key_exists( 'lead_editor', $roles ) )
			$roles[] = 'lead_editor';

		if( ! array_key_exists( 'section_editor', $roles ) )
			$roles[] = 'section_editor';

		return $roles;

	}
}

/**
 * Section Editor
 */
class BU_Section_Editor {

	/**
	 * Checks whether or not a specific user can edit a post
	 *
	 * @param int $user_id
	 * @param int $post_id
	 * @param int $parent_id
	 */
	static function can_edit($user_id, $post_id, $parent_id = null)  {

		if($user_id == 0) return false;

		// Extra checks for any "allowed" users
		if( BU_Section_Editing_Plugin::is_allowed_user( $user_id ) ) {

			// Get groups associated with post
			$post_groups = get_post_meta( $post_id, BU_Edit_Group::META_KEY );

			// Get all groups for this user
			$edit_groups_o = BU_Edit_Groups::get_instance();
			$groups = $edit_groups_o->find_groups_for_user( $user_id );

			foreach( $groups as $key => $group ) {

				// This group is good, bail here
				if( in_array( (string) $group->id . BU_Edit_Group::SUFFIX_ALLOWED, $post_groups ) ) {
					return true;
				}
				// If group is denied, skip this group
				if( in_array( (string) $group->id . BU_Edit_Group::SUFFIX_DENIED, $post_groups ) ) {
					unset($groups[$key]); // remove group so that we don't check the ancestors for the present of the group
				}
			}

			// check a different ancestor tree from that of the existing post
			if(isset($parent_id)) {
				$post = get_post( $parent_id, OBJECT, null);
				$ancestors = get_post_ancestors( $post );
				array_unshift($ancestors, $post->ID);
			} else {
				// Note that get_post_ancestors only works if the post object is unfiltered
				$post = get_post( $post_id, OBJECT, null );
				$ancestors = get_post_ancestors( $post );
			}


			// Bubble up through ancestors, checking status along the way
			foreach( $ancestors as $ancestor_id ) {

				$ancestor_groups = get_post_meta( $ancestor_id, BU_Edit_Group::META_KEY );

				foreach( $groups as $key => $group ) {

					if( in_array( (string) $group->id . BU_Edit_Group::SUFFIX_ALLOWED, $ancestor_groups ) ) {
						return true;
					}

					if( in_array( (string) $group->id . BU_Edit_Group::SUFFIX_DENIED, $ancestor_groups ) ) {
						unset($groups[$key]);
					}
				}
				if( empty($groups) ) {
					break;
				}
			}

			// User is section editor, but not allowed for this section
			return false;
		}

		// User is not restricted by plugin, normal meta_caps apply
		return true;

	}

	/**
	 * Filter that modifies the caps based on the current state.
	 *
	 * @param type $caps
	 * @param type $cap
	 * @param type $user_id
	 * @param type $args
	 * @return string
	 */
	static function map_meta_cap($caps, $cap, $user_id, $args) {
		global $post_ID;

		// edit_page and delete_page get a post ID passed, but publish does not
		if( isset( $args[0] ) ) {
			$id = $args[0];
		}

		// get all post types because section editors start with no rights
		$post_types = get_post_types(null, 'objects');

		// Override normal edit post permissions
		if( in_array( $cap, self::get_caps_for_post_types( 'edit_post', $post_types ) ) ) {
			$post = get_post($id);
			if($id && $post->post_status == 'publish' && ! self::can_edit($user_id, $id)) {
				$caps = array('do_not_allow');
			}
			return $caps;
		}

		if( in_array( $cap, self::get_caps_for_post_types( 'delete_post', $post_types ) ) ) {
			if($id && ! self::can_edit($user_id, $id)) {
				$caps = array('do_not_allow');
			}
			return $caps;
		}

		if( in_array( $cap, self::get_caps_for_post_types( 'publish_posts', $post_types ) ) ) {
			$id = $post_ID;
			$post = get_post($id);
			if (!$id || !self::can_edit($user_id, $id)) {
				$caps = array('do_not_allow');
			}

			return $caps;
		}

		return $caps;

	}

	public function get_caps_for_post_types( $cap, $post_types ) {

		$caps = array();

		foreach( $post_types as $post_type ) {
			if( isset( $post_type->cap->$cap ))
				$caps[] = $post_type->cap->$cap;
		}

		return $caps;

	}

}

?>