<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php _e( "Section Groups", SECDOR_TEXTDOMAIN ); ?>
	</h1>
	<a href="<?php echo Secdor\Groups_Admin::manage_groups_url( "add" ); ?>" class="page-title-action">
		<?php _e( "Add New", SECDOR_TEXTDOMAIN ); ?>
	</a>
	<table id="section-groups" class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th class="manage-column"><?php _e( "Name", SECDOR_TEXTDOMAIN ); ?></th>
				<th class="manage-column"><?php _e( "Description", SECDOR_TEXTDOMAIN ); ?></th>
				<th class="manage-column num"><?php _e( "Members", SECDOR_TEXTDOMAIN ); ?></th>
				<th class="manage-column"><?php _e( "Editable", SECDOR_TEXTDOMAIN ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $group_list->valid() ) : ?>
			<?php foreach ( $group_list as $i => $group ) : ?>
			<?php
			$li_class = ($i % 2) ? '' : 'class="alternate"';
			$edit_url = Secdor\Groups_Admin::manage_groups_url( 'edit', array( 'id' => $group->id ) );
			$description = (strlen( $group->description ) > 60) ? substr( $group->description, 0, 60 ) . ' [...]' : $group->description;
			?>
			<tr <?php echo $li_class; ?>>
				<td class="has-row-actions">
					<a href="<?php esc_attr_e( $edit_url ); ?>"><?php esc_html_e( $group->name ); ?></a>
					<br />
					<div class="row-actions">
						<span class="edit"><a href="<?php esc_attr_e( $edit_url ); ?>">Bearbeiten</a>
						<span> | </span>
						<span class="delete"><a class="submitdelete" href="<?php echo Secdor\Groups_Admin::manage_groups_url( "delete", array( "id" => $group->id ) ); ?>"><?php _e( "Remove", SECDOR_TEXTDOMAIN ); ?></a></span>
					</div>
				</td>
				<td><?php esc_html_e( $description ); ?></td>
				<td class="num"><?php echo count( $group->users ); ?></td>
				<td><?php echo Secdor\Groups_Admin::group_permissions_string( $group ); ?></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th class="manage-column"><?php _e( "Name", SECDOR_TEXTDOMAIN ); ?></th>
				<th class="manage-column"><?php _e( "Description", SECDOR_TEXTDOMAIN ); ?></th>
				<th class="manage-column num"><?php _e( "Members", SECDOR_TEXTDOMAIN ); ?></th>
				<th class="manage-column"><?php _e( "Editable", SECDOR_TEXTDOMAIN ); ?></th>
			</tr>
		</tfoot>
	</table>
</div>
