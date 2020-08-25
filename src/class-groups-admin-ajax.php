<?php
namespace Secdor;

use \StdClass;

/**
 * Centralized admin ajax routing
 *
 * @todo sanitize ALL input
 * @todo AJAX nonces
 */
class Groups_Admin_Ajax {

	static public function register_hooks() {

		add_action( 'wp_ajax_secdor_site_users_script', array( __CLASS__, 'site_users_script' ) );
		add_action( 'wp_ajax_secdor_search_posts', array( __CLASS__, 'search_posts' ) );
		add_action( 'wp_ajax_secdor_render_post_list', array( __CLASS__, 'render_post_list' ) );
		add_action( 'wp_ajax_secdor_can_edit', array( __CLASS__, 'can_edit' ) );
		add_action( 'wp_ajax_secdor_can_move', array( __CLASS__, 'can_move' ) );

	}

	/**
	 * Generates a Javscript file that contains a variable with all site users and relevant meta
	 *
	 * The variable is used for autocompletion (find user tool) and while adding members
	 */
	public static function site_users_script() {
		$return = array();

		// Get all users of the current site
		$wp_users = get_users();

		// Format output
		foreach ( $wp_users as $wp_user ) {
			$edit_user = new Edit_User( $wp_user );

			$email = (
				! empty( $edit_user->email_address() )
					? " ({$edit_user->email_address()})"
					: ""
			);

			$return[] = array(
				"autocomplete" => array(
					"label" => sprintf(
						"%1$s%2$s",
						$edit_user->display_name(),
						$email
					),
					"value" => $edit_user->login(),
				),
				"user" => array(
					"id" => $edit_user->id(),
					"login" => $edit_user->login(),
					"nicename" => $edit_user->nice_name(),
					"display_name" => $edit_user->display_name(),
					"email" => $edit_user->email_address(),
					"is_section_editor" => $edit_user->is_allowed(),
				),
			);
		}

		header( 'Content-type: application/x-javascript' );
		echo 'var secdor_site_users = ' . json_encode( $return );
		die();
	}

	/**
	 * Renders an unordered list of posts for specified post type, optionally starting at a specifc post
	 *
	 * @uses Secdor\Hierarchical_Permissions_Editor or Secdor\Flat_Permissions_Editor depending on post_type
	 *
	 * @todo add nonce
	 */
	static public function render_post_list() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			$group_id = intval( trim( $_REQUEST['group_id'] ) );
			$post_type = trim( $_REQUEST['post_type'] );
			$query_vars = isset( $_REQUEST['query'] ) ? $_REQUEST['query'] : array();
			$post_type_obj = get_post_type_object( $post_type );

			if ( is_null( $post_type_obj ) ) {
				error_log( 'Bad post type: ' . $post_type );
				die();
			}

			$perm_editor = null;

			if ( $post_type_obj->hierarchical ) {

				$perm_editor = new Hierarchical_Permissions_Editor( $group_id, $post_type_obj->name );
				$perm_editor->format = 'json';

			} else {

				$perm_editor = new Flat_Permissions_Editor( $group_id, $post_type_obj->name );

			}

			$perm_editor->query( $query_vars );

			$response = new StdClass();
			$child_of = isset( $query_vars['child_of'] ) ? $query_vars['child_of'] : 0;

			$response->posts = $perm_editor->get_posts( $child_of );
			$response->page = $perm_editor->page;
			$response->found_posts = $perm_editor->found_posts;
			$response->post_count = $perm_editor->post_count;
			$response->max_num_pages = $perm_editor->max_num_pages;

			header( 'Content-type: application/json' );
			echo json_encode( $response );
			die();

		}

	}

	/**
	 * Not yet in use
	 *
	 * @todo implement
	 */
	static public function search_posts() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			$group_id = intval( trim( $_REQUEST['group_id'] ) );
			$post_type = trim( $_REQUEST['post_type'] );
			$search_term = trim( $_REQUEST['search'] ) ? $_REQUEST['search'] : '';

			$post_type_obj = get_post_type_object( $post_type );

			if ( is_null( $post_type_obj ) ) {
				error_log( 'Bad post type: ' . $post_type );
				die();
			}

			die();

		}

	}

	static public function can_move() {
		$post_id = (int) trim( $_POST['post_id'] );
		$parent_id = (int) trim( $_POST['parent_id'] );

		if ( ! isset( $post_id ) || ! isset( $parent_id ) ) {
			echo '-1';
			die();
		}

		$post = get_post( $post_id );
		$post_type_obj = get_post_type_object( $post->post_type );

		if ( $parent_id == 0 && $post->post_parent == 0 ) {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $post_id );
		} else {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $parent_id );
		}

		$response = new StdClass();

		$response->post_id = $post_id;
		$response->parent_id = $parent_id;
		$response->can_edit = $answer;
		$response->original_parent = $post->post_parent;
		$response->status = $post->post_status;

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		die();
	}

	static public function can_edit() {

		$post_id = (int) trim( $_POST['post_id'] );

		if ( ! isset( $post_id ) ) {
			echo '-1';
			die();
		}

		$post = get_post( $post_id );
		$post_type_obj = get_post_type_object( $post->post_type );

		if ( $post->post_status != 'publish' ) {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $post->post_parent );
		} else {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $post_id );
		}

		$response = new StdClass();

		$response->post_id = $post_id;
		$response->parent_id = $post->post_parent;
		$response->can_edit = $answer;
		$response->status = $post->post_status;

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		die();
	}
}
