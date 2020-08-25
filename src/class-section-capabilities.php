<?php
namespace Secdor;

use \WP_User;

class Section_Capabilities {

	/**
	 * Get all Section Editing caps for the registered post types.
	 *
	 * @return array $caps
	 **/
	public function get_caps() {

		$caps = array( 'edit_in_section' );
		$operations = array( 'edit', 'delete', 'publish' );
		$post_types = $this->get_post_types();

		foreach ( $post_types as $post_type ) {
			if ( $post_type->public != true || in_array( $post_type->name, array( 'attachment' ) ) ) {
				continue;
			}
			foreach ( $operations as $op ) {
				$caps[] = $this->get_section_cap( $op, $post_type->name );
			}
		}

		return $caps;

	}

	/**
	 * Add (edit|publish|delete)_*_in_section caps to the given role.
	 *
	 * @param mixed $role a WP_Role object, or string representation of a role name
	 */
	public function add_caps( $role ) {

		if ( is_string( $role ) ) {
			$role = get_role( $role );
		}

		if ( empty( $role ) || ! is_object( $role ) ) {
			error_log( __METHOD__ . ' - Invalid role!' );
			return false;
		}

		foreach ( $this->get_caps() as $cap ) {
			$role->add_cap( $cap );
		}

	}

	/**
	 * Filter that modifies the caps needing to take certain actions in cases
	 * where the user ($user_id) does not have the capabilities that WordPress
	 * has mapped to the meta capability. The mapping is based on which post is
	 * being edited, the Section Groups granted access to the post, and the
	 * membership of the user in those groups.
	 *
	 * @param array  $caps
	 * @param string $cap
	 * @param int    $user_id
	 * @param mixed  $args
	 * @return array
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		global $post_ID;

		$wp_user = new WP_User( intval( $user_id ) );
		$edit_user = new Edit_User( $wp_user );

		$post_id = $post_ID;

		if ( isset( $args[0] ) ) {
			$post_id = $args[0];
		}

		// if user already has the caps as passed by map_meta_cap() pre-filter or
		// the user doesn't have the main "section editing" cap
		if ( $edit_user->has_capabilities( $caps ) || !$edit_user->has_capability( 'edit_in_section' ) ) {
			return $caps; // bail early
		}

		if ( $this->is_post_capability( $cap, 'edit_post' ) ) {
			$caps = $this->_override_edit_caps( $wp_user, $post_id, $caps );
		}

		if ( $this->is_post_capability( $cap, 'delete_post' ) ) {
			$caps = $this->_override_delete_caps( $wp_user, $post_id, $caps );
		}

		// As publish_posts does not come tied to a post ID, relying on the global $post_ID is fragile
		// For instance, the "Quick Edit" interface of the edit posts page does not populate this
		// global, and therefore the "Published" status is unavailable with this meta_cap check in place
		if ( $this->is_post_capability( $cap, 'publish_posts' ) ) {
			$caps = $this->_override_publish_caps( $wp_user, $post_id, $caps );
		}

		return $caps;
	}

	/**
	 * Check some $_POST variables to see if the posted data matches the post
	 * we are checking permissions against
	 **/
	private function is_parent_changing( $post ) {
		return isset( $_POST['post_ID'] ) && $post->ID == $_POST['post_ID'] && isset( $_POST['parent_id'] ) &&  $post->post_parent != $_POST['parent_id'];
	}

	private function get_new_parent() {
		return (int) $_POST['parent_id'];
	}

	/**
	 * When working with revisions, check for parent post's permissions.
	 * Returns the parent post ID if `$post_id` points to a revision.
	 *
	 * @param  int $post_id
	 * @return int
	 */
	private function switch_revision_to_parent( $post_id ) {
		$post = get_post( $post_id );

		if ( 'revision' == $post->post_type ) {
			$post_id = $post->post_parent;
		}

		return $post_id;
	}

