<?php
namespace Secdor;

use \Iterator;
use \RecursiveIterator;
use \RecursiveIteratorIterator;

class Post_Tree_Iterator implements Iterator {
  private $currentItemIterator;
  private $nextItemIterator;

  public function __construct(RecursiveIterator $iterator) {
    $this->currentItemIterator = new RecursiveIteratorIterator(
      $iterator,
      RecursiveIteratorIterator::SELF_FIRST
    );
    $this->nextItemIterator = new RecursiveIteratorIterator(
      $iterator,
      RecursiveIteratorIterator::SELF_FIRST
    );
  }

  public function getDepth() {
    return $this->currentItemIterator->getDepth();
  }

  public function current() {
    return $this->currentItemIterator->current();
  }

  public function key() {
    return $this->currentItemIterator->key();
  }

  public function next() {
    $this->currentItemIterator->next();
    $this->nextItemIterator->next();
  }

  public function valid() {
    return $this->currentItemIterator->valid();
  }

  public function rewind() {
    $this->currentItemIterator->rewind();
    $this->nextItemIterator->rewind();

    if ($this->nextItemIterator->valid()) {
      $this->nextItemIterator->next();
    }
  }

  public function is_start_of_group() {
    return (
      $this->currentItemIterator->getDepth() < $this->nextItemIterator->getDepth()
    );
  }

  public function is_end_of_group() {
    return (
      $this->currentItemIterator->getDepth() > $this->nextItemIterator->getDepth()
    );
  }
}
