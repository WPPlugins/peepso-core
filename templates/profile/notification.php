<?php
$PeepSoProfile=PeepSoProfile::get_instance();
$user_id = $PeepSoProfile->notification_user();
$PeepSoUser		= PeepSoUser::get_instance($user_id);

$readstatus = $PeepSoProfile->notification_readstatus();

?>
<div class="ps-notification<?php echo ($readstatus === FALSE)? ' ps-notification--unread':''?>">
	<a class="ps-notification__inside" href="<?php echo $PeepSoProfile->notification_link(false); ?>">
		<div class="ps-notification__check">
			<input type="checkbox" id="ckbx-<?php $PeepSoProfile->notification_id(); ?>" />
			<label for="ckbx-<?php $PeepSoProfile->notification_id(); ?>"></label>
		</div>

		<div class="ps-notification__header">
			<div class="ps-avatar ps-avatar--notification">
				<img src="<?php echo $PeepSoUser->get_avatar(); ?>" alt="<?php echo strip_tags($PeepSoUser->get_fullname()); ?>">
			</div>
		</div>

		<div class="ps-notification__body">
			<div class="ps-notification__desc">
				<strong><?php

				//[peepso]_[action]_[WHICH_PLUGIN]_[WHERE]_[WHAT]_[BEFORE/AFTER]
				do_action('peepso_action_render_user_name_before', $PeepSoUser->get_id());

				echo $PeepSoUser->get_fullname();

				//[peepso]_[action]_[WHICH_PLUGIN]_[WHERE]_[WHAT]_[BEFORE/AFTER]
				do_action('peepso_action_render_user_name_after', $PeepSoUser->get_id());

				?></strong>
				<?php $PeepSoProfile->notification_message(); ?><?php $PeepSoProfile->notification_link(); ?>
			</div>

			<div class="ps-notification__meta">
				<small
				class="activity-post-age"
				data-timestamp="<?php $PeepSoProfile->notification_timestamp(); ?>"><?php $PeepSoProfile->notification_age(); ?></small>
			</div>
		</div>
	</a>
</div>
