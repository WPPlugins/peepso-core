<?php
$PeepSoProfile=PeepSoProfile::get_instance();
?>
<div class="peepso ps-page-profile">
    <section id="mainbody" class="ps-wrapper clearfix">
        <section id="component" role="article" class="clearfix">
            <?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
            <div id="cProfileWrapper" class="clearfix">
                <?php PeepSoTemplate::exec_template('profile', 'focus'); ?>

                <div id="editLayout-stop" class="page-action" style="display: none;">
                    <a onclick="profile.editLayout.stop()" href="javascript:void(0)"><?php _e('Finished Editing Apps Layout', 'peepso-core'); ?></a>
                </div>

                <div class="ps-body">
                    <?php
                    // widgets top
                    $widgets_profile_sidebar_top = apply_filters('peepso_widget_prerender', 'profile_sidebar_top');

                    // widgets bottom
                    $widgets_profile_sidebar_bottom = apply_filters('peepso_widget_prerender', 'profile_sidebar_bottom');
                    ?>

                    <?php
                    $sidebar = NULL;

                    if (count($widgets_profile_sidebar_top) > 0 || count($widgets_profile_sidebar_bottom) > 0) { ?>

                        <?php
                        ob_start();
                        PeepSoTemplate::exec_template('sidebar', 'sidebar', array('profile_sidebar_top'=>$widgets_profile_sidebar_top, 'profile_sidebar_bottom'=>$widgets_profile_sidebar_bottom, ));
                        $sidebar = ob_get_clean();

                        echo $sidebar;
                        ?>
                    <?php } ?>

                    <div class="ps-main <?php if (strlen($sidebar)) echo ''; else echo 'ps-main-full'; ?>">
                        <!-- js_profile_feed_top -->
                        <div class="activity-stream-front">
                            <?php
                            PeepSoTemplate::exec_template('general', 'postbox-legacy', array('is_current_user' => $PeepSoProfile->is_current_user()));
                            ?>

                            <div class="ps-latest-activities-container" data-actid="-1" style="display: none;">
                                <a id="activity-update-click" class="btn btn-block" href="javascript:void(0);"></a>
                            </div>


                            <div class="tab-pane active" id="stream">
                                <!-- recent posts -->
                                <div id="ps-activitystream-recent" class="ps-stream-container" style="display:none"></div>

                                <div id="ps-activitystream" class="ps-stream-container cstream-list creset-list" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>
                                    <!-- pinned posts -->
                                    <?php
                                    $activity = new PeepSoActivity();

                                    if ($activity->has_posts(0, TRUE) ) { ?>

                                        <div id="ps-activitystream-pinned" class="ps-stream-container" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>

                                            <?php
                                            while( $activity->next_post() ) {
                                                $activity->show_post(); // display post and any comments
                                            }
                                            ?>

                                        </div>

                                    <?php }?>

                                    <!-- remaining posts -->
                                    <?php

                                    $showNoMorePostNotice = FALSE;

                                    if ($activity->has_posts(0, FALSE, PeepSoActivity::ACTIVITY_LIMIT_PAGE_LOAD) ) {

                                        $showNoMorePostNotice = TRUE;
                                        $showNoMoreCounter = 0;

                                        // display all posts
                                        while( $activity->next_post() ) {
                                            $activity->show_post(); // display post and any comments
                                            if ( ++$showNoMoreCounter >= PeepSoActivity::ACTIVITY_LIMIT_PAGE_LOAD ) {
                                                $showNoMorePostNotice = FALSE;
                                            }
                                        }

                                        $activity->show_more_posts_link();
                                    }
                                    ?>
                                </div>

                                <div id="ps-no-more-posts" class="ps-alert" <?php echo $showNoMorePostNotice ? '' : 'style="display:none"'; ?>><?php _e('Nothing more to show.', 'peepso-core'); ?></div>

                            </div>
                        </div><!-- end activity-stream-front -->

                        <?php PeepSoTemplate::exec_template('activity','dialogs'); ?>
                        <div id="apps-sortable" class="connectedSortable"></div>
                    </div><!-- cMain -->
                </div><!-- end row -->
            </div><!-- end cProfileWrapper --><!-- js_bottom -->
            <div id="ps-dialogs" style="display:none">
                <?php do_action('peepso_profile_dialogs'); // give add-ons a chance to output some HTML ?>
            </div>
        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
