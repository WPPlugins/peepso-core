<?php
require_once(PeepSo::get_plugin_dir() . 'lib' . DIRECTORY_SEPARATOR . 'install.php');
/*
 * Performs installation process
 * @package PeepSo
 * @author PeepSo
 */
class PeepSoActivate extends PeepSoInstall
{
	const DBVERSION_OPTION_NAME = 'peepso_database_version';
	const DBVERSION = '3';
	
	// these items are stored under the mail 'peepso_config' option
	protected $default_config = array(
		'install_date' => NULL, 					// defined when peepso is first installed

		'system_enable_logging' => 0,				// default logging to OFF
		'system_show_peepso_link' => 0,				// default Powered by PeepSo link to OFF
		'avatars_peepso_only' => 0,
		'system_display_name_style' => 'real_name',
		'system_override_name' => 0,
		'allow_username_change'=> 1,
		'appearance-avatars-square' => 1,

		'site_show_notification_on_navigation_bar' => 1, // default Show notification icons on WP Toolbar
		'site_reporting_enable' => 1,
		'site_reporting_types' => "Spamming\nAdvertisement\nProfanity\nInappropriate Content/Abusive",

		'site_activity_comments' => 2,
		'site_activity_limit_comments' => 0,
		'site_activity_comments_allowed' => 50,
		'activity_comments_batch' => 5,
		'site_activity_readmore' => 1000,
		'site_activity_readmore_single' => 2000,
		'site_activity_open_links_in_new_tab' => 1,
		'site_activity_hide_stream_from_guest' => 0,

		'site_dashboard_reportperiod' => 168,

		'site_advsearch_allowguest' => 1,
		'site_advsearch_email' => '0',


		'site_registration_enableverification' => 0,
		'site_registration_enable_ssl' => 0,
		'site_registration_enableterms' => 0,
		'site_registration_terms' => '',
		'site_registration_enablerecaptcha' => 0,
		'site_registration_recaptchasecure' => 0,
		'site_registration_recaptchapublic' => '',
		'site_registration_recaptchaprivate' => '',
		'site_registration_allowdelete' => 0,
		'site_registration_recaptchatheme' => 'red',
		'site_registration_recaptchalanguage' => 'English',
		'site_registration_alloweddomains' => '',
		'site_registration_denieddomains' => '',
		'site_registration_header' => 'Get Connected!',
		'site_registration_callout' => 'Come and join our community. Expand your network and get to know new people!',
		'site_registration_buttontext' => 'Join us now, it\'s free!',

		'site_activity_privacy' => 1,
		'site_activity_linknewtab' => 0,
		'site_activity_everyonecomment' => 1,
		'site_activity_hide_stream_from_guest' => 0,

		'site_likes_profile' => 1,

		'site_frontpage_title' => 'PeepSo',
		'site_frontpage_redirectlogin' => 0, // change to page ID
//		'site_frontpage_redirectlogout' => 'frontpage',

		'site_socialsharing_enable' => 1,
		'site_repost_enable' => 0,
//		'site_socialsharing_shareemail' => 1,

		'site_messaging_enable' => 1,

		'site_walls_editcomment' => 1,
		'site_walls_friendswrite' => 1,
		'site_walls_videofriendscomment' => 1,
		'site_walls_photofriendscomment' => 1,
		'site_walls_groupsmemberswrite' => 1,
		'site_walls_eventsresponderswrite' => 1,
		'site_walls_autorefresh' => 1,
		'site_walls_refreshinterval' => 30000,

		'site_timezone_dstoffset' => 0,

		'site_emails_copyright' => 'Copyright {sitename}',
		'site_emails_sender' => '{sitename} Community',
		'site_emails_admin_email' => 'no-reply@peepso.com',
		'site_emails_process_count' => 25,

		'site_status_limit' => 4000,

		'site_profiles_enablemultiple' => 1,

		'site_filtering_alpha' => 1,
			
		'delete_on_deactivate' => 0,
		'delete_post_data' => 0,
		
		'opengraph_enable' => 1,
		'opengraph_title' => '{sitename}',
		'opengraph_description' => 'Come and join our community. Expand your network and get to know new people!',
		'opengraph_image' => ''
	);

	// these items are stored individually
	protected $extended_config = array(
		'site_registration_terms' => '',
		'site_registration_welcome' => '',
		'site_registration_confirm' => '',
	);

	private $invalid_usernames = array('admin', 'edit', 'sysop', 'owner');

