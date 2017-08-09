<?php

class PeepSoNotificationsAjax extends PeepSoAjaxCallback
{
	public function get_latest(PeepSoAjaxResponse $resp)
	{
		$user_id = get_current_user_id();

		$profile = PeepSoProfile::get_instance();
		$profile->init($user_id);

		$limit = $this->_input->val('per_page', 10);
		$page = $this->_input->val('page', 1);

		$offset = $limit * max(0, $page - 1);

		$notifications = array();

		if ($profile->has_notifications()) {
			while ($profile->next_notification($limit, $offset)) {
				$notifications[] = PeepSoTemplate::exec_template('general', 'notification-popover-item', NULL, TRUE);
			}

			$resp->success(TRUE);
			$resp->set('notifications', $notifications);
		} else {
			$resp->success(FALSE);
			$resp->error(__('You currently have no notifications', 'peepso-core'));
		}
	}

	// @todo docblock
	public function get_latest_count(PeepSoAjaxResponse $resp) {

		$note = PeepSoNotifications::get_instance();
		$unread_notes = $note->get_unread_count_for_user();
		$data = array('count' => $unread_notes);
		
		$resp->data['ps-js-notifications'] 			= array();
		$resp->data['ps-js-notifications'] 			= $data;
		$resp->data['ps-js-notifications']['el'] 	= 'ps-js-notifications';

		$resp->success(TRUE);
		$resp = apply_filters('peepso_live_notifications', $resp);
	}

	/**
	 * Mark a specific notification (or all notifications) as read
	 * @param $resp Object of PeepSoAjaxResponse
	 */
	public function mark_as_read(PeepSoAjaxResponse $resp) {

		// required note_id if set mark as read on clicked notification or mark as read button
		// otherwise all notification will be mark as read
		$note_id = $this->_input->val('note_id', NULL);

		$note = new PeepSoNotifications();
		$mark = $note->mark_as_read(get_current_user_id(), $note_id);

		if( $mark === FALSE ) {
			$resp->success(FALSE);
			$resp->error(__('Something went wrong', 'peepso-core'));
		} else {
			$resp->success(TRUE);
		}
	}

	/**
	 * Hide notification
	 * @param $resp Object of PeepSoAjaxResponse
	 */
	public function hide(PeepSoAjaxResponse $resp) {

		// required note_id if set mark as read on clicked notification or mark as read button
		// otherwise all notification will be mark as read
		$note_id = $this->_input->val('note_id', NULL);

		if($note_id !== NULL) {

			$ids = array();
			$ids[] = $note_id;
			$note = new PeepSoNotifications();
			$hide = $note->delete_by_id($ids, get_current_user_id());

			if( $hide === FALSE ) {
				$resp->success(FALSE);
				$resp->error(__('You don\'t have permission to do that', 'peepso-core'));
			} else {
				$resp->success(TRUE);
			}
		} else {
			$resp->success(FALSE);
			$resp->error(__('Missing notification ID', 'peepso-core'));
		}
	}
}

// EOF