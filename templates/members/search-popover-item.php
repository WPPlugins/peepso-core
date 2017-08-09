<?php
$PeepSoUser	= PeepSoUser::get_instance($user_id);
?>
<div class="ps-comment-item">
	<div class="ps-avatar-comment" style="cursor:pointer;" onclick="window.location='<?php echo $PeepSoUser->get_profileurl(); ?>'">
		<img src="<?php echo $PeepSoUser->get_avatar(); ?>" alt="<?php echo trim(strip_tags($PeepSoUser->get_fullname())); ?>">
	</div>
	<div class="ps-comment-body" style="cursor:pointer;" onclick="window.location='<?php echo $PeepSoUser->get_profileurl(); ?>'">
		<div class="ps-comment-user">
			<?php 
			
			//[peepso]_[action]_[WHICH_PLUGIN]_[WHERE]_[WHAT]_[BEFORE/AFTER]
			do_action('peepso_action_render_user_name_before', $PeepSoUser->get_id());

			echo $PeepSoUser->get_fullname(); 

			//[peepso]_[action]_[WHICH_PLUGIN]_[WHERE]_[WHAT]_[BEFORE/AFTER]
			do_action('peepso_action_render_user_name_after', $PeepSoUser->get_id());

			?>
		</div>
	</div>
	<?php if (isset($buttons) && count($buttons) >= 1) { ?>
	<div class="ps-popover-actions">
		<?php foreach ($buttons as $button) { ?>
		<button class="<?php echo esc_attr($button['class']); ?>" <?php echo isset($button['click-notif']) ? 'onclick="' . esc_attr($button['click-notif']) . '"' : ''; ?>>
			<?php echo esc_attr($button['label']); ?>
		</button>
		<?php } ?>
	</div>
	<?php } ?>
</div>
