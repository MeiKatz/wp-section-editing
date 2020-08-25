<div id="group-stats-widget" class="secdor-widget">
	<div class="secdor-widget-header"><h4><?php _e( 'Modify Group', SECDOR_TEXTDOMAIN ); ?></h4></div>
	<div class="secdor-widget-body">
		<?php $perm_str = Secdor\Groups_Admin::group_permissions_string( $group, array( 'sep' => "\n" ) ); ?>
		<ul>
			<li><span class="title"><?php _e( 'Name', SECDOR_TEXTDOMAIN ); ?>:</span> <span id="group-stats-name"><?php echo $group->name; ?></span></li>
			<li><span class="title"><?php _e( 'Members', SECDOR_TEXTDOMAIN ); ?>:</span> <span class="member-count"><?php echo count( $group->users ); ?></span></li>
			<li class="clearfix"><span id="group-stats-permissions"><?php echo $perm_str; ?></span> <span class="title"><?php _e( 'Permission to Edit', SECDOR_TEXTDOMAIN ); ?>:</span> </li>
		</ul>
		<div class="actions clearfix">
			<?php if ( $group_id == -1 ) : ?>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add Group', SECDOR_TEXTDOMAIN ); ?>" />
			</div>
			<?php else : ?>
			<?php $delete_url = Secdor\Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group_id ) ); ?>
			<div id="delete-action">
				<a href="<?php echo $delete_url; ?>" class="submitdelete deletion" title="<?php esc_attr_e( 'Delete group', SECDOR_TEXTDOMAIN ); ?>"><?php _e( 'Delete', SECDOR_TEXTDOMAIN ); ?></a>
			</div>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Update Group', SECDOR_TEXTDOMAIN ); ?>" />
			</div>
			<?php endif; ?>
		</div><!-- /.actions -->
	</div><!-- /.secdor-widget-body -->
</div><!-- /#group-stats-widget -->
