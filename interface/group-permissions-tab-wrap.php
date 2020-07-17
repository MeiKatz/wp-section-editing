<div class="members-tab-wrap">
  <?php foreach ( $post_types as $post_type ) { ?>
  <?php
    $posts = get_pages(array(
      "post_type" => $post_type->name,
    ));
  ?>
  <?php
    $panel_id = sprintf(
      "members-tab-%s",
      $post_type->name
    );
    $is_active = ($perm_panel == $post_type->name);

    $active = (
      $is_active
        ? " active"
        : ""
    );

    $hierarchical = !!$post_type->hierarchical;

    $hiearchical_class = (
      $hierarchical
        ? "hierarchical"
        : "flat"
    );

    $is_post = "post" === $post_type->name;

    $editable = $groups->get_allowed_posts(array(
      "group" => $group_id,
      "post_type" => $post_type->name,
    ));
  ?>
    <div id="<?php echo $panel_id; ?>" class="members-tab-content editable-role" <?php echo ($is_active ? "" : "hidden"); ?> role="tabpanel" tabindex="0" data-post-type="<?php echo $post_type->name; ?>">
      <?php if (!empty($posts)) { ?>
      <?php
        $posts = new Secdor\Post_Tree_Iterator(
          new Secdor\Post_Iterator( $posts ),
        );
      ?>
      <ol role="tree">
      <?php foreach ( $posts as $post ) { ?>
      <?php
        if ( $posts->is_start_of_group() ) {
      ?>
      <li role="treeitem" data-post-id="<?php echo $post->ID; ?>" aria-expanded="false" aria-labelledby="tree-btn-<?php echo $post->ID; ?>">
        <div class="secdor-row" role="none">
          <div class="child-toggle" style="padding-left: <?php echo ($posts->getDepth() * 30); ?>px" aria-hidden="true">
            <div class="child-toggle-spacer"></div>
          </div>
          <div class="secdor-row-inner" role="none">
            <button id="tree-btn-<?php echo $post->ID; ?>" type="button" tabindex="0" class="secdor-page-title"><?php echo $post->post_title; ?></button>
          </div>
          <div class="secdor-grant-checkbox">
            <span class="screen-reader-text">Berechtigung erteilen</span>
            <input
              type="checkbox"
              name="grant-caps[]"
              value="<?php echo $post->ID; ?>"
              <?php
                if ( $group->can_edit( $post ) ) {
                  echo ' checked="checked" ';
                }
              ?>
              tabindex="-1"
            />
          </div>
        </div>
        <ol role="group">
      <?php
        } else {
      ?>
      <li role="none" data-post-id="<?php echo $post->ID; ?>">
        <div class="secdor-row" role="none">
          <div class="child-toggle" style="padding-left: <?php echo ($posts->getDepth() * 30); ?>px" aria-hidden="true">
            <div class="child-toggle-spacer"></div>
          </div>
          <div class="secdor-row-inner" role="none">
            <button id="tree-btn-<?php echo $post->ID; ?>" type="button" role="treeitem" tabindex="0" class="secdor-page-title"><?php echo $post->post_title; ?></button>
          </div>
          <div class="secdor-grant-checkbox">
            <span class="screen-reader-text">Berechtigung erteilen</span>
            <input
              type="checkbox"
              name="grant-caps[]"
              value="<?php echo $post->ID; ?>"
              <?php
                if ( $group->can_edit( $post ) ) {
                  echo ' checked="checked" ';
                }
              ?>
              tabindex="-1"
            />
          </div>
        </div>
      </li>
      <?php } /* if */ ?>
      <?php if ( $posts->is_end_of_group() ) { ?>
        </ol>
      </li>
      <?php } /* if */ ?>
      <?php } /* foreach */ ?>
      </ol>
      <?php } /* if */ ?>
    </div>
  <?php } /* foreach */ ?>
</div><!-- .members-tab-wrap -->
