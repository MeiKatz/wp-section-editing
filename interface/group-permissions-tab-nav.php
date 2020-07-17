<ul class="members-tab-nav" role="tablist">
  <?php foreach ( $post_types as $post_type ) { ?>
  <?php
    $is_active = ($perm_panel == $post_type->name);
    $panel_id = sprintf(
      "members-tab-%s",
      $post_type->name
    );
    $menu_icon = $post_type->menu_icon;

    if ( !$menu_icon || substr( $menu_icon, 0, 5 ) === "data:" ) {
      // maybe add support for custom icons
      // @todo: find out how to colorize custom icons
      //        in svg + base64
      $menu_icon = "dashicons-admin-post";
    }
  ?>
    <li class="members-tab-title">
      <a
        href="#<?php echo $panel_id; ?>"
        role="tab"
        aria-selected="<?php echo ($is_active ? "true" : "false"); ?>"
        aria-controls="<?php echo $panel_id; ?>"
      ><i class="dashicons <?php echo $menu_icon; ?>"></i> <span class="label"><?php echo $post_type->label; ?></span></a>
    </li>
  <?php } /* foreach */ ?>
</ul><!-- .members-tab-nav -->
