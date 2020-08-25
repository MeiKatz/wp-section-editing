<?php
namespace Secdor;

use \WP_Query;

class Edit_Group_Permissions {
  private $group_id;

  public function __construct( $group_id ) {
    $this->group_id = $group_id;
  }

  /**
   * Update permissions for a group
   *
   * @param array $permissions Permissions, as an associative array indexed by post type
   */
  public function update( array $permissions ) {
    global $wpdb;

    foreach ( $permissions as $post_type => $ids_by_status ) {
      if ( !is_array( $ids_by_status ) ) {
        error_log( "Unexpected value found while updating permissions: $ids_by_status" );
        continue;
      }

      // Incoming allowed posts
      $allowed_ids = (
        isset( $ids_by_status['allowed'] )
          ? $ids_by_status['allowed']
          : array()
      );

      if ( !empty( $allowed_ids ) ) {
        // Make sure we don't add allowed meta twice
        $previously_allowed = $wpdb->get_col(
          $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE post_id IN (%s) AND meta_key = %s AND meta_value = %s",
            implode( ',', $allowed_ids ),
            Edit_Group::META_KEY,
            $this->group_id
          )
        );

        $additions = array_merge(
          array_diff(
            $allowed_ids,
            $previously_allowed
          )
        );

        foreach ( $additions as $post_id ) {
          add_post_meta(
            $post_id,
            Edit_Group::META_KEY,
            $this->group_id
          );
        }
      }

      // Incoming restricted posts
      $denied_ids = (
        isset( $ids_by_status['denied'] )
          ? $ids_by_status['denied']
          : array()
      );

      if ( !empty( $denied_ids ) ) {
        // Sanitize the list of IDs for direct use in the query.
        $denied_ids_str = implode( ',', array_map( 'intval', $denied_ids ) );

        // Select meta_id's for removal based on incoming posts
        $denied_meta_ids = $wpdb->get_col(
          $wpdb->prepare(
            "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id IN ({$denied_ids_str}) AND meta_key = %s AND meta_value = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            Edit_Group::META_KEY,
            $this->group_id
          )
        );

        // Bulk deletion
        if ( !empty( $denied_meta_ids ) ) {
          // Sanitize the list of IDs for direct use in the query.
          $denied_meta_ids_str = implode( ',', array_map( 'intval', $denied_meta_ids ) );

          // Remove allowed status in one query
          $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_id IN ({$denied_meta_ids_str})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

          // Purge cache
          foreach ( $denied_ids as $post_id ) {
            wp_cache_delete( $post_id, 'post_meta' );
          }
        }
      }
    }
  }

  public function delete() {
    $supported_post_types = Section_Editing_Plugin::get_supported_post_types( 'names' );

    $meta_query = array(
      'key' => Edit_Group::META_KEY,
      'value' => $this->group_id,
      'compare' => 'LIKE',
    );

    $args = array(
      'post_type' => $supported_post_types,
      'meta_query' => array( $meta_query ),
      'posts_per_page' => -1,
      'fields' => 'ids',
    );

    $query = new WP_Query( $args );

    foreach ( $query->posts as $post_id ) {
      delete_post_meta(
        $post_id,
        Edit_Group::META_KEY,
        $this->group_id
      );
    }
  }}
