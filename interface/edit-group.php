<div id="section-group-editor" class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( $page_title ); ?>
	</h1>
	<?php if ( $group->id() !== null ) : ?>
	<a href="<?php echo Secdor\Groups_Admin::manage_groups_url( "add" ); ?>" class="page-title-action">
		<?php _e( "Add New", SECDOR_TEXTDOMAIN ); ?>
	</a>
	<?php endif; ?>
	<hr class="wp-header-end" />
	<div id="poststuff">
		<form name="group-edit-form" id="group-edit-form" method="post">
			<?php
				wp_nonce_field(
					"save_section_editing_group"
				);
				// needed for storage of collapsed/expanded meta boxes
				wp_nonce_field(
					"closedpostboxes",
					"closedpostboxesnonce",
					false
				);
				// needed for storage of order of meta boxes
				wp_nonce_field(
					"meta-box-order",
					"meta-box-order-nonce",
					false
				);
			?>

			<?php if ( $group->id() !== null ) : ?>
				<input type="hidden" id="group_id" name="id" value="<?php echo $group->id(); ?>" />
			<?php endif; ?>

			<input type="hidden" id="perm_panel" name="perm_panel" value="<?php echo $perm_panel; ?>" />

			<?php /* echo 1 == get_current_screen()->get_columns() ? 1 : 2; */ ?>
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv" class="members-title-div">
						<div id="titlewrap">
							<label class="screen-reader-text" for="edit-group-name"><?php _e( "Section Group Name", SECDOR_TEXTDOMAIN ); ?></label>
							<input id="edit-group-name" type="text" value="<?php echo $group->name(); ?>" />
						</div>
					</div>
					<div id="group-permissions-editor">Editor wird geladen ...</div>
				</div>
				<div id="postbox-container-1" class="postbox-container side">
					<?php
						do_meta_boxes(
							get_current_screen()->id,
							"side",
							$group
						);
					?>
				</div>
			</div>
		</form>
	</div>
</div><!-- /.wrap -->
