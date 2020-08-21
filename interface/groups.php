<div class="wrap">
	<div id="icon-section-groups" class="icon32"></div>
	<h2><?php _e( 'Section Groups', SECDOR_TEXTDOMAIN ); ?></h2>
	<p><a href="<?php echo Secdor\Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary"><?php _e( 'Add an Editor Group', SECDOR_TEXTDOMAIN ); ?></a></p>
	<table id="section-groups" class="wp-list-table widefat">
		<thead>
			<tr>
				<th><?php _e( 'Name', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Description', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Members', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Editable', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Remove', SECDOR_TEXTDOMAIN ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php _e( 'Name', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Description', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Members', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Editable', SECDOR_TEXTDOMAIN ); ?></th>
				<th><?php _e( 'Remove', SECDOR_TEXTDOMAIN ); ?></th>
			</tr>
		</tfoot>
		<tbody>
		<?php if ( $group_list->have_groups() ) : ?>
			<?php $count = 0; ?>
			<?php while ( $group_list->have_groups() ) : $group = $group_list->the_group(); ?>
			<?php
			$li_class = $count % 2 ? '' : 'class="alternate"';
			$edit_url = Secdor\Groups_Admin::manage_groups_url( 'edit', array( 'id' => $group->id ) );
			$description = (strlen( $group->description ) > 60) ? substr( $group->description, 0, 60 ) . ' [...]' : $group->description;
			?>
			<tr <?php echo $li_class; ?>>
				<td><a href="<?php echo $edit_url ?>"><?php echo $group->name; ?></a></td>
				<td><?php echo $description; ?></td>
				<td><?php echo count( $group->users ); ?></td>
				<td><?php echo Secdor\Groups_Admin::group_permissions_string( $group ); ?></td>
				<td>
					<a class="submitdelete" href="<?php echo Secdor\Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group->id ) ); ?>">
					<img src="<?php echo plugins_url( SECDOR_PLUGIN_PATH . '/images/group_remove.png' ); ?>" alt="<?php esc_attr_e( 'Delete', SECDOR_TEXTDOMAIN ); ?>"></a>
				</td>
			</tr>
			<?php $count++; ?>
			<?php endwhile; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p><a href="<?php echo Secdor\Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary"><?php _e( 'Add an Editor Group', SECDOR_TEXTDOMAIN ); ?></a></p>
</div>
