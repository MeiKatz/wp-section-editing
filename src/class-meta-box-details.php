<?php
namespace Secdor;

final class Meta_Box_Details
  extends Abstract_Meta_Box {

  public function add_meta_boxes( $screen_id, $group = null ) {
    // Add the meta box.
    add_meta_box(
      "submitdiv",
      esc_html__( "Section Group", SECDOR_TEXTDOMAIN ),
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
    <div class="submitbox">
      <div id="misc-publishing-actions">
        <div class="misc-pub-section misc-pub-section-users">
          <i class="dashicons dashicons-admin-users"></i>
          <?php _e( "Members", SECDOR_TEXTDOMAIN ); ?>:
          <strong class="member-count"><?php echo count( $group->users ); ?></strong>
        </div>
      </div>
      <div id="major-publishing-actions">
        <div id="delete-action">
          <?php if ( $group->id() !== null ): ?>
            <a class="submitdelete deletion" href="<?php echo Groups_Admin::manage_groups_url( "delete", array( "id" => $group->id() ) ); ?>"><?php _e( "Remove", SECDOR_TEXTDOMAIN ); ?></a>
          <?php endif; ?>
        </div>
        <div id="publishing-action">
          <?php if ( $group->id() === null ): ?>
            <button type="submit" id="publish" class="button button-primary" name="publish" value="create"><?php _e( "Add Group", SECDOR_TEXTDOMAIN ); ?></button>
          <?php else: ?>
            <button type="submit" id="publish" class="button button-primary" name="publish" value="update"><?php _e( "Update Group", SECDOR_TEXTDOMAIN ); ?></button>
          <?php endif; ?>
        </div>
        <div class="clear"></div>
      </div>
    </div>
  <?php }
}

Meta_Box_Details::get_instance();
