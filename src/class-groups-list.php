<?php
namespace Secdor;

use \Countable;
use \Iterator;

/**
 * Class for listing groups (designed to be extended)
 *
 * @todo rework to use standard array traversal function and allow for keyed arrays
 */
class Groups_List
  implements Countable, Iterator {

  private $currentIndex = 0;
  private $groups = array();

  public function __construct() {
    $this->groups = Edit_Groups::get_instance()->groups;
  }

  public function count() {
    return count( $this->groups );
  }

  public function rewind() {
    $this->currentIndex = 0;
  }

  public function valid() {
    return (
      $this->count() > 0
        && $this->currentIndex < $this->count()
    );
  }

  public function current() {
    return $this->groups[ $this->currentIndex ];
  }

  public function key() {
    return $this->currentIndex;
  }

  public function next() {
    ++$this->currentIndex;
  }
}
