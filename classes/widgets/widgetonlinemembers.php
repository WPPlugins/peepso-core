<?php


class PeepSoWidgetOnlinemembers extends WP_Widget
{

    /**
     * Set up the widget name etc
     */
    public function __construct($id = null, $name = null, $args= null) {
        if(!$id)    $id     = 'PeepSoWidgetOnlineMembers';
        if(!$name)  $name   = __('PeepSo Online Members', 'peepso-core');
        if(!$args)  $args   = array( 'description' => __('PeepSo Online Members Widget', 'peepso-core'), );

        parent::__construct(
            $id, // Base ID
            $name, // Name
            $args // Args
        );
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        $instance['user_id']    = get_current_user_id();
        $instance['user']       = PeepSoUser::get_instance($instance['user_id']);

        if(isset($instance['is_profile_widget']))
        {
            // Override the HTML wrappers
            $args = apply_filters('peepso_widget_args_internal', $args);
        }

        // Additional shared adjustments
        $instance = apply_filters('peepso_widget_instance', $instance);          

        if(!array_key_exists('template', $instance) || !strlen($instance['template']))
        {
            $instance['template'] = 'online-members.tpl';
        }

        $trans_online_members = 'peepso_cache_widget_onlinemembers';

        // check cache
        $list_online_members = get_transient($trans_online_members);
        if(false === $list_online_members) {
            // List of links to be displayed
            $args['orderby']= 'peepso_last_activity';
            $args['order']  = 'desc';
            $args_pagination['offset'] = 0;
            $args_pagination['number'] = $instance['limit'];

            $args_hide_online = array();
            // Check config option for Allow users to hide themselves from all user listings
            if (!PeepSo::is_admin()) {
                $args_hide_online['meta_query'] = array( 
                    'relation' => 'OR',
                    array(
                        'key' => 'peepso_hide_online_status', 
                        'value' => '1', 
                        'compare' => '!='
                        ),
                    array(
                      'compare' => 'NOT EXISTS',
                      'key' => 'peepso_hide_online_status',
                    )
                );
            }   

            // Merge pagination args and run the query to grab paged results
            $args = array_merge($args, $args_pagination, $args_hide_online);

            $list_online_members = new PeepSoUserSearch($args, get_current_user_id(), '');
            set_transient( $trans_online_members, $list_online_members, 60 );
        }

        $members_online = $list_online_members->results;
        $members_found = $list_online_members->total;

        if(!array_key_exists('list', $instance) || !array_key_exists('total', $instance))
        {
            $instance['list'] = $members_online;
            $instance['total'] = $members_found;
        }

        if(0==$instance['total'] && true == $instance['hideempty']) {
            return FALSE;
        }

        $new = array();
        foreach($instance['list'] as $user_id)
        {
            $user = PeepSoUser::get_instance($user_id);
            if(TRUE === $user->is_online())
            {
                $new[] = $user;
            }
        }

        $instance['list'] = $new;
		
		if (isset($instance['totalmember']) && $instance['totalmember'] == 1) {
			$trans_member_count = 'peepso_cache_widget_total_member';
			$total_member_value = get_transient($trans_member_count);
			
			if ($total_member_value == false) {
				$user_args = array(
					'peepso_roles' => array('admin', 'moderator', 'member'),
				);

				$user_query = new WP_User_Query($user_args);
				add_action('pre_user_query', array(PeepSo::get_instance(), 'filter_user_roles'));
				$user_results = $user_query->get_results();
				remove_action('pre_user_query', array(PeepSo::get_instance(), 'filter_user_roles'));
				
				$total_member_value = count($user_results);
				set_transient($trans_member_count, $total_member_value, 300);
			}
			
			$instance['totalmembervalue'] = $total_member_value;
		}

        PeepSoTemplate::exec_template( 'widgets', $instance['template'], array( 'args'=>$args, 'instance' => $instance ) );
    }

    /**
     * Outputs the admin options form
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {

        $instance['fields'] = array(
            // general
            'limit'     => TRUE,
            'title'     => TRUE,

            // peepso
            'integrated'   => TRUE,
            'position'  => TRUE,
            'ordering'  => TRUE,
            'hideempty' => TRUE,
			'totalmember' => TRUE,
        );
		
		if (!isset($instance['title'])) {
			$instance['title'] = __('Online Members', 'peepso-core');
		}
		
        $this->instance = $instance;

        $settings =  apply_filters('peepso_widget_form', array('html'=>'', 'that'=>$this,'instance'=>$instance));
        echo $settings['html'];
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['guest_behavior'] = $new_instance['guest_behavior'];
        $instance['title']          = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['limit']       = (int) $new_instance['limit'];

        $instance['integrated']  = 1;
        $instance['hideempty']   = (int) $new_instance['hideempty'];
        $instance['position']    = strip_tags($new_instance['position']);        
		$instance['totalmember'] = (int) $new_instance['totalmember'];

        return $instance;
    }
}

// EOF