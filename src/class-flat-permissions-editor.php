<?php
namespace Secdor;

/**
 * Permissions editor for flat post types
 */
class Flat_Permissions_Editor extends Permissions_Editor {

  protected function load() {

    // Load user setting for posts per page on the manage groups screen
    $user = get_current_user_id();
    $per_page = get_user_meta(
      $user,
      Groups_Admin::POSTS_PER_PAGE_OPTION,
      true
    );

    if ( empty( $per_page ) || $per_page < 1 ) {
      // get the default value if none is set
      $per_page = 10;
    }

    $this->per_page = $per_page;

  }

  /**
   * Display posts using designated output format
   */
  public function display() {

    switch ( $this->format ) {

      case 'json':
        $output = $this->get_posts();
        echo json_encode( $output );
        break;

      case 'html':default:
          echo $this->get_posts();
        break;
    }

  }

  /**
   * Get posts intended for display by permissions editors
   */
  public function get_posts( $post_id = 0 ) {

    if ( $this->format == 'json' ) {
      $posts = array();
    } else if ( $this->format == 'html' ) {
      $posts = '';
    }

    if ( ! empty( $this->posts ) ) {

      $count = 0;

      if ( $this->format == 'html' ) {
        $posts = '<ul class="perm-list flat">';
      }

      foreach ( $this->posts as $id => $post ) {

        // Format post data for permissions editor display
        $p = $this->format_post( $post );

        // Alternating table rows for prettiness
        $alt_class = $count % 2 ? '' : 'alternate';

        if ( $alt_class ) {
          $p['attr']['class'] = $alt_class;
        }

        // Add this post with the specified format
        switch ( $this->format ) {

          case 'json':
            array_push( $posts, $p );
            break;

          case 'html': default:
              $posts .= $this->get_post_markup( $p );
            break;
        }

        $count++;

      }

      if ( $this->format == 'html' ) {
        $posts .= '</ul>';
      }
    } else {
          $labels = get_post_type_object( $this->post_type )->labels;
          $posts = sprintf( '<ul class="perm-list flat"><li><p>%s</p></li></ul>', $labels->not_found );
    }

    return $posts;

  }

  /**
   * Takes an array of post data formatted for permissions editor output,
   * converts to HTML markup
   *
   * The format of this markup lines up with default jstree markup
   */
  public function get_post_markup( $p ) {

    // Permission status
    $icon = "<ins class=\"{$p['data']['icon']}\"> </ins>\n";

    // Publish information
    $meta = '';
    $published_label = __( 'Published on', SECDOR_TEXTDOMAIN );
    $draft_label = __( 'Draft', SECDOR_TEXTDOMAIN );

    switch ( $p['metadata']['post_status'] ) {

      case 'publish':
        $meta = " &mdash; $published_label {$p['metadata']['post_date']}";
        break;

      case 'draft':
        $meta = " &mdash; <em>$draft_label</em>";
        break;

    }

    // Bulk Edit Checkbox
    $checkbox = sprintf('<input type="checkbox" name="bulk-edit[%s][%s]" value="1">',
      $this->post_type,
      $p['metadata']['post_id']
    );

    // Perm actions button
    $perm_state = $p['metadata']['editable'] ? 'denied' : 'allowed';
    $perm_label = $perm_state == 'allowed' ? __( 'Allow', SECDOR_TEXTDOMAIN ) : __( 'Deny', SECDOR_TEXTDOMAIN );
    $button = sprintf( '<button class="edit-perms %s">%s</button>', $perm_state, $perm_label );

    // Anchor
    $a = sprintf( '<a href="#"><span class="title">%s</span>%s%s</a>',
      $p['data']['title'],
      $meta,
      $button
    );

    // Post list item
    $li = sprintf( "<li id=\"%s\" class=\"%s\" rel=\"%s\" data-editable=\"%s\" data-editable-original=\"%s\">%s%s%s</li>\n",
      $p['attr']['id'],
      $p['attr']['class'],
      $p['attr']['rel'],
      json_encode( $p['metadata']['editable'] ),
      json_encode( $p['metadata']['editable-original'] ),
      $icon,
      $checkbox,
      $a
    );

    return $li;

  }

  /**
   * Format a single post for display by the permissions editor
   *
   * Data structure is jstree-friendly
   *
   * @todo merge with hierarchical format_post logic
   */
  public function format_post( $post, $has_children = false ) {

    $editable = Group_Permissions::group_can_edit( $this->group->id, $post->ID, 'ignore_global' );
    $perm = $editable ? 'allowed' : 'denied';

    $post->post_title = empty( $post->post_title ) ? __( '(no title)', SECDOR_TEXTDOMAIN ) : $post->post_title;

    $p = array(
      'attr' => array(
        'id' => esc_attr( 'p' . $post->ID ),
        'rel' => esc_attr( $perm ),
        'class' => '',
      ),
      'data' => array(
        'title' => esc_html( $post->post_title ),
        'icon' => 'flat-perm-icon',
      ),
      'metadata' => array(
        'post_id' => $post->ID,
        'post_date' => date( get_option( 'date_format' ), strtotime( $post->post_date ) ),
        'post_status' => $post->post_status,
        'editable' => $editable,
        'editable-original' => $editable,
        ),
      );

    return $p;

  }
}
