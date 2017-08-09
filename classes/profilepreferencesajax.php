<?php

class PeepSoProfilePreferencesAjax extends PeepSoAjaxCallback
{
	// @todo docblock
	public function save(PeepSoAjaxResponse $resp)
	{
		$view_uid 	= $this->_input->int('view_user_id',0);
		$uid 		= $this->_input->int('user_id',0);
		$cur_uid	= get_current_user_id();

	    // create a user instance for this user
        $user = PeepSoUser::get_instance($cur_uid);
        $data = $user->get_peepso_user();

		if( (!$view_uid || !$uid || !$cur_uid) || ($cur_uid != $uid) || ($view_uid !=$uid && !current_user_can('edit_users')) ) {
			$resp->error('Insufficient permissions');
			$resp->success(FALSE);
		}

		$meta_key    = $this->_input->val('meta_key');
		$meta_prefix = 'peepso_';
		$new_value   = $this->_input->raw('value');

		// TODO : check if meta key is from user meta or peepso_user table
		if($meta_key == 'usr_profile_acc') {
			$data['usr_profile_acc'] = (isset($new_value)) ? $new_value : PeepSo::ACCESS_MEMBERS;

			// update the peepso_user table with the post data
			$success = $user->update_peepso_user($data);

		// prevent updating non-peepso meta key
		} else if (strpos($meta_key, $meta_prefix) === 0) {
			$old_value	= get_user_meta($cur_uid, $meta_key, true );
			$success = FALSE;
			if($old_value !== $new_value) {
				// will return false if the previous value is the same as $new_value
				$success = update_user_meta( $cur_uid, $meta_key, $new_value );
			}

		} else {
			$success = FALSE;
		}

		if( TRUE === $success || is_int($success) ) {
			$resp->notice(__('Preferences saved.', 'peepso-core'));
			$resp->set('count', $success);
			$resp->success($success);
		} else {
			$resp->success( FALSE );
			$resp->error(__('Failed to save changes.', 'peepso-core'));
		}
	}

	// @todo docblock
	public function save_notifications(PeepSoAjaxResponse $resp)
	{
		$view_uid 	= $this->_input->int('view_user_id',0);
		$uid 		= $this->_input->int('user_id',0);
		$cur_uid	= get_current_user_id();

	    // create a user instance for this user
        $user = PeepSoUser::get_instance($cur_uid);
        $data = $user->get_peepso_user();

		if( (!$view_uid || !$uid || !$cur_uid) || ($cur_uid != $uid) || ($view_uid !=$uid && !current_user_can('edit_users')) ) {
			$resp->error('Insufficient permissions');
			$resp->success(FALSE);
		}

		// get existing un-checklist notification
		$peepso_notifications = get_user_meta($cur_uid, 'peepso_notifications');
		$notifications = ($peepso_notifications) ? $peepso_notifications[0] : array();

		$fieldname	= $this->_input->val('fieldname');
		$new_value	= $this->_input->int('value');

		if(1 === $new_value) {
			$key = array_search($fieldname, $notifications);
			unset($notifications[$key]);
		}
		else
			$notifications[] = $fieldname;

		// will return false if the previous value is the same as $existing_unchecked
		$success = update_user_meta( $cur_uid, 'peepso_notifications', $notifications );

		if( TRUE === $success || is_int($success) ) {
			$resp->notice(__('Preferences has been changes.', 'peepso-core'));
			$resp->set('count', $success);
			$resp->success($success);
		} else {
			$resp->success( FALSE );
			$resp->error(__('Failed to save changes.', 'peepso-core'));
		}
	}
}

// EOF
