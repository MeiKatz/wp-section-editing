<?php
namespace Secdor;

use \WP_Query;
use \WP_User;
use \WP_Post;
use \StdClass;

/**
 * A Section Editing group model
 */
class Edit_Group {
  const MAX_NAME_LENGTH = 60;
  const META_KEY        = "_bu_section_group";
  const MEMBER_KEY      = "_bu_section_group_users";
  const GLOBAL_EDIT     = "_bu_section_group_global_edit";
  const POST_TYPE_NAME  = "buse_group";

  private $id           = -1;
  private $name         = "";
  private $description  = "";
  private $users        = array();
  private $global_edit  = array();
  private $created      = null;
  private $modified     = null;
  private $permissions  = null;

  /**
   * Maps a WP post object to group object
   *
   * @return Secdor\Edit_Group $group Resulting group object
   */
  public static function from_post( WP_Post $post ) {
    // Map post -> group fields
    $data["id"] = $post->ID;
    $data["name"] = $post->post_title;
    $data["description"] = $post->post_content;
    $data["created"] = strtotime( $post->post_date );
    $data["modified"] = strtotime( $post->post_modified );

    // Users and global_edit setting are stored in post meta
    $users = get_post_meta(
      $post->ID,
      self::MEMBER_KEY,
      true
    );

    $data["users"] = (
      $users
        ? $users
        : array()
    );

    $global_edit = get_post_meta(
      $post->ID,
      self::GLOBAL_EDIT,
      true
    );

    $data["global_edit"] = $global_edit;

    // Create a new group
    return new self( $data );
  }

  /**
   * Create a new group
   *
   * @todo test coverage
   *
   * @param array $data a parameter list of group data for insertion
   * @return bool|Secdor\Edit_Group False on failure.  A Secdor\Edit_Group instance for the new group on success.
   */
  public static function create( array $data ) {
    // Create new group
    $group = new self( $data );

    if ( $group->save() ) {
      return $group;
    }

    return null;
  }

  /**
   * Instantiate new edit group
   *
   * @param array $args optional parameter list to merge with defaults
   */
  public function __construct( array $args = array() ) {
    // Merge defaults
    $defaults = $this->defaults();
    $args = wp_parse_args( $args, $defaults );

    // Update fields based on incoming parameter list
    $this->assign_attributes( $args );
  }

  public function id() {
    if ( $this->id === -1 ) {
      return null;
    }

    return $this->id;
  }

  /**
   * Returns an array with default parameter values for edit group
   *
   * @return array default values for edit group model
   */
  private function defaults() {
    $attrs = $this->attribute_names();

    // derive default attribut values
    // from property definitions
    return array_merge(
      $attrs,
      array(
        "created" => time(),
        "modified" => time(),
      ),
    );
  }

  /**
   * Query for all posts that have section editing permissions assigned for this group
   *
   * @uses WP_Query
   *
   * @param array $args an optional array of WP_Query arguments, will override defaults
   * @return array an array of posts that have section editing permissions for this group
   */
  public function get_allowed_posts( array $args = array() ) {
    $defaults = array(
      "post_type" => "page",
      "meta_key" => self::META_KEY,
      "meta_value" => $this->id,
      "posts_per_page" => -1,
    );

    $args = wp_parse_args( $args, $defaults );

    $query = new WP_Query( $args );

    return $query->posts;
  }

  /**
   * Maps a group object to post object
   *
   * @return StdClass $post Resulting post object
   */
  public function to_post() {
    $post = new StdClass();

    if ( $this->id > 0 ) {
      $post->ID = $this->id;
    }

    $post->post_type = self::POST_TYPE_NAME;
    $post->post_title = $this->name;
    $post->post_content = $this->description;
    $post->post_status = "publish";

    return new WP_Post( $post );
  }

  /**
   * Does the specified user exist for this group?
   *
   * @todo test coverage
   *
   * @param int|WP_User|Secdor\Edit_User $user : either an int-like
   *  value, an instance of WP_User, or an instance of
   *  Secdor\Edit_User. It represents the user to check for.
   *
   * @return bool true if user exists, false otherwise
   */
  public function has_user( $user ) {
    $user_id = $this->user_id_of( $user );

    if ( $user_id === null ) {
      return false;
    }

    return isset( $this->users[ $user_id ] );
  }

