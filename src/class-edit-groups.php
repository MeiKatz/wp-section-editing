<?php
namespace Secdor;

use \StdClass;

/**
 * Section editor group controller
 *
 * @todo investigate replacing in-memory groups store with cache API
 */
class Edit_Groups {

	public $groups = array();

	static protected $instance;

	/**
	 * Load groups and index from db on instantiation
	 *
	 * Usage of global singleton pattern assures this method is only called once
	 */
	protected function __construct() {

		// Load group data
		$this->load();

	}

	/**
	 * Generates/fetches global singleton instance
	 */
	static public function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	static public function register_hooks() {

		add_action( 'init', array( __CLASS__, 'register_post_type' ) );

	}

	/**
	 * Register hidden post type for group data storage
	 */
	static public function register_post_type() {

		$labels = array(
			'name'                => _x( 'Section Groups', 'Post Type General Name', SECDOR_TEXTDOMAIN ),
			'singular_name'       => _x( 'Section Group', 'Post Type Singular Name', SECDOR_TEXTDOMAIN ),
		);

		$args = array(
			'labels'              => $labels,
			'supports'            => array(),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 5,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
		);

		register_post_type( Edit_Group::POST_TYPE_NAME, $args );

	}

	// ___________________PUBLIC_INTERFACE_____________________
	/**
	 * Returns a group by ID from internal groups array
	 *
	 * @param int $id unique ID of section group to return
	 * @return Secdor\Edit_Group|bool the requested section group object, or false on bad ID
	 */
	public function get( $id ) {

		foreach ( $this->groups as $group ) {
			if ( $group->id == $id ) {
				return $group;
			}
		}

		return false;
	}

	/**
	 * Add a group object to the internal groups array
	 *
	 * @param Secdor\Edit_Group $group a valid section editing group object
	 */
	public function add( $group ) {

		if ( ! $group instanceof Edit_Group ) {
			return false;
		}

		$this->groups[] = $group;

	}

	/**
	 * Remove a group by ID from the internal groups array
	 *
	 * @param int $id unique ID of section group to delete
	 * @return Secdor\Edit_Group|bool the deleted section group object on success, otherwise false
	 */
	public function delete( $id ) {

		foreach ( $this->groups as $i => $g ) {
			if ( $g->id == $id ) {
				unset( $this->groups[ $i ] );
				$this->groups = array_values( $this->groups );	// reindex
				return $g;
			}
		}

		return false;

	}

	/**
	 * Return an array of all groups
	 *
	 * @todo *_groups methods usually touch the DB
	 * 	- investigate renaming to get_all()
	 *
	 * @return type
	 */
	public function get_groups() {

		return $this->groups;

	}

	/**
	 * Remove all groups from internal array
	 *
	 * @todo *_groups methods usually touch the DB
	 * 	- investigate renaming to delete_all()
	 */
	public function delete_groups() {

		$this->groups = array();

	}

	/**
	 * Add a new section editing group
	 *
	 * @param array $data an array of parameters for group initialization
	 * @return Secdor\Edit_Group the group that was just added
	 */
	public function add_group(
		array $data
	) {
		// Sanitize input
		$this->_clean_group_data( $data );

		// Create new group from args
		$group = Edit_Group::create( $data );

		if ( $group === null ) {
			return false;
		}

		// Set permissions
		if ( isset( $data['perms'] ) ) {
			$group->permissions()->update( $data['perms'] );
		}

		// Notify
		add_action( 'secdor_add_section_editing_group', $group );

		return $group;
	}

	/**
	 * Update an existing section editing group
	 *
	 * @param int   $id the id of the group to update
	 * @param array $data an array of parameters with group fields to update
	 * @return Secdor\Edit_Group|bool the group that was just updated or false if none existed
	 */
	public function update_group(
		$id,
		array $data = array()
	) {
		$group = $this->get( $id );

		if ( !$group ) {
			return false;
		}

		// Sanitize.
		$this->_clean_group_data( $data );

		// Update group.
		$group->update_attributes( $data );

		// Update permissions.
		if ( isset( $data['perms'] ) ) {
			$group->permissions()->update( $data['perms'] );
		}

		return $group;
	}

