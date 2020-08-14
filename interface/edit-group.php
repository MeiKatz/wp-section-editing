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
	<ul class="subsubsub" role="tablist">
		<li id="nav-tab-properties">
			<a
				role="tab"
				href="#group-properties-panel"
				class="<?php if ( $tab == "properties" ) { echo "current"; } ?>"
				data-target="properties"
				<?php if ( $tab == "properties" ) { echo 'aria-current="page"'; } ?>
				aria-selected="<?php echo (($tab == "properties") ? "true" : "false"); ?>"
			><?php _e( "Properties", SECDOR_TEXTDOMAIN ); ?></a>
		</li>
		<li id="nav-tab-members">
			<a
				role="tab"
				href="#group-members-panel"
				class="<?php if ( $tab == "members" ) { echo "current"; } ?>"
				data-target="members"
				<?php if ( $tab == "members" ) { echo 'aria-current="page"'; } ?>
				aria-selected="<?php echo (($tab == "members") ? "true" : "false"); ?>"
			><?php _e( "Members", SECDOR_TEXTDOMAIN ); ?> <span class="count">(<?php echo count( $group->users ) ?>)</span></a>
		</li>
		<li id="nav-tab-permissions">
			<a
				role="tab"
				href="#group-permissions-panel"
				class="<?php if ( $tab == "permissions" ) { echo "current"; } ?>"
				data-target="permissions"
				<?php if ( $tab == "permissions" ) { echo 'aria-current="page"'; } ?>
				aria-selected="<?php echo (($tab == "permissions") ? "true" : "false"); ?>"
			><?php _e( "Permissions", SECDOR_TEXTDOMAIN ); ?></a>
		</li>
	</ul>
	<form name="group-edit-form" id="group-edit-form" method="post">
		<?php if ( $group->id() === null ) :  ?>
		<input type="hidden" name="action" value="add"/>
		<?php else : ?>
		<input type="hidden" name="action" value="update"/>
		<input type="hidden" id="group_id" name="id" value="<?php echo $group->id(); ?>" />
		<?php endif; ?>
		<input type="hidden" id="tab" name="tab" value="<?php echo $tab; ?>" />
		<input type="hidden" id="perm_panel" name="perm_panel" value="<?php echo $perm_panel; ?>" />
		<?php wp_nonce_field( 'save_section_editing_group' ); ?>
		<div id="panel-container">
			<div role="tabpanel" id="group-properties-panel" class="group-panel<?php if ( $tab == 'properties' ) { echo ' active'; } ?>">
				<a name="group-properties-panel"></a>
				<?php include 'group-properties.php'; ?>
			</div>
			<div role="tabpanel" id="group-members-panel" class="group-panel<?php if ( $tab == 'members' ) { echo ' active'; } ?>">
				<a name="group-members-panel"></a>
				<?php include 'group-members.php'; ?>
			</div>
			<div role="tabpanel" id="group-permissions-panel" class="group-panel<?php if ( $tab == 'permissions' ) { echo ' active'; } ?>">
				<a name="group-permissions-panel"></a>
				<?php include 'group-permissions.php'; ?>
			</div>
		</div><!-- /#panel-container -->
		<?php if ( $group->id() === null ) : ?>
		<div id="update-action">
			<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( "Add Group", SECDOR_TEXTDOMAIN ); ?>" />
		</div>
		<?php else : ?>
		<div id="update-action">
			<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( "Update Group", SECDOR_TEXTDOMAIN ); ?>" />
		</div>
		<?php endif; ?>
	</form>
</div><!-- /.wrap -->
