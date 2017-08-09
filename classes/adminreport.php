<?php

class PeepSoAdminReport extends PeepSoAjaxCallback
{
	/**
	 * Displays the table for reported items such as comments and posts
	 * @return void
	 */
	public static function dashboard()
	{
		wp_enqueue_script('peepso-window');
		wp_enqueue_script('adminactivityreport-js');

		$peepso_list_table = new PeepSoAdminReportListTable();
		$peepso_list_table->prepare_items();

        PeepSoAdmin::admin_header(__('User Reported Items', 'peepso-core'));


		// after updating remember to fix CSS rule in admin.css: #form-mailqueue .row-actions
		echo '<form id="form-reporteditems" method="post">';
		wp_nonce_field('bulk-action', 'report-nonce');
		$peepso_list_table->display();
		echo '</form>';
	}


	/**
	 * Handle an incoming ajax request (called from admin-ajax.php)
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function sort(PeepSoAjaxResponse $resp)
	{
		if (FALSE === PeepSo::is_admin()) {
			$resp->success(FALSE);
			$resp->error(__('Insufficient permissions.', 'peepso-core'));
			return;
		}

		$peepso_list_table = new PeepSoAdminReportListTable();
		$peepso_list_table->prepare_items();

		// assign to local variable first since extract() requires pass by reference
		$_args = $peepso_list_table->_args;
		$_pagination_args = $peepso_list_table->_pagination_args;
		extract($_args);
		extract($_pagination_args, EXTR_SKIP);

		ob_start();
		if (!empty( $_REQUEST['no_placeholder']))
			$peepso_list_table->display_rows();
		else
			$peepso_list_table->display_rows_or_placeholder();
		$rows = ob_get_clean();

		ob_start();
		$peepso_list_table->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$peepso_list_table->pagination('top');
		$pagination_top = ob_get_clean();

		ob_start();
		$peepso_list_table->pagination('bottom');
		$pagination_bottom = ob_get_clean();

		$pagination['top'] = $pagination_top;
		$pagination['bottom'] = $pagination_bottom;

		if (isset($total_items))
			$resp->set('total_items_i18n',
				sprintf(
					_n('1 item', '%s items', $total_items, 'peepso-core'),
					number_format_i18n($total_items)
				)
			);

		if (isset($total_pages)) {
			$resp->set('total_pages', $total_pages);
			$resp->set('total_pages_i18n', number_format_i18n($total_pages));
		}

		$resp->set('rows', $rows);
		$resp->set('pagination', $pagination);
		$resp->set('column_headers', $headers);
		$resp->success(TRUE);
	}

	/**
	 * AJAX callback - Unpublishes the selected item identified by $_POST['rep_id']
	 * and sets the proper response.
	 *
	 * @param  PeepSoAjaxResponse $resp The response is_object
	 * @return void
	 */
	public function unpublish(PeepSoAjaxResponse $resp)
	{
		if (FALSE === PeepSo::is_admin()) {
			$resp->success(FALSE);
			$resp->error(__('Insufficient permissions.', 'peepso-core'));
			return;
		}

		if (FALSE === wp_verify_nonce($this->_input->val('_wpnonce', ''), 'bulk-action')) {
			$resp->error(__('Could not verify nonce.', 'peepso-core'));
			$resp->success(FALSE);
		} else {
			$report = new PeepSoReport();

			$success = $report->unpublish_report($this->_input->int('rep_id'));
			$resp->notice(__('The reported item has been successfully unpublished.', 'peepso-core'));
			$resp->set('count', $success);
			$resp->success($success);
		}
	}

	/**
	 * AJAX callback - Dismisses the selected item identified by $_POST['rep_id']
	 * and sets the proper response.
	 *
	 * @param  PeepSoAjaxResponse $resp The response is_object
	 * @return void
	 */
	public function dismiss(PeepSoAjaxResponse $resp)
	{
		if (FALSE === PeepSo::is_admin()) {
			$resp->success(FALSE);
			$resp->error(__('Insufficient permissions.', 'peepso-core'));
			return;
		}

		if (FALSE === wp_verify_nonce($this->_input->val('_wpnonce', ''), 'bulk-action')) {
			$resp->error(__('Could not verify nonce.', 'peepso-core'));
			$resp->success(FALSE);
		} else {
			$report = new PeepSoReport();

			$success = $report->dismiss_report($this->_input->int('rep_id'));
			$resp->notice(__('The reported item has been successfully dismissed.', 'peepso-core'));
			$resp->set('count', $success);
			$resp->success($success);
		}
	}


	/**
	 * AJAX callback - Bans the selected profile identified by $_POST['rep_id']
	 * and sets the proper response.
	 *
	 * @param  PeepSoAjaxResponse $resp The response is_object
	 * @return void
	 */
	public function ban(PeepSoAjaxResponse $resp)
	{
		if (FALSE === PeepSo::is_admin()) {
			$resp->success(FALSE);
			$resp->error(__('Insufficient permissions.', 'peepso-core'));
			return;
		}

		if (FALSE === wp_verify_nonce($this->_input->val('_wpnonce', ''), 'bulk-action')) {
			$resp->error(__('Could not verify nonce.', 'peepso-core'));
			$resp->success(FALSE);
		} else {
			$report = new PeepSoReport();

			$success = $report->ban_user($this->_input->int('rep_id'));
			$resp->notice(__('The reported item has been successfully banned.', 'peepso-core'));
			$resp->set('count', $success);
			$resp->success($success);
		}
	}
}

// EOF
