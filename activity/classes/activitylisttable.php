<?php

class PeepSoActivityListTable extends PeepSoListTable 
{
	private $_users = array();

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Defines the query to be used, performs sorting, filtering and calling of bulk actions.
	 * @return void
	 */
	public function prepare_items()
	{
		global $wpdb;

		add_filter('peepso_admin_activity_column_data', array(&$this, 'get_column_data'), 10, 2);

		$input = new PeepSoInput();

		if ($input->exists('action'))
			$this->process_bulk_action();

		$limit = 20;
		$offset = ($this->get_pagenum() - 1) * $limit;

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$activities = PeepSoActivity::get_instance();
		$orderby = '';
		$order = 'DESC';
		
		if (isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $this->get_sortable_columns())) {
			$orderby = $_GET['orderby'];
			
			if (isset($_GET['order']))
				$order = strtoupper($_GET['order']);
			$order = ($order === 'ASC') ? 'ASC' : 'DESC';
		}


		$search = array();
		if (isset($_REQUEST['s'])) {
			$wp_list_table = _get_list_table('WP_Users_List_Table');
			$wp_list_table->prepare_items();
			$search['author__in'] = empty($wp_list_table->items) ? array(0) : array_keys($wp_list_table->items);			
		}

		$items = $activities->get_all_activities($orderby, $order, $limit, $offset, $search);

		$this->set_pagination_args(array(
				'total_items' => $items->found_posts,
				'per_page' => $limit
			));

		$this->items = $items->posts;
	}

	/**
	 * Return and define columns to be displayed on the Activity table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_columns()
	{
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'activity_action' => __('Details', 'peepso-core'),
			'ID' => __('ID (link)', 'peepso-core'),
			'post_date_gmt' => __('Created', 'peepso-core'),
			'post_status' => __('Status', 'peepso-core')
		);

		return (apply_filters('peepso_admin_activity_columns', $columns));
	}

	/**
	 * Return and define columns that may be sorted on the Activity table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_sortable_columns()
	{
		return (array(
			'post_date_gmt' => array('post_date_gmt', true),
		));
	}

	/**
	 * Return default values to be used per column
	 * @param  array $item The post item.
	 * @param  string $column_name The column name, must be defined in get_columns().
	 * @return string The value to be displayed.
	 */
	public function column_default($item, $column_name)
	{
		return (apply_filters('peepso_admin_activity_column_data', $item, $column_name));
	}

	/**
	 * Return values based on the column requested.
	 * @param  array $item The post item.
	 * @param  string $column_name The column name, must be defined in get_columns().
	 * @return mixed The value to be displayed.
	 */
	public function get_column_data($item, $column_name)
	{
		$user = $this->get_user($item->post_author);

		switch ($column_name)
		{
		case 'post_status':
			// publish trash private archive

			if('publish' == $item->post_status) {
				return __('Published', 'peepso-core');
			}

			if('archive' == $item->post_status) {
				return __('Archived', 'peepso-core');
			}

			if('trash' == $item->post_status) {
				return __('Trash', 'peepso-core');
			}

			if('private' == $item->post_status) {
				return __('Private', 'peepso-core');
			}
			return (__(ucfirst($item->post_status), 'peepso-core'));
//		case 'post_title':
//			return ('<a href="' . PeepSo::get_page('activity') . '?status/' . $item->$column_name . '/" target="_blank">' . $item->$column_name . '</a>');
			case 'ID':
				$content = '<a href="' . PeepSo::get_page('activity') . '?status/' . $item->post_title . '/" target="_blank">';
				$content .= $item->ID. ' <i class="fa fa-external-link"></i></a>';
				return($content);
		case 'post_excerpt':
			return (substr(strip_tags($item->$column_name), 0, 30));
		case 'user_avatar':
			$content = '<a href="' . $user->get_profileurl() . '" target="_blank" title="' . trim(strip_tags($user->get_fullname())) . '">';
			$content .= '<img src="' . $user->avatar . '" title="' . trim(strip_tags($user->get_fullname())) . '" width="48" height="48" alt="" style="float:left;margin-right:3px;"/></a>';
			return ($content);
		case 'activity_action':
			$type = get_post_type_object($item->post_type);
			ob_start(); ?>

			<a href="<?php echo $user->get_profileurl();?>" target="_blank">
				<img src="<?php echo $user->avatar;?>" width="24" height="24" alt="" style="float:left;margin-right:10px" />

				<div>
					<?php 
					//[peepso]_[action]_[WHICH_PLUGIN]_[WHERE]_[WHAT]_[BEFORE/AFTER]
					do_action('peepso_action_render_user_name_before', $user->get_id());

					echo $user->get_fullname(); 

					//[peepso]_[action]_[WHICH_PLUGIN]_[WHERE]_[WHAT]_[BEFORE/AFTER]
					do_action('peepso_action_render_user_name_after', $user->get_id());
					?>
					<i class="fa fa-external-link"></i>
				</div>
			</a>
			<div style="clear:both;margin-bottom:5px;"></div>
			<i><?php echo strip_tags($item->post_excerpt);?></i>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return ($content);
		}

		return ($item->$column_name);
	}

	/**
	 * Gets a PeepSoUser object and caches it.
	 * @param  integer $id The user ID.
	 * @return object The PeepSoUser object.
	 */
	public function get_user($id = 0)
	{
		if (!isset($this->_users[$id])) {
			$user = PeepSoUser::get_instance($id);
			$user->avatar = $user->get_avatar();
			$this->_users[$id] = $user;
		}

		return ($this->_users[$id]);
	}

	/**
	 * Returns the HTML for the checkbox column.
	 * @param  array $item The current post item in the loop.
	 * @return string The checkbox cell's HTML.
	 */
	public function column_cb($item)
	{
		return (sprintf('<input type="checkbox" name="posts[]" value="%d" />', $item->ID));
	}

	/**
	 * Define bulk actions available
	 * @return array Associative array of bulk actions, keys are used in self::process_bulk_action().
	 */
	public function get_bulk_actions() 
	{
		return (array(
			'archive' => __('Archive', 'peepso-core'),
			'publish' => __('Publish', 'peepso-core'),
			'delete' => __('Delete', 'peepso-core'),
		));
	}

	/** 
	 * Performs bulk actions based on $this->current_action()
	 * @return void Redirects to the current page.
	 */
	public function process_bulk_action()
	{
		if ($this->current_action() && check_admin_referer('bulk-action', 'activity-nonce')) {
			$input = new PeepSoInput();
			$count = 0;
			$posts = $input->val('posts', array());
			$post = array();
			if ('archive' === $this->current_action() || 'publish' === $this->current_action()) {
				foreach ($posts as $id) {
					$post['ID'] = intval($id);
					$post['post_status'] = $this->current_action();

					wp_update_post($post);
				}

				$message = __('Updated', 'peepso-core');
			} else if ('delete' === $this->current_action()) {
				$activity = new PeepSoActivity();

				foreach ($posts as $id) {
					#wp_delete_post(intval($id));
					$activity->delete_post($id);
				}

				$message = __('Deleted', 'peepso-core');
			}

			$count = count($posts);

			PeepSoAdmin::get_instance()->add_notice(
				sprintf(__('%1$d %2$s %3$s', 'peepso-core'),
					$count,
					_n('post', 'posts', $count, 'peepso-core'),
					$message),
				'note');

			PeepSo::redirect("//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		}
	}
}

// EOF
