<?php
PeepSoTemplate::exec_template('general', 'js-unavailable');
$PeepSoGeneral = PeepSoGeneral::get_instance();
?>

<?php if (is_user_logged_in()) { ?>
<div class="ps-toolbar ps-toolbar--desktop js-toolbar">
	<div class="ps-toolbar__menu">
		<?php $PeepSoGeneral->toolbar_menu(); ?>
	</div>
	<div class="ps-toolbar__notifications">
		<?php $PeepSoGeneral->toolbar_notifications(); ?>
	</div>
</div>

<div class="ps-toolbar">
	<div class="ps-toolbar__menu">
		<span>
			<a href="javascript:" class="ps-toolbar__toggle">
				<i class="ps-icon-menu"></i>
			</a>
		</span>
		<?php $PeepSoGeneral ->navbar_mobile(); ?>
	</div>

	<div id="ps-main-nav" class="ps-toolbar__submenu">
		<?php $PeepSoGeneral->navbar_sidebar_mobile(); ?>
	</div>
</div>
<?php } ?>
