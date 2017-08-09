<?php

class PeepSoActivityRanking {

	const TABLE = 'peepso_activity_ranking';
	const WCOMM = 100;
	const WLIKE = 50;
	const WSHARE = 20;
	const WVIEW = 1;
	const WAGE = 0;

	public function add_new($rank)
	{
		if (is_array($rank))
		{
			$aRankData = array(
				'rank_act_id' => $rank['id'],
				'rank_act_comments' => $rank['comments'],
				'rank_act_likes' => $rank['likes'],
				'rank_act_shares' => $rank['shares'],
				'rank_act_score' => $rank['score'],
				'rank_act_date' => current_time('Y-m-d H:i:s')
			);
		} else
		{
			$aRankData = array(
				'rank_act_id' => $rank,
				'rank_act_date' => current_time('Y-m-d H:i:s')
			);
		}

		global $wpdb;
		$res = $wpdb->insert($wpdb->prefix . self::TABLE, $aRankData);

		return ($res);
	}

	public function delete($rank_act_id)
	{
		global $wpdb;

		$wpdb->delete($wpdb->prefix . self::TABLE, array('rank_act_id' => $rank_act_id));
	}

	public function add_comment_count($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_comments = rank_act_comments+1 " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, $rank_act_id));
		$this->calculate_rank($rank_act_id);
		return ($res);
	}

	public function remove_comment_count($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_comments = rank_act_comments-1 " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, $rank_act_id));
		$this->calculate_rank($rank_act_id);
		return ($res);
	}

	public function add_like_count($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_likes = rank_act_likes+1 " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, $rank_act_id));
		$this->calculate_rank($rank_act_id);
		return ($res);
	}

	public function remove_like_count($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_likes = rank_act_likes-1 " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, $rank_act_id));
		$this->calculate_rank($rank_act_id);
		return ($res);
	}

	public function add_share_count($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_shares = rank_act_shares+1 " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, $rank_act_id));
		$this->calculate_rank($rank_act_id);
		return ($res);
	}

	public function remove_share_count($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_shares = rank_act_shares-1 " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, $rank_act_id));
		$this->calculate_rank($rank_act_id);
		return ($res);
	}

	public function add_view_count($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_views = rank_act_views+1 " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, $rank_act_id));
		$this->calculate_rank($rank_act_id);
		return ($res);
	}

