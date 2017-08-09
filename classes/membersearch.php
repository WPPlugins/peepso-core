<?php

class PeepSoMemberSearch extends PeepSoAjaxCallback
{
    private $_member_query = NULL;

    public $template_tags = array(
        'found_members',
        'get_next_member',
        'show_member',
        'show_online_member',
        'show_latest_member'
    );

    /**
     * Called from PeepSoAjaxHandler
     * Declare methods that don't need auth to run
     * @return array
     */
    public function ajax_auth_exceptions()
    {
        $list_exception = array();
        $allow_guest_access = PeepSo::get_option('allow_guest_access_to_members_listing', 0);
        if($allow_guest_access) {
            array_push($list_exception, 'search');
        }

        return $list_exception;
    }

    /**
     * GET
     * Search for users matching the query.
     * @param  PeepSoAjaxResponse $resp
     */
    public function search(PeepSoAjaxResponse $resp)
    {
        $args = array();
        $args_pagination = array();
        $page = $this->_input->int('page', 1);

        // Sorting
        $column = (PeepSo::get_option('system_display_name_style', 'real_name') == 'real_name' ? 'display_name' : 'username');

        $order_by	= $this->_input->val('order_by', $column);
        $order		= $this->_input->val('order', ($order_by == $column ? 'ASC' : NULL));

        if( NULL !== $order_by && strlen($order_by) ) {
            if('ASC' !== $order && 'DESC' !== $order) {
                $order = 'DESC';
            }

            $args['orderby']= $order_by;
            $args['order']	= $order;
        }

        // Additional peepso specific filters

        // Avatar only
        $peepso_args['avatar_custom'] = (int) $this->_input->val('peepso_avatar', 0);
        if ( 1 !== $peepso_args['avatar_custom'] ) {
            unset( $peepso_args['avatar_custom'] );
        }

        // Gender filter
        $peepso_args['meta_gender'] = strtolower($this->_input->val('peepso_gender', ''));
        if ( !in_array( $peepso_args['meta_gender'], array('m','f') ) && strpos($peepso_args['meta_gender'], 'option_') === FALSE) {
            unset( $peepso_args['meta_gender'] );
        }

        if( is_array($peepso_args) && count($peepso_args)) {
            $args['_peepso_args'] = $peepso_args;
        }

        // default limit is 1 (NewScroll)
        $limit = $this->_input->int('limit', 1);

        $resp->set('page', $page);
        $args_pagination['offset'] = ($page-1)*$limit;
        $args_pagination['number'] = $limit;

        // Merge pagination args and run the query to grab paged results
        $args = array_merge($args, $args_pagination);
        $query = stripslashes_deep($this->_input->val('query', ''));
        $query_results = new PeepSoUserSearch($args, get_current_user_id(), $query);
        $members_page = count($query_results->results);
        $members_found = $query_results->total;

        if (count($query_results->results) > 0) {

            foreach ($query_results->results as $user_id) {

                // @todo this seems to be unused
                // $buttons = apply_filters('peepso_member_notification_buttons', array(), $user_id);

                ob_start();

                echo '<div id="" class="ps-members-item-wrapper">';
                echo '<div id="" class="ps-members-item">';
                $this->show_member(PeepSoUser::get_instance($user_id));
                echo '</div>';
                echo '</div>';

                $members[] = ob_get_contents();

                ob_end_clean();

            }

            if($members_found > 0)
            {
                $resp->success(TRUE);
                $resp->set('members', $members);
            }
            else
            {
                $resp->success(FALSE);
                $resp->error(__('No users found.', 'peepso-core'));
            }



        } else {
            $resp->success(FALSE);
            $resp->error(__('No users found.', 'peepso-core'));
        }


        $resp->set('members_page', $members_page);
        $resp->set('members_found', $members_found);
    }

    /**
     * Sets the _member_query variable to use is template tags
     * @param PeepSoUserSearch $query
     */
    public function set_member_query(PeepSoUserSearch $query)
    {
        $this->_member_query = $query;
    }

    /**
     * Return TRUE/FALSE if the user has friends
     * @return boolean
     */
    public function found_members()
    {
        if (is_null($this->_member_query))
            return FALSE;

        return (count($this->_member_query) > 0);
    }

    /**
     * Iterates through the $_member_query and returns the current member in the loop.
     * @return PeepSoUser A PeepSoUser instance of the current member in the loop.
     */
    public function get_next_member()
    {
        if (is_null($this->_member_query))
            return FALSE;

        return $this->_member_query->get_next();
    }

    /**
     * Displays the member.
     * @param  PeepSoUser $member A PeepSoUser instance of the member to be displayed.
     */
    public function show_member($member)
    {
        $online = '';
        if (get_transient('peepso_cache_'.$member->get_id().'_online')) {
            $online = '<span class="ps-member-is-online ps-icon-circle"></span>';
        }

        echo '<div class="ps-members-item-avatar"><div class="ps-avatar">
				<a href="' . $member->get_profileurl() . '">
					<img alt="' . strip_tags($member->get_fullname()) . '"
					src="' . $member->get_avatar() . '" class="ps-name-tips"></a>' .
            '</div>
			</div>
			<div class="ps-members-item-body">
				<a href="' , $member->get_profileurl(), '" class="ps-members-item-title" title="', strip_tags($member->get_fullname()), '" alt="', strip_tags($member->get_fullname()), '">'
        , $online , do_action('peepso_action_render_user_name_before', $member->get_id()), $member->get_fullname() , do_action('peepso_action_render_user_name_after', $member->get_id()) ,
        '</a><span class="ps-members-item-status">';

        do_action('peepso_after_member_thumb', $member->get_id());

        echo '</span></div>';


        $this->member_options($member->get_id());
        $this->member_buttons($member->get_id());
    }

