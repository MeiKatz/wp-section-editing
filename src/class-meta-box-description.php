<?php
namespace Secdor;

final class Meta_Box_Description
  extends Abstract_Meta_Box {

  public function add_meta_boxes( $screen_id, $group = null ) {
    // Add the meta box.
    add_meta_box(
      "secdor-edit-description",
      esc_html__( "Description", SECDOR_TEXTDOMAIN ),
      array( $this, "meta_box" ),
      $screen_id,
      "side",
      "core",
      array(
        "group" => $group,
      )
    );
  }

  public function meta_box( $group ) { ?>
    <p>
      <textarea placeholder="<?php esc_attr_e( "Description", SECDOR_TEXTDOMAIN ); ?>" type="text" class="widefat"></textarea>
    </p>
  <?php }
}

Meta_Box_Description::get_instance();
