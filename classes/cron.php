<?php

class PeepSoCron 
{
	public static function initialize()
	{
		if(0==PeepSo::get_option('disable_mailqueue', 0)) {
			add_action(PeepSo::CRON_MAILQUEUE, array('PeepSoMailQueue', 'process_mailqueue'));
		}
		
		if(PeepSo::get_option('rebuild_activity_rank') == 1) {
			add_action(PeepSo::CRON_REBUILD_RANK_EVENT, array('PeepSoActivityRanking', 'rebuild_rank'), 10, 1);
		}

		add_action(PeepSo::CRON_DAILY_EVENT, array('PeepSoDataPurge', 'purge_notification_items'));
		do_action('peepso_cron_init');
	}
}

// EOF