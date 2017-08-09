<?php if ( ! is_user_logged_in()) { ?>
	<div class="ps-landing">
		<div class="ps-landing-cover">
			<?php
			$default = PeepSo::get_option('landing_page_image', PeepSo::get_asset('images/landing/register-bg.jpg'));
			$landing_page = !empty($default) ? $default : PeepSo::get_asset('images/landing/register-bg.jpg');
			?>
			<div class="ps-landing-image" style="background:url('<?php echo $landing_page;?>');background-size:cover"></div>

			<div class="ps-landing-content">
				<div class="ps-landing-text">
					<h2><?php echo PeepSo::get_option('site_registration_header', __('Get Connected!', 'peepso-core')); ?></h2>
					<p><?php echo PeepSo::get_option('site_registration_callout', __('Come and join our community. Expand your network and get to know new people!', 'peepso-core')); ?></p>
				</div>
				<div class="ps-landing-signup">
					<a class="ps-btn ps-btn-join" href="<?php echo get_bloginfo('wpurl'), '/', PeepSo::get_option('page_register'), '/';?>">
						<?php echo PeepSo::get_option('site_registration_buttontext', __('Join us now, it\'s free!', 'peepso-core')); ?></a>
				</div>
			</div>
		</div>

		<?php PeepSoTemplate::exec_template('general', 'login');?>
	</div>
<?php
} // is_user_logged_in() ?>
