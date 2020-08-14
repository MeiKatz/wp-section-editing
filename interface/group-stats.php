<div id="group-stats-widget" class="secdor-widget">
	<h1 class="wp-heading-inline">
		<?php _e( "Modify Group", SECDOR_TEXTDOMAIN ); ?>
	</h1>
	<hr class="wp-header-end" />
	<div class="secdor-widget-body">
		<?php $perm_str = Secdor\Groups_Admin::group_permissions_string( $group, "\n" ); ?>
		<ul>
			<li class="clearfix"><span id="group-stats-permissions"><?php echo $perm_str; ?></span> <span class="title"><?php _e( 'Editable entries', SECDOR_TEXTDOMAIN ); ?>:</span> </li>
		</ul>
		<div class="actions clearfix">
			<?php if ( $group_id == -1 ) : ?>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add Group', SECDOR_TEXTDOMAIN ); ?>" />
			</div>
			<?php else : ?>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Update Group', SECDOR_TEXTDOMAIN ); ?>" />
			</div>
			<?php endif; ?>
		</div><!-- /.actions -->
	</div><!-- /.secdor-widget-body -->
</div><!-- /#group-stats-widget -->