	/*
	 * called on plugin activation; performs all installation tasks
	 */
	public function plugin_activation( $is_core = TRUE )
	{
		$activated = parent::plugin_activation($is_core);

		if ($activated) {
			// Create peepso_users record for each user on the site
			global $wpdb;
			// exclude existing peepso users, in case of update
			$wp_peepso_user_query = "SELECT `usr_id` FROM `{$wpdb->prefix}" . PeepSoUser::TABLE . "` `peepsousers`";
			$peepso_users = $wpdb->get_col($wp_peepso_user_query);

			$args = array('fields' => 'ID');

			if (count($peepso_users) > 0) {
                $args['exclude'] = $peepso_users;
            }

			$user_query = new WP_User_Query($args);
			if (!empty($user_query->results)) {
				foreach ($user_query->results as $user_id) {
					$data = array(
						'usr_id' => $user_id,
						'usr_profile_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_first_name_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_last_name_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_description_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_user_url_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_gender_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_birthdate_acc' => PeepSo::ACCESS_PUBLIC,
					);
					$wpdb->insert($wpdb->prefix . PeepSoUser::TABLE, $data);
				}
			}

			register_post_type('peepso_user_field');

			// install profile fields
			require_once(dirname(__FILE__).'/../classes/profilefields.php');
			PeepSoProfileFields::install();

            // @TODO: #203 promote WP admins to peepso admins
            $user_query = new WP_User_Query( array( 'fields'=>'ID', 'role' => 'Administrator' ) );
            $results = $user_query->results;

            if(count($results)) {
                foreach($results as $user_id){
                    $user = PeepSoUser::get_instance($user_id);
                    $user->set_user_role('admin');
                }
            }


			// TODO: need to use the WP_Filesystem API
			// copy the .htaccess file from the plugins/peepso/ directory to wp-content/peepso/
			copy(PeepSo::get_plugin_dir() . '.htaccess', PeepSo::get_peepso_dir() . '.htaccess');

			// Update the current user's first name to their login name if and only if the name is blank or not filled out.
			$current_user = get_user_meta(get_current_user_id());
			$first_name = $current_user['first_name'][0];
			$last_name = $current_user['last_name'][0];
			if (empty($first_name) && empty($last_name)) {
				$user = wp_get_current_user();
				update_user_meta(get_current_user_id(), 'first_name', $user->user_login);
			}
		}

		return ($activated);
	}

	/*
	 * return default email templates
	 */
	public function get_email_contents()
	{
		$emails = array(
			'email_new_user' => "Hello {userfullname}

Welcome to {sitename}!

Click on this link to verify your email.
{activatelink}
Once approved you will be notified and then be able to login and participate.

Thank you.",
			'email_new_user_no_approval' => "Hello {userfullname}

Welcome to {sitename} community!

Click on this link to verify your email and login to your account.
{activatelink}

Thank you.",
			'email_activity_notice' => "Hello {userfirstname},

The user {fromfirstname} likes what you have to say.

You can see this post here: {permalink}

Thank you.",
			'email_like_post' => "Hello {userfirstname},

The user {fromfirstname} likes your post!

You can see the post here: {permalink}

Thank you.",
			'email_user_comment' => "Hello {userfirstname},

{fromfirstname} had something to say about your post!

You can see the post here:
{permalink}

Thank you.",
			'email_share' => "Hello {userfirstname},

{fromfirstname} had shared your post!

You can see the post here:
{permalink}

Thank you.",
			'email_user_reply_comment' => "Hello {userfirstname},

{fromfirstname} replied to your comment!

You can see it here:
{permalink}

Thank you.",
			'email_like_comment' => "Hello {userfirstname},

The user {fromfirstname} likes your comment!

You can see the post here:
{permalink}

Thank you.",
			'email_wall_post' => "Hello {userfirstname},

{fromfirstname} wrote on your profile!

You can visit your profile here:
{profileurl}
or view the post directly here:
{permalink}

Thank you.",
			'email_password_recover' => "Someone requested that the password be reset for the following account:

Username: {userlogin}

At {siteurl}

If this was a mistake, just ignore this email and nothing will happen.

To reset your password, visit the following address:

{recover_url}

Thank you.",

			'email_password_changed' => "Hello {userfirstname},

You have successfully changed your password.

You can login with the new credentials here: {activityurl}

Thank you.",

			'email_user_approved' => "Your account has been approved. You may now login at <a href=\"{activityurl}\">{sitename}</a>",
			'email_like_profile' => "Hello {userfirstname},

{fromfirstname} likes your profile!

You can see all of your notifications here:
{permalink}

Thank you.",
			'email_new_user_registration' => "Hello Administrator,

A new user has signed up! Please welcome {userfullname} with the login name of &rsquo;{userlogin}&rsquo; to the site!

You can activate the user&rsquo;s account here: {permalink}

Thank you."
		);
		
		return ($emails);
	}

