<?php
/**
 * Permissions editor for hierarchical post types
 *
 * @todo now that the navigation plugin has the BU_Navigation_Tree_View class, most of this
 *  logic is redundant.  The only added complexity is the need for a "group_id" field for
 *  filtering post meta.
 *
 * @uses (depends on) BU Navigation library
 */
class BU_Hierarchical_Permissions_Editor extends BU_Permissions_Editor {

  private $child_of = 0;

  protected function load() {

    // We don't need these
    remove_filter( 'bu_navigation_filter_pages', 'bu_navigation_filter_pages_exclude' );
    remove_filter( 'bu_navigation_filter_pages', 'bu_navigation_filter_pages_external_links' );

    // But we definitely need these
    add_filter( 'bu_navigation_filter_pages', array( $this, 'filter_posts' ) );

  }

  // ____________________INTERFACE_________________________
  /**
   * Custom query for hierarchical posts
   *
   * @uses BU Navigation plugin
   */
  public function query( $args = array() ) {

    $defaults = array(
      'child_of' => 0,
      'post_type' => $this->post_type,
      );

    $r = wp_parse_args( $args, $defaults );

    // Search term
    // @todo not yet implemented
    if ( ! empty( $r['s'] ) ) {
      if ( isset( $r['child_of'] ) ) {
        unset( $r['child_of'] );
      }
      parent::query( $args );
      return;
    }

    $this->child_of = $r['child_of'];

    $section_args = array( 'direction' => 'down', 'post_types' => $r['post_type'] );

    // Don't load the whole tree at once
    if ( $this->child_of == 0 ) {
      $section_args['depth'] = 1;
    } else {
      $section_args['depth'] = 0;
    }

    // Make sure navigation plugin functions are available before querying
    if ( ! function_exists( 'bu_navigation_get_pages' ) ) {
      $this->posts = array();
      error_log( 'BU Navigation Plugin must be activated in order for hierarchical permissions editors to work' );
      return false;
    }

    // Get post IDs for this section
    $sections = bu_navigation_gather_sections( $this->child_of, $section_args );

    // Fetch posts
    $page_args = array(
      'sections' => $sections,
      'post_types' => $r['post_type'],
      'suppress_urls' => true,
      );

    $root_pages = bu_navigation_get_pages( $page_args );
    $this->posts = bu_navigation_pages_by_parent( $root_pages );

  }

  /**
   * Display posts using designated output format
   */
  public function display() {

    switch ( $this->format ) {

      case 'json':
        $posts = $this->get_posts( $this->child_of );
        echo json_encode( $posts );
        break;

      case 'html': default:
          echo $this->get_posts( $this->child_of );
        break;

    }

  }

  /**
   * Get posts intended for display by permissions editors
   */
  public function get_posts( $post_id = 0 ) {

    if ( array_key_exists( $post_id, $this->posts ) && ( count( $this->posts[ $post_id ] ) > 0 ) ) {
      $posts = $this->posts[ $post_id ];
    } else {
      $posts = array();
    }

    // Initialize output var depending on format
    $output = null;

    switch ( $this->format ) {

      case 'json':
        $output = array();
        break;

      case 'html':default:
          $output = '';
        break;
    }

    // Loop through posts recursively
    foreach ( $posts as $post ) {

      $has_children = array_key_exists( $post->ID, $this->posts );

      // Format post data
      $p = $this->format_post( $post, $has_children );

      // Maybe fetch descendents
      if ( $has_children ) {

        // Default to closed with children
        $p['state'] = 'closed';

        if ( $this->child_of > 0 ) {

          $post_id = $post->ID;
          $descendents = $this->get_posts( $post_id );

          if ( ! empty( $descendents ) ) {
            $p['children'] = $descendents;
          }
        } else {
          $perm = $post->editable ? 'allowed' : 'denied';
          // Let users known descendents have not yet been loaded
          $p['attr']['rel'] = $perm . '-desc-unknown';

        }
      }

      // Return post in correct format
      switch ( $this->format ) {

        case 'json':
          array_push( $output, $p );
          break;

        case 'html': default:
            $output .= get_post_markup( $p );
          break;

      }
    }

    return $output;

  }

  /**
   * Takes an array of post data formatted for permissions editor output,
   * converts to HTML markup
   *
   * The format of this markup lines up with default jstree markup
   */
  protected function get_post_markup( $p ) {

    $a = sprintf( '<a href="#">%s</a>', $p['data'] );

    $descendents = ! empty( $p['children'] ) ? sprintf( "<ul>%s</ul>\n", $p['children'] ) : '';

    $markup = sprintf("<li id=\"%s\" class=\"%s\" rel=\"%s\" data-editable=\"%s\" data-editable-original=\"%s\">%s %s</li>\n",
      $p['attr']['id'],
      $p['attr']['class'],
      $p['attr']['rel'],
      $p['metadata']['editable'],
      $p['metadata']['editable-original'],
      $a,
      $descendents
    );

    return $markup;
  }

  /**
   * Format a single post for display by the permissions editor
   *
   * Data structure is jstree-friendly
   *
   * @todo merge with flat format_post logic
   */
  protected function format_post( $post, $has_children = false ) {

    $title = isset( $post->navigation_label ) ? $post->navigation_label : $post->post_title;
    $classes = ( $has_children ) ? 'jstree-closed' : 'jstree-default';
    $perm = $post->editable ? 'allowed' : 'denied';

    $p = array(
      'attr' => array(
        'id' => esc_attr( 'p' . $post->ID ),
        'rel' => esc_attr( $perm ),
        'class' => esc_attr( $classes ),
      ),
      'data' => array(
        'title' => esc_html( $title ),
        ),
      'metadata' => array(
        'editable' => $post->editable,
        'editable-original' => $post->editable,
        ),
      'children' => null,
      );

    return $p;

  }


  // __________________NAVIGATION FILTERS______________________
  /**
   * Add custom section editable properties to the post objects returned by bu_navigation_get_pages()
   */
  public function filter_posts( $posts ) {
    global $wpdb;

    if ( ( is_array( $posts ) ) && ( count( $posts ) > 0 ) ) {

      /* Gather all group post meta in one shot */
      $ids = array_keys( $posts );

      // Sanitize the list of IDs for direct use in the query.
      $ids = implode( ',', array_map( 'intval', $ids ) );

      $group_meta = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$ids}) AND meta_value = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
          BU_Group_Permissions::META_KEY,
          $this->group->id
        ),
        OBJECT_K
      ); // get results as objects in an array keyed on post_id

      if ( ! is_array( $group_meta ) ) {
        $group_meta = array();
      }

      // Append permissions to post object
      foreach ( $posts as $post ) {

        $post->editable = false;

        if ( array_key_exists( $post->ID, $group_meta ) ) {
          $perm = $group_meta[ $post->ID ];

          if ( $perm->meta_value === (string) $this->group->id ) {
            $post->editable = true;
          }
        }
      }
    }

    return $posts;

  }
}
