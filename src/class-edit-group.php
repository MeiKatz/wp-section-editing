<?php
namespace Secdor;

/**
 * A Section Editing group model
 */
class Edit_Group {

  private $id = null;
  private $name = null;
  private $description = null;
  private $users = array();
  private $global_edit = array();
  private $created = null;
  private $modified = null;

  const MAX_NAME_LENGTH = 60;

  /**
   * Instantiate new edit group
   *
   * @param array $args optional parameter list to merge with defaults
   */
  function __construct( $args = array() ) {

    // Merge defaults
    $defaults = $this->defaults();
    $args = wp_parse_args( $args, $defaults );

    // Update fields based on incoming parameter list
    $fields = array_keys( $this->get_attributes() );
    foreach ( $fields as $key ) {
      $this->$key = $args[ $key ];
    }

  }

  /**
   * Returns an array with default parameter values for edit group
   *
   * @return array default values for edit group model
   */
  private function defaults() {

    $fields = array(
      'id' => -1,
      'name' => '',
      'description' => '',
      'users' => array(),
      'global_edit' => array(),
      'created' => time(),
      'modified' => time(),
      );

    return $fields;
  }

  /**
   * Does the specified user exist for this group?
   *
   * @todo test coverage
   *
   * @return bool true if user exists, false otherwise
   */
  public function has_user( $user_id ) {
    return in_array( $user_id, $this->users );
  }

  /**
   * Add a new user to this group
   *
   * @todo test coverage
   *
   * @param int $user_id WordPress user ID to add for this group
   */
  public function add_user( $user_id ) {

    // need to make sure the user is a member of the site
    if ( ! $this->has_user( $user_id ) ) {
      array_push( $this->users, $user_id );
    }

  }

  /**
   * Remove a user from this group
   *
   * @todo test coverage
   *
   * @param int $user_id WordPress user ID to remove from this group
   */
  public function remove_user( $user_id ) {

    if ( $this->has_user( $user_id ) ) {
      unset( $this->users[ array_search( $user_id, $this->users ) ] );
    }

  }

  /**
   * Update data fields for this group
   *
   * @param array $args an array of key => value parameters to update
   */
  public function update( $args = array() ) {

    $valid_fields = array_keys( $this->get_attributes() );

    foreach ( $args as $key => $val ) {
      if ( in_array( $key, $valid_fields ) ) {
        $this->$key = $val;
      }
    }

  }

  /**
   * Returns privata data field keys as an array of attribute names
   *
   * Used for data serialization
   */
  public function get_attributes() {

    return get_object_vars( $this );

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
}
