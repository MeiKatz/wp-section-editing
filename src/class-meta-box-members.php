<?php
namespace Secdor;

final class Meta_Box_Members
  extends Abstract_Meta_Box {

  public function add_meta_boxes( $screen_id, $group = null ) {
    // Add the meta box.
    add_meta_box(
      "secdor-edit-members",
      esc_html__( "Members", SECDOR_TEXTDOMAIN ),
      array( $this, "meta_box" ),
      $screen_id,
      "side",
      "core",
      array(
        "group" => $group,
      )
    );
  }

  public function meta_box( $group ) {
    $users = Edit_User::get_allowed_users();
  ?>
    <p>
      <ul id="secdor-member-list">
        <?php /* list members */ ?>
        <?php foreach ( $users as $user ): ?>
          <?php if ( $group->has_user( $user ) ): ?>
            <li data-user-id="<?php echo $user->id(); ?>">
              <div class="secdor-member-row">
                <div class="secdor-member-name">
                  <?php echo $user->display_name(); ?>
                </div>
                <div class="secdor-member-remove">
                  <a href="#">
                    <span class="screen-reader-text">Mitglied entfernen</span>
                    <i class="dashicons dashicons-no-alt"></i>
                  </a>
                </div>
              </div>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </p>
    <p>
      <label for="secdor-member-search-field" id="secdor-member-search-label" class="screen-reader-text">Mitglieder suchen</label>
      <div id="secdor-member-search" role="combobox" aria-haspopup="listbox" aria-autocomplete="list" aria-expanded="false" aria-labelledby="secdor-member-search-label">
        <input id="secdor-member-search-field" type="text" class="widefat" placeholder="<?php esc_attr_e( "Add Member", SECDOR_TEXTDOMAIN ); ?>" />
      </div>
    </p>
  <?php }
}

Meta_Box_Members::get_instance();
