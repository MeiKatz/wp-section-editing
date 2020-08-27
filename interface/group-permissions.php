<?php
  $post_types = Secdor\Section_Editing_Plugin::get_supported_post_types();
  $active_post_type = current(array_filter(
    $post_types,
    function ($post_type) use ($perm_panel) {
      return ($post_type->name == $perm_panel);
    }
  ));
?>
<div id="tabcapsdiv" class="postbox">
  <h2 class="hndle">
    <?php _e( "Edit Post Type", SECDOR_TEXTDOMAIN ); ?>: <span class="members-which-tab"><?php echo $active_post_type->label; ?></span>
  </h2>
  <div class="inside">
    <div class="members-cap-tabs" aria-label="@todo">
      <?php include "group-permissions-tab-nav.php"; ?>
      <?php include "group-permissions-tab-wrap.php"; ?>
    </div><!-- .members-cap-tabs -->
  </div><!-- .inside -->
</div>