    /**
     * Displays the online member.
     * @param  PeepSoUser $member A PeepSoUser instance of the member to be displayed.
     */
    public function show_online_member($member)
    {
        echo '<a class="ps-avatar" href="' . $member->get_profileurl() . '" title="' . strip_tags($member->get_fullname()) . '">
				<img alt="' . strip_tags($member->get_fullname()) . '"
				src="' . $member->get_avatar() . '" class="ps-name-tips"></a>';


        //$this->member_options($member->get_id());
        //$this->member_buttons($member->get_id());
    }

    /**
     * Displays the latest member.
     * @param  PeepSoUser $member A PeepSoUser instance of the member to be displayed.
     */
    public function show_latest_member($member)
    {
        $online = '';
        if (get_transient('peepso_cache_'.$member->get_id().'_online')) {
            $online = '<span class="ps-member-is-online ps-icon-circle"></span>';
        }

        echo '<a class="ps-avatar" href="' . $member->get_profileurl() . '" title="' . strip_tags($member->get_fullname()) . '">
				<img alt="' . strip_tags($member->get_fullname()) . '"
				src="' . $member->get_avatar() . '" class="ps-name-tips"></a>';

        echo $online;

        //$this->member_options($member->get_id());
        //$this->member_buttons($member->get_id());
    }

    /**
     * Displays a dropdown menu of options available to perform on a certain user based on their member status.
     * @param int $user_id The current member in the loop.
     */
    public static function member_options($user_id, $profile = FALSE)
    {
        if( get_current_user_id() == $user_id ) {
            return array();
        }

        $options = array();

        /*$blk = new PeepSoBlockUsers();

        if ($blk->is_user_blocking(get_current_user_id(), $user_id)) {

            $options['unblock'] = array(
                'label' => __('Unblock User', 'peepso-core'),
                'click' => 'profile.unblock_user(' . $user_id . ', this); return false;',
                'title' => __('Allow this user to see all of your activities', 'peepso-core'),
                'icon' => 'lock',        // @todo icon
            );

        } else {
        */
        $options['block'] = array(
            'label' => __('Block User', 'peepso-core'),
            'click' => 'ps_member.block_user(' . $user_id . ', this); return false;',
            'title' => __('This user will be blocked from all of your activities', 'peepso-core'),
            'icon' => 'remove',
        );

        // ban/unban only available for admin role
        if( FALSE != PeePso::is_admin())
        {
            // ban
            $options['ban'] = array(
                'label' => __('Ban', 'peepso-core'),
                'click' => 'ps_member.ban_user(' . $user_id . ', this); return false;',
                'icon' => 'minus-sign',
            );

            // "unban" is only available from profile page
            if( FALSE !== $profile )
            {
                $options['unban'] = array(
                    'label' => __('Unban', 'peepso-core'),
                    'click' => 'ps_member.unban_user(' . $user_id . ', this); return false;',
                    'icon' => 'plus-sign',
                );

                // check ban status
                $user = PeepSoUser::get_instance($user_id);
                if( 'ban' == $user->get_user_role()) {
                    unset( $options['ban'] );
                } else {
                    unset( $options['unban'] );
                }
            }
        }

        $options = apply_filters('peepso_member_options', $options, $user_id);

        if (0 === count($options))
            // if no options to display, exit
            return;

        $member_options = '';
        foreach ($options as $name => $data) {
            $member_options .= '<li';

            if (isset($data['li-class']))
                $member_options .= ' class="' . $data['li-class'] . '"';
            if (isset($data['extra']))
                $member_options .= ' ' . $data['extra'];

            $member_options .= '><a href="#" ';
            if (isset($data['click']))
                $member_options .= ' onclick="' . esc_js($data['click']) . '" ';
            $member_options .= ' ">';

            $member_options .= '<i class="ps-icon-' . $data['icon'] . '"></i><span>' . $data['label'] . '</span>' . PHP_EOL;
            $member_options .= '</a></li>' . PHP_EOL;
        }

        if( FALSE === $profile) {
            echo PeepSoTemplate::exec_template('members', 'member-options', array('member_options' => $member_options), TRUE);
        } else {
            echo PeepSoTemplate::exec_template('profile', 'profile-options', array('profile_options' => $member_options), TRUE);
        }
    }

    /**
     * Displays a available buttons to perform on a certain user based on their member status.
     * @param int $user_id The current member in the loop.
     */
    public static function member_buttons($user_id)
    {
        if( $user_id == get_current_user_id() ) {
            return;
        }

        $buttons = apply_filters('peepso_member_buttons', array(), $user_id);

        if (0 === count($buttons)) {
            // if no buttons to display, exit
            return;
        }

        $member_buttons = '';
        foreach ($buttons as $name => $data) {
            $member_buttons .= '<button';

            if (isset($data['class']))
                $member_buttons .= ' class="' . $data['class'] . '"';
            if (isset($data['extra']))
                $member_buttons .= ' ' . $data['extra'];
            if (isset($data['click']))
                $member_buttons .= ' onclick="' . esc_js($data['click']) . '" ';

            $member_buttons .= ' ">';

            if (isset($data['icon']))
                $member_buttons .= '<i class="ps-icon-' . $data['icon'] . '"></i> ';
            if (isset($data['label']))
                $member_buttons .= '<span>' . $data['label'] . '</span>';

            if (isset($data['loading']))
                $member_buttons .= ' <img style="margin-left:2px;display:none" src="' . PeepSo::get_asset('images/ajax-loader.gif') .'" alt=""></span>';

            $member_buttons .= '</button>' . PHP_EOL;
        }

        echo PeepSoTemplate::exec_template('members', 'member-buttons', array('member_buttons' => $member_buttons, 'user_id' => $user_id), TRUE);
    }
}

// EOF