	/**
	 * Delete an existing section editing group
	 *
	 * @param int $id the id of the group to delete
	 * @return bool true on success, false on failure
	 */
	public function delete_group( $id ) {

		// Remove group.
		$group = $this->delete( $id );

		if ( ! $group ) {
			error_log( 'Error deleting group: ' . $id );
			return false;
		}

		// Delete from db
		$result = wp_delete_post( $id, true );

		if ( $result === false ) {
			return false;
		}

		// Remove group permissions.
		$group->delete_permissions();

		return true;

	}

	/**
	 * Returns an array of group ID's for which the specified user is a member
	 *
	 * @param int $user_id WordPress user id
	 * @return array array of group ids for which the specified user belongs
	 */
	public function find_groups_for_user( $user_id ) {
		$groups = array();

		foreach ( $this->groups as $group ) {
			if ( $group->has_user( $user_id ) ) {
				$groups[ $group->id ] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Returns whether or not a user exists in an array of edit groups
	 *
	 * @todo remove this if it unused
	 *
	 * @param array $groups an array of Secdor\Edit_Group objects to check
	 * @param int   $user_id WordPress user id to check
	 */
	public function has_user( $groups, $user_id ) {

		if ( ! is_array( $groups ) ) {
			$groups = array( $groups );
		}

		foreach ( $groups as $group_id ) {

			$group = $this->get( $group_id );

			if ( $group && $group->has_user( $user_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get allowed post ids, optionally filtered by user ID, group or post_type
	 *
	 * @todo implement caching with md5 of args
	 * @todo possibly move to Secdor\Group_Permissions
	 *
	 * @param $args array optional args
	 *
	 * @return array post ids for the given post type, group or user
	 */
	public function get_allowed_posts( $args = array() ) {
		global $wpdb, $bu_navigation_plugin;

		$defaults = array(
			'user_id' => null,
			'group' => null,
			'post_type' => null,
			'include_unpublished' => false,
			'include_links' => true,
			);

		extract( wp_parse_args( $args, $defaults ) );

		$group_ids = array();

		// If user_id is passed, populate group ID's from their memberships
		if ( $user_id ) {

			if ( is_null( get_userdata( $user_id ) ) ) {
				error_log( 'No user found for ID: ' . $user_id );
				return array();
			}

			// Get groups for users
			$group_ids = array_keys(
				$this->find_groups_for_user( $user_id )
			);

		}

		// If no user ID is passed, but a group is, convert to array
		if ( is_null( $user_id ) && $group ) {

			if ( is_array( $group ) ) {
				$group_ids = $group;
			}

			if ( is_numeric( $group ) && $group > 0 ) {
				$group_ids = array( $group );
			}
		}

		// Bail if we don't have any valid groups by now
		if ( empty( $group_ids ) ) {
			return array();
		}

		// Generate query
		$post_type_clause = $post_status_clause = '';

		// Maybe filter by post type and status
		if ( ! is_null( $post_type ) && ! is_null( $pto = get_post_type_object( $post_type ) ) ) {

			// Only a single post type is expected, so it should be prepared as a string.
			$post_type_clause = $wpdb->prepare( "AND post_type = %s", $post_type );

			if ( $include_links && $post_type == 'page' && isset( $bu_navigation_plugin ) ) {
				if ( $bu_navigation_plugin->supports( 'links' ) ) {
					$link_post_type = defined( 'BU_NAVIGATION_LINK_POST_TYPE' ) ? BU_NAVIGATION_LINK_POST_TYPE : 'bu_link';

					// Only a single post type string is passed, so it can be prepared as normal.
					$post_type_clause = $wpdb->prepare( "AND post_type IN ('page', %s) ", $link_post_type );
				}
			}
		}

		// Include unpublished should only work for hierarchical post types
		if ( $include_unpublished ) {

			// Flat post types are not allowed to include unpublished, as perms can be set for drafts
			if ( $post_type ) {

				$pto = get_post_type_object( $post_type );

				if ( $pto->hierarchical ) {

					// The `$post_type_clause` statement is prepared above and can be considered safe here.
					$post_status_clause = "OR (post_status IN ('draft','pending') $post_type_clause)";

				}
			} else {

				$post_status_clause = "OR post_status IN ('draft','pending')";

			}
		}

		// Prepare the first section of the SQL statement.
		$count_query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE ( ID IN ( SELECT post_ID FROM {$wpdb->postmeta} WHERE meta_key = %s",
			Edit_Group::META_KEY
		);

		// Build the remaining SQL from previously prepared statements. The `group_ids` array is forced to integer values for safety.
		$count_query .= " AND meta_value IN (" . implode( ',', array_map( 'intval', $group_ids ) ) . ') ) ' . $post_type_clause . ') ' . $post_status_clause;

		// Execute query
		$ids = $wpdb->get_col( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $ids;
	}

	/**
	 * Get allowed post count, optionally filtered by user ID, group or post_type
	 *
	 * @param $args array optional args
	 *
	 * @return int allowed post count for the given post type, group or user
	 */
	public function get_allowed_post_count( $args = array() ) {
		$ids = $this->get_allowed_posts( $args );
		return count( $ids );
	}

	// ____________________PERSISTENCE________________________
	/**
	 * Load all groups
	 */
	public function load() {

		$args = array(
			'post_type' => Edit_Group::POST_TYPE_NAME,
			'numberposts' => -1,
			'order' => 'ASC',
			);

		$group_posts = get_posts( $args );

		if ( is_array( $group_posts ) ) {
			foreach ( $group_posts as $group_post ) {
				$this->groups[] = Edit_Group::from_post( $group_post );
			}
		}
	}

	/**
	 * Save all groups
	 *
	 * @todo refactor so that both insert and update groups utilize this method
	 * @todo test coverage
	 *
	 * @param bool : return true if all groups could be
	 *	saved successfully
	 */
	public function save() {
		return array_reduce(
			$this->groups,
			function ( $result, $group ) {
				return ( $result && $group->save() );
			},
			true
		);
	}

	/**
	 * Sanitizes array of group data prior to group creation or updating
	 */
	protected function _clean_group_data( &$args ) {

		// Process input
		$args['name'] = sanitize_text_field( stripslashes( $args['name'] ) );
		$args['description'] = isset( $args['description'] ) ? sanitize_text_field( stripslashes( $args['description'] ) ) : '';
		$args['users'] = isset( $args['users'] ) ? array_map( 'absint', $args['users'] ) : array();

		$args["users"] = array_fill_keys($args["users"], true);

		if ( ! isset( $args['global_edit'] ) || ! is_array( $args['global_edit'] ) ) {
			$args['global_edit'] = array();
		}

		$sanitized_global_edit_value = array();
		foreach ($args['global_edit'] as $custom_type) {
			if ( post_type_exists( $custom_type ) ) {
				if ( ! is_post_type_hierarchical( $custom_type ) ) {
					$sanitized_global_edit_value[] = $custom_type;
				}
			}
		}

		$args['global_edit'] = $sanitized_global_edit_value;

		if ( isset( $args['perms'] ) && is_array( $args['perms'] ) ) {
			foreach ( $args['perms'] as $post_type => $ids_by_status ) {

				if ( ! is_array( $ids_by_status ) ) {

					error_log( "Unepected value for permissions data: $ids_by_status" );
					unset( $args['perms'][ $post_type ] );
					continue;
				}

				if ( ! isset( $ids_by_status['allowed'] ) ) {
					$args['perms'][ $post_type ]['allowed'] = array();
				}
				if ( ! isset( $ids_by_status['denied'] ) ) {
					$args['perms'][ $post_type ]['denied'] = array();
				}

				foreach ( $ids_by_status as $status => $post_ids ) {

					if ( ! in_array( $status, array( 'allowed', 'denied', '' ) ) ) {
						error_log( "Unexpected status: $status" );
						unset( $args['perms'][ $post_type ][ $status ] );
					}
				}
			}
		}
	}

	/**
	 * Checks if the post (or post type) is marked as globally editable in this group
	 *
	 * @param int|string $post Post ID (int) or post type name (string)
	 * @param int $group_id Section editing group ID
	 * @return Boolean
	 */
	public function post_is_globally_editable_by_group( $post, $group_id )
	{
		if ($post === intval( $post )) {
			$post_type = get_post_type($post);
		}
		else {
			$post_type = $post;
		}

		$global_edit = get_post_meta( $group_id, Edit_Group::GLOBAL_EDIT, true);

		return is_array( $global_edit ) && in_array( $post_type, $global_edit );
	}
}