	private function _override_edit_caps( WP_User $wp_user, $post_id, $caps ) {

		if ( empty( $post_id ) ) {
			return $caps;
		}

		$parent_id = null;
		$post_id   = $this->switch_revision_to_parent( $post_id );
		$post      = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		$edit_user = new Edit_User( $wp_user );

		if ( $post_type->hierarchical != true ) {

			if ( $edit_user->can_edit_section( $post_id ) ) {
				$caps = array( $this->get_section_cap( 'edit', $post->post_type ) );
			}
		} else {

			if ( $this->is_parent_changing( $post ) ) {
				$parent_id = $this->get_new_parent( $post );

				if ( $post->post_status == 'publish' && $edit_user->can_edit_section( $parent_id ) ) {
					$caps = array( $this->get_section_cap( 'edit', $post->post_type ) );
				}
			}

			if ( $post_id && $post->post_status == 'publish' && $edit_user->can_edit_section( $post_id ) ) {
				$caps = array( $this->get_section_cap( 'edit', $post->post_type ) );
			}
		}

		return $caps;
	}

	private function _override_delete_caps( WP_User $wp_user, $post_id, $caps ) {

		if ( empty( $post_id ) ) {
			return $caps;
		}

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );
		$edit_user = new Edit_User( $wp_user );

		if ( $post_type->hierarchical != true ) {
			if ( $edit_user->can_edit_section( $post_id ) ) {
				$caps = array( $this->get_section_cap( 'delete', $post->post_type ) );
			}
		} else {
			if ( $post_id && in_array( $post->post_status, array( 'publish', 'trash' ) ) && $edit_user->can_edit_section( $post_id ) ) {
				$caps = array( $this->get_section_cap( 'delete', $post->post_type ) );
			}
		}

		return $caps;
	}

	private function _override_publish_caps( WP_User $wp_user, $post_id, $caps ) {

		if ( ! isset( $post_id ) ) {
			return $caps;
		}

		$edit_user = new Edit_User( $wp_user );

		$parent_id = null;

		$post = get_post( $post_id );

		$post_type = get_post_type_object( $post->post_type );

		$is_alt = false;

		// BU Versions uses the post_parent to relate the alternate version
		// to the original
		if ( class_exists( '\\BU_Version_Workflow' ) ) {
			$is_alt = \BU_Version_Workflow::$v_factory->is_alt( $post->post_type );
		}

		if ( $post_type->hierarchical != true && $is_alt != true ) {
			if ( $edit_user->can_edit_section( $post_id ) ) {
				$caps = array( $this->get_section_cap( 'publish', $post->post_type ) );
			}
		} else {
			// User is attempting to switch post parent while publishing
			if ( $this->is_parent_changing( $post ) ) {

				$parent_id = $this->get_new_parent( $post );

				// Can't move published posts under sections they can't edit
				if ( $edit_user->can_edit_section( $parent_id ) ) {
					$caps = array( $this->get_section_cap( 'publish', $post->post_type ) );
				}
			} else {
				if ( isset( $post_id ) && $edit_user->can_edit_section( $post->post_parent ) ) {
					$caps = array( $this->get_section_cap( 'publish', $post->post_type ) );
				}
			}
		}

		return $caps;
	}

	public function get_section_cap( $type, $post_type ) {

		$cap = '';
		switch ( $type ) {
			case 'edit':
				$cap = 'edit_' . $post_type . '_in_section';
				break;

			case 'publish':
				$cap = 'publish_' . $post_type . '_in_section';
				break;

			case 'delete':
				$cap = 'delete_' . $post_type . '_in_section';
				break;

			default:
				$cap = 'edit_in_section';
		}
		return $cap;
	}

	/**
	 * Get post types and store them in a property.
	 *
	 * @return Array
	 **/
	public function get_post_types() {
		if ( ! isset( $this->post_types ) ) {
			$this->post_types = get_post_types( null, 'objects' );
		}

		return $this->post_types;
	}

	/**
	 * Whether or not the $cap is a meta cap for one of the registered post types.
	 *
	 * @param $cap
	 * @param $meta_cap
	 * @return bool
	 **/
	public function is_post_capability( $cap, $map_cap ) {
		foreach ( $this->get_post_types() as $post_type ) {
			if ( isset( $post_type->cap->$map_cap ) ) {
				if ( $post_type->cap->$map_cap == $cap ) {
					return true;
				}
			}
		}

		return false;
	}
}
