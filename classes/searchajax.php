<?php

class PeepSoSearchAjax extends PeepSoAjaxCallback
{
	public function search(PeepSoAjaxResponse $resp)
	{
		$limit = 5;
		$query = stripslashes_deep($this->_input->val('query', ''));

		$args = array();
		$args['offset'] = 0;
		$args['number'] = $limit;


		// Search members
		$query_results = new PeepSoUserSearch($args, get_current_user_id(), $query);
		$members_found = $query_results->total;

		if (count($query_results->results) > 0) {

			foreach ($query_results->results as $user_id) {

				$user = PeepSoUser::get_instance($user_id);

				ob_start();
	            do_action('peepso_action_render_user_name_before', $user->get_id());
	            $before_fullname = ob_get_clean();
	            
	            ob_start();
	            do_action('peepso_action_render_user_name_after', $user->get_id());
	            $after_fullname = ob_get_clean();

				$members[] = array(
					'fullname' => $before_fullname . $user->get_fullname() . $after_fullname,
					'avatar_full' => $user->get_avatar('full'),
				);
			}
		}

		$resp->set('members', $members);
		$resp->set('members_total', $members_found);
	}
}

// EOF
