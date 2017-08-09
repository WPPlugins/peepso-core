<?php
/**
 * Plugin Name: PeepSo Core
 * Plugin URI: https://peepso.com
 * Description: The Next Generation Social Networking
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 1.8.2
 * Copyright: (c) 2015 PeepSo LLP. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepso-core
 * Domain Path: /language
 *
 * We are Open Source. You can redistribute and/or modify this software under the terms of the GNU General Public License (version 2 or later)
 * as published by the Free Software Foundation. See the GNU General Public License or the LICENSE file for more details.
 * This software is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 */

class PeepSo
{
    const MODULE_ID = 0;

    const PLUGIN_VERSION = '1.8.2';
    const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE

    const PLUGIN_NAME = 'PeepSo';
    const PLUGIN_SLUG = 'peepso_';
    const PEEPSOCOM_LICENSES = 'http://tiny.cc/peepso-licenses';

    const ACCESS_FORCE_PUBLIC = -1;
    const ACCESS_PUBLIC = 10;
    const ACCESS_MEMBERS = 20;
    const ACCESS_PRIVATE = 40;
    const CRON_MAILQUEUE = 'peepso_mailqueue_send_event';
    const CRON_DAILY_EVENT = 'peepso_daily_event';
    const CRON_WEEKLY_EVENT = 'peepso_weekly_event';
    const CRON_REBUILD_RANK_EVENT = 'peepso_rebuild_rank_event';


    private static $_instance = NULL;
    private static $_current_shortcode = NULL;

    private $_widgets = array(
        'PeepSoWidgetMe',
        'PeepSoWidgetOnlinemembers',
        'PeepSoWidgetLatestmembers',
    );

    /* array of paths to use in autoloading */
    private static $_autoload_paths = array();

    /* options data */
    private static $_config = NULL;

    private $is_ajax = FALSE;

    private $wp_title = array();

    public $shortcodes= array(
        'peepso_activity' => 'PeepSoActivityShortcode::get_instance',
        'peepso_profile' => 'PeepSo::profile_shortcode',
        'peepso_register' => 'PeepSo::register_shortcode',
        'peepso_recover' => 'PeepSo::recover_shortcode',
        'peepso_reset' => 'PeepSo::reset_shortcode',
        'peepso_members' => 'PeepSo::search_shortcode',
    );

