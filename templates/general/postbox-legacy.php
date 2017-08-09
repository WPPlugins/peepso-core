<?php if(TRUE === apply_filters('peepso_permissions_post_create', is_user_logged_in())) {
$PeepSoPostbox = PeepSoPostbox::get_instance();
$PeepSoGeneral = PeepSoGeneral::get_instance();
?>

<?php if (is_user_logged_in() && FALSE === PeepSoActivityShortcode::get_instance()->is_permalink_page()) { ?>
<div id="postbox-main" class="ps-postbox clearfix" style="">
	<?php $PeepSoPostbox->before_postbox(); ?>
	<div id="ps-postbox-status" class="ps-postbox-content">
		<div class="ps-postbox-tabs">
			<?php $PeepSoPostbox ->postbox_tabs(); ?>
		</div>
		<?php PeepSoTemplate::exec_template('general', 'postbox-status'); ?>
	</div>

	<div class="ps-postbox-tab ps-postbox-tab-root clearfix">
		<div class="ps-postbox__menu">
			<?php $PeepSoGeneral->post_types(array('is_current_user' => isset($is_current_user) ? $is_current_user : NULL)); ?>
		</div>
	</div>

	<nav class="ps-postbox-tab selected interactions" style="display: none;">
		<div class="ps-postbox__menu">
			<?php $PeepSoPostbox->post_interactions(array('is_current_user' => isset($is_current_user) ? $is_current_user : NULL)); ?>
		</div>
		<div class="ps-postbox__action ps-postbox-action" style="display: block;">
			<button type="button" class="ps-btn ps-btn--postbox ps-button-cancel"><?php _e('Cancel', 'peepso-core'); ?></button>
			<button type="button" class="ps-btn ps-btn--postbox ps-button-action postbox-submit" style="display: none;"><?php _e('Post', 'peepso-core'); ?></button>
		</div>
		<div class="ps-postbox-loading" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>
	</nav>
<?php $PeepSoPostbox->after_postbox(); ?>
</div>
<?php } // is_user_logged_in() ?>
<?php } // peepso_permissions_post_create ?>
