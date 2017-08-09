<?php

class PeepSoActivityAdmin
{
	public static function administration()
	{
		$oPeepSoListTable = new PeepSoActivityListTable();
		$oPeepSoListTable->prepare_items();


		echo '<form id="form-activity" method="post">';
		PeepSoAdmin::admin_header('Activities');
		echo '<div id="peepso" class="wrap">';
		wp_nonce_field('bulk-action', 'activity-nonce');
		echo $oPeepSoListTable->search_box(__('Search User', 'peepso-core'), 'search');
		$oPeepSoListTable->display();
		echo '</div>';
		echo '</form>';
	}

	/**
	 * adds items to the dashboard tabs
	 * @param array $tabs Dashboard tabs
	 * @return array $tabs Dashboard tabs with new post menu
	 */
	// TODO: make this a non-static method
	public static function add_dashboard_tabs($tabs)
	{
		global $wpdb;
		
		$activity = array(
			'slug' => 'peepso-activities',
			'menu' => __('Activities', 'peepso-core'),
			'icon' => 'format-aside',
			'function' => array('PeepSoActivityAdmin', 'administration'),
		);

		$tabs['blue']['posts'] = $activity;
		return ($tabs);
	}
}

// EOF