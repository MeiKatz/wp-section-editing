<div id="perm-panel-<?php echo $post_type->name; ?>" class="perm-panel" data-editable-original="<?php echo htmlspecialchars( implode( ',', $editable ) ); ?>">
  <?php if ( ! $hierarchical && ! $is_post ) { ?>
    <div class="perm-global-edit clearfix">
      <div class="perm-global-edit-checkbox">
        <input id="perm-global-edit-<?php echo $post_type->name; ?>" class="perm-global-edit-action" type="checkbox" name="group[global_edit][]" value="<?php echo $post_type->name; ?>" <?php echo $groups->post_is_globally_editable_by_group( $post_type->name, $group_id ) ? 'checked' : ''; ?> >
        <label class="perm-global-edit-label" for="perm-global-edit-<?php echo $post_type->name ?>">
          <?php esc_html_e( 'Full access (edit/publish/delete) to all posts of this type', SECDOR_TEXTDOMAIN ); ?>
        </label>
      </div>
    </div>
  <?php } /* if */ ?>
  <div id="perm-toolbar-<?php echo $post_type->name; ?>-top" class="perm-toolbar top clearfix">
    <?php if ( $hierarchical ) {  ?>
    <p class="alignright">
      <a href="#" class="perm-tree-expand" data-target="perm-editor-<?php echo $post_type->name; ?>"><?php _e( 'Expand All', SECDOR_TEXTDOMAIN ); ?></a> |
      <a href="#" class="perm-tree-collapse" data-target="perm-editor-<?php echo $post_type->name; ?>"><?php _e( 'Collapse All', SECDOR_TEXTDOMAIN ); ?></a>
    </p>
    <?php } else { ?>
    <p class="alignleft">
      <input id="perm-search-<?php echo $post_type->name; ?>" type="text" name="perm-action[][search]" class="perm-search <?php echo $hiearchical_class; ?>" >
      <button class="perm-search flat button-secondary"><?php printf( __( 'Search %s', SECDOR_TEXTDOMAIN ), $post_type->label ); ?></button>
    </p>
    <p class="alignright">
      <a class="perm-editor-bulk-edit" href="#" title="<?php esc_attr_e( 'Enable bulk edit mode', SECDOR_TEXTDOMAIN ); ?>"><?php _e( 'Bulk Edit', SECDOR_TEXTDOMAIN ); ?></a>
    </p>
    <?php } /* if */ ?>
  </div><!-- .perm-tooblar.top -->
  <?php if ( ! $hierarchical ) {  ?>
  <div class="perm-editor-bulk-edit-panel clearfix">
    <div class="bulk-edit-actions">
      <input type="checkbox" class="bulk-edit-select-all" name="perm-ed-bulk-edit[select-all]" value="1">
      <select name="perm-ed-bulk-edit[action]">
        <option value="none"><?php _e( 'Bulk Actions', SECDOR_TEXTDOMAIN ); ?></option>
        <option value="allowed"><?php _e( 'Allow selected', SECDOR_TEXTDOMAIN ); ?></option>
        <option value="denied"><?php _e( 'Deny selected', SECDOR_TEXTDOMAIN ); ?></option>
      </select>
      <button class="button-secondary"><?php _e( 'Apply', SECDOR_TEXTDOMAIN ); ?></button>
    </div>
  </div>
  <?php } /* if */ ?>
  <div class="perm-scroll-area">
    <input type="hidden" id="secdor-edits-<?php echo $post_type->name; ?>" class="secdor-edits" name="group[perms][<?php echo $post_type->name; ?>]" value="" />
    <div id="perm-editor-<?php echo $post_type->name; ?>" class="perm-editor <?php echo $hiearchical_class; ?>" data-post-type="<?php echo $post_type->name; ?>" data-original-global-edit="<?php echo $groups->post_is_globally_editable_by_group( $post_type->name, $group_id ) ? 'true' : ''; ?>"></div><!-- perm-editor-<?php echo $post_type->name; ?> -->
  </div>
  <?php if ( ! $hierarchical ) {  // Flat post editors get pagination ?>
  <div class="perm-toolbar bottom clearfix">
    <div class="tablenav">
      <div id="perm-editor-pagination-<?php echo $post_type->name; ?>" class="tablenav-pages">
        <span id=""class="displaying-num"><?php _e( '0 items', SECDOR_TEXTDOMAIN ); ?></span>
        <span class="pagination-links">
          <a class="first-page" title="<?php esc_attr_e( 'Go to the first page', SECDOR_TEXTDOMAIN ); ?>" href="#">«</a>
          <a class="prev-page" title="<?php esc_attr_e( 'Go to the previous page', SECDOR_TEXTDOMAIN ); ?>" href="#">‹</a>
          <span class="paging-input">
            <input type="text" class="current-page" name="perm-editor-page[<?php echo $post_type->name; ?>]" size="2" value="1"> of <span class="total-pages">1</span>
          </span>
          <a class="next-page" title="<?php esc_attr_e( 'Go to the next page', SECDOR_TEXTDOMAIN ); ?>" href="#">›</a>
          <a class="last-page" title="<?php esc_attr_e( 'Go to the last page', SECDOR_TEXTDOMAIN ); ?>" href="#">»</a>
        </span>
      </div>
    </div><!-- .tablenav -->
  </div><!-- .perm-toolbar.bottom -->
  <?php } /* if */ ?>
</div><!-- perm-panel-<?php echo $post_type->name; ?> -->