	public function purge_table()
	{
		global $wpdb;

		$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . self::TABLE);
	}

	public function rebuild_rank_trigger()
	{
		if (PeepSo::get_option('rebuild_activity_rank') == 1)
		{
			wp_schedule_event(current_time(), 'one_minute', PeepSo::CRON_REBUILD_RANK_EVENT);
		} else
		{
			wp_clear_scheduled_hook(PeepSo::CRON_EMAIL_DIGEST_EVENT);
		}
	}

	public function rebuild_rank()
	{
		global $wpdb;

		$activity_table = $wpdb->prefix . PeepSoActivity::TABLE_NAME;
		$like_table = $wpdb->prefix . PeepSoLike::TABLE;

		$sql = "SELECT * " .
				" FROM `$activity_table` " .
				" LEFT JOIN (SELECT `$like_table`.`like_external_id`, count(*) as like_count FROM `$like_table` GROUP BY `like_external_id`) as likes ON `like_external_id`=`act_external_id` " .
				" LEFT JOIN (SELECT `$activity_table`.`act_comment_object_id` as `join_act_comment_object_id`, count(*) as comment_count FROM `$activity_table` GROUP BY `act_comment_object_id`) as comments ON `comments`.`join_act_comment_object_id`=`act_external_id` " .
				" LEFT JOIN (SELECT `$activity_table`.`act_repost_id` as `join_act_repost_id`, count(*) as share_count FROM `$activity_table` GROUP BY `act_repost_id`) as shares ON `shares`.`join_act_repost_id`=`act_id` " .
				' WHERE `act_comment_object_id` = 0 ' .
				' AND act_id > %d' .
				' ORDER BY `act_id` ASC';

		$activity_results = $wpdb->get_results($wpdb->prepare($sql, PeepSo::get_option('rebuild_rank_last_act_id')));
		if ($activity_results > 0)
		{
			foreach ($activity_results as $activity)
			{
				$act_id = $activity->act_id;
				$comments = isset($activity->comment_count) ? $activity->comment_count : 0;
				$likes = isset($activity->like_count) ? $activity->like_count : 0;
				$shares = isset($activity->share_count) ? $activity->share_count : 0;

				$this->add_new(array(
					'id' => $act_id,
					'comments' => $comments,
					'likes' => $likes,
					'shares' => $shares,
					'score' => ($comments * self::WCOMM) + ($likes * self::WLIKE) + ($shares * self::WSHARE)
				));
			}

			if (isset($act_id))
			{
				PeepSoConfigSettings::get_instance()->set_option(
						'rebuild_rank_last_act_id', $act_id
				);
			}
		}
	}

	public function calculate_rank($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "UPDATE $table" .
				" SET rank_act_score = (rank_act_comments * %d) + " .
				" (rank_act_likes * %d) + " .
				" (rank_act_shares * %d) + " .
				" (rank_act_views * %d) " .
				" WHERE rank_act_id = %d";

		global $wpdb;
		$res = $wpdb->query($wpdb->prepare($sql, self::WCOMM, self::WLIKE, self::WSHARE, self::WVIEW, $rank_act_id));

		return ($res);
	}


	public static function get_score($rank_act_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$sql = "SELECT rank_act_score  FROM $table WHERE rank_act_id = %d";

		$res = $wpdb->get_row($wpdb->prepare($sql, $rank_act_id), ARRAY_A);

		return $res['rank_act_score'];
	}

	public static function get_score_decayed($rank_act_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		// timestamp PeepSo was installed
		$ps_start= strtotime(get_option('peepso_install_date'));

		// time since PeepSo was installed
		$ps_time = strtotime(current_time('Y-m-d H:i:s')) - $ps_start;

		// raw activity age
		$sql = "SELECT rank_act_date FROM $table WHERE rank_act_id = %d";
		$res = $wpdb->get_row($wpdb->prepare($sql, $rank_act_id), ARRAY_A);
		$rank_time = strtotime($res['rank_act_date']);

		// activity age since PeepSo was installed
		$rank_ps_time = $rank_time - $ps_start;

		// multiplier is a number between 0 and 1
		// representing a point in time between ps_start and ps_time
		$decay = $rank_ps_time / $ps_time;

		// raw activity score
		$rank_score = self::get_score($rank_act_id);

		$rank_score_decayed = $rank_score * $decay;
		#echo "<hr>rank_score_decayed = rank_score * decay<br>";
		#echo "<b>$rank_score_decayed</b> = $rank_score * $decay";
	}


	public function get_most_activity($args)
	{
		global $wpdb;

		$activity_table = $wpdb->prefix . PeepSoActivity::TABLE_NAME;
		$rank_table = $wpdb->prefix . self::TABLE;
		$post_table = $wpdb->prefix . 'posts';
		$order_by = 'rank_act_' . $args['type'];

		$sql = "SELECT * " .
				" FROM `$rank_table` " .
				" LEFT JOIN `$activity_table` ON `rank_act_id`=`act_id` " .
				" LEFT JOIN `$post_table` ON `act_external_id`=`$post_table`.`ID` " .
				' WHERE `post_type` = %s ' .
				' AND `post_status` = %s ' .
				' AND `post_date` BETWEEN %s AND %s ' .
				' AND `ID` != %d ' .
				" ORDER BY `$rank_table`.`$order_by` DESC" .
				' LIMIT 1';

		$sql = $wpdb->prepare($sql, PeepSoActivityStream::CPT_POST, 'publish', $args['date_start'], $args['date_end'], isset($args['not_id']) ? $args['not_id'] : 0);
		$res = $wpdb->get_row($sql);
		return ($res);
	}

}

// EOF