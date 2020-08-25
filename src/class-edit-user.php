<?php
namespace Secdor;

use \WP_User;
use \WP_User_Query;

class Edit_User {
  const MAX_NAME_LENGTH = 60;

  private $wp_user;

  public function __construct( WP_User $wp_user ) {
    $this->wp_user = $wp_user;
  }

  public static function get_current() {
    $wp_user = wp_get_current_user();
    return new self( $wp_user );
  }

  /**
   * Query for all users with the cability to be added to section groups
   */
  public static function get_allowed_users(
    array $query_args = array()
  ) {

    $defaults = array(
      "search_columns" => array(
        "user_login",
        "user_nicename",
        "user_email",
      ),
    );

    $query_args = wp_parse_args( $query_args, $defaults );
    $wp_user_query = new WP_User_Query( $query_args );

    $allowed_users = array();

    // Filter blog users by section editing status
    foreach ( $wp_user_query->get_results() as $wp_user ) {
      $edit_user = new Edit_User( $wp_user );

      if ( $edit_user->is_allowed() ) {
        $allowed_users[] = $wp_user;
      }
    }

    return $allowed_users;
  }

  /**
   * Check if a user has the capability to be added to section groups
   */
  public function is_allowed() {
    if ( is_super_admin( $this->id() ) ) {
      return false;
    }

    if ( $this->wp_user->has_cap( "edit_in_section" ) ) {
      return true;
    }

    return false;
  }

  public function id() {
    if ($this->wp_user->ID == 0) {
      return null;
    }

    return intval( $this->wp_user->ID );
  }

  public function login() {
    return $this->wp_user->user_login;
  }

  public function nice_name() {
    return $this->wp_user->user_nicename;
  }

  public function email_address() {
    return $this->wp_user->user_email;
  }

  public function first_name() {
    return $this->wp_user->first_name;
  }

  public function last_name() {
    return $this->wp_user->last_name;
  }

  public function full_name() {
    return sprintf(
      "%s %s",
      $this->first_name(),
      $this->last_name()
    );
  }

  public function display_name() {
    return $this->wp_user->display_name;
  }

  public function can_edit_section( $post_id ) {
    if ( $this->id() === null ) {
      return false;
    }

    if ( $post_id == 0 ) {
      return false;
    }

    $groups = $this->edit_groups();

    if ( empty( $groups ) ) {
      return false;
    }

    foreach ( $groups as $key => $group ) {
      // This group is good, bail here
      if ( $group->can_edit( $post_id ) ) {
        return true;
      }
    }

    // User is section editor, but not allowed for this section
    return false;
  }

  public function has_capability( $capability ) {
    $wp_user = $this->wp_user;

    return (
      isset( $wp_user->allcaps[ $capability ] )
        && $wp_user->allcaps[ $capability ]
    );
  }

  public function has_capabilities( array $capabilities ) {
    foreach ( $capabilities as $capability ) {
      if ( !$this->has_capability( $capability ) ) {
        return false;
      }
    }

    return true;
  }

  public function edit_groups() {
    // Get all groups for this user
    $edit_groups_o = Edit_Groups::get_instance();
    return $edit_groups_o->find_groups_for_user( $this->id() );
  }
}
