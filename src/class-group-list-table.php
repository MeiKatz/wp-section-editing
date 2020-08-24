<?php
namespace Secdor;

use \WP_List_Table;

class Group_List_Table extends WP_List_Table {
  public function get_columns() {
    return array(
      "name" => __( "Name" ),
      "description" => __( "Description" ),
      "members_count" => __( "Members count", SECDOR_TEXTDOMAIN ),
      "editable_entries" => __( "Editable entries", SECDOR_TEXTDOMAIN ),
    );
  }

  public function column_name( $group ) {
    return sprintf(
      '<a href="%s">%s</a><br />%s',
      esc_attr( $this->edit_url_for( $group ) ),
      esc_html( $group->name ),

      $this->row_actions(
        array(
          "edit" => sprintf(
            '<a href="%s">%s</a>',
            esc_attr( $this->edit_url_for( $group ) ),
            __( "Edit" )
          ),
          "delete" => sprintf(
            '<a href="%s" class="submitdelete">%s</a>',
            esc_attr( $this->delete_url_for( $group ) ),
            __( "Delete" )
          ),
        )
      )
    );
  }

  public function column_description( $group ) {
    $description = $group->description;

    if ( empty( $description ) ) {
      return "—";
    }

    if ( strlen( $description ) > 60 ) {
      return sprintf(
        "%.60s [...]",
        $description
      );
    }

    return $description;
  }

  public function column_members_count( $group ) {
    return count( $group->users );
  }

  public function column_editable_entries( $group ) {
    $string = Groups_Admin::group_permissions_string( $group );

    if ( empty( $string ) ) {
      return "—";
    }

    return $string;
  }

  public function prepare_items() {
    $group_list = new Groups_List();
    $groups = array();

    foreach ( $group_list as $group ) {
      array_push(
        $groups,
        $group
      );
    }

    $columns = $this->get_columns();
    $hidden = array();
    $sortable = array();

    $this->_column_headers = array(
      $columns,
      $hidden,
      $sortable
    );
    $this->items = $groups;
  }

  private function truncate_description( $value ) {
    if ( empty( $value ) ) {
      return "—";
    }

    if ( strlen( $value ) > 60 ) {
      return sprintf(
        "%.60s [...]",
        $value
      );
    }

    return $value;
  }

  private function edit_url_for( $group ) {
    return Groups_Admin::manage_groups_url(
      "edit",
      array(
        "id" => $group->id,
      )
    );
  }

  private function delete_url_for( $group ) {
    return Groups_Admin::manage_groups_url(
      "delete",
      array(
        "id" => $group->id,
      )
    );
  }
}