	/*
	 * return default page names information
	 */
	protected function get_page_data()
	{
		// default page names/locations
		$aRet = array(
			'home' => array(
				'title' => __('Home', 'peepso-core'),
				'slug' => '',
				'content' => NULL,
			),
			'activity' => array(							//
				'title' => __('Recent Activity', 'peepso-core'),
				'slug' => 'activity',
				'content' => '[peepso_activity]',
			),

			'profile' => array(								//
				'title' => __('User Profile', 'peepso-core'),
				'slug' => 'profile',
				'content' => '[peepso_profile]'
			),
			'register' => array(							//
				'title' => __('Site Registration', 'peepso-core'),
				'slug' => 'register',
				'content' => '[peepso_register]'
			),
			'recover' => array(								//
				'title' => __('Recover Password', 'peepso-core'),
				'slug' => 'password-recover',
				'content' => '[peepso_recover]',
			),
			'reset' => array(								//
				'title' => __('Reset Password', 'peepso-core'),
				'slug' => 'password-reset',
				'content' => '[peepso_reset]',
			),
			'members' => array(
				'title' => __('Members', 'peepso-core'),
				'slug' => 'members',
				'content' => '[peepso_members]',
			),
//			'members-latest',
//			'members-online'
		);
		return ($aRet);
	}

