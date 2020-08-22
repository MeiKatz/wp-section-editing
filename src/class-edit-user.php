<?php
namespace Secdor;

use \WP_User;

class Edit_User {
  const MAX_NAME_LENGTH = 60;

  private $wp_user;

  public function __construct( WP_User $wp_user ) {
    $this->wp_user = $wp_user;
  }

  public function id() {
    if ($this->wp_user->ID == 0) {
      return null;
    }

    return intval( $this->wp_user->ID );
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
