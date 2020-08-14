<?php
/**
 * Abstract base class for post permissions editor
 */
abstract class BU_Permissions_Editor {

  protected $group;
  protected $post_type;
  protected $posts;

  public $page;
  public $found_posts;
  public $post_count;
  public $max_num_pages;

  public $format = 'html';

  /**
   * $group can be either a BU_Edit_Group object or a group ID
   */
  function __construct( $group, $post_type ) {

    if ( is_numeric( $group ) ) {

      $group_id = intval( $group );

      $controller = BU_Edit_Groups::get_instance();

      $this->group = $controller->get( $group_id );

      // Could be a new group
      if ( ! $this->group ) {

        $this->group = new BU_Edit_Group();
      }
    } else if ( $group instanceof BU_Edit_Group ) {

      $this->group = $group;

    } else {

      error_log( 'Not a valid group ID or object: ' . $group );
    }

    $this->post_type = $post_type;

    $this->load();

  }

  public function query( $args = array() ) {

    $defaults = array(
      'post_type' => $this->post_type,
      'post_status' => 'any',
      'posts_per_page' => $this->per_page,
      'orderby' => 'modified',
      'order' => 'DESC',
      'paged' => 1,
      );

    $args = wp_parse_args( $args, $defaults );

    $query = new WP_Query( $args );

    // Parse results
    $this->posts = $query->posts;
    $this->page = $args['paged'];
    $this->found_posts = $query->found_posts;
    $this->post_count = $query->post_count;
    $this->max_num_pages = $query->max_num_pages;

    wp_reset_postdata();

  }

  abstract public function get_posts( $post_id = 0 );
  abstract public function display();

  abstract protected function load();
  abstract protected function format_post( $post, $has_children = false );
  abstract protected function get_post_markup( $p );

}
