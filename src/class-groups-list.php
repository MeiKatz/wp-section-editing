<?php
/**
 * Class for listing groups (designed to be extended)
 *
 * @todo rework to use standard array traversal function and allow for keyed arrays
 */
class BU_Groups_List {

  public $current_group;
  public $edit_groups;

  function __construct() {
    $this->edit_groups = BU_Edit_Groups::get_instance();
    $this->current_group = -1;
  }

  function have_groups() {
    if ( count( $this->edit_groups->groups ) > 0 && $this->current_group < (count( $this->edit_groups->groups ) - 1) ) {
      return true;
    } else {
      return false;
    }
  }

  function the_group() {
    $this->current_group++;
    return $this->edit_groups->groups[ $this->current_group ];
  }

  function rewind() {
    $this->current_group = -1;
  }
}
