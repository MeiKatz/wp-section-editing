<div id="add-group-members">
	<h4>Add User to this Editor Group</h4>
	<fieldset>
		<div class="form-row">
			<label for="user_login">Enter the email address of an existing user on this network to add them to this Editor Group.</label>
			<input id="user_login" type="text" class="regular-text" name="user_login" value="" />
			<button id="find_user" class="button-secondary">Find User</button>
		</div>
		<div class="form-row">
			<button id="add_member" class="button-secondary">Add</button>
		</div>
	</fieldset>
</div>

<div id="group-members">
	<h4>Editor Group List <span class="member-count"><?php echo count( $group->get_users() ); ?> members</span></h4>
	<fieldset>
		<ul id="group-member-list">
			<?php $users = get_users(); ?>
			<?php foreach( $users as $user ): ?>
			<?php $checked = $group->has_user( $user->ID ) ? 'checked="checked"' : ''; ?>
			<li class="member<?php if( $group->has_user( $user->ID ) ): ?> active<?php endif; ?>" >
				<input id="member_<?php echo $user->ID; ?>" type="checkbox" name="group[users][]" value="<?php echo $user->ID; ?>" <?php echo $checked; ?> />
				<label for="member_<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></label>
				<a id="remove_member_<?php echo $user->ID; ?>" class="remove_member" href="#">Remove</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</fieldset>
</div>