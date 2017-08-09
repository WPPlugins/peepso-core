<?php
$PeepSoActivity = PeepSoActivity::get_instance();

$user = PeepSoUser::get_instance(PeepSoProfileShortcode::get_instance()->get_view_user_id());

$can_edit = FALSE;
if($user->get_id() == get_current_user_id() || current_user_can('edit_users')) {
	$can_edit = TRUE;
}


$args = array('post_status'=>'publish');


$user->profile_fields->load_fields($args);
$fields = $user->profile_fields->get_fields();
?>

<div class="peepso ps-page-profile">
<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>

<?php PeepSoTemplate::exec_template('profile', 'focus', array('current'=>'about')); ?>

<section id="mainbody" class="ps-page-unstyled">
	<section id="component" role="article" class="clearfix">

		<?php
		$stats = $user->profile_fields->profile_fields_stats;

		if( $can_edit ) {

			echo '<div class="ps-progress ps-completeness-info"';

			if( $stats['completeness'] >= 100 && $stats['missing_required'] <= 0) {
				echo ' style="display:none" ';
			}

			echo '>';

				echo '<div class="ps-progress-status ps-completeness-status ';

				if(1 === PeepSo::get_option('force_required_profile_fields',0) && $stats['filled_required'] < $stats['fields_required']) {
					echo 'ps-text--danger';
				}

				echo '"';

				if( $stats['completeness'] >= 100) {
					echo ' style="display:none" ';
				}

				echo '>' . $stats['completeness_message'];

				if(isset($stats['completeness_message_detail'])) {
					echo $stats['completeness_message_detail'];
				}

				do_action('peepso_action_render_profile_completeness_message_after', $stats);

				echo '</div>';

				echo '<div class="ps-progress-bar ps-completeness-bar" ';

				if( $stats['completeness'] >= 100) {
					echo ' style="display:none" ';
				}

				echo '><span style="width:' . $stats['completeness'] . '%;"></span>';

				echo "</div>";

				echo '<div class="ps-progress-message ps-missing-required-message" ';

				if( $stats['missing_required'] <= 0) {
					echo ' style="display:none" ';
				}

				echo '>';

					echo '<i class="ps-icon-warning-sign"></i> ' . $stats['missing_required_message'];

				echo '</div>';
			echo "</div>";
		} ?>

		<div class="ps-list--column cfield-list creset-list ps-js-profile-list">

		<?php

		if( count($fields) ) {
			foreach ($fields as $key => $field) {

				$field_can_edit = ($can_edit && !isset($field::$user_disable_edit));

				?>
				<div class="ps-list__item <?php if (TRUE == $field_can_edit) : ?> ps-list-info-mine <?php endif; ?> ps-js-profile-item">
					<?php
					if(!isset($field::$user_hide_title)) :
					?>
					<h4 class="ps-list-info-name creset-h"><?php _e($field->title, 'peepso-core');

					if(TRUE == $field_can_edit &&  1 == $field->prop('meta','validation','required' ))
					{
					 	echo " <strong>*</strong>";
					}
					?>
					</h4>
					<?php endif;?>

					<div class="ps-list-info-content">
						<div class="ps-list-info-content-text">
							<div class="ps-list-info-content-data"><?php $field->render(); ?></div>
							<?php if (TRUE == $field_can_edit) : ?>

								<div class="ps-list-info-action">
									<?php
									$field->render_access();
									?>

									<button class="ps-btn ps-btn-small"
											onclick="profile.edit_field(this);"><?php _e('Edit', 'peepso-core'); ?></button>
								</div>

							<?php endif; ?>
						</div>
						<?php if (TRUE == $field_can_edit) : ?>
							<div class="ps-list-info-content-form" style="display:none">
								<?php

								$field->render_validation();

								?>
								<div class="ps-alert ps-alert--sm ps-alert-danger ps-list-info-content-error"></div>
								<?php $field->render_input(); ?>

								<div class="ps-list-info-action">
									<button class="ps-btn ps-btn-small"
											onclick="profile.cancel_field(this);"><?php _e('Cancel', 'peepso-core'); ?></button>
									<button class="ps-btn ps-btn-small ps-btn-primary ps-js-btn-save"
											onclick="profile.save_field(this);">
										<?php _e('Save', 'peepso-core'); ?>
										<img style="display:none"
											 src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
									</button>
								</div>

							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php
			}
		} else {
			echo __('Sorry, no data to show', 'peepso-core');
		}
		?>
		</div>
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->

<div id="ps-dialogs" style="display:none">
	<?php $PeepSoActivity->dialogs(); // give add-ons a chance to output some HTML ?>
	<?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>
</div>
