<?php
namespace Secdor;

use \RecursiveIterator;

class Post_Iterator implements RecursiveIterator {
  private $posts;
  private $parent_id;

  public function __construct(
    array $posts = array(),
    $parent_id = 0
  ) {
    $this->posts = $posts;
    $this->parent_id = $parent_id;
  }

  public function hasChildren() {
    // create a copy of the posts array
    // so we don't mess with the internal pointer
    $posts = array_replace( $this->posts );

    foreach ( $posts as $post ) {
      if ( $post->post_parent == $this->key() ) {
        return true;
      }
    }

    return false;
  }

  public function getChildren() {
    return new self(
      $this->posts,
      $this->key()
    );
  }

  public function current() {
    return current( $this->posts );
  }

  /**
   * returns the post id of the current WP_Post object
   */
  public function key() {
    $current = $this->current();
    return (int) $current->ID;
  }

  public function next() {
    while ( $this->valid() ) {
      $next = next( $this->posts );

      if ( $next && $next->post_parent == $this->parent_id ) {
        break;
      }
    }
  }

  public function rewind() {
    reset( $this->posts );
  }

  public function valid() {
    return key( $this->posts ) !== null;
  }
}