	/**
	 * Returns definitions for plugin tables.
	 * @return array
	 */
	public static function get_table_data()
	{
		$aRet = array(
			'activities' => "
				CREATE TABLE `activities` (
					`act_id`			BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					`act_owner_id`		BIGINT(20) UNSIGNED NOT NULL,
					`act_external_id`	INT(11) UNSIGNED DEFAULT '0',
					`act_module_id`		SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
					`act_ip`			VARCHAR(64) NOT NULL DEFAULT '',
					`act_access`		TINYINT(3) UNSIGNED NOT NULL,
					`act_has_replies`	TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					`act_location_id`	INT(11) UNSIGNED NOT NULL DEFAULT '0',
					`act_repost_id`		INT(11) UNSIGNED NOT NULL DEFAULT '0',
					`act_link`			VARCHAR(100) NULL,
					`act_link_title`	VARCHAR(100) NULL,
					`act_link_image_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
					`act_description`   TEXT NULL DEFAULT NULL,
					`act_comment_object_id`   BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
					`act_comment_module_id`   SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',

					PRIMARY KEY (`act_id`),
					INDEX `owner` (`act_owner_id`),
					INDEX `external` (`act_external_id`),
					INDEX `module` (`act_module_id`)
				) ENGINE=InnoDB",
			'activity_hide' => "
				CREATE TABLE `activity_hide` (
					`hide_activity_id` BIGINT(11) UNSIGNED NOT NULL,
					`hide_user_id` BIGINT(11) UNSIGNED NOT NULL,

					UNIQUE `index` (`hide_activity_id`, `hide_user_id`)
				) ENGINE=InnoDB",
			'blocks' => "
				CREATE TABLE `blocks` (
					`blk_user_id` BIGINT(20) UNSIGNED NOT NULL,
					`blk_blocked_id` BIGINT(20) UNSIGNED NOT NULL,

					UNIQUE `block` (`blk_user_id`, `blk_blocked_id`)
				) ENGINE=InnoDB",
			'cache' => "
				CREATE TABLE `cache` (
					`user_id` BIGINT(20) UNSIGNED NOT NULL,
					`tables` VARCHAR(200) NOT NULL,
					`query_name` VARCHAR(32) NOT NULL,
					`query` VARCHAR(255) NOT NULL,
					`query_hash` VARCHAR(32) NOT NULL,
					`data` TEXT NOT NULL,
					`expires` INT(11) NOT NULL,

					INDEX `user_id` (`user_id`),
					INDEX `tables` (`tables`),
					INDEX `query_name` (`query_name`),
					INDEX `query_hash` (`query_hash`)
				) ENGINE=InnoDB",
			'errors' => "
				CREATE TABLE `errors` (
				    `err_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				    `err_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
				    `err_extra` VARCHAR( 32 ) NOT NULL,
  					`err_file` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  					`err_func` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
					`err_msg` VARCHAR(255) NOT NULL,
					`err_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					`err_user_id` BIGINT(20) UNSIGNED NOT NULL,
					`err_ip` VARCHAR(64) NOT NULL,

					PRIMARY KEY (`err_id`),
					INDEX `user_id` (`err_user_id`),
					INDEX `timestamp` (`err_timestamp`)
				) ENGINE=InnoDB",
			'likes' => "
				CREATE TABLE `likes` (
					`like_id`			INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`like_user_id`		BIGINT(20) UNSIGNED NOT NULL,
					`like_external_id`	BIGINT(20) UNSIGNED NOT NULL,
					`like_module_id`	SMALLINT(5) UNSIGNED NOT NULL,
					`like_type`			SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
					`like_timestamp` 	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

					PRIMARY KEY (`like_id`),
					INDEX `external` (`like_external_id`),
					UNIQUE `module` (`like_user_id`, `like_module_id`, `like_external_id`),
					INDEX `user` (`like_user_id`)
				) ENGINE=InnoDB",
			'mail_queue' => "
				CREATE TABLE `mail_queue` (
					`mail_id`			INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`mail_user_id`		BIGINT(20) UNSIGNED NULL DEFAULT '0',
					`mail_created_at`	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					`mail_recipient`	VARCHAR(128) NOT NULL,
					`mail_subject`		VARCHAR(200) NOT NULL,
					`mail_message`		TEXT NOT NULL,
					`mail_status`		TINYINT(1) NOT NULL DEFAULT '0',
					`mail_attempts` 	TINYINT(1) NOT NULL DEFAULT '0',
					`mail_module_id`	SMALLINT(5) UNSIGNED NULL,
					`mail_message_id`	SMALLINT(5) UNSIGNED NULL,

					PRIMARY KEY (`mail_id`),
					INDEX `user` (`mail_user_id`),
					INDEX `status` (`mail_status`),
					INDEX `module` (`mail_module_id`)
				) ENGINE=InnoDB",
			'notifications' => "
				CREATE TABLE `notifications` (
					`not_id`				INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`not_user_id`			BIGINT(20) UNSIGNED NOT NULL,
					`not_from_user_id`		BIGINT(20) UNSIGNED NOT NULL,
					`not_timestamp`			TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					`not_module_id`			SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
					`not_external_id`		BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
					`not_type`				VARCHAR(128) NOT NULL,
					`not_message`			VARCHAR(200) NOT NULL,
					`not_read`				TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',

					PRIMARY KEY (`not_id`),
					INDEX `user` (`not_user_id`),
					INDEX `from` (`not_from_user_id`),
					INDEX `module` (`not_module_id`),
					INDEX `timestamp` (`not_timestamp`),
					INDEX `read` (`not_read`)
				) ENGINE=InnoDB",
			'report' => "
				CREATE TABLE `report` (
					`rep_id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`rep_user_id`			BIGINT(20) UNSIGNED NOT NULL,
					`rep_external_id`		BIGINT(20) UNSIGNED NOT NULL,
					`rep_timestamp`			TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`rep_reason`			VARCHAR(128) NULL DEFAULT NULL,
					`rep_module_id`			SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
					`rep_status`			TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',

					PRIMARY KEY (`rep_id`),
					INDEX `user` (`rep_user_id`),
					INDEX `external` (`rep_external_id`),
					INDEX `timestamp` (`rep_timestamp`),
					INDEX `module` (`rep_module_id`),
					INDEX `status` (`rep_status`)
				) ENGINE=InnoDB",
			'unfollow' => "
				CREATE TABLE `unfollow` (
					`unf_user_id` BIGINT(20) UNSIGNED NOT NULL,
					`unf_unfollowed_id` BIGINT(20) UNSIGNED NOT NULL,
					UNIQUE `unfollow` (`unf_user_id`, `unf_unfollowed_id`)
				) ENGINE=InnoDB",
            /*
             * admin - manage everything
             *
             * moderator - manage content in groups / forums (in the future)
             *
             * ban - no access to the site, can't login
             *
             * register  - after registration, not confirmed yet
             *      auto activation: member
             *      admin activation: verified
             *
             * user - wordpress user that's not a PeepSo member
             *
             */
			'users' => "
				CREATE TABLE `users` (
					`usr_id`                BIGINT(20) UNSIGNED NOT NULL,
					`usr_last_activity`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					`usr_views`             INT(11) UNSIGNED NOT NULL DEFAULT '0',
					`usr_likes`             INT(11) UNSIGNED NOT NULL DEFAULT '0',
					`usr_role`              ENUM('user', 'member', 'moderator', 'admin', 'ban', 'register', 'verified') DEFAULT 'member',
					`usr_send_emails`		TINYINT(1) NOT NULL DEFAULT '1',
					`usr_cover_photo`		VARCHAR(255) NULL DEFAULT NULL,
					`usr_avatar_custom`		TINYINT(1) NOT NULL DEFAULT '0',
					`usr_profile_acc`    	TINYINT(1) NOT NULL DEFAULT '10',
					`usr_first_name_acc`    TINYINT(1) NOT NULL DEFAULT '10',
					`usr_last_name_acc`     TINYINT(1) NOT NULL DEFAULT '10',
					`usr_description_acc`   TINYINT(1) NOT NULL DEFAULT '10',
					`usr_user_url_acc`      TINYINT(1) NOT NULL DEFAULT '10',
					`usr_gender`            CHAR(1) DEFAULT 'u',
					`usr_gender_acc`        TINYINT(1) NOT NULL DEFAULT '10',
					`usr_birthdate`         DATE NULL,
					`usr_birthdate_acc`     TINYINT(1) NOT NULL DEFAULT '10',

					PRIMARY KEY (`usr_id`),
					INDEX `last_activity` (`usr_last_activity`)
				) ENGINE=InnoDB",
			'ranking' => "
				CREATE TABLE `activity_ranking` (
					`rank_id` int(11) NOT NULL AUTO_INCREMENT,
					`rank_act_id` int(11) NOT NULL,
					`rank_act_date` datetime NOT NULL,
					`rank_act_comments` int(11) NOT NULL,
					`rank_act_likes` int(11) NOT NULL,
					`rank_act_shares` int(11) NOT NULL,
					`rank_act_views` int(11) NOT NULL,
					`rank_act_score` int(11) NOT NULL,
					
					PRIMARY KEY (`rank_id`)
				  ) ENGINE=InnoDB;
			"
		);

		return ($aRet);
	}

	protected function migrate_database_tables()
	{
		global $wpdb;
		$wpdb->query('START TRANSACTION');
		$rollback = FALSE;

		$current = intval(get_option(self::DBVERSION_OPTION_NAME, -1));
		if (-1 === $current) {
			$current = 0;
			add_option(self::DBVERSION_OPTION_NAME, $current, NULL, 'no');
		}


		if(0 == $current) {
			$sql = "ALTER TABLE `{$wpdb->prefix}peepso_activities` CHANGE `act_id` `act_id` BIGINT(20) NOT NULL AUTO_INCREMENT";
			$wpdb->query($sql);

			$sql = "UPDATE `{$wpdb->prefix}peepso_activities` SET `act_external_id` = `act_id` WHERE `act_external_id` = 0";
			$wpdb->query($sql);
		}

		// @since 1.7.2 - #1639 bigger not_type
		if(2 == $current) {
				$sql = "ALTER TABLE `{$wpdb->prefix}peepso_notifications` CHANGE `not_type` `not_type` VARCHAR(128) NOT NULL";
				$wpdb->query($sql);
		}

		if ($rollback) {
			$wpdb->query('ROLLBACK');
		} else {
			$wpdb->query('COMMIT');
		}

		// set the dbversion in the option so we don't keep migrating
		update_option(self::DBVERSION_OPTION_NAME, self::DBVERSION);
	}

	/**
	 * Adds PeepSo specific roles to Wordpress
	 */
	protected function create_roles()
	{
//		$cap = array('read');
//		$res = add_role('peepso_verified', __('PeepSo Verified', 'peepso-core'), $cap);
//		$res = add_role('peepso_member', __('PeepSo Member', 'peepso-core'), $cap);
//		$res = add_role('peepso_moderator', __('PeepSo Moderator', 'peepso-core'), $cap);
//		$res = add_role('peepso_admin', __('PeepSo Administrator', 'peepso-core'), $cap);
//		$res = add_role('peepso_ban', __('PeepSo Banned', 'peepso-core'), $cap);
//		$res = add_role('peepso_register', __('PeepSo Registered', 'peepso-core'), $cap);
	}

	/**
	 * Adds site options to the peepso_config option, which will return an arrya of values.
	 */
	protected function create_options( $is_core = TRUE)
	{
		parent::create_options($is_core);
	}

	/*
	 * Create all of the scheduled events
	 */
	protected function create_scheduled_events()
	{
		wp_schedule_event(current_time('timestamp'), 'daily', PeepSo::CRON_DAILY_EVENT);
		wp_schedule_event(current_time('timestamp'), 'weekly', PeepSo::CRON_WEEKLY_EVENT);
		wp_schedule_event(current_time('timestamp'), 'five_minutes', PeepSo::CRON_MAILQUEUE);
	}	
}

// EOF
