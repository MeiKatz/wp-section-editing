<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php _e( "Section Groups", SECDOR_TEXTDOMAIN ); ?>
	</h1>
	<a href="<?php echo Secdor\Groups_Admin::manage_groups_url( "add" ); ?>" class="page-title-action">
		<?php _e( "Add New", SECDOR_TEXTDOMAIN ); ?>
	</a>
	<hr class="wp-header-end" />
	<?php
		$table = new Secdor\Group_List_Table();
		$table->prepare_items();
		$table->display();
	?>
</div>
