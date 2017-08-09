<div class="ps-submenu">
	<?php $class = '';
			if (isset($_GET['edit']))
				$class = ' current';
	?>
	<a href="<?php echo PeepSo::get_page('profile'); ?>?edit" class="ps-submenu__item <?php echo $class; ?>" ><?php _e('Edit Account', 'peepso-core'); ?></a>

	<?php $class = '';
			if (isset($_GET['pref']))
				$class = ' current';
	?>
	<a href="<?php echo PeepSo::get_page('profile'); ?>?pref" class="ps-submenu__item <?php echo $class; ?>" ><?php _e('Preferences', 'peepso-core'); ?></a>

	<?php $class = '';
			if (isset($_GET['notifications']))
				$class = ' current';
	?>
	<a href="<?php echo PeepSo::get_page('profile'); ?>?notifications" class="ps-submenu__item <?php echo $class; ?>" ><?php _e('Notifications', 'peepso-core'); ?></a>

	<?php $class = '';
			if (isset($_GET['blocked']))
				$class = ' current';
	?>
	<a href="<?php echo PeepSo::get_page('profile'); ?>?blocked" class="ps-submenu__item <?php echo $class; ?>" ><?php _e('Block List', 'peepso-core'); ?></a>

	<?php if (PeepSo::get_option('site_registration_allowdelete', FALSE) && ! PeepSo::is_admin()) { ?>
		<?php $class = '';
				if (isset($_GET['delete']))
					$class = ' current';
		?>
		<a href="#" onclick="profile.delete_profile(); return false;" class="ps-submenu__item <?php echo $class; ?>" ><?php _e('Delete Profile', 'peepso-core'); ?></a>
	<?php } ?>
</div>

<?php
PeepSoTemplate::exec_template('activity','dialogs');