    private function __construct()
    {
        add_filter('wp_ajax_peepso_should_get_notifications', array(&$this, 'ajax_should_get_notifications'));

        // set up autoloading
        self::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
        self::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR);
        self::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR);
        self::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR);
        $res = spl_autoload_register(array(&$this, 'autoload'));

        PeepSoTemplate::add_template_directory(self::get_peepso_dir().'overrides/');
        PeepSoTemplate::add_template_directory(dirname(__FILE__));

        // add five minute schedule to be used by mailqueue
        add_filter('authenticate', array(&$this, 'auth_signon'), 30, 3);
        add_filter('allow_password_reset', array(&$this, 'allow_password_reset'), 20, 2);
        add_filter('body_class', array(&$this,'body_class_filter'));
        add_filter('cron_schedules', array(&$this, 'filter_cron_schedules'));
        add_filter('peepso_widget_me_links', array(&$this, 'peepso_widget_me_links'));
        add_filter('peepso_widget_me_community_links', array(&$this, 'peepso_widget_me_community_links'));
        add_filter('peepso_widget_args_internal', array(&$this, 'peepso_widget_args_internal'));
        add_filter('peepso_widget_instance', array(&$this, 'peepso_widget_instance'));
        add_filter('peepso_activity_more_posts_link', array(&$this, 'peepso_activity_more_posts_link'));
        add_filter('peepso_activity_remove_shortcode', array(&$this, 'peepso_activity_remove_shortcode'));
        add_filter('the_title', array(&$this,'the_title'), 5, 2);
        add_filter('get_avatar', array(&$this, 'filter_avatar'), 20, 5);
        add_filter('author_link', array(&$this, 'modify_author_link'), 10, 3 );
        add_filter('edit_profile_url', array(&$this, 'modify_edit_profile_link'), 10, 3 );
        add_filter('get_comment_author_link', array(&$this,'new_comment_author_profile_link'),10,3);
        add_filter('peepso_default_stream_options', array(&$this, 'filter_default_stream_options'));

        add_action('wp_ajax_submit-uninstall-reason', array(PeepSoAdmin::get_instance(), 'submit_uninstall_reason'));

        register_sidebar(array(
            'name'=> 'PeepSo',
            'id' => 'peepso',
            'description' => 'Area reserved for PeepSo Integrated widgets. Widgets that are not "PeepSo Integrated" will not be shown on the page.',
        ));

        add_filter('peepso_profile_segment_menu_links', array(&$this, 'peepso_profile_segment_menu_links'));

        if (defined('DOING_CRON') && DOING_CRON) {
            PeepSoCron::initialize();
        }

        add_action('plugins_loaded', array(&$this, 'load_textdomain'));

        // setup plugin's hooks
        if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
            add_action('admin_init', array(__CLASS__, 'can_install'));
            add_action('init', array(&$this, 'check_admin_access'));

            add_action('peepso_init', array(&$this, 'check_plugins'));


            PeepSoAdmin::get_instance();

            if(0==PeepSo::get_option('disable_mailqueue')) {

                if (!stristr(json_encode(_get_cron_array()), PeepSo::CRON_MAILQUEUE)) {
                    add_action('admin_notices', array($this, 'mailqueue_notice'));
                }
            }

            add_action( 'save_post', array(&$this,'peepso_save_post_action'),1,3);

            delete_option('peepso_email_register');

            // modify admin footer text
            add_filter('admin_footer_text', array(&$this, 'remove_footer_admin'), 100, 1);

            // plugin name for WP filters
            $path = basename(dirname(__FILE__)).'/'.basename(__FILE__);

            // hijack update_plugins

            add_action('peepso_config_after_save-advanced', function() {
                delete_site_transient('peepso_block_updates');
                delete_site_transient('peepso_new_version');
                delete_site_transient('update_plugins');
            });

            if(0 == PeepSo::get_option('override_fstvl', 0)) { // FSTVL
                add_filter('pre_set_site_transient_update_plugins', function ($value, $expiration = NULL, $transient = NULL) use ($path) {

                    $delete_peepso_trans = TRUE;

                    // if PeepSo is on the list of new updates
                    if (isset($value->response[$path])) {
                        $plugins = apply_filters('peepso_all_plugins', array());

                        $new_version = $value->response[$path]->new_version;

                        $conflict = array();
                        if (count($plugins)) {
                            foreach ($plugins as $class) {

                                // ignore plugins not in FSTVL
                                if (defined("$class::PEEPSO_VER_MAX") && defined("$class::PEEPSO_VER_MIN")) {
                                    continue;
                                }

                                // ignore plugins that are already up to date or for whatever reason more recent
                                if (version_compare($class::PLUGIN_VERSION, $new_version, '>=')) {
                                    continue;
                                }

                                // raise hell over incompatible FSTVL plugins
                                $conflict['PeepSo ' . $class::PLUGIN_NAME] = $class::PLUGIN_VERSION;
                            }
                        }

                        // at least one FSTVL plugin is outdated, block updates
                        if (is_array($conflict) && count($conflict)) {

                            $delete_peepso_trans = FALSE;

                            // PeepSo will hook into these to display a custom message
                            set_site_transient('peepso_block_updates', $conflict);
                            set_site_transient('peepso_new_version', $new_version);

                            // Prevent WP updates
                            unset($value->checked[$path]);
                            unset($value->response[$path]);
                        }
                    }

                    if ($delete_peepso_trans) {
                        delete_site_transient('peepso_block_updates');
                        delete_site_transient('peepso_new_version');
                    }

                    return $value;
                });


                // Check if PeepSo raised hell over incompatible FSTVL plugins in light of upcoming update
                add_action('after_plugin_row_' . $path, function ($plugin_file, $plugin_data = NULL, $status = NULL) use ($path) {

                    $conflict = get_site_transient('peepso_block_updates');
                    $new_version = get_site_transient('peepso_new_version');

                    if (is_array($conflict) && count($conflict)) {
                        ?>
                        <tr class="plugin-update-tr active">
                            <td colspan="3">
                                <div class="update-message notice inline notice-warning notice-alt">
                                    <p>
                                        <strong>
                                            <?php echo sprintf(__('There is a new version of PeepSo Core available (%s), but it cannot be installed yet.', 'peepso-core'), $new_version); ?>
                                        </strong>
                                    </p>
                                    <br/>

                                    <span style="color:red">
                                <strong>
                                    <?php echo sprintf(__('To avoid conflicts and issues, please make sure the following plugins are %s:', 'peepso-core'), $new_version); ?>
                                </strong>
                            </span>

                                    <ul>
                                        <?php foreach ($conflict as $name => $version) {
                                            echo "<li><a href=\"?s=$name\" target=\"_blank\">$name</a> ($version)</li>";
                                        }
                                        ?>
                                    </ul>

                                    <strong><?php echo __('After updating these plugins please refresh this page, update PeepSo Core and reactivate all plugins.'); ?></strong>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                });
            } // EOF FSTVL

        } else {
            $this->register_shortcodes();
            add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
            add_action('wp_enqueue_scripts',array(&$this,'enqueue_scripts_overrides'), 99);

            add_action('wp_head', array(&$this, 'opengraph_tags'));
            add_action('wp_head', array(&$this, 'peepso_change_page_title'), 100, 2);
            add_action('wp_loaded', array(&$this, 'check_ajax_query'));
            add_action('wp', array(&$this, 'check_query'), 1);

            // oEmbed handling
            add_filter('oembed_discovery_links', array(&$this,'modify_oembed_links'),100,1);
        }

        add_action('admin_init', array(&$this, 'activation_redirect'));
        add_action('init', array(&$this, 'init_callback'), 50);
        add_action('set_current_user', array(&$this, 'set_user'));
        add_action('init', array(&$this, 'init_mysql_big_size'));


        // activation hooks
        register_activation_hook(__FILE__, array(&$this, 'activate'));
        register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));

        // register widgets
        add_action('widgets_init', array(&$this, 'widgets_init'));
        add_filter( 'peepso_widget_prerender', array(&$this, 'get_widgets_in_position'));
        add_filter( 'peepso_widget_form', array(&$this, 'get_widget_form'));
        add_filter( 'peepso_widget_list_positions', array(&$this, 'get_widget_positions'));

        add_filter('peepso_access_types', array(&$this, 'filter_access_types'));

        add_filter( 'wp_title', array(&$this, 'peepso_change_page_title'), 100, 2);
        add_filter( 'pre_get_document_title', array(&$this, 'peepso_change_page_title'), 100, 2);

        add_action('deleted_user', array(&$this, 'action_deleted_user'));
        add_action('peepso_profile_completeness_redirect', array(&$this, 'action_peepso_profile_completeness_redirect'));

        add_filter('peepso_filter_opengraph_' . self::MODULE_ID, array(&$this, 'peepso_filter_opengraph'), 10, 2);

        // move from activity
        add_filter('peepso_post_extras', array(&$this, 'filter_post_extras'), 10, 1);
        add_action('peepso_activity_post_attachment', array(&$this, 'post_attach_repost'), 10, 1);

        /** Plugin Name: Add Admin Bar Icon */
        add_action('admin_bar_menu', array(&$this, 'wp_admin_bar_menu'), 999 );

		add_filter('upload_size_limit', function($size) { if ($size == 0) return 4 * 1024 * 1024; else return $size; }, 100);
    }

    /**
     * Determine if something changed in chats for current user and if get_chats() call is necessary
     * @return int|void
     */
    public function ajax_should_get_notifications($return = FALSE)
    {
        $delay_min 			= 1000;
        $delay_max 		  	= 20000;
        $delay_multiplier 	= 1.5;

        $delay 				= intval($_POST['delay']);

        $multiply = TRUE;

        if($delay<$delay_min) {
            $multiply = FALSE; // do not multiply the default (first request without param)
            $delay = $delay_min;
        }

        $chats = 0;

        // if the option is set, it means something changed and we should refresh
        if(get_user_option('peepso_should_get_notifications')) {
            delete_user_option(get_current_user_id(), 'peepso_should_get_notifications');
            $delay = $delay_min;
            $chats = 1;
        } else {

            if($multiply) {
                $delay = floor($delay * $delay_multiplier);
            }

            if($delay>$delay_max) {
                $delay = $delay_max;
            }
        }

        $resp = array($chats,$delay);

        if($return) {
            return($resp);
        }

        echo json_encode($resp);
        exit();
    }

    public function wp_admin_bar_menu($bar)
    {
        if(0 === PeepSo::get_option('site_show_notification_on_navigation_bar', 0)) {
            return;
        }

        $note = PeepSoNotifications::get_instance();
        $unread_notes = $note->get_unread_count_for_user();
        $toolbar = array(
            'notifications' => array(
                'href' => PeepSo::get_page('notifications'),
                'icon' => 'globe',
                'class' => 'dropdown-notification ps-js-notifications',
                'title' => __('Pending Notifications', 'peepso-core'),
                'count' => $unread_notes,
                'order' => 80
            ),
        );

        $toolbar = apply_filters('peepso_admin_bar_notifications', $toolbar);

        $sort_col = array();

        foreach ($toolbar as $nav) {
            $sort_col[] = (isset($nav['order']) ? $nav['order'] : 10);
        }

        array_multisort($sort_col, SORT_ASC, $toolbar);
        $toolbar = array_reverse($toolbar);

        foreach ($toolbar as $item => $data) {

            $title = '';
            if (isset($data['icon'])) {
                $title .= '<i class="ps-icon-'. $data['icon']. '"></i>';
            }
            if (isset($data['label'])) {
                $title .= $data['label'];
            }
            if (isset($data['count'])) {
                $title .= '<span class="js-counter ps-notification-counter ps-js-counter"' . ($data['count'] > 0 ? '' : ' style="display:none"').'>'. ($data['count'] > 0 ? $data['count'] : ''). '</span>';
            }

            $bar->add_menu( array(
                'id'     => 'toolbar-'. $data['icon'],
                'parent' => 'top-secondary',
                'group'  => null,
                'title'  => $title,
                'href'   => $data['href'],
                'meta'   => array(
                    'class' => $data['class'],
                    'target' => is_admin() ? '_blank' : '',
                )
            ) );
        }
    }

    public function check_plugins()
    {
        $trans = 'peepso_all_plugins';
        $plugins = apply_filters($trans, array());

        if(count($plugins) && $plugins != get_transient($trans)) {

            // We will stick potential warnings in here to render them later in wp-admin
            $plugin_warnings 	= array();

            // Loop throug the plugin list and perform compatibility checks
            foreach($plugins as $file => $class) {

                $plugin = new stdClass();
                $plugin->file	= $file;
                $plugin->class	= $class;
                $plugin->name 	= $class::PLUGIN_NAME;
                $plugin->version= $class::PLUGIN_VERSION;
                $plugin->release= $class::PLUGIN_RELEASE;

                if(defined("$class::PEEPSO_VER_MAX") && defined("$class::PEEPSO_VER_MIN")) {
                    // PEEPSO_VER_MIN and PEEPSO_VER_MAX are present
                    // use them to verify compatibility (default path for 3rd party plugins)
                    $plugin->peepso_min = $class::PEEPSO_VER_MIN;
                    $plugin->peepso_max = $class::PEEPSO_VER_MAX;
                    $plugin->version_check = self::check_version_minmax($plugin->version, $plugin->peepso_min, $plugin->peepso_max);
                } else {
                    // PEEPSO_VER_MIN and PEEPSO_VER_MAX are missing
                    // assume a strict version lock (all official PeepSo Core, Tools and Extras)
                    $plugin->version_check = self::check_version_compat($plugin->version, $plugin->release);
                }

                // if it's not OK, render an error/warning
                if (1 != $plugin->version_check['compat']) {

                    $plugin_warnings[] = $plugin;

                    add_action('admin_notices', array(&$this, 'plugins_version_notice'));

                    // only if it's a total failure, disable the plugin
                    if (0 == $plugin->version_check['compat']) {
                        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                        deactivate_plugins($plugin->file);
                    }
                }
            }

            add_action('admin_notices', array(&$this, 'plugins_version_notice'));

            set_transient('peepso_plugins_version_notice',$plugin_warnings);
        }
    }

    public function filter_default_stream_options( $options )
    {
        $options[1] = __('Community feed', 'peepso-core');
        ksort($options);
        return $options;
    }

    function action_peepso_profile_completeness_redirect() {

        if(0 == PeepSo::get_option('force_required_profile_fields', 0)) {
            return;
        }

        if( 1 == get_user_meta( get_current_user_id(), 'peepso_after_register_profile_complete', TRUE)) {
            return TRUE;
        }

        $user = PeepSoUser::get_instance(get_current_user_id());

        $user->profile_fields->load_fields();
        $stats = $user->profile_fields->profile_fields_stats;

        if($stats['missing_required'] > 0) {
            PeepSo::redirect(PeepSo::get_page('profile').'?'.$user->get_nicename().'/about');
        }
    }

    function action_deleted_user( $id )
    {
        global $wpdb;

        // Delete all received and sent notifications

        $sql =  "DELETE FROM {$wpdb->prefix}". PeepSoNotifications::TABLE
            .   " WHERE `not_user_id`='$id' OR  `not_from_user_id`='$id'";

        $wpdb->query($sql);

        // Delete all likes

        $sql = "DELETE FROM {$wpdb->prefix}" . PeepSoLike::TABLE . " WHERE `like_user_id`='$id'";
        $wpdb->query($sql);

        // Delete all peepso related posts

        $wpdb->delete(
            $wpdb->posts,
            array('post_author'=>$id, 'post_type' => PeepSoActivityStream::CPT_POST ),
            array('%d','%s')
        );

        $success = $wpdb->delete(
            $wpdb->posts,
            array('post_author'=>$id, 'post_type' => PeepSoActivityStream::CPT_COMMENT ),
            array('%d','%s')
        );

        do_action( 'peepso_user_delete_data', $id );
    }

    public function peepso_save_post_action($post_id, $post, $update)
    {
        preg_match('/\[(peepso_[A-z]+)\]/', $post->post_content, $matches);

        if (!is_array($matches) || !isset($matches[1])) {
            return FALSE;
        }
        $content = $matches[1];

        // register core filter
        add_filter('peepso_save_post', array(&$this, 'peepso_save_post'));

        // look for peepso_something in other filters
        $option = apply_filters('peepso_save_post', $content);

        // if something changed, update the option
        // changed for post type PAGE only
        if ($option != $content && $post->post_type == 'page') {
            $settings = PeepSoConfigSettings::get_instance();
            $settings->set_option($option, $post->post_name);
        }
    }

    public function peepso_save_post($shortcode)
    {
        if(array_key_exists($shortcode, $this->shortcodes)) {
            $page = 'page_'.str_replace(array('peepso_', 'peepso-core'),'',$shortcode);
            return $page;
        }

        return $shortcode;
    }

    public function opengraph_tags()
    {
        global $post;

        if (is_null($post) || $post->post_type != 'page' || PeepSo::get_option('opengraph_enable') === 0)
        {
            return;
        }

        // default tags
        $tags = array(
            'title'			=> str_replace('{sitename}', get_bloginfo('name'), PeepSo::get_option('opengraph_title')),
            'description'	=> PeepSo::get_option('opengraph_description'),
            'image'			=> PeepSo::get_option('opengraph_image', PeepSo::get_asset('images/landing/register-bg.jpg')),
            'url'			=> (( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        );

        switch ($post->post_name)
        {
            case PeepSo::get_option('page_activity') :
                $url = PeepSoUrlSegments::get_instance();

                $post_slug = $url->get(2);
                if (!empty($post_slug))
                {
                    $peepso_activity = new PeepSoActivity();
                    $activity = $peepso_activity->get_activity_by_permalink(sanitize_key($post_slug));
                    $activity = apply_filters('peepso_filter_check_opengraph', $activity);

                    if (is_object($activity) && $activity->act_access == PeepSo::ACCESS_PUBLIC)
                    {
                        $user = PeepSoUser::get_instance($activity->post_author);

                        $tags = apply_filters('peepso_filter_opengraph_' . $activity->act_module_id, $tags, $activity);
                        $tags['title'] .= ' - Post by ' . trim(strip_tags($user->get_fullname()));
                        $tags['description'] = strip_tags(apply_filters('peepso_remove_shortcodes', $activity->post_content, $activity->ID));
                        $tags['description'] = (!empty($tags['description'])) ? $tags['description'] : PeepSo::get_option('opengraph_description');
                    }
                }

                break;
            case PeepSo::get_option('page_profile') :
                $url = PeepSoUrlSegments::get_instance();

                if ($url->get(1))
                {
                    $user = get_user_by('slug', $url->get(1));

                    if (FALSE === $user) {
                        $user = get_user_by('id', get_current_user_id());
                    }
                } else
                {
                    $user = get_user_by('id', get_current_user_id());
                }

                if ($user && is_object($user)) {
                    $user = PeepSoUser::get_instance($user->ID);

                    if ($user && $user->get_profile_accessibility() == PeepSo::ACCESS_PUBLIC)
                    {

                        $tags['title'] .= ' - ' . trim(strip_tags($user->get_fullname()));
                        $tags['image'] = $user->get_avatar();
                        $tags['url'] = $user->get_profileurl();
                    }
                }
                break;
        }

        if (isset($tags) && is_array($tags))
        {
            add_filter('peepso_filter_format_opengraph', array(&$this, 'peepso_filter_format_opengraph'), 10, 2);
            $output = apply_filters('peepso_filter_format_opengraph', $tags);
            echo $output;
        }
    }

    // todo: handling oEmbed when visiting peepso Page
    public function modify_oembed_links($output) {

        global $post;

        // checking og_handling
        if (is_null($post) || $post->post_type != 'page' || PeepSo::get_option('opengraph_enable') === 0)
        {
            return $output;
        }

        switch ($post->post_name) {
            case PeepSo::get_option('page_activity'):
            case PeepSo::get_option('page_profile') :
            case PeepSo::get_option('page_register') :
            case PeepSo::get_option('page_recover') :
            case PeepSo::get_option('page_reset') :
            case PeepSo::get_option('page_members') :
                if(!empty($output)) {
                    #todo adding some modified oembed provider
                    #$output = '<link rel="alternate" type="application/json+oembed" href="http://peep.so/oembed.php">';
                    $output = '';
                }
                break;
        }

        return $output;
    }

    public function remove_footer_admin()
    {
        $page = isset($_GET['page'])? $_GET['page'] : '';
        if(substr($page, 0,6) == 'peepso') {
            echo '<p id="footer-left" class="alignleft">If you like <strong>PeepSo</strong> please leave us a <a href="https://wordpress.org/support/view/plugin-reviews/peepso-core?filter=5#postform" target="_blank" class="wc-rating-link" data-rated="Thanks :)">★★★★★</a> rating. Thank you in advance! And have a great time using PeepSo!</p>';
        }
    }

    public function peepso_change_page_title($title, $sep=''){

        if ( !is_admin() ) {

            $post = get_post();
            $check = apply_filters('peepso_page_title_check', $post);

            if ( !is_object($check) && !is_null($check) && $check) {
                $old_title 	= $title;

                $title = $post->post_content;
                $start = strpos($title, '[peepso') + 1;

                $title=substr($title,$start);
                $stop=strpos($title,']');

                $title 		= substr($title,0,$stop);

                $title 		= apply_filters('peepso_page_title', array('title'=>$title,'newtitle'=>$title));

                if (isset($title['newtitle']) && $title['newtitle'] != '') {
                    $this->wp_title = array('old_title' => $old_title, 'title' => $title['title'], 'newtitle' => str_replace('stream', __('stream', 'peepso-core'), $title['newtitle']));

                    return $this->wp_title['newtitle'];
                }
            }
        }

        return $title;
    }

    public function the_title($title, $post_id = NULL) {

        if (in_the_loop() && !is_admin() ) {

            $post = get_post();
            $check = $post->ID === (int) $post_id ? apply_filters('peepso_page_title_check', $post) : FALSE;

            if ( !is_object($check) && !is_null($check) && $check) {
                $old_title 	= $title;

                $title = $post->post_content;
                $start = strpos($title, '[peepso') + 1;

                $title=substr($title,$start);
                $stop=strpos($title,']');

                $title 		= substr($title,0,$stop);

                if (empty($this->wp_title) || (isset($this->wp_title['newtitle']) && $this->wp_title['newtitle'] == '')) {
                    $this->wp_title 				= apply_filters('peepso_page_title', array('title'=>$title,'newtitle'=>$title));
                    $this->wp_title['old_title'] 	= $old_title;
                }
                $title= ''
                    . '<span id="peepso_page_title">'.$this->wp_title['newtitle'].'</span>'
                    . '<span id="peepso_page_title_old" style="display:none">'.$old_title.'</span>';
            }
        }

        return $title;
    }

    /**
     * Displays	the original author name from a repost
     */
    public function filter_post_extras( $extras = array() )
    {
        global $post;

        $repost = isset($post->act_repost_id) ? $post->act_repost_id : FALSE;

        if ($repost) {
            ob_start();
            $PeepSoActivity = PeepSoActivity::get_instance();
            $repost = $PeepSoActivity->get_activity_post($repost);

            if (NULL !== $repost) {
                $author = PeepSoUser::get_instance($repost->post_author);

                ob_start();
                do_action('peepso_action_render_user_name_before', $author->get_id());
                $before_fullname = ob_get_clean();

                ob_start();
                do_action('peepso_action_render_user_name_after', $author->get_id());
                $after_fullname = ob_get_clean();

                printf(__('via %s', 'peepso-core'),
                    '<a href="' . $author->get_profileurl() . '">' . $before_fullname . $author->get_fullname() . $after_fullname . '</a>');
            }

            $extras[] = ob_get_clean();
        }

        return $extras;
    }

    /**
     * Checks if a post is a repost and sets the html
     * @param  object $current_post The post
     */
    public function post_attach_repost($current_post)
    {
        $repost = $current_post->act_repost_id;

        if ($repost) {
            global $post;
            // Store original loop query, calling $this->get_post() will overwrite it.
            $PeepSoActivity = PeepSoActivity::get_instance();
            // $_orig_post_query = $PeepSoActivity->post_query;
            // $_orig_post_data = $PeepSoActivity->post_data;
            $activity = $PeepSoActivity->get_activity($repost);

            // $act_post = apply_filters('peepso_activity_get_post', NULL, $activity, NULL, NULL);
            $act_post = $PeepSoActivity->activity_get_post(NULL, $activity, NULL, NULL);

            if (NULL !== $act_post) {
                // TODO: resetting the value of the global $post variable is dangerous.
                $post = $act_post;
                // Add this property so that callbacks can do necessary adjustments if it's a repost.
                $post->is_repost = TRUE;
                setup_postdata($post);

                $PeepSoActivity->post_data = get_object_vars($post);
                PeepSoTemplate::exec_template('activity', 'repost', $PeepSoActivity->post_data);
            } else {
//				$post = get_post($repost);
                // TODO: this will reset the global $post variable. Avoid this
//				$post = get_post($repost)
                $re_post = get_post($activity->act_external_id);
                $data = array(
                    'post_author' => (NULL !== $re_post) ? $re_post->post_author : ''
                );
                PeepSoTemplate::exec_template('activity', 'repost-private', $data);
            }

            // Reset to the original loop
            // $PeepSoActivity->post_query = $_orig_post_query;
            // $PeepSoActivity->post_data = $_orig_post_data;
            // $PeepSoActivity->comment_query = NULL;

            // TODO: if you can avoid changing this then it's not needed. Definitely not needed in both cases above so only change it in one and reset before the end of the if-block
            #$post = $_orig_post_data;
            #setup_postdata($post);
        }
    }


    /**
     * Loads the translation file for the PeepSo plugin
     */
    public function load_textdomain()
    {
        $path = str_ireplace(WP_PLUGIN_DIR, '', dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
        load_plugin_textdomain('peepso', FALSE, $path);
    }

    /*
     * retrieve singleton class instance
     * @return instance reference to plugin
     */
    public static function get_instance()
    {
        if (self::$_instance === NULL)
            self::$_instance = new self();
        return (self::$_instance);
    }


    /*
     * Checks for AJAX queries, sets up AJAX Handler
     */
    public function check_ajax_query()
    {
        global $wp_query;

        $sPageName = $_SERVER['REQUEST_URI'];
        $path = trim(parse_url($sPageName, PHP_URL_PATH), '/');

        $parts = explode('/', $path);
        $segment = count($parts) - 2;

        if ($segment >= 0 && 'peepsoajax' === $parts[$segment]) {
            $page = (isset($parts[$segment + 1]) ? $parts[$segment + 1] : '');
            new PeepSoAjaxHandler($page);		// loads AJAX handling code

            header('HTTP/1.0 200 OK');			// reset HTTP result code, no longer a 404 error
            $wp_query->is_404 = FALSE;
            $wp_query->is_page = TRUE;
            $wp_query->is_admin = FALSE;
            unset($wp_query->query['error']);


            if (array_key_exists('HTTP_REFERER', $_SERVER)) {
                setcookie('peepso_last_visited_page', $_SERVER['HTTP_REFERER'], time() + (MINUTE_IN_SECONDS * 30), '/');
            }

            $this->is_ajax = TRUE;
            return;
        }
    }


    /*
     * Called when WP is loaded; need to signal PeepSo plugins that everything's ready
     */
    public function init_callback()
    {
        do_action('peepso_init');
        $act = new PeepSoActivityShortcode();
    }


    /*
     * Initialize all PeepSo widgets
     */
    public function widgets_init()
    {
        $this->_widgets = apply_filters('peepso_widgets', $this->_widgets);

        if (count($this->_widgets)) {
            foreach ($this->_widgets as $widget_name) {
                register_widget($widget_name);
            }
        }
    }

    /*
     * Load widget instances for a given position
     */
    public function get_widgets_in_position($profile_position){

        $widgets = wp_get_sidebars_widgets();

        $result_widgets = array();

        foreach($widgets as $position => $list) {

            // SKIP if the position name does not start with peepso
            if ('peepso' != substr($position,0,6)){
                continue;
            }

            // SKIP if the position is empty
            if (!count($list)) {
                continue;
            }

            $widget_instances = array();

            // loop through widgets in a position
            foreach($list as $widget) {

                // SKIP if the widget name does not contain "peepsowidget"
                if (!stristr($widget, 'peepsowidget')) {
                    continue;
                }

                // remove "peepsowidget"
                $widget = str_ireplace('peepsowidget', '', $widget);

                // extract last part of class name and id of the instance
                // eg "videos-1" becomes "videos" and "1"
                $widget = explode('-', $widget);

                $widget_class = 'PeepSoWidget'.ucfirst($widget[0]);
                $widget_instance_id = $widget[1];

                // to avoid creating multiple instances  use the local aray to store repeated widgets
                if (!array_key_exists($widget_class, $widget_instances) && class_exists($widget_class)) {
                    $widget_instance = new $widget_class;
                    $widget_instances[$widget_class] = $widget_instance->get_settings();
                }

                // load the instance we are interested in (eg PeepSoVideos 1)
                if (array_key_exists($widget_class, $widget_instances)){
                    $current_instance = $widget_instances[$widget_class][$widget_instance_id];
                } else {
                    continue;
                }
                // SKIP if the instance isn't in a valid position
                if (!isset($current_instance['position']) || $current_instance['position'] != $profile_position) {
                    continue;
                }

                $current_instance['widget_class'] = $widget_class;

                // add to result array
                $result_widgets[]=$current_instance;
            }
        }

        return $result_widgets;
    }

    /**
     * Returns HTML used to render options for PeepSo Widgets (including profile widgets)
     * @TODO parameters (optional/additional fields) when needed
     * @TODO text domain
     * @param $widget
     * @return array
     */
    public function get_widget_form($widget)
    {
        $widget['html'] = $widget['html'] . PeepSoTemplate::exec_template('widgets', 'admin_form', $widget, true);
        return $widget;
    }

    public function get_widget_positions($positions)
    {
        return array_merge($positions, array('profile_sidebar_top', 'profile_sidebar_bottom'));
    }

    /*
     * checks current URL to see if it's one of the PeepSo specific pages
     * If it is, loads the appropriate shortcode early so it can set up it's hooks
     */
    public function check_query()
    {
        if ($this->is_ajax) {
            return;
        }

        if(isset($_GET['peepso_process_mailqueue'])) {
            PeepSoMailQueue::process_mailqueue();
            die();
        }

        if(isset($_GET['peepso_delete_transients'])) {
            global $wpdb;
            $wpdb->query('DELETE FROM ' . $wpdb->options .' WHERE `option_name` LIKE \'%transient_peepso_cache%\'');
            $wpdb->query('DELETE FROM ' . $wpdb->options .' WHERE `option_name` LIKE \'%transient_timeout_peepso_cache%\'');
            die('Transients Deleted');
        }

        // check if a logout is requested
        if (isset($_GET['logout'])) {
            setcookie('peepso_last_visited_page', '', time() - 3600);
            wp_logout();
            PeepSo::redirect(PeepSo::get_page('logout_redirect'));
        } else {
            setcookie('peepso_last_visited_page', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}", time() + (MINUTE_IN_SECONDS * 30), '/');
        }

        $url = PeepSoUrlSegments::get_instance();

        // If permalinks are turned on use the post name instead. For example 'register':
        // TODO: this is probably no longer needed
        $pl = get_option('permalink_structure');
        if (!empty($pl)) {
            global $post;
            if (NULL !== $post)
                $page = $post->post_name;
        }

        $sc = NULL;

        switch ($url->get(0))
        {
            case 'peepso_profile':				// PeepSo::get_option('page_profile'):
                $sc = PeepSoProfileShortcode::get_instance();
                break;

            case 'peepso_recover':				// PeepSo::get_option('page_recover'):
                PeepSoRecoverPasswordShortcode::get_instance();
                break;

            case 'peepso_reset':				// PeepSo::get_option('page_resetpassword'):
                PeepSoResetPasswordShortcode::get_instance();
                break;

            case 'peepso_register':				// PeepSo::get_option('page_register'):
                PeepSoRegisterShortcode::get_instance();
                break;

            case 'peepso_activity':
                $sc = PeepSoActivityShortcode::get_instance();
                break;

            default:
                $sc = apply_filters('peepso_check_query', NULL, $url->get(0), $url);
                break;
        }

        if (NULL !== $sc) {

            if ($user_id = get_current_user_id()) {

                $user = PeepSoUser::get_instance($user_id);

                if ('ban' == $user->get_user_role()) {
                    $ban_date = get_user_meta( $user_id, 'peepso_ban_user_date', true );
                    if(empty($ban_date)) {
                        wp_logout();
                        echo "<script type=text/javascript>"
                            ." alert('" . __('Your account has been suspended.', 'peepso-core') . "');"
                            . "window.location.replace('" . PeepSo::get_page('activity') . "');"
                            . "</script>";
                        die();
                    } else {
                        #$current_time = strtotime(current_time('Y-m-d H:i:s',1));
                        $current_time = time();

                        $suspense_expired = intval($ban_date) - $current_time;
                        if($suspense_expired > 0)
                        {
                            wp_logout();
                            echo "<script type=text/javascript>"
                                ." alert('" . sprintf(__('Your account has been suspended until %s.', 'peepso-core') , date_i18n(get_option('date_format'), $ban_date)) ."');"
                                . "window.location.replace('" . PeepSo::get_page('activity') . "');"
                                . "</script>";
                            die();
                        } else {
                            // unset ban_date
                            // set user role to member
                            $user->set_user_role('member');
                            delete_user_meta($user_id, 'peepso_ban_user_date');
                        }
                    }
                }

                if( !$sc instanceof PeepSoProfileShortcode || !stristr($_SERVER['REQUEST_URI'] ,'/about') ) {
                    do_action('peepso_profile_completeness_redirect');
                }
            }

            add_filter( 'the_title', ARRAY(&$this,'the_title'), 10, 2 );
            $sc->set_page($url);
        }
    }


    /*
     * Checks the user role and redirects non-admin requests back to the front of the site
     */
    public function check_admin_access()
    {
        return;
        $role = self::_get_role();
        if ('admin' !== $role) {
            PeepSo::redirect(get_home_url());
        }

        // if it's a "peepso_" user, redirect to the front page
//		$sRole = self::get_user_role();
//		if (substr($sRole, 0, 7) == 'peepso_') {
//			PeepSo::redirect(get_home_url());
//			die;
//		}
    }


    /*
     * autoloading callback function
     * @param string $class name of class to autoload
     * @return TRUE to continue; otherwise FALSE
     */
    public function autoload($class)
    {
        // setup the class name
        $classname = $class = strtolower($class);
        if ('peepso' === substr($class, 0, 6))
            $classname = substr($class, 6);		// remove 'peepso' prefix on class file name

        // check each path
        $continue = TRUE;
        foreach (self::$_autoload_paths as $path) {
            $classfile = $path . $classname . '.php';
            if (file_exists($classfile)) {
                require_once($classfile);
                $continue = FALSE;
                break;
            }
        }
        return ($continue);
    }


    /*
     * Adds a directory to the list of autoload directories. Can be used by add-ons
     * to include additional directories to look for class files in.
     * @param string $dirname the directory name to be added
     */
    public static function add_autoload_directory($dirname)
    {
        if (substr($dirname, -1) != DIRECTORY_SEPARATOR) {
            $dirname .= DIRECTORY_SEPARATOR;
        }

        ob_start();
        $dirs = array_diff(scandir($dirname), array('..', '.'));
        ob_end_clean();

        if(count($dirs)) {
            foreach ($dirs as $dir) {
                $path = $dirname . $dir;
                if (!is_dir($path)) {
                    continue;
                }

                PeepSo::add_autoload_directory($path);
            }
        }

        self::$_autoload_paths[] = $dirname;
    }


    /*
     * called on plugin first activation
     */
    public function activate()
    {
        if ($this->can_install()) {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
            $install = new PeepSoActivate();
            $res = $install->plugin_activation();
            if (FALSE === $res) {
                // error during installation - disable
                deactivate_plugins(plugin_basename(__FILE__));
            } else if (NULL === get_option('peepso_install_date', NULL)) {
                add_option('peepso_do_activation_redirect', TRUE);
                add_option('peepso_install_date', date('Y-m-d'));
            }
        }
    }

    /**
     * Redirects to the File Systems settings after activation, to setup directories.
     */
    public function activation_redirect()
    {
        // stage 1, redirect to filesystem settings
        if (get_option('peepso_do_activation_redirect', FALSE) || isset($_GET['freshinstall']))  {
            set_transient('peepso_cache_set_dashboard',1,3600*24*365);
            delete_option('peepso_do_activation_redirect');
            PeepSo::redirect('admin.php?page=peepso_config&tab=advanced&filesystem');
        } else {
            // stage 2, mark a future welcome page redirect
            if(get_transient('peepso_cache_set_dashboard')) {
                delete_transient('peepso_cache_set_dashboard');
                set_transient('peepso_cache_do_welcome_screen',1,3600*24*365);
            } else {
                // stage 3, redirect to dashboard
                if(get_transient('peepso_cache_do_welcome_screen')) {
                    delete_transient('peepso_cache_do_welcome_screen');
                    PeepSo::redirect('admin.php?page=peepso');
                }
            }
        }
    }

    /*
     * Method for determining if permalinks are turned on and disabling PeepSo if not
     * @return Boolean TRUE if a permalink structure is defined; otherwise FALSE
     */
    public static function has_permalinks()
    {
        if (!get_option('permalink_structure')) {
            if (isset($_GET['activate']))
                unset($_GET['activate']);

            deactivate_plugins(plugin_basename(__FILE__));

            $msg = sprintf(__('Cannot activate PeepSo; it requires <b>Permalinks</b> to be enabled. Go to <a href="%1$s">Settings -&gt; Permalinks</a> and select anything but the <i>Default</i> option.', 'peepso-core'),
                get_admin_url(get_current_blog_id()) . 'options-permalink.php');
            PeepSoAdmin::get_instance()->add_notice($msg);

            if (is_plugin_active(plugin_basename(__FILE__))) {
                PeepSo::deactivate();
            }
            return (FALSE);
        }
        return (TRUE);
    }


    /**
     * Checks whether PeepSo can be installed on the current hosting and Wordpress setup.
     * Checks if necessary directories are writeable and if permalinks are enabled.
     *
     * @return boolean TRUE|FALSE if install is possible.
     */
    public static function can_install()
    {
        return (self::has_permalinks());

        /*if (!is_writable(WP_CONTENT_DIR)) {
            if (isset($_GET['activate']))
                unset($_GET['activate']);

            deactivate_plugins(plugin_basename(__FILE__));

            PeepSoAdmin::get_instance()->add_notice(
                sprintf(__('PeepSo requires the %1$s folder to be writable.', 'peepso-core'), WP_CONTENT_DIR));

            if (is_plugin_active(plugin_basename(__FILE__)))
                PeepSo::deactivate();

            return (FALSE);
        }*/
    }

    /*
     * called on plugin deactivation
     */
    public function deactivate()
    {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'deactivate.php');
        PeepSoUninstall::plugin_deactivation();
    }

    /*
     * enqueue scripts needed
     */
    public function enqueue_scripts()
    {
        $template = PeepSo::get_option('site_css_template','');
        if(strlen($template)) {
            $template = 'template-'.$template.'.css';

            if ( is_rtl() ) {
                $template = 'template-'.$template.'-rtl.css';
            }
        } else {
            $template='template.css';

            if ( is_rtl() ) {
                $template = 'template-rtl.css';
            }
        }

        wp_register_style('peepso', PeepSo::get_template_asset(NULL, 'css/'.$template),
            NULL, PeepSo::PLUGIN_VERSION, 'all');
        wp_enqueue_style('peepso');

        // core peepso libraries
        wp_register_script('peepso-core', PeepSo::get_asset('js/peepso-core.min.js'), array('jquery', 'underscore'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-observer', FALSE, array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-npm', FALSE, array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-util', FALSE, array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso', PeepSo::get_asset('js/peepso.js'), array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepso');

        wp_register_script('peepso-page-autoload', PeepSo::get_asset('js/page-autoload.min.js'), array('jquery', 'underscore', 'peepso'), PeepSo::PLUGIN_VERSION, TRUE);

        // auto-update time label script
        wp_register_script('peepso-time', PeepSo::get_asset('js/time.min.js'), array('jquery', 'peepso-observer'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_localize_script('peepso-time', 'peepsotimedata', array(
            'ts'     => current_time('U'),
            'now'    => __('just now', 'peepso-core'),
            'min'    => sprintf( __('%s ago', 'peepso-core'), _n('%s min', '%s mins', 1, 'peepso-core') ),
            'mins'   => sprintf( __('%s ago', 'peepso-core'), _n('%s min', '%s mins', 2, 'peepso-core') ),
            'hour'   => sprintf( __('%s ago', 'peepso-core'), _n('%s hour', '%s hours', 1, 'peepso-core') ),
            'hours'  => sprintf( __('%s ago', 'peepso-core'), _n('%s hour', '%s hours', 2, 'peepso-core') ),
            'day'    => sprintf( __('%s ago', 'peepso-core'), _n('%s day', '%s days', 1, 'peepso-core') ),
            'days'   => sprintf( __('%s ago', 'peepso-core'), _n('%s day', '%s days', 2, 'peepso-core') ),
            'week'   => sprintf( __('%s ago', 'peepso-core'), _n('%s week', '%s weeks', 1, 'peepso-core') ),
            'weeks'  => sprintf( __('%s ago', 'peepso-core'), _n('%s week', '%s weeks', 2, 'peepso-core') ),
            'month'  => sprintf( __('%s ago', 'peepso-core'), _n('%s month', '%s months', 1, 'peepso-core') ),
            'months' => sprintf( __('%s ago', 'peepso-core'), _n('%s month', '%s months', 2, 'peepso-core') ),
            'year'   => sprintf( __('%s ago', 'peepso-core'), _n('%s year', '%s years', 1, 'peepso-core') ),
            'years'  => sprintf( __('%s ago', 'peepso-core'), _n('%s year', '%s years', 2, 'peepso-core') )
        ));
        wp_enqueue_script('peepso-time');

        // member script
        wp_register_script('peepso-member', PeepSo::get_asset('js/member.min.js'), array('jquery', 'peepso-observer'), PeepSo::PLUGIN_VERSION, TRUE);
        $ban_start_date = date_i18n(get_option('date_format'), strtotime('+1 day'));
        wp_localize_script('peepso-member', 'peepsomemberdata', array(
            'ban_popup_title' => __('Ban this user', 'peepso-core'),
            'ban_popup_content' => PeepSoTemplate::exec_template('profile', 'dialog-ban', array('start_date' => $ban_start_date), TRUE),
            'ban_popup_save' => __('Ban this user', 'peepso-core'),
            'ban_popup_cancel' => __('Cancel', 'peepso-core'),
        ));
        wp_enqueue_script('peepso-member');

        // popup window
        wp_register_script('peepso-window', PeepSo::get_asset('js/pswindow.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_localize_script('peepso-window', 'peepsowindowdata', array(
            'label_confirm' => __('Confirm', 'peepso-core'),
            'label_confirm_delete' => __('Confirm Delete', 'peepso-core'),
            'label_confirm_delete_content' => __('Are you sure you want to delete this?', 'peepso-core'),
            'label_yes' => __('Yes', 'peepso-core'),
            'label_no' => __('No', 'peepso-core'),
            'label_delete' => __('Delete', 'peepso-core'),
            'label_cancel' => __('Cancel', 'peepso-core'),
            'label_okay' => __('Okay', 'peepso-core'),
        ));

        // postbox
        wp_register_script('peepso-postbox-legacy', PeepSo::get_asset('js/postbox-legacy.min.js'), array('peepso'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-postbox', PeepSo::get_asset('js/postbox.min.js'), array('peepso', 'peepso-postbox-legacy'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_localize_script('peepso-postbox', 'psdata_postbox', array(
            'template' => PeepSoTemplate::exec_template('general', 'postbox', NULL, TRUE),
            'max_chars' => PeepSo::get_option('site_status_limit', 4000)
        ));

        // file uploader library
        wp_register_style('peepso-fileupload', PeepSo::get_asset('css/jquery.fileupload.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
        wp_register_script('peepso-iframetransport', PeepSo::get_asset('js/jquery.iframe-transport.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-fileupload', PeepSo::get_asset('js/jquery.fileupload.min.js'), array('jquery-ui-widget', 'peepso-iframetransport'), PeepSo::PLUGIN_VERSION, TRUE);

        // avatar
        wp_register_script('peepso-avatar', PeepSo::get_asset('js/avatar.js'), array('peepso', 'peepso-fileupload'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-hammer', PeepSo::get_asset('js/hammer.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-crop', PeepSo::get_asset('js/crop.js'), array('peepso', 'peepso-hammer'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-avatar-dialog', PeepSo::get_asset('js/avatar-dialog.js'), array('peepso', 'peepso-avatar', 'peepso-crop'), PeepSo::PLUGIN_VERSION, TRUE);
        add_filter('peepso_data', function( $data ) {
            $data['avatar'] = array(
                'uploadNonce' => wp_create_nonce('profile-photo'),
                'uploadMaxSize' => wp_max_upload_size(),
                'templateDialog' => PeepSoTemplate::exec_template('profile', 'dialog-avatar', NULL, TRUE),
                'textErrorFileType' => __('The file type you uploaded is not allowed. Only JPEG/PNG allowed.', 'peepso-core'),
                'textErrorFileSize' => sprintf(__('The file size you uploaded is too big. The maximum file size is %s.', 'peepso-core'), '<strong>' . PeepSoGeneral::get_instance()->upload_size() . '</strong>'),
            );
            return $data;
        }, 10, 1 );

        // datepicker
        wp_register_style('peepso-datepicker', PeepSo::get_asset('css/datepicker.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
        wp_register_script('peepso-datepicker', PeepSo::get_asset('js/bootstrap-datepicker.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_localize_script('peepso-datepicker', 'peepsodatepickerdata', array(
            'config' => ps_datepicker_config()
        ));

        wp_register_script('peepso-share', PeepSo::get_asset('js/share.min.js'), array('jquery', 'peepso-window'), PeepSo::PLUGIN_VERSION, TRUE);

        wp_register_script('peepso-posttabs', PeepSo::get_asset('js/posttabs.min.js'), array('underscore', 'peepso', 'peepso-observer'), PeepSo::PLUGIN_VERSION, TRUE);

        wp_register_script('peepso-form', PeepSo::get_asset('js/form.js'), array('jquery', 'peepso-datepicker'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('image-scale', PeepSo::get_asset('js/image-scale.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-lightbox', PeepSo::get_asset('js/lightbox.min.js'), array('jquery', 'peepso'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-modal-comments', PeepSo::get_asset('js/modal-comments.min.js'), array('underscore', 'peepso-observer', 'peepso-activitystream-js', 'image-scale', 'peepso-lightbox', 'peepso'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-chosen', plugin_dir_url(__FILE__) . 'assets/js/chosen.jquery.min.js', array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-load-image', plugin_dir_url(__FILE__) . 'assets/js/load-image.all.min.js', array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

        wp_register_script('peepso-autosize', PeepSo::get_asset('js/autosize.min.js'), array('jquery', 'underscore'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepso-autosize');

        wp_register_style('peepso-chosen', plugin_dir_url(__FILE__) . 'assets/css/chosen.min.css', array('peepso'), PeepSo::PLUGIN_VERSION);
        // Enqueue peepso-window, a lot of functionality uses the popup dialogs
        wp_register_script('peepso-jquery-mousewheel', PeepSo::get_asset('js/jquery.mousewheel.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepso-notification', PeepSo::get_asset('js/notifications.min.js'), array('underscore', 'peepso-observer', 'jquery-ui-position', 'peepso-jquery-mousewheel'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepso-window');
        wp_enqueue_script('peepso-modal-comments');

        if(PeepSo::get_option('site_registration_recaptcha_enable', 0)) {
            wp_register_script('recaptcha', 'https://www.google.com/recaptcha/api.js');
            wp_enqueue_script('recaptcha');
        }

    }

    public function enqueue_scripts_overrides()
    {
        // 1. "Appearance" config page overrides
        $css_overrides = PeepSoConfigSectionAppearance::$css_overrides;

        foreach($css_overrides as $key) {
            if(0 == PeepSo::get_option($key, 0)) {
                continue;
            }

            $path = $this->get_plugin_dir(__FILE__).'assets/css/overrides/'.$key.'.css';

            if(file_exists($path)){
                $handle = 'peepso-'.$key;
                $uri = PeepSo::get_asset('css/overrides/'.$key.'.css');

                wp_register_style($handle, $uri, array(), PeepSo::PLUGIN_VERSION, 'all');
                wp_enqueue_style($handle);
            }
        }

        // 2. Theme overrides
        $custom = locate_template('peepso/custom.css');
        // only enqueue if custom.css exists in theme/peepso directory
        if (!empty($custom)) {
            $custom = get_stylesheet_directory_uri() . '/peepso/custom.css';
            wp_register_style('peepso-custom', $custom, array(),
                PeepSo::PLUGIN_VERSION, 'all');
            wp_enqueue_style('peepso-custom');
        }


        // 3. User overrides
        $custom_user_file = 'overrides/css/style.css';

        $custom_user_path = self::get_peepso_dir() . $custom_user_file;

        // only enqueue if file exists
        if (file_exists($custom_user_path)) {
            $custom_user_uri = self::get_peepso_uri() . $custom_user_file;
            wp_register_style('peepso-custom-user', $custom_user_uri, array(),
                PeepSo::PLUGIN_VERSION, 'all');
            wp_enqueue_style('peepso-custom-user');
        }

        // javascript `peepsodata` object
        wp_localize_script('peepso', 'peepsodata', apply_filters('peepso_data', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajaxurl_legacy' => get_bloginfo('wpurl') . '/peepsoajax/',
            'version' => PeepSo::PLUGIN_VERSION,
            'postsize' => PeepSo::get_option('site_status_limit', 4000),
            'currentuserid' => get_current_user_id(),
            'userid' => apply_filters('peepso_user_profile_id', 0),		// user id of the user being viewed (from PeepSoProfileShortcode)
            'objectid' => apply_filters('peepso_object_id', 0),			// user id of the object being viewed
            'objecttype' => apply_filters('peepso_object_type', ''),	// type of object being viewed (profile, group, etc.)
            'date_format' => ps_dateformat_php_to_datepicker(get_option('date_format')),
            'notifications_page' => $this->get_page('notifications'),
            'notifications_title' => __('Notifications', 'peepso-core'),
            'members_page' => $this->get_page('members'),
            'open_in_new_tab' => PeepSo::get_option('site_activity_open_links_in_new_tab'),
            'loading_gif' => PeepSo::get_asset('images/ajax-loader.gif'),
            'upload_size' => wp_max_upload_size(),
            'peepso_nonce' => wp_create_nonce('peepso-nonce'),
            // TODO: all labels and messages, etc. need to be moved into HTML content instead of passed in via js data
            // ART: Which template best suited to define the HTML content for these labels?
            // TODO: the one in which they're used. The 'Notice' string isn't used on all pages. Find the javascript that uses it and add it to that page's template
            'label_error' => __('Error', 'peepso-core'),
            'label_notice' => __('Notice', 'peepso-core'),
            'view_all_text' => __('View All', 'peepso-core'),
            'mark_all_as_read_text' => __('Mark All as Read', 'peepso-core'),
            'mime_type_error' => __('The file type you uploaded is not allowed.', 'peepso-core'),
            'login_dialog' => PeepSoTemplate::exec_template('general', 'login', NULL, TRUE),
            'like_text' => _n(' person likes this', ' people like this.', 1, 'peepso-core'),
            'like_text_plural' => _n(' person likes this', ' people like this.', 2, 'peepso-core'),
            'profile_unsaved_notice' => __('There are unsaved changes on this page.', 'peepso-core'),
            'profile_saving_notice' => __('The system is currently saving your changes.', 'peepso-core'),
            'activity_limit_page_load' => PeepSoActivity::ACTIVITY_LIMIT_PAGE_LOAD,
            'activity_limit_below_fold' => PeepSoActivity::ACTIVITY_LIMIT_BELOW_FOLD,
            'loadmore_enable' => PeepSo::get_option('loadmore_enable', 0),
        )));
    }

    /*
     * registers shortcode
     */
    private function register_shortcodes()
    {
        foreach ($this->shortcodes as $shortcode => $callback) {
            if(is_callable($callback)) {
                add_shortcode($shortcode, $callback);
            }
        }
    }

    /**
     * Sets the current shortcode identifier, only the first call to this method is ran
     * @param string $shortcode A string that may be used to identify which shortcode ran first
     */
    public static function set_current_shortcode($shortcode)
    {
        if (NULL === self::$_current_shortcode)
            self::$_current_shortcode = $shortcode;
    }

    /**
     * Returns the identifier for the first PeepSo shortcode that was called
     * @return string
     */
    public static function get_current_shortcode()
    {
        return (self::$_current_shortcode);
    }

    /*
     * callback function for the 'peepso_profile' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function profile_shortcode($atts, $content = '')
    {
        $sc = new PeepSoProfileShortcode($atts, $content);
        return ($sc->do_shortcode($atts, $content));
    }

    /*
     * callback function for the 'peepso_register' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function register_shortcode($atts, $content = '')
    {
        $sc = new PeepSoRegisterShortcode();
        return ($sc->do_shortcode($atts, $content));
    }

    /*
     * callback function for the 'peepso_recover' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function recover_shortcode($atts, $content = '')
    {
        $sc = new PeepSoRecoverPasswordShortcode();
        return ($sc->do_shortcode($atts, $content));
    }

    /*
     * callback function for the 'peepso_reset' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function reset_shortcode($atts, $content = '')
    {
        $sc = new PeepSoResetPasswordShortcode();
        return ($sc->do_shortcode($atts, $content));
    }

    /*
     * callback function for the 'peepso_members' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function search_shortcode($atts, $content = '')
    {
        $sc = new PeepSoMembersShortcode();
        return ($sc->shortcode_search($atts, $content));
    }

    /*
     * return PeepSo option values
     * @param string $name name of the option value being requested
     * @param string $default default value to return if nothing found
     * @return multi the stored option value
     */
    public static function get_option($name, $default = NULL, $check_length = FALSE)
    {
        if (NULL === self::$_config) {
            self::$_config = PeepSoConfigSettings::get_instance();
        }

        $value = self::$_config->get_option($name, $default);

        if(TRUE == $check_length && !strlen($value)) {
            return $default;
        }

        return $value;
    }


    /*
     * Return a named page as a fully qualified URL
     * @param string $name Name of page
     * @return string URL to the fully qualified page name
     */
    public static function get_page($name)
    {
        switch ($name)
        {
            case 'logout':
                $ret = self::get_page('profile') . '?logout';
                break;

            case 'notifications':
                $ret = self::get_page('profile') . '?notifications';
                break;

            case 'redirectlogin':
                $page_id = PeepSo::get_option('site_frontpage_redirectlogin');

                $ret = '';

                if(is_numeric($page_id)) {
                    $page_id = intval($page_id);
                    if ($page_id > 0) {
                        $post = get_post($page_id);
                        $ret = get_page_link($post);
                    }
                }
                break;
            case 'logout_redirect':
                $page_id = PeepSo::get_option('logout_redirect');

                $ret = $_SERVER['HTTP_REFERER'];

                if(is_numeric($page_id)) {
                    $page_id = intval($page_id);
                    if ($page_id > 0) {
                        $post = get_post($page_id);
                        $ret = get_page_link($post);
                    }
                }
                break;

            default:
                $ret = get_bloginfo('url') . '/' . self::get_option('page_' . $name) . '/';
                break;
        }

        return ($ret);
    }


    /*
     * builds a link to a user's profile page
     * @param int $user_id
     * @return string URL to user's profile
     */
    public static function get_user_link($user_id)
    {
        $ret = get_home_url();

        $user = get_user_by('id', $user_id);
        if (FALSE !== $user) {
            $ret .= '/' . PeepSo::get_option('page_profile') . '/?';
            $ret .= $user->user_nicename. '/';
        }

        return (apply_filters('peepso_username_link', $ret, $user_id));
    }

    /*
     * Filter function for 'get_avatar'. Substitutes the PeepSo avatar for the WP one
     * @param string $avater The HTML for the <img> reference to the avatar
     * @param mixed $id_or_email The user id for the avatar (if value is numeric)
     * @param int $size Size in pixels of desired avatar
     * @param string $default The src= attribute value for the <img>
     * @param string $alt The alt= attribute for the <img>
     * @return string The HTML for the full <img> element
     */
    public function filter_avatar($avatar, $id_or_email, $size, $default, $alt)
    {
        if( 0 === intval(PeepSo::get_option('avatars_peepso_only', 0))) {
            return $avatar;
        }

        // https://github.com/jomsocial/peepso/issues/735
        // http://wordpress.stackexchange.com/questions/125692/how-to-know-if-admin-is-in-edit-page-or-post
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if (is_object($screen) && $screen->parent_base == 'edit') {
                return ($avatar);
            }
        }

        // if id_or email is an object, it's a Wordpress default, try getting an email address from it
        if (is_object($id_or_email) && property_exists($id_or_email, 'comment_author_email')) {

            // if the email exists
            if (strlen($id_or_email->comment_author_email) && get_user_by('email', $id_or_email->comment_author_email)){
                $id_or_email = $id_or_email->comment_author_email;
            } else {
                $id_or_email = $id_or_email->user_id;
            }
        }

        // numeric id
        if (is_numeric($id_or_email)) {
            $user_id = intval($id_or_email);
        } else if (is_object($id_or_email)) {
            // if it's an object then it's a wp_comments avatar; just return what's already there
            return ($avatar);
        } else {
            $user = get_user_by('email', $id_or_email);
            $user_id = $user->ID;
            if (FALSE === $user_id)						// if we can't lookup by email
                return ($avatar);						// just return what's already found
        }
        $user = PeepSoUser::get_instance($user_id);
        $img = $user->get_avatar();
        $avatar = '<img alt="' . esc_attr(trim(strip_tags($user->get_fullname()))) . ' avatar" src="' . $img . '" class="avatar avatar-' . $size . " photo\" width=\"{$size}\" height=\"{$size}\" />";
        return ($avatar);
    }

    /**
     * returns URL to PeepSo user's profile page if config enable
     * @return string URL to PeepSo user's profile page
     */
    public static function modify_author_link( $link, $user_id, $user_nicename )
    {
        if( 1 === intval(PeepSo::get_option('always_link_to_peepso_profile', 0))) {
            $user = PeepSoUser::get_instance($user_id);
            if($user){
                $link = $user->get_profileurl();
            }
        }
        return $link;
    }

    /**
     * returns URL to PeepSo user's profile page if config enable
     * @return string URL to PeepSo user's profile page
     */
    public static function modify_edit_profile_link( $link, $user_id, $scheme )
    {
        if($scheme != 'admin') {
            if( 1 === intval(PeepSo::get_option('always_link_to_peepso_profile', 0))) {
                $user = PeepSoUser::get_instance($user_id);
                if($user){
                    $link = $user->get_profileurl();
                }
            }
        }
        return $link;
    }

    /**
     * returns URL to PeepSo user's profile page if config enable
     * @return string URL to PeepSo user's profile page
     */
    public function new_comment_author_profile_link($return, $author, $comment_ID){

        $comment = get_comment( $comment_ID );

        /* Get the comment author config option */
        if( 1 === intval(PeepSo::get_option('always_link_to_peepso_profile', 0))) {
            $user = PeepSoUser::get_instance($comment->user_id);
            if($user){
                $return = "<a href='".$user->get_profileurl()."' rel='' class='author-url'>$author</a>";
            }
        }

        return $return;
    }

// Users, roles, permissions

    // the following are used to check permissions
    // @todo clean up the const
    const PERM_POST = 'post';
    const PERM_POST_VIEW = 'post_view';
    const PERM_POST_EDIT = 'post_edit';
    const PERM_POST_DELETE = 'post_delete';
    const PERM_COMMENT = 'comment';
    const PERM_COMMENT_DELETE = 'delete_comment';
    const PERM_POST_LIKE = 'like_post';
    const PERM_COMMENT_LIKE = 'like_comment';
    const PERM_PROFILE_LIKE = 'like_profile';
    const PERM_PROFILE_VIEW = 'view_profile';
    const PERM_PROFILE_EDIT = 'edit_profile';
    const PERM_REPORT = 'report';

    /**
     * Returns the PeepSo specific role assigned to the current user
     * @return string One of the role names, 'user','member','moderator','admin','ban','register','verified' or FALSE if the user is not logged in
     */
    private static function _get_role()
    {
        static $role = NULL;
        if (NULL !== $role)
            return ($role);

        if (!is_user_logged_in())
            return ($role = FALSE);

        $user = PeepSoUser::get_instance(get_current_user_id());
        return ($role = $user->get_user_role());
    }

    /*
     * Checks if current user has admin priviledges
     * @return boolean TRUE if user has admin priviledges, otherwise FALSE
     */
    public static function is_admin()
    {
        static $is_admin = NULL;
        if (NULL !== $is_admin)
            return ($is_admin);

        // WP administrators is set to PeepSo admins automatically
        if (current_user_can('manage_options'))
            return ($is_admin = TRUE);

        // if user not logged in, always return FALSE
        if (!is_user_logged_in())
            return ($is_admin = FALSE);

        // check the PeepSo user role
        $role = self::_get_role();
        if ('admin' === $role)
            return ($is_admin = TRUE);

        // TODO: use current_user_can() when/if we create capabilities
//		if (current_user_can('peepso_admin'))
//			return ($is_admin = TRUE);

        return ($is_admin = FALSE);
    }

    /**
     * Checks if current user is a member, i.e. has access to viewing the site.
     * @return boolean TRUE if user is allowed to view the site; otherwise FALSE.
     */
    public static function is_member()
    {
        static $is_member = NULL;
        if (NULL !== $is_member)
            return ($is_member);

        $role = self::_get_role();
        // banned, and registered/verified but not approved users are not full members
        if ('ban' === $role || 'register' === $role || 'verified' === $role)
            return ($is_member = FALSE);

        // TODO: use current_user_can() when/if we create capabilities
//		if (current_user_can('peepso_member'))
//			return ($is_member = FALSE);

        return ($is_member = TRUE);
    }

    /**
     * Checks if current user is a moderator.
     * @return boolean TRUE if user is a moderator; otherwise FALSE.
     */
    public static function is_moderator()
    {
        static $is_moderator = NULL;
        if (NULL !== $is_moderator)
            return ($is_moderator);

        $role = self::_get_role();
        if ('moderator' === $role)
            return ($is_moderator = TRUE);

        // TODO: use current_user_can() when/if we create capabilities
//		if (current_user_can('peepso_moderator'))
//			return ($is_moderator = TRUE);

        return ($is_moderator = FALSE);
    }

    /*
     * Check if author has permission to perform action on an owner's Activity Stream
     * @param int $owner The user id of the owner of the Activity Stream
     * @param string $action The action that the author would like to perform
     * @param int $author The author requesting permission to perform the action
     * @param boolean $allow_logged_out Whether or not to allow guest permissions
     * @return Boolean TRUE if author can take the requested action; otherwise FALSE
     */
    public static function check_permissions($owner, $action, $author, $allow_logged_out = FALSE)
    {
        // verify user and author ids
        if (0 === $owner || (0 === $author && FALSE === $allow_logged_out)) {
            return (FALSE);
        }

        // owner always has permissions to do something to themself
        if ($owner === $author) {
            return (TRUE);
        }

        // admin always has permissions to do something
        if (self::is_admin()) {
            return (TRUE);
        }

        // check if author_id is the current user
        if ($author != get_current_user_id()) {
            return (FALSE);
        }

        // check if on the user's block list
        $blk = new PeepSoBlockUsers();
        if ($blk->is_user_blocking($owner, $author, TRUE)) {
            // author is on the owner's block list - exit
            return (FALSE);
        }

        // check author access depending on the action being performed
        switch ($action)
        {
            case self::PERM_POST_VIEW:

                global $post;
                if (isset($post->act_access)) {
                    $access = intval($post->act_access);
                    $post_owner = intval($post->act_owner_id);
                } else {
                    // in case someone calls this from outside PeepSoActivityShortcode
                    global $wpdb;
                    $sql = 'SELECT `act_access`, `act_owner_id` ' .
                        " FROM `{$wpdb->posts}` " .
                        " LEFT JOIN `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` `act` ON `act`.`act_external_id`=`{$wpdb->posts}`.`ID` " .
                        ' WHERE `ID`=%d AND `act`.`act_module_id`=%d
					  LIMIT 1 ';

                    $module_id = (isset($post->act_module_id) ? $post->act_module_id : PeepSoActivity::MODULE_ID);
                    $ret = $wpdb->get_row($wpdb->prepare($sql, $post->ID, $module_id));

                    if ($ret) {
                        $access = intval($ret->act_access);
                        $post_owner = intval($ret->act_owner_id);
                    } else {
                        $access = 10;
                        $post_owner = NULL;
                    }
                }
                switch ($access)
                {
                    case self::ACCESS_PUBLIC:
                        return (TRUE);
                        break;
                    case self::ACCESS_MEMBERS:
                        if (is_user_logged_in())
                            return (TRUE);
                        break;
                    case self::ACCESS_PRIVATE:
                        if (get_current_user_id() === $owner)
                            return (TRUE);
                        break;
                }

                $can_access = apply_filters('peepso_check_permissions-' . $action, -1, $owner, $author, $allow_logged_out);

                if (-1 !== $can_access)
                    return ($can_access);
                return (FALSE);
                break;

            case self::PERM_POST:
            case self::PERM_COMMENT:
                break;

            case self::PERM_POST_EDIT:
                if ($owner !== get_current_user_id())
                    return (FALSE);
                break;

            case self::PERM_POST_DELETE:
            case self::PERM_COMMENT_DELETE:
                return (($owner === $author) || ($owner === get_current_user_id()));
                break;

            case self::PERM_POST_LIKE:			 // intentionally fall through
            case self::PERM_COMMENT_LIKE:
            case self::PERM_PROFILE_VIEW:
                $user = PeepSoUser::get_instance($owner);
                return ($user->is_accessible('profile'));
                break;

            case self::PERM_PROFILE_LIKE:
                if (! PeepSo::get_option('site_likes_profile', TRUE))
                    return (FALSE);

                $user = PeepSoUser::get_instance($owner);
                return ($user->is_profile_likable());
                break;

            case self::PERM_REPORT:
                if (1 === PeepSo::get_option('site_reporting_enable'))
                    return (TRUE);				// if someone can see the content, they can report it
                // TODO: possibly allow reporting only by logged in users
                return (FALSE);
                break;

            default:
                $can_access = apply_filters('peepso_check_permissions-' . $action, -1, $owner, $author, $allow_logged_out);

                if (-1 !== $can_access)
                    return ($can_access);
            // Fall through if a filter for the action doesn't exist.
        }


        // anything that falls through -- check owner's access settings

        $ret = FALSE;

        $own = PeepSoUser::get_instance($owner);
        if ($own) {
            $ret = $own->check_access($action, $author);

        }


        return ($ret);
    }


    /* Determine if a given user id is the owner of an item
     * @param int $post_id The id of the post item to check
     * @param int $owner_id The user id of the post item to check
     * @return Boolean TRUE if it's the owner, otherwise FALSE
     */
    public static function is_owner($post_id, $owner_id)
    {
        // TODO: expand capabilities to do checks on other types of data/tables

        global $wpdb;
        // TODO: use class constant for table name
        $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}peepso_activities` " .
            " WHERE `act_id`=%d AND `act_owner_id`=%d ";
        $ret = $wpdb->get_var($wpdb->prepare($sql, $post_id, $owner_id));

        return (intval($ret) > 0 ? TRUE : FALSE);
    }

    public static function get_last_used_privacy($user_id)
    {
        $privacy = get_user_meta($user_id, 'peepso_last_used_post_privacy', TRUE);

        return $privacy;
    }

    public function set_user()
    {
        if (!current_user_can('edit_posts'))
            show_admin_bar(false);
    }

    /*
     * Returns the current user's role
     * @return string The name of the current user's PeepSo role (one of 'ban', 'register', 'verified', 'user', 'member', 'moderator', 'admin') or NULL if the user is not logged in
     */
    public static function get_user_role()
    {
        // http://wordpress.org/support/topic/how-to-get-the-current-logged-in-users-role
        $role = NULL;
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $role = self::_get_role();
//			global $current_user;
//
//			$aRoles = array_values($current_user->roles);
//			if (count($aRoles) > 0)
//				$sRet = $aRoles[0];
        }
        return ($role);
    }

// Notifications
    /*
     * Return user id of administrator that should receive notifications
     * @return boolean|int Admin user id if email exists, FALSE if otherwise
     */
    public static function get_notification_user()
    {
        $email = self::get_notification_emails();
        $wpuser = get_user_by('email', $email);

        return (FALSE !== $wpuser) ? $wpuser->ID : FALSE;
    }

    public static function get_notification_emails()
    {
        $email = get_option( 'admin_email' );
        return ($email);
    }


// URLs and paths

    /*
     * return user's IP address
     * @return string The IP address of the current user
     */
    public static function get_ip_address()
    {
        // ci/system/libraries/Email.php
        static $ip = NULL;

        if (NULL !== $ip)
            return ($ip);

        $ret = '';

        if (empty($ret) && isset($_SERVER['REMOTE_ADDR']))
            $ret = $_SERVER['REMOTE_ADDR'];

        if (empty($ret) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can return a comma-separated list of IP addresses
            $aParts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'], 1);
            $ret = $aParts[0];
        }

        if (empty($ret) && isset($_SERVER['HTTP_X_REAL_IP']))
            $ret = $_SERVER['HTTP_X_REAL_IP'];

        if (empty($ret) && isset($_SERVER['HTTP_CLIENT_IP']))
            $ret = $_SERVER['HTTP_CLIENT_IP'];

        if (empty($ret))						// use localhost as a last resort
            $ret = '127.0.0.1';

        return ($ip = $ret);
    }

    /*
     * Returns the current page URL with any directory prefixes (when WP is installed in a child directory) removed
     * @return string The URL of the current page, with directory prefixes removed
     */
    public static function get_page_url()
    {
        $url = $_SERVER['REQUEST_URI'];

        $page = get_site_url('/');
        $page = str_replace('http://', '', $page);
        $page = str_replace('https://', '', $page);

        // remove host name at beginning of URL
        if (isset($_SERVER['HTTP_HOST']) && substr($page, 0, strlen($_SERVER['HTTP_HOST'])) === $_SERVER['HTTP_HOST'])
            $page = substr($page, strlen($_SERVER['HTTP_HOST']));

        // remove directory prefix from REQUEST_URI
        if (substr($url, 0, strlen($page)) === $page)
            $url = substr($url, strlen($page));

        // remove any surrounding / characters
        $url = trim($url, '/');

        return ($url);
    }

    /*
     * Get the directory that PeepSo is installed in
     * @return string The PeepSo plugin directory, including a trailing slash
     */
    public static function get_plugin_dir()
    {
        return (plugin_dir_path(__FILE__));
    }

    /*
     * return reference to asset, relative to the base plugin's /assets/ directory
     * @param string $ref asset name to reference
     * @return string href to fully qualified location of referenced asset
     */
    public static function get_asset($ref)
    {
        if('images'==substr($ref,0,6)) {
            $override = 'overrides/' . $ref;
            if (file_exists(PeepSo::get_peepso_dir() . $override)) {
                return (PeepSo::get_peepso_uri() . $override);
            }
        }

        $ret = plugin_dir_url(__FILE__) . 'assets/' . $ref;

        return ($ret);
    }

    /*
     * return the URL to an asset within the template directories
     * @param string $section application section to load the template asset from
     * @param string $ref the reference to the asset
     * @return string the fully qualified URL to the requested asset
     */
    public static function get_template_asset($section, $ref)
    {
        $dir = plugin_dir_url(__FILE__) . 'templates/';
        if (NULL !== $section)
            $dir .= $section . '/';
        $dir = apply_filters('peepso_template_asset', $dir, $section);
        $ret = $dir . $ref;
        return ($ret);
    }

    /*
     * Return the PeepSo working directory, adjusted for MultiSite installs
     * @return string PeepSo working directory
     */
    public static function get_peepso_dir()
    {
        static $peepso_dir;

        if (!isset($peepso_dir)) {
            // wp-content/peepso/users/{user_id}/
            //$peepso_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso';
            $peepso_dir = self::get_option('site_peepso_dir', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso');
            if (is_multisite())
                $peepso_dir .= '-' . get_current_blog_id();
            $peepso_dir .= DIRECTORY_SEPARATOR;
        }
        $peepso_dir = apply_filters('peepso_working_directory', $peepso_dir);
        return ($peepso_dir);
    }

    /*
     * Return the PeepSo working directory as a URL
     * @return string PeepSo working directory URL
     */
    public static function get_peepso_uri()
    {
        static $peepso_uri;

        if (!isset($peepso_uri)) {
            // Clean up Windows nonsense and potential double slashes
            $wp_content_dir = str_replace('\\','/', WP_CONTENT_DIR);
            $abs_path = str_replace('\\','/', ABSPATH);
            $peepso_dir = str_replace('\\','/', self::get_option('site_peepso_dir', WP_CONTENT_DIR . '/peepso'));

            $working_uri = str_replace(array($wp_content_dir, $abs_path), '', $peepso_dir);
            if (strpos($peepso_dir, $wp_content_dir) !== FALSE) {
                $peepso_uri = content_url() . '/' . $working_uri;
            } else {
                $peepso_uri = site_url() . '/' . $working_uri;
            }

            if (is_multisite()) {
                $peepso_uri .= '-' . get_current_blog_id();
            }

            $peepso_uri .= '/';
        }

        $peepso_uri = apply_filters('peepso_working_url', $peepso_uri);

        // Clean up Windows nonsense and potential double slashes
        $peepso_uri = str_replace('\\','/', $peepso_uri);
        $peepso_uri = str_replace(':/','://', $peepso_uri);
        $peepso_uri = str_replace('//','/', $peepso_uri);

        return ($peepso_uri);
    }

    /*
     * return the fully qualified directory for a specific user
     * @param int user id
     * @return string directory name
     */
    public static function get_userdir($user)
    {
        $ret = self::get_peepso_dir() . $user . '/';
        return ($ret);
    }

    public static function get_useruri($user)
    {
        $ret = self::get_peepso_uri() . $user . '/';
        return ($ret);
    }

// Auth

    /**
     * Perform our own authentication on login.
     * @param  mixed $user      null indicates no process has authenticated the user yet. A WP_Error object indicates another process has failed the authentication. A WP_User object indicates another process has authenticated the user.
     * @param  string $username The user's username.
     * @param  string $password The user's password (encrypted).
     * @return mixed            Either a WP_User object if authenticating the user or, if generating an error, a WP_Error object.
     */
    public function auth_signon($user, $username, $password)
    {
        if (!is_wp_error($user) && NULL !== $user) {
            $ban = $for_approval = FALSE;
            $PeepSoUser = PeepSoUser::get_instance($user->ID);
            $role = $PeepSoUser->get_user_role();
            $ban = ('ban' === $role);
            $for_approval = ('verified' === $role || 'register' === $role);

            if ($ban) {
                $ban_date = get_user_meta( $user->ID, 'peepso_ban_user_date', true );
                if(!empty($ban_date)) {
                    #$current_time = strtotime(current_time('Y-m-d H:i:s',1));
                    $current_time = time();
                    $suspense_expired = intval($ban_date) - $current_time;
                    if($suspense_expired > 0)
                    {
                        return (new WP_Error('account_suspended', sprintf(__('Your account has been suspended until %s.' , 'peepso-core'), date_i18n(get_option('date_format'), $ban_date) )));
                    }
                    else
                    {
                        // unset ban_date
                        // set user role to member
                        $PeepSoUser->set_user_role('member');
                        delete_user_meta($user->ID, 'peepso_ban_user_date');
                    }
                } else {
                    return (new WP_Error('account_suspended', __('Your account has been suspended.', 'peepso-core')));
                }
            }

            if ($for_approval && self::get_option('site_registration_enableverification', '0')) {
                return (new WP_Error('pending_approval', __('Your account is awaiting admin approval.', 'peepso-core')));
            }

            if ('register' === $role) {
                return (new WP_Error('pending_approval', __('Please verify the email address you have provided using the link in the email that was sent to you.', 'peepso-core')));
            }
        }

        /*
        @todo commented out due to #304 -  "PeepSo login hook breaks WP mobile app login"

        // check referer to ensure login came from installed domain
        if (!isset($_SERVER['HTTP_REFERER']))
            return (new WP_Error('nonwebsite_login', __('Must login from web site', 'peepso-core')));

        $ref_domain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $our_domain = parse_url(get_bloginfo('wpurl'), PHP_URL_HOST);
        if ($ref_domain !== $our_domain)
            return (new WP_Error('nonwebsite_login', __('Must login from web site', 'peepso-core')));
        */
        return ($user);
    }

    /**
     * Checks peepso roles whether to allow a password to be reset.
     * @param bool $allow Whether to allow the password to be reset. Default true.
     * @param int  $user_id The ID of the user attempting to reset a password.
     * @return mixed TRUE if password reset is allowed, WP_Error if not
     */
    public function allow_password_reset($allow, $user_id)
    {
        $role = self::_get_role();

        $ban = $for_approval = FALSE;

        $ban = ('ban' === $role);
        $for_approval = in_array($role, array('register', 'verified'));

        // end process and display success message
        if ($ban || ($for_approval && PeepSo::get_option('site_registration_enableverification', '0')))
            $allow = new WP_Error('user_login_blocked', __('This user may not login at the moment.', 'peepso-core'));

        return ($allow);
    }

// HTML, widget, linking utils

    public function body_class_filter($classes)
    {
        $classes[]='plg-peepso';
        return $classes;
    }

    /*
    * Clean up default HTML output for integrated widgets
    */
    public function peepso_widget_args_internal( $args )
    {
        $args['before_widget']  = str_replace('widget ','', $args['before_widget']);
        $args['after_widget']   = '</div>';
        $args['before_title']   = str_replace('widgettitle','', $args['before_title']);
        $args['after_title']    = '</h2>';

        return $args;
    }

    /*
    * Adjust widget instance
    */
    public function peepso_widget_instance( $instance )
    {
        if (isset($instance['is_profile_widget'])) {
            $instance['class_suffix'] ='';
        } else {
            $instance['class_suffix'] ='--external';
        }

        return $instance;
    }

    /*
     * Hide "load more" link for guests
     */
    public function peepso_activity_more_posts_link( $link )
    {
        if (!get_current_user_id()) {
            $link = '';
        }

        return $link;
    }

    public function peepso_activity_remove_shortcode( $content )
    {
        foreach($this->shortcodes as $shortcode=>$class) {
            foreach($this->shortcodes as $shortcode=>$class) {
                $from = array('['.$shortcode.']','['.$shortcode);
                $to = array('&#91;'.$shortcode.'&#93;', '&#91;'.$shortcode);
                $content = str_ireplace($from, $to, $content);
            }
        }
        return $content;
    }

    /*
     * Add links to the profile widget
     */
    public function peepso_widget_me_links($links)
    {
        $user = PeepSoUser::get_instance(get_current_user_id());

        $links[0][] = array(
            'href' => $user->get_profileurl(),
            'title' => __('Stream', 'peepso-core'),
            'icon' => 'ps-icon-home',
        );

        $links[1][] = array(
            'href' => $user->get_profileurl().'about',
            'title' => __('About', 'peepso-core'),
            'icon' => 'ps-icon-user2',
        );

        $links[99][] = array(
            'href' => PeepSo::get_page('logout'),
            'title' => __('Log Out', 'peepso-core'),
            'icon' => 'ps-icon-off',
            'class' => 'ps-link--logout',
        );

        ksort($links);
        return $links;
    }

    /*
     * Add links to the profile widget community section
     */
    public function peepso_widget_me_community_links($links)
    {
        $links[0][] = array(
            'href' => PeepSo::get_page('activity'),
            'title' => __('Activity', 'peepso-core'),
            'icon' => 'ps-icon-home',
        );

        $links[1][] = array(
            'href' => PeepSo::get_page('members'),
            'title' => __('Members', 'peepso-core'),
            'icon' => 'ps-icon-users',
        );

        ksort($links);
        return $links;
    }

    /*
     * Add links to the profile segment submenu
     */
    public function peepso_profile_segment_menu_links($links)
    {
        $links[0][]=
            array(
                'href' => '',
                'title' => __('Stream', 'peepso-core'),
                'id' => 'stream',
                'icon' => 'user'
            );
        ksort($links);
        return $links;
    }

// Versioning

    /**
     * Used to check PeepSo version-locked plugin compatibility
     * For third party PEEPSO_VER_MIN and PEEPSO_VER_MAX checks, use check_version_minmax
     * @param $version
     * @param null $release
     * @param null $version_compare
     * @param null $release_compare
     * @return array
     */
    public static function check_version_compat($version, $release = '', $version_compare = '', $release_compare = '')
    {
        $version_compare = (strlen($version_compare)) ? $version_compare : self::PLUGIN_VERSION;
        $release_compare = (strlen($release_compare)) ? $release_compare : self::PLUGIN_RELEASE;
        // initial success array
        $response = array(
            'ver_core' => $version_compare,
            'rel_core' => $release_compare,
            'ver_self' => $version,
            'rel_self' => $release,
            'compat'   =>  1, // 1 - OK, 0 - ERROR, -1 - WARNING
            'part'     => '',
        );

        // if the strings are the same check the "release/build" (alpha, beta etc)
        if ( $version == $version_compare && $release != $release_compare ) {
            $response['compat'] = -1;
        }

        if ($version != $version_compare){
            $response['compat'] = 0;
        }

        return $response;
    }

    /**
     * Check if PeepSo is not older than the minimum required version
     * Check if PeepSo is not newer than the maximum tested version
     * Return values:
     *  1 == OKAY (PeepSo is well in the min-max region)
     *  0 == FAIL (PeepSo is older than minimum required version)
     * -1 == WARN (PeepSo is newer than the max tested version)
     * @param $peepso_ver_min
     * @param $peepso_ver_max
     * @return int
     */
    public static function check_version_minmax($version, $peepso_min, $peepso_max)
    {
        /*
         * version_compare(X,Y)
         * -1 X <  Y
         *  0 X == Y
         *  1 X >  Y
         */

        $result = array(
            'ver_core' 	=> self::PLUGIN_VERSION,
            'ver_self'	=> $version,
            'ver_min'	=> $peepso_min,
            'ver_max'	=> $peepso_max,
            'compat'	=> 1
        );

        // "maximum tested" failure is not fatal
        // PeepSo <= ver_max (-1,0)
        if( 1== version_compare(self::PLUGIN_VERSION, $peepso_max)) {
            $result['compat'] = -1;
        }

        // "minimum required" overrides if needed
        // PeepSo >= ver_min (1,0)
        if( -1==version_compare(self::PLUGIN_VERSION, $peepso_min) ) {
            $result['compat'] = 0;
        }

        return $result;
    }

    public static function get_version_parts($version)
    {
        $version = explode('.', $version);

        if (is_array($version) && 3 == count($version)) {
            foreach($version as $sub) {
                if (!is_numeric($sub)) {
                    return false;
                }
            }

            return array(
                'major' => $version[0],
                'minor' => $version[1],
                'bugfix' => $version[2],
            );
        }

        return false;
    }

// Admin notices & alerts

    // @todo HTML rendering methods should probably be refactored

    /**
     * Show message if peepsofriends can not be installed or run
     */
    public static function license_notice($plugin_name, $plugin_slug, $forced=FALSE)
    {
        $style="";
        if (isset($_GET['page']) && 'peepso_config' == $_GET['page'] && !isset($_GET['tab'])) {

            if (!$forced) {
                return;
            }

            $style="display:none";
        }

        $license_data = PeepSoLicense::get_license($plugin_slug);
        echo "<!--";print_r($license_data);echo "-->";
        switch ($license_data['response']) {
            case 'site_inactive':
                $message = __('This domain is not registered, you can still use PeepSo with PLUGIN_NAME, but you will need to register your domain to get technical support. You can do it <a target="_blank" href="PEEPSOCOM_LICENSES">here</a>.', 'peepso-core');
                break;
            case 'expired':
                $message = __('License for PLUGIN_NAME has expired. Please renew your license on peepso.com and enter a valid license. You can do it <a target="_blank" href="PEEPSOCOM_LICENSES">here</a>.', 'peepso-core');
                break;
            case 'invalid':
            case 'inactive':
            case 'item_name_mismatch':
            default:
                $message = __('License for PLUGIN_NAME is missing or invalid. Please <a href="ENTER_LICENSE">enter a valid license</a> to activate it. You can get your license key <a target="_blank" href="PEEPSOCOM_LICENSES">here</a>.', 'peepso-core');
                break;
        }

        #var_dump($license_data);
        $from = array(
            'PLUGIN_NAME',
            'ENTER_LICENSE',
            'PEEPSOCOM_LICENSES',
        );

        $to = array(
            $plugin_name,
            'admin.php?page=peepso_config#licensing',
            self::PEEPSOCOM_LICENSES,
        );

        $message = str_ireplace( $from, $to, $message );
        #var_dump($message);

        echo '<div class="error peepso" id="error_'.$plugin_slug.'" style="'.$style.'">';
        echo '<strong>', $message , '</strong>';
        echo '</div>';
    }

    public static function mailqueue_notice()
    {
        wp_schedule_event(current_time('timestamp'), 'five_minutes', PeepSo::CRON_MAILQUEUE);
        echo '<div class="error peepso"><strong>' .__('It looks like PeepSo emails were not processing properly. We just tried to fix it automatically.<br> If you see this message repeatedly, consider deactivating and re-activating PeepSo or contacting Support', 'peepso-core').'</strong></div>';

    }


    public static function plugins_version_notice()
    {
        $plugins = get_transient('peepso_plugins_version_notice','');
        if(count($plugins)) {
            foreach($plugins as $plugin) {
                self::version_notice($plugin->name, $plugin->name, $plugin->version_check, FALSE);
            }
        }
    }

    public static function version_notice($plugin_name, $plugin_slug, $version_check, $legacy = TRUE)
    {
        // releases (beta, alpha etc) are only considered in the version-lock scenario
        $version_lock = TRUE;
        if(!isset($version_check['rel_core'])) {
            $version_lock = FALSE;
        }

        if( $version_lock ) {
            if (strlen($version_check['rel_core'])) {
                $version_check['ver_core'] .= "-" . $version_check['rel_core'];
            }

            if (strlen($version_check['ver_self']) && strlen($version_check['rel_self'])) {
                $version_check['ver_self'] .= "-" . $version_check['rel_self'];
            }
        }

        ?>
        <div class="error peepso" style="<?php echo(-1==$version_check['compat']) ? 'border-left-color: orange;':'';?>">
            <?php

            // PeepSo Plugin X.Y.Z
            printf('<strong>%s %s</strong> ',$plugin_name, $version_check['ver_self']);

            if($version_lock) {
                if ( -1 == $version_check['compat'] ) {
                    // is not fully compatible with PeepSo X.Y.Z
                    printf(__('is not fully compatible with <strong>PeepSo %s</strong>. ', 'peepso-core'), $version_check['ver_core']);
                } else {
                    // is not compatible with PeepSo X.Y.Z
                    printf(__('is not compatible with <strong>PeepSo %s</strong> and has been deactivated. ', 'peepso-core'), $version_check['ver_core']);
                }
            }else {
                if ( -1 == $version_check['compat'] ) {
                    // was only tested up to PeepSo X.Y.Z
                    printf(
                        __('was only tested up to <strong>PeepSo %s</strong>. ', 'peepso-core'), $version_check['ver_max']);
                } else {
                    // requires PeepSo X.Y.Z
                    printf(__('has been disabled because it requires <strong>PeepSo %s</strong>. ', 'peepso-core'), $version_check['ver_min']);
                }

                printf(__('You are running PeepSo %s.', 'peepso-core'), $version_check['ver_core']);
            }



            if($version_lock) {
                // Please upgrade
                printf(__('Please upgrade %s and PeepSo Core to avoid conflicts and issues. ', 'peepso-core'), $plugin_name);

                // Upgrade link
                printf(' <a href="%s" target="_blank" style="float:right">%s</a>', self::PEEPSOCOM_LICENSES, __('Upgrade now!', 'peepso-core'));
            }
            ?>
        </div>
        <?php
    }

// Debug & utils

    /*
     * Issue #241
     * Adjust WP_Query flags to disable comments rendering under pages
     * Attempt re-init() of WP_Query where %postname% permalink structure might interfere with our routing
     *
     * @todo might yield UNFORESEEN CONSEQUENCES
     * 2-4-1 = -3
     * Half Life 3 confirmed
     */
    public static function reset_query()
    {
        return FALSE; // #637 resetting query not compatible with SEO & antispam plugins

        wp_reset_query();

        // disable WP comments from displaying on page
        global $wp_query;

        $permalink = get_option('permalink_structure');

        if (stristr($permalink, '%postname%')) {
            $wp_query->init();
        }

        $wp_query->is_single = FALSE;
        $wp_query->is_page = FALSE;
    }

    /*
     * Adds needed intervals
     * @param array $schedules
     * @return array $schedules
    */
    public static function filter_cron_schedules($schedules)
    {
        // adds an interval called 'one_minute' to cron schedules
        $schedules['one_minute'] = array(
            'interval' => 60,
            'display' => __('Every One Minute', 'peepso-core')
        );

        // adds an interval called 'five_minutes' to cron schedules
        $schedules['five_minutes'] = array(
            'interval' => 300,
            'display' => __('Every Five Minutes', 'peepso-core')
        );

        // Adds once weekly to the existing schedules.
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display' => __('Once Weekly', 'peepso-core')
        );

        return ($schedules);
    }

    private static $log_to_console = FALSE;
    public static function log_to_console()
    {
        self::$log_to_console = TRUE;
    }

    /**
     * Add access types hook required for PeepSoPMPro plugin
     * @param array $types existing access types
     * @return array $types new access types
     */
    public function filter_access_types($types)
    {
        $types['peepso_activity'] = array(
            'name' => __('Activity Stream', 'peepso-core'),
            'module' => PeepSoActivity::MODULE_ID,
        );

        $types['peepso_members'] = array(
            'name' => __('Search', 'peepso-core'),
            'module' => self::MODULE_ID,
        );

        $types['peepso_profile'] = array(
            'name' => __('Profile Pages', 'peepso-core'),
            'module' => self::MODULE_ID,
        );

        return ($types);
    }

    public function peepso_filter_opengraph($tags, $activity)
    {
        return $tags;
    }

    public function peepso_filter_format_opengraph($tags, $parent_key = '')
    {
        $output = '';

        foreach($tags as $key => $val) {
            if (is_array($val))
            {
                $output .= apply_filters('peepso_filter_format_opengraph', $val, $key);
            }
            else
            {
                $key = !empty($parent_key) ? $parent_key : esc_attr($key);
                $val = esc_attr($val);

                $output .= "<meta property=\"og:$key\" content=\"$val\" />\n";
            }
        }

        return $output;
    }


    /**
     * Filters the WP_User_Query, add FROM and WHERE clause for join into peepso_users table
     * @param WP_User_query $query The query object to filter
     * @return WP_User_Query The modified query object
     */
    function filter_user_roles(WP_User_Query $user_query)
    {
        global $wpdb;

        if (isset($user_query->query_vars['peepso_roles'])){
            if (is_array($user_query->query_vars['peepso_roles']))
            {
                $roles = "'" . implode("', '", $user_query->query_vars['peepso_roles']) . "'";
            } else
            {
                $roles = "'" . $user_query->query_vars['peepso_roles'] . "'";
            }
            $user_query->query_from .= " LEFT JOIN `{$wpdb->prefix}" . PeepSoUser::TABLE . "` ON `{$wpdb->users}`.`ID` = `{$wpdb->prefix}" . PeepSoUser::TABLE . "`.`usr_id` ";
            $user_query->query_where .= " AND `{$wpdb->prefix}" . PeepSoUser::TABLE . "`.`usr_role` IN ($roles)";

            return $user_query;
        }
    }

    public static function redirect($url)
    {
        #if (is_user_logged_in()) {

        if(!headers_sent()) {
            wp_redirect($url);
            die();
        }

        echo '<script>window.location.replace("'.$url.'");</script>';
        die();
    }

    public function init_mysql_big_size() {
        global $wpdb;
        $wpdb->query('SET SQL_BIG_SELECTS=1');
    }
}

defined('WPINC') || die;
PeepSo::get_instance();

/*
 * WSL Hook for a new social buttons structure.
 */
function wsl_use_peepso_icons( $provider_id, $provider_name, $authenticate_url )
{
    ?>
    <div class="wp-social-login-provider-item">
        <a
                rel           = "nofollow"
                href          = "<?php echo $authenticate_url; ?>"
                data-provider = "<?php echo $provider_id ?>"
                class         = "wp-social-login-provider wp-social-login-provider-<?php echo strtolower( $provider_id ); ?>"
        >
            <i class="ps-icon--social-<?php echo strtolower( $provider_id ); ?>"></i>
            <span>
                    <?php echo $provider_name; ?>
                </span>
        </a>
    </div>
    <?php
}


// load the ActivityStream plugin
require_once(dirname(__FILE__) . '/activity/activitystream.php');
// load helpers
require_once(dirname(__FILE__) . '/lib/helpers.php');
require_once(dirname(__FILE__) . '/lib/pluggable.php');
// EOF
