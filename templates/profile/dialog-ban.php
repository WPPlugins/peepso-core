<form id="form_ban_user" class="ps-form--ban">
	<div class="ps-form__row">
		<div class="ps-form__label">
			<div class="ps-checkbox">
				<input type="radio" name="ban_type" id="ban-period" value="ban_period" checked="checked">
				<label for="ban-period"><?php echo __('Ban this user until', 'peepso-core'); ?></label>
			</div>
		</div>
		<div class="ps-form__controls">
			<input type="text" class="ps-input" width="auto" name="ban_period_date" value="<?php echo $start_date; ?>" data-date-start-date="<?php echo $start_date; ?>" />
			<div id="ban-period-empty" class="ps-text--danger ps-form__helper" style="display:none"><?php echo __('Please fill in the date', 'peepso-core'); ?></div>
		</div>
	</div>
	<div class="ps-form__row">
		<div class="ps-form__label ps-full">
			<div class="ps-checkbox">
				<input type="radio" name="ban_type" id="ban-forever" value="ban_forever">
				<label for="ban-forever"><?php echo __('Ban this user forever', 'peepso-core'); ?></label>
			</div>
		</div>
	</div>
</form>