  /**
   * Add a new user to this group
   *
   * @todo test coverage
   *
   * @param int|WP_User|Secdor\Edit_User $user : either an int-like
   *  value, an instance of WP_User, or an instance of
   *  Secdor\Edit_User. It represents the user to add to this
   *  group.
   */
  public function add_user( $user ) {
    // need to make sure the user is a member of the site
    if ( !$this->has_user( $user ) ) {
      $user_id = $this->user_id_of( $user );
      $this->users[ $user_id ] = true;
    }
  }

  /**
   * Remove a user from this group
   *
   * @todo test coverage
   *
   * @param int|WP_User|Secdor\Edit_User $user : either an int-like
   *  value, an instance of WP_User, or an instance of
   *  Secdor\Edit_User. It represents the user to remove from this
   *  group.
   */
  public function remove_user( $user ) {
    if ( $this->has_user( $user ) ) {
      $user_id = $this->user_id_of( $user );
      unset( $this->users[ $user_id ] );
    }
  }

  /**
   * Update data fields for this group
   *
   * @param array $args an array of key => value parameters to update
   */
  public function assign_attributes( $args = array() ) {
    $names = array_keys( $this->attribute_names() );

    foreach ( $args as $name => $value ) {
      if ( in_array( $name, $names ) ) {
        $this->$name = $value;
      }
    }
  }

  public function save() {
    // Map group data to post for update
    $post = $this->to_post();

    // Update DB
    $result = wp_insert_post( $post );

    if ( is_wp_error( $result ) ) {
      error_log(
        sprintf(
          "Error updating group %s: %s",
          $this->id(),
          $result->get_error_message()
        )
      );

      return false;
    }

    if ( $this->id() === null ) {
      $this->assign_attributes([
        "id" => $result,
      ]);
    }

    // Update modified time stamp
    $this->assign_attributes([
      "modified" => get_post_modified_time(
        "U", false, $result
      )
    ]);

    // Update meta data
    update_post_meta(
      $this->id(),
      self::MEMBER_KEY,
      $this->users
    );

    update_post_meta(
      $this->id(),
      self::GLOBAL_EDIT,
      $this->global_edit
    );

    return true;
  }

  public function update_attributes( array $data ) {
    // Update group data
    $this->assign_attributes( $data );

    return $this->save();
  }

  /**
   * Returns private data field keys as an array of attribute names
   *
   * Used for data serialization
   */
  private function attribute_names() {
    return get_object_vars( $this );
  }

  private function attributes() {
    $names = $this->attribute_names();
    $attrs = array();

    foreach ( $names as $name ) {
      $attrs[ $name ] = $this->$name;
    }

    return $attrs;
  }

  private function user_id_of( $user ) {
    if ( $user instanceof WP_User ) {
      return $user->ID;
    }

    if ( $user instanceof Edit_User ) {
      return $user->id();
    }

    if ( is_numeric( $user ) ) {
      return intval( $user );
    }

    return null;
  }

  public function __get( $key ) {
    if ( isset( $this->$key ) ) {
      return $this->$key;
    }

    return null;
  }

  public function __set( $key, $val ) {
    $this->$key = $val;
  }

  public function permissions() {
    if ( $this->permissions === null ) {
      $this->permissions = new Edit_Group_Permissions(
        $this->id()
      );
    }

    return $this->permissions;
  }

  /**
   * Can this group edit a particular post
   */
  public function can_edit(
    $post_id,
    $ignore = ""
  ) {
    if ( 'ignore_global' !== $ignore ) {
      $groups = Edit_Groups::get_instance();

      if ( $groups->post_is_globally_editable_by_group( $post_id, $this->id ) ) {
        return true;
      }
    }

    $allowed_groups = get_post_meta( $post_id, self::META_KEY );

    return (
      is_array( $allowed_groups )
        && in_array( $this->id, $allowed_groups )
    );
  }
}
