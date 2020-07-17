<?php
namespace Secdor;

abstract class Abstract_Meta_Box {
  private static $instances = array();
  private $loaded = false;

  final private function __construct() {
    add_action(
      "secdor_load_group_edit",
      array( $this, "load" )
    );
    add_action(
      "secdor_load_group_new",
      array( $this, "load" )
    );
  }

  final public static function get_instance() {
    $class_name = get_called_class();

    if ( !isset( self::$instances[ $class_name ] ) ) {
      self::$instances[ $class_name ] = new static();
    }

    return self::$instances[ $class_name ];
  }

  final public function load() {
    if ( $this->loaded ) {
      return;
    }

    add_action(
      "add_meta_boxes",
      array( $this, "add_meta_boxes" )
    );

    $this->loaded = true;
  }

  abstract public function add_meta_boxes( $screen_id, $group = null );
}
