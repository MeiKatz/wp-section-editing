<?php
namespace Secdor;

use \StdClass;
use \WP_Http;

/**
 * Centralized admin ajax routing
 *
 * @todo sanitize ALL input
 * @todo AJAX nonces
 */
class Groups_Admin_Ajax {
	public static function register_hooks() {
		add_action(
			"wp_ajax_secdor-users",
			array(
				__CLASS__,
				"handle_users",
			)
		);

		add_action(
			"wp_ajax_secdor-users-autocomplete",
			array(
				__CLASS__,
				"handle_users_autocomplete",
			)
		);

		// legacy actions
		add_action(
			"wp_ajax_secdor_site_users_script",
			array(
				__CLASS__,
				"site_users_script",
			)
		);

		add_action(
			"wp_ajax_secdor_search_posts",
			array(
				__CLASS__,
				"search_posts",
			)
		);

		add_action(
			"wp_ajax_secdor_render_post_list",
			array(
				__CLASS__,
				"render_post_list",
			)
		);

		add_action(
			"wp_ajax_secdor_can_edit",
			array(
				__CLASS__,
				"can_edit",
			)
		);

		add_action(
			"wp_ajax_secdor_can_move",
			array(
				__CLASS__,
				"can_move",
			)
		);

		add_action(
			"wp_ajax_secdor-editor-panels",
			array(
				__CLASS__,
				"editor_panels",
			)
		);
	}

	public static function editor_panels() {
		self::assert_ajax();

		$group = self::get_current_group();
		$post_types = Section_Editing_Plugin::get_supported_post_types();

		$panels = array_map(function ( $post_type ) use ( $group ) {
			$posts = get_pages(array(
				"post_type" => $post_type->name,
			));

			$menu_icon = $post_type->menu_icon;

			if ( ! $menu_icon || substr( $menu_icon, 0, 5 ) === "data:" ) {
			  // maybe add support for custom icons
			  // @todo: find out how to colorize custom icons
			  //        in svg + base64
			  $menu_icon = "dashicons-admin-post";
			}

			if ( empty( $posts ) ) {
				$posts = array();
			}

			$reduced_posts = array_map(function ( $post ) use ( $group ) {
			  return array(
			    "id" => $post->ID,
			    "label" => $post->post_title,
			    "parent_id" => $post->post_parent,
			    "selected" => $group->can_edit( $post ),
			  );
			}, $posts);

			$posts_by_id = array_reduce(
				$reduced_posts,
				function ( $posts, $post ) {
					$posts[ $post["id"] ] = $post;
					return $posts;
				},
				array()
			);

			return array(
				"id" => $post_type->name,
				"icon" => $menu_icon,
				"type" => $post_type->name,
				"label" => $post_type->label,
				"posts" => $posts_by_id,
				"display_as" => (
					$post_type->hierarchical
						? "tree"
						: "list"
				),
			);
		}, $post_types);

		return self::send_json(
			WP_Http::OK,
			$panels
		);
	}

	public static function handle_users_autocomplete() {
		self::assert_ajax();

		$term = (
			isset( $_GET[ "term" ] )
				? $_GET[ "term" ]
				: ""
		);

		$users = Edit_User::get_allowed_users();

		$current_group = self::get_current_group();

		if ( $current_group !== null ) {
			$users = array_filter(
				$users,
				function ( $user ) use ( $current_group ) {
					return !$current_group->has_user( $user );
				}
			);
		}


		$users = self::filter_users( $users, $term );
		$users = self::format_users( $users );

		return self::send_json(
			WP_Http::OK,
			$users
		);
	}

	public static function handle_users() {
		self::assert_ajax();

		$request_method = $_SERVER[ "REQUEST_METHOD" ];

		if ( $request_method === "GET" ) {
			return self::show_users();
		}

		if ( $request_method !== "POST" ) {
			return self::method_not_allowed();
		}

		$data = self::get_body();
		$action = $data[ "action" ];

		switch ( $action ) {
			case "add":
				return self::add_user();
			case "remove":
				return self::remove_user();
		}
	}

	private static function method_not_allowed() {
		return self::send_json(
			WP_Http::METHOD_NOT_ALLOWED
		);
	}

	private static function show_users() {
		$term = (
			isset( $_GET[ "term" ] )
				? $_GET[ "term" ]
				: ""
		);

		$users = Edit_User::get_allowed_users();

		$users = self::filter_users( $users, $term );
		$users = self::format_users( $users );

		return self::send_json(
			WP_Http::OK,
			$users
		);
	}

	private static function add_user() {
		$data = self::get_body();

		$user = Edit_User::find( $data[ "user_id" ] );
		$current_group = self::get_current_group();

		if ( $user === null || $current_group === null ) {
			return self::send_json(
				WP_Http::BAD_REQUEST
			);
		}

		$current_group->add_user( $user );

		if ( !$current_group->save() ) {
			return self::send_json(
				WP_Http::INTERNAL_SERVER_ERROR
			);
		}

		return self::send_json(
			WP_Http::OK,
			array(
				"label" => $user->full_name(),
				"value" => $user->id(),
				"id" => $user->id(),
				"email_address" => $user->email_address(),
			)
		);
	}

	private static function remove_user() {
		$data = self::get_body();

		$user = Edit_User::find( $data[ "user_id" ] );
		$current_group = self::get_current_group();

		if ( $user === null || $current_group === null ) {
			return self::send_json(
				WP_Http::BAD_REQUEST
			);
		}

		$current_group->remove_user( $user );

		if ( !$current_group->save() ) {
			return self::send_json(
				WP_Http::INTERNAL_SERVER_ERROR
			);
		}

		return self::send_json(
			WP_Http::OK,
			array(
				"label" => $user->full_name(),
				"value" => $user->id(),
				"id" => $user->id(),
				"email_address" => $user->email_address(),
			)
		);
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

	private static function assert_ajax() {
		if ( !defined( "DOING_AJAX" ) || !DOING_AJAX ) {
			return self::send_json(
				WP_Http::FORBIDDEN
			);
		}
	}

	private static function filter_users(
		array $users,
		$term = null
	) {
		if ( empty( $term ) ) {
			return $users;
		}

		return array_filter(
			$users,
			function ( $user ) use ( $term ) {
				return (
					stripos( $user->full_name(), $term ) !== false
						|| stripos( $user->email_address(), $term ) !== false
				);
			}
		);
	}

	private static function format_users( array $users ) {
		return array_map(function ( $user ) {
			return array(
				"label" => $user->full_name(),
				"value" => $user->id(),
				"id" => $user->id(),
				"email_address" => $user->email_address(),
			);
		}, $users );
	}

	private static function send_json(
		$status_code,
		$data = null
	) {
		if ( ! headers_sent() ) {
			header( "Content-Type: application/json" );
			http_response_code( $status_code );
		}

		echo json_encode( $data );

		die();
	}

	private static function get_group_id() {
		if ( isset( $_GET[ "group_id" ] ) ) {
			return intval( $_GET[ "group_id" ] );
		}

		return null;
	}

	private static function get_current_group() {
		$group_id = self::get_group_id();

		if ( $group_id === null ) {
			return null;
		}

		$groups = Edit_Groups::get_instance();
		$group = $groups->get( $group_id );

		if ( $group === null || $group === false ) {
			return null;
		}

		return $group;
	}

	private static function get_body() {
		static $body = null;
		static $parsed = false;

		if ( !$parsed ) {
			$content_type = $_SERVER[ "CONTENT_TYPE" ];
			$data = file_get_contents( "php://input" );;

			switch ( $content_type ) {
				case "application/x-www-form-urlencoded";
					parse_str( $data, $body );
					break;

				case "application/json":
					$body = json_decode( $data, true );
					break;
			}

			$parsed = true;
		}

		return $body;
	}
}
