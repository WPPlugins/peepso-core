<?php

class PeepSoAdminNewsletterDashboard extends PeepSoAjaxCallback
{
	public function set_subscribed_user() 
	{
		add_user_meta(intval($this->_input->val('user_id')), 'peepso_admin_newsletter_subscribe', TRUE);
	}
}