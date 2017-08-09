<?php //$PeepSoActivity = PeepSoActivity::get_instance();?>
<div data-type="stream-more" class="ps-comment-more ps-js-comment-more" data-commentmore="true">
	<a onclick="return activity.show_comments(<?php global $post; echo $post->act_id; ?>, this)" href="#showallcomments">
		<?php $PeepSoActivity->show_more_comments_link() ;?>
	</a>
	<img class="hidden comment-ajax-loader ps-js-loading" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
</div>
