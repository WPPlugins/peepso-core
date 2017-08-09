/*
 * Handles user interactions for the Activity Stream
 * @package PeepSo
 * @author PeepSo
 */
function PsActivity()
{
	this.current_post = null;
	// used for multiple ajax checks
	this.is_ajax_loading = {};
	this.is_editing = {};
}

/**
 * Initializes this instance's container and selector reference to a postbox instance.
 */
PsActivity.prototype.init = function()
{
	var _self = this;
	this.$post_container = jQuery("#ps-activitystream");

	jQuery(".comment-container").each(function(index, e) {
		if (0 === jQuery("div", e).length)
			jQuery(this).hide();
	});

	jQuery(".cstream-respond").each(function(index, e) {
		if (0 === jQuery("div", e).not(".comment-container").length)
			jQuery(this).hide();

		jQuery("textarea[name=comment]", e).ps_autosize();
	});

	jQuery(document).on("click", ".ps-js-content-excerpt-toggle", function(e) {
		var $btn = jQuery(e.currentTarget),
			single = $btn.data("single-act"),
			$excerpt;

		if (!single) {
			e.preventDefault();
			e.stopPropagation();
			$excerpt = $btn.closest(".ps-js-content-excerpt");
			$excerpt.length && $excerpt.hide().siblings(".ps-js-content-full").show();
			return false;
		}
	});

	jQuery(document).on("ps_activitystream_loaded peepso_repost_shown peepso_report_shown ps_activitystream_append", function( e ) {
		_self.toggle_anchor_target();
		_self.setup_comment_textarea();
		_self.update_pinned_color();
		_self.parseXFBML();
	});

	// Use this event to fire js specific to stream items
	jQuery(document).trigger("ps_activitystream_loaded");

	jQuery( document ).on('ps_comment_addon_added ps_comment_addon_removed', function( e, $addon ) {
		var $ct, $textarea;

		$addon = jQuery( $addon );
		$ct = $addon.closest('.ps-js-addons');
		$textarea = $ct.closest('.ps-textarea-wrapper').find('textarea');

		if ( e.type === 'ps_comment_addon_added' ) {
			$addon.siblings().hide(); // hide other addons
			$ct.show();
		} else if ( e.type === 'ps_comment_addon_removed' ) {
			if ( $addon.siblings(':visible').length ) {
				$ct.show();
			} else {
				$ct.hide();
			}
		}

		$textarea.trigger('input');
	});

	var evtResizeName = 'resize.activity-container';

	jQuery(window)
		.off(evtResizeName)
		.on(evtResizeName, _.debounce(function() {
			var screenSize = $PeepSo.screenSize();
			var $container = jQuery('.ps-stream-container');
			var narrowClass = 'ps-stream-container-narrow';
			if ( $container.filter(':visible').width() <= 480 ) {
				$container.addClass( narrowClass );
			} else {
				$container.removeClass( narrowClass );
			}
		}, 500 ))
		.trigger(evtResizeName);
};

/**
 * Defines events for the comment textarea
 */
PsActivity.prototype.setup_comment_textarea = function() {
	var $textarea = jQuery('[data-type="stream-newcomment"] textarea');
	$textarea.off('keydown.peepso').on('keydown.peepso', jQuery.proxy(function( e ) {
		return this.on_comment_keydown( e );
	}, this ));
	$textarea.off('keyup.peepso').on('keyup.peepso', function( e ) {
		e.stopPropagation();
	});
};

PsActivity.prototype.on_comment_keydown = function(e) {
	if (e.shiftKey || e.ctrlKey) {
		return true;
	}

	var keycode = (e.keyCode ? e.keyCode : e.which);

	if (13 === keycode) {
		var mobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
		if ( mobile.test( navigator.userAgent ) ) {
			return true;
		}

		var $textarea = jQuery(e.target);
		var is_empty = jQuery.trim($textarea.val()) === '';
		var obj = ps_observer.apply_filters('comment_can_submit', {
			el: $textarea,
			can_submit: !is_empty
		});

		if (obj.can_submit) {
			this.comment_save($textarea.data('act-id'));
			e.preventDefault();
			return false;
		}
	}

	return true;
};

PsActivity.prototype.toggle_anchor_target = function()
{
	var regExp = new RegExp(location.host);

	var $external_links = jQuery(".ps-stream-content .cstream-attachment a, .ps-stream-content .content a, .ps-share-status-inner a")
		.filter(function() {
			var href = jQuery(this).attr("href");
			return (href.substring(0,4) === "http") ? !regExp.test(href) : false;
		});

	if (0 == peepsodata.open_in_new_tab)
		$external_links.removeAttr("target");
	else
		$external_links.attr("target", "_blank");

	return;
};

/**
 * Performs a like on a post
 * @param {obj} elem The clicked element
 * @param {int} act_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_like = function(elem, act_id)
{
	if ( this.action_like_progress ) return false;
	this.action_like_progress = true;

	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	var that = this;

	var $elem = jQuery(elem);
	var $count = jQuery(".ps-js-act-like--" + act_id);
	var liked = $elem.hasClass("liked");
	var count = +$count.data("count") + ( liked ? -1 : 1 );
	var oldhtml = $count.html();

	$elem.toggleClass("liked");
	if ( count < 1 ) {
		$count.hide();
	} else if ( count == 1 ) {
		$count.html('<a href="javascript:" onclick="return activity.show_likes(' + act_id + ');">1 ' + peepsodata.like_text + '</a>');
		$count.show();
	} else {
		$count.html('<a href="javascript:" onclick="return activity.show_likes(' + act_id + ');">' + count + ' ' + peepsodata.like_text_plural + '</a>');
		$count.show();
	}

	$PeepSo.postJson("activity.like", req, function(json) {
		that.action_like_progress = false;

		if (json.success) {
			$elem.replaceWith(json.data.like_html);
			$count.data("count", json.data.count);
			if (json.data.count > 0) {
				$count.html(json.data.count_html).show();
			} else {
				$count.hide();
			}
		} else {
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
			$elem.toggleClass("liked");
			$count.html( html );
			if ( +$count.data("count") >= 1 ) {
				$count.show();
			} else {
				$count.hide();
			}
		}

	});
	return (false);
};

/**
 * Shares a comment
 * @param {int} act_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_repost = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid, user_id: peepsodata.userid };
	var title = jQuery("#repost-dialog .dialog-title").html();

	content = jQuery("#ajax-loader-gif").html();
	pswindow.show(title, content);

	$PeepSo.postJson("activity.ajax_show_post", req, function(json) {
		if (0 == json.success) {
			pswindow.hide();
			return;
		}

		var content = jQuery("#repost-dialog .dialog-content").html();
		var actions = jQuery("#repost-dialog .dialog-action").html();
		var post = json.data.html.trim();

		post = post.replace("<p>", "<span>").replace("</p>", "<br/></span>").replace("<br/></span>", "</span>");

		content = content.replace("{post-content}", post);
		content = content.replace("{post-id}", act_id + "");
		pswindow.set_content(content).set_actions(actions).refresh();

		jQuery(document).trigger("peepso_repost_shown");

		jQuery("#share-post-box", "#ps-window").autosize();

		jQuery('#ps-window').find('ul.ps-dropdown-menu').css({ left: 0, right: 'auto' });
	});

	return (false);
};

/**
 * Sends/submits reposted Post
 */
PsActivity.prototype.submit_repost = function()
{
	var post_id = jQuery("#postbox-post-id", "#ps-window").val();

	var req = {
		content: jQuery("#share-post-box", "#ps-window").val(),
		id: peepsodata.currentuserid,
		uid: peepsodata.currentuserid,
		repost: post_id,
		acc: jQuery("#cWindowContent input[name='repost_acc']").val(),
		type: "activity"
	};
	// send req through filter
	req = ps_observer.apply_filters("postbox_req", req);

	$PeepSo.postJson("postbox.post", req, function(json) {
		if (json.success) {
			pswindow.hide();

			if ("0" === peepsodata.userid || peepsodata.currentuserid === peepsodata.userid) {
				jQuery(json.data.html).hide().prependTo("#ps-activitystream").fadeIn("slow", function() {
					jQuery(document).trigger("peepso_repost_added");

					// hook up the drop-down menu within the new post
					var post_id = json.data.post_id;
					jQuery("#peepso-wrap .ps-js-activity--" + post_id + " .ps-dropdown-toggle")
						.click(dropdown_toggle_click)
						.on("mouseenter", dropdown_toggle_mouseenter);
					jQuery("#peepso-wrap .ps-js-activity--" + post_id + " .ps-dropdown-menu a")
						.click(dropdown_toggle_a_click);
				});

				// Scroll to top to view new post.
				jQuery("html, body").animate({
					scrollTop: jQuery("#ps-activitystream").offset().top
				}, 1000);
			}
		}
	});

	return (false);
};

/**
 * Reports a post as inappropriate content
 * @param {int} act_id of post content
 */
PsActivity.prototype.action_report = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid, user_id: peepsodata.userid };
	var title = jQuery("#activity-report-title").html();

	content = jQuery("#ajax-loader-gif").html();
	pswindow.show(title, content);

	$PeepSo.postJson("activity.ajax_show_post", req, function(json) {
		var content = jQuery("#activity-report-content").html();
		var post = json.data.html.trim();

		post = post.replace("<p>", "<span>").replace("</p>", "<br/></span>").replace("<br/></span>", "</span>");

		content = content.replace("{post-content}", post);
		content = content.replace("{post-id}", act_id + "");

		actions = jQuery("#activity-report-actions").html();

		pswindow.set_content(content).set_actions(actions).refresh();

		jQuery(document).trigger("peepso_report_shown");
	});
};

/**
 * Submits report information to server
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.submit_report = function()
{
	jQuery("#cWindowContent .report-error-div").hide();

	var act_id = jQuery("#postbox-report-popup #postbox-post-id", "#ps-window").val();
	var reas = jQuery("#rep_reason option:selected", "#ps-window").val();

	if ("0" === reas) {
		// display message with reason not submitting
		var msg = jQuery("#report-error-select-reason").text();
		jQuery("#cWindowContent .report-error-div").text(msg).show();
		return (false);
	}

	var req = ps_observer.apply_filters("activity_report_params", {
		act_id: act_id,
		uid: peepsodata.currentuserid,
		reason: reas
	});

	var report_action = ps_observer.apply_filters("activity_report_action", "activity.report");

	$PeepSo.postJson(report_action, req, function(json) {
		if (json.success) {
			jQuery(window).trigger("report.submitted", json);
			pswindow.set_content(json.notices[0]).fade_out(pswindow.fade_time);
		} else {
			pswindow.hide();
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
		}
	});

	return (false);
};

/**
 * Performs a delete action on a post
 * @param {int} post_id ID of post content
 * @param {object} extra Optional data to be sent back via ajax
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_delete = function(post_id, extra)
{
	var that = this;

	pswindow.confirm_delete(function() {
		var req = jQuery.extend({ postid: post_id, uid: peepsodata.currentuserid }, extra || {});
		$PeepSo.postJson("activity.delete", req, function(json) {
			that.toggle_comment_box(post_id, false);
			if (json.success)
				window.location.reload();
		});
		pswindow.hide();
		return (false);
	});
	return (false);
};

/**
 * Performs a pin/unpin action on a post
 * @param {int} post_id ID of post content
 * @param {int} pin_status 0 - disable pin,  1 - enable pin
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_pin = function(post_id, pin_status)
{
		var req = { postid: post_id, pinstatus: pin_status, uid: peepsodata.currentuserid };
		$PeepSo.postJson("activity.pin", req, function(json) {

			if (json.success) {
				window.location.reload();
			}
		});

	return (false);
};

/**
 * Performs a remove link preview on a post
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_remove_link_preview = function(post_id, act_id)
{
	var $act = jQuery('.ps-js-activity--' + act_id);
	if ( $act.length ) {
		$act.find('.ps-stream-body .ps-stream-attachments').empty();
		$act.find('.ps-stream-actions .actaction-removepreview').remove();
		peepso.postJson('activity.remove_link_preview', { postid: post_id, uid: peepsodata.currentuserid });
	}
	return (false);
};

/**
 * Called when contents of comment box are changed to reset UI elements
 * @param {int} post_id ID of post content
 */
PsActivity.prototype.on_commentbox_change = function(textarea)
{
	var $sel = jQuery(textarea),
		value = $sel.val();

	if (value.length > peepsodata.postsize) {
		value = value.substring(0, peepsodata.postsize);
		$sel.val(value);
	}

	var is_empty = jQuery.trim(value) === '';
	var obj = ps_observer.apply_filters('comment_show_button', {
		el: $sel,
		show: !is_empty
	});

	var $buttons = $sel.parents('.cstream-form-input').next('.cstream-form-submit');

	if (obj.show) {
		$buttons.show();
		$buttons.find('.ps-comment-actions').show();
		$buttons.find('.ps-button-action').removeAttr("disabled");
	} else {
		$buttons.hide();
		$buttons.find('.ps-comment-actions').hide();
		$buttons.find('.ps-button-action').attr("disabled", "disabled");
	}
};

/**
 * Sends a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_save = function(act_id, elem)
{
	if (this.is_ajax_loading['save-comment-' + act_id])
		return;

	var that = this;
	var sel = jQuery("#act-new-comment-" + act_id + " .cstream-form-text");

	if (elem && elem.tagName) {
		sel = jQuery(elem).closest("#act-new-comment-" + act_id).find(".cstream-form-text");
	}

	jQuery("#act-new-comment-" + act_id + " .ps-comment-loading").show();
	jQuery("#act-new-comment-" + act_id + " .ps-comment-actions").hide();

	var textarea = jQuery(sel);
	var comment_content = textarea.val();
	req = ps_observer.apply_filters("comment_req", {
		act_id: act_id,
		uid: peepsodata.currentuserid,
		content: comment_content,
		last: jQuery(".comment-container[data-act-id=" + act_id + "] .cstream-comment:last").data("comment-id")
	}, sel);

	jQuery(document).trigger("ps_comment_beforesave", [ act_id, sel ]);

	this.is_ajax_loading['save-comment-' + act_id] = true;
	textarea.attr('disabled', 'disabled');

	$PeepSo.postJson("activity.makecomment", req, function(json) {
		that.is_ajax_loading['save-comment-' + act_id] = false;
		textarea.removeAttr('disabled');

		if (json.success) {
			//.hide().fadeIn(2000)
			var $html = jQuery(json.data.html);
			jQuery(".comment-container[data-act-id=" + act_id + "]").show();
			$html.appendTo(".comment-container[data-act-id=" + act_id + "]").hide().fadeIn(2000);
			jQuery("#peepso-wrap").trigger("comment.saved", [act_id, sel, req, $html]);
			jQuery(document).trigger("ps_comment_save");
		} else {
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
		}

		jQuery(document).trigger("ps_comment_aftersave", [ act_id, sel ]);

		activity.comment_cancel(act_id);

		activity.current_post = null;

		jQuery("#act-new-comment-" + act_id + " .ps-comment-loading").hide();
		jQuery("#act-new-comment-" + act_id + " .ps-comment-actions").hide();
		jQuery("#act-new-comment-" + act_id + " .ps-button-action").attr("disabled", "disabled");

		if ('undefined' !== typeof(json.data.has_max_comments))
			that.toggle_comment_box(act_id, json.data.has_max_comments);
	});
	return (false);
};

/**
 * Edits a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 */
PsActivity.prototype.comment_action_edit = function(post_id, elem)
{
	// Prevent further actions when an edit div is already present.
	if (jQuery("#comment-item-" + post_id + " .cstream-content .cstream-edit").length > 0)
		return;

	var that = this;
	var $comment_container = jQuery(elem).closest("#comment-item-" + post_id);

	if (undefined === this.is_ajax_loading['comment-edit-' + post_id] ||
		false === this.is_ajax_loading['comment-edit-' + post_id]) {
		this.is_ajax_loading['comment-edit-' + post_id] = true;

		var req = { postid: post_id, uid: peepsodata.currentuserid };
		$PeepSo.postJson("activity.editcomment", req, function(json) {
			if (json.success) {
				var html = jQuery(json.data.html);
				// hide current container of post information
				jQuery("[data-type='stream-comment-content']", $comment_container).first().hide()
					.after(html);				// add new <div> with edit form

				jQuery(".ps-comment-media", $comment_container).hide();

				// @deprecated
				// will not work on pages where there is no #peepso-wrap element
				// hook into below ps_observer `comment_edit` action instead
				jQuery("#peepso-wrap").trigger("comment_edit.shown", [post_id, html]);

				ps_observer.do_action("comment_edit", post_id, html);

				jQuery(".cstream-edit textarea", $comment_container)
					.on('input propertychange', function() {
						if (jQuery(this).val().length > peepsodata.postsize)
							jQuery(this).val(jQuery(this).val().substring(0, peepsodata.postsize));
					})
					.autosize();

				that.is_ajax_loading['comment-edit-' + post_id] = false;
			}
		});
	}

	return (false);
};

/**
 * Cancels button responder for editing a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 */
PsActivity.prototype.option_canceleditcomment = function(post_id, elem)
{
	var $ai = jQuery(elem).closest("#comment-item-" + post_id);
	if ($ai.length > 0) {
		jQuery(".cstream-edit", $ai).remove();					// remove the post edit form elements
		jQuery("[data-type='stream-comment-content']", $ai).show();				// show the original post content
		jQuery(".ps-comment-media", $ai).show();
	}

	return (false);
};

/**
 * Saves button responder for editing a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 */
PsActivity.prototype.option_savecomment = function(post_id, elem)
{
	var $ai = jQuery(elem).closest("#comment-item-" + post_id);
	if ($ai.length > 0) {
		var contents = jQuery(".cstream-edit textarea", $ai).val();
		jQuery(".cstream-edit textarea", $ai).attr("disabled", "disabled");
		jQuery(".ps-edit-loading", $ai).show();
		jQuery(".cstream-edit button", $ai).hide();

		var sel = jQuery(".cstream-edit textarea", $ai);
		var req = ps_observer.apply_filters("comment_req", {
			postid: post_id,
			uid: peepsodata.currentuserid,
			post: contents
		}, sel );

		jQuery(document).trigger("ps_comment_beforesave", [ post_id, sel ]);

		$PeepSo.postJson("activity.savecomment", req, function(json) {
			if (json.success) {
				jQuery(".cstream-edit", $ai).remove();				// remove the post edit form elements
				jQuery("[data-comment-id=" + post_id + "] [data-type='stream-comment-content']").html(json.data.html);
				jQuery("[data-type='stream-comment-content']", $ai).show();	// reset contents of the activity stream item
				jQuery("[data-comment-id=" + post_id + "] .cstream-content > .cstream-attachments").html(json.data.attachments);
				jQuery(".cstream-content > .cstream-attachments", $ai).show();	// reset contents of the activity stream item
				jQuery("span.ps-stream-status-action", $ai).html(json.data.actions); // update comment actions
				jQuery(document).trigger("ps_comment_save");
			} else {
				psmessage.show('', json.notices[0]);
				jQuery(".cstream-edit button", $ai).show();
				jQuery(".ps-edit-loading", $ai).hide();
				jQuery(".cstream-edit textarea", $ai).removeAttr("disabled");
			}
		});
	}

	return (false);
};

/**
 * Deletes a comment
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_action_delete = function(post_id)
{
	var that = this;
	pswindow.confirm_delete(function() {
		var req = { postid: post_id, uid: peepsodata.currentuserid };
		$PeepSo.postJson("activity.delete", req, function(json) {
			that.toggle_comment_box(post_id, false);
			if (json.success)
				jQuery(".ps-comment-item[data-comment-id=" + post_id + "]").remove();
			pswindow.hide();
		});
		return (false);
	});
};

/**
 * Performs a remove link preview on a comment
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_action_remove_preview = function(post_id)
{
	var $act = jQuery('#comment-item-' + post_id);
	if ( $act.length ) {
		$act.find('.ps-comment-media').empty();
		$act.find('.actaction-removepreview').remove();
		peepso.postJson('activity.remove_link_preview', { postid: post_id, uid: peepsodata.currentuserid });
	}
	return (false);
};

/**
 * Reports comment as inappropriate
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.comment_action_report = function(act_id)
{
	var req = { act_id: act_id };
	$PeepSo.postJson("activity.ajax_show_comment", req, function(json) {
		var title = jQuery("#activity-report-title").html();
		var content = jQuery("#activity-report-content").html();

		var post = json.data.html.trim();
		post = post.replace("<p>", "<span>").replace("</p>", "<br/></span>").replace("<br/></span>", "</span>");

		content = content.replace("{post-content}", post);
		content = content.replace("{post-id}", act_id + "");

		ps_observer.add_filter("activitystream_notice_container", function(container, act_id) {
			return "#comment-item-" + act_id + " .cstream-more";
		}, 10, 2);

		actions = jQuery("#activity-report-actions").html();
		pswindow.show(title, content).set_actions(actions).refresh();

		jQuery("#ps-window").one("pswindow.hidden", function() {
			ps_observer.remove_filter("activitystream_notice_container", function(container, act_id) {
				return ("#comment-item-" + act_id + " .cstream-more");
			}, 10);
		});
	});

	return (false);
};

/**
 * Reports comment as inappropriate
 * @param {int} act_id ID of post content
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 */
PsActivity.prototype.comment_action_reply = function(act_id, post_id, elem, data)
{
	peepso.comment.reply(act_id, post_id, elem, data);
	return (false);
};

/**
 * Likes a comment
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_action_like = function(elem, act_id)
{
	if ( this.comment_action_like_progress ) return;
	this.comment_action_like_progress = true;

	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	var that = this;

	var $elem = jQuery(elem);
	var $count = jQuery(".ps-js-act-like--" + act_id);
	var liked = $elem.hasClass("liked");
	var count = +$count.data("count") + ( liked ? -1 : 1 );
	var oldhtml = $count.html();

	$elem.toggleClass("liked");
	if ( count < 1 ) {
		$count.hide();
	} else if ( count == 1 ) {
		$count.html('<a href="javascript:" onclick="return activity.show_likes(' + act_id + ');">1 ' + peepsodata.like_text + '</a>');
		$count.show();
	} else {
		$count.html('<a href="javascript:" onclick="return activity.show_likes(' + act_id + ');">' + count + ' ' + peepsodata.like_text_plural + '</a>');
		$count.show();
	}

	$PeepSo.postJson("activity.like", req, function(json) {
		that.comment_action_like_progress = false;

		if (json.success) {
			$elem.replaceWith(json.data.like_html);
			$count.data("count", json.data.count);
			if (json.data.count > 0) {
				$count.html(json.data.count_html).show();
			} else {
				$count.hide();
			}
		} else {
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
			$elem.toggleClass("liked");
			$count.html( html );
			if ( +$count.data("count") >= 1 ) {
				$count.show();
			} else {
				$count.hide();
			}
		}
	});
	return (false);
};

/**
 * Cancels a comment; clears the comment form
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_cancel = function(post_id)
{
	var ct = jQuery("#act-new-comment-" + post_id);
	var sel = ct.find(".cstream-form-text");

	ct.find(".cstream-form-submit").hide();
	ct.find(".ps-js-commentbox-addons").hide();
	sel.val("").trigger("autosize.resize");

	ps_observer.apply_filters("comment_cancel", sel);

	return (false);
};

/**
 * Displays details on post/comment likes
 * @param {int} act_id of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.show_likes = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	var _self = this;
	var likes_id = 'likes-' + act_id;

	if ( this.is_ajax_loading[likes_id] ) {
		this.is_ajax_loading[likes_id].ret && this.is_ajax_loading[likes_id].ret.abort();
		this.is_ajax_loading[likes_id] = false;
	}

	this.is_ajax_loading[likes_id] = $PeepSo.postJson("activity.get_like_names", req, function(json) {
		jQuery("#act-like-" + act_id + " a").replaceWith(json.data.html);
		_self.is_ajax_loading[likes_id] = false;
	});

	return (false);
};

/**
 * Shows all comments for given post
 * @param {int} act_id of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.show_comments = function(act_id, elem)
{
	peepso.comment.show_previous( act_id, elem );
	return (false);
};

/**
 * Edits an existing post in the activity stream
 * @param {number} post_id
 * @param {number} act_id
 */
PsActivity.prototype.option_edit = function(post_id, act_id)
{
	var flag = 'edit-post-' + act_id;

	if ( this.is_editing[ flag ] ) {
		return;
	}

	this.is_editing[ flag ] = true;

	var $ct = jQuery('.ps-js-activity--' + act_id ),
		$content = $ct.find('.ps-js-activity-content'),
		$editor = $ct.find('.ps-js-activity-edit'),
		$actions = $ct.find('.ps-stream-actions'),
		$attachments = $ct.find('.ps-stream-body .cstream-attachments'),
		$extras = $ct.find('.ps-js-activity-extras');

	$editor.ps_postbox({
		fetch: function( postbox, callback ) {
			peepso.postJson('activity.editpost', {
				uid: peepsodata.currentuserid,
				postid: post_id
			}, function( json ) {
				if ( json.success ) {
					postbox.update( json.data );
				}
				callback();
			});
		},
		cancel: jQuery.proxy(function( postbox ) {
			this.is_editing[ flag ] = false;
			$editor.ps_postbox('destroy');
			$content.show();
		}, this ),
		submit: jQuery.proxy(function( postbox, data, callback ) {
			data = jQuery.extend({}, data, {
				uid: peepsodata.currentuserid,
				act_id: act_id
			});
			data.post = data.content;
			delete data.content;
			peepso.postJson('activity.savepost', data, jQuery.proxy(function( json ) {
				if ( json.success ) {
					this.is_editing[ flag ] = false;
					$editor.ps_postbox('destroy');
					$content.html( json.data.html ).show();
					$attachments.html( json.data.attachments );
					$actions.html( json.data.actions );
					$extras.html( json.data.extras || '' );
				} else {
					psmessage.show('', json.errors[0]);
				}
				jQuery( document ).trigger('peepso_post_edit_saved');
				callback();
			}, this ));
		}, this )
	});

	$content.hide();
	$editor.show();

	return false;

	$PeepSo.postJson('activity.editpost', {
		postid: post_id,
		uid: peepsodata.currentuserid
	}, jQuery.proxy(function(json) {
		this.is_ajax_loading[ flag ] = false;

		if (json.success) {
			this.is_editing[ flag ] = true;

			// hide current container of post information
			var html = jQuery(json.data.html);
			jQuery(".ps-js-activity--" + json.data.act_id + " .cstream-attachment").first().hide()
				.after(html);				// add new <div> with edit form
			jQuery("#peepso-wrap").trigger("post_edit.shown", [json.data.act_id, html]);

			var $act = jQuery(".ps-js-activity--" + json.data.act_id);
			var $textarea = $act.find('.cstream-edit textarea');
			var $charcount = $act.find('.ps-postbox-charcount');

			$textarea
				.on('input propertychange', function() {
					if (jQuery(this).val().length > peepsodata.postsize)
						jQuery(this).val(jQuery(this).val().substring(0, peepsodata.postsize));
					$charcount.html( peepsodata.postsize - this.value.length );
				})
				.triggerHandler('input')
				.autosize();
		}
	}, this) );

	return false;
};

/**
 * Cancels button responder for editing a post
 * @param {int} post_id ID of post content
 */
PsActivity.prototype.option_canceledit = function(post_id)
{
	this.is_editing[ 'edit-post-' + post_id ] = false;

	var $ai = jQuery(".ps-js-activity--" + post_id);
	if ($ai.length > 0) {
		jQuery(".cstream-edit", $ai).remove();					// remove the post edit form elements
		jQuery(".cstream-attachment", $ai).show();				// show the original post content
	}

	return (false);
};

/**
 * Cancels button responder for editing an activity
 * @param {int} act_id The activity ID
 */
PsActivity.prototype.option_cancel_edit_description = function(act_id)
{
	var $ai = jQuery(".ps-js-modal-attachment--" + act_id);
	if ($ai.length > 0) {
		jQuery(".cstream-edit", $ai).remove();					// remove the post edit form elements
		jQuery(".cstream-attachment", $ai).show();				// show the original post content
	}

	return (false);
};

/**
 * Saves button responder for editing a post
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.option_savepost = function(act_id)
{
	var $ai = jQuery(".ps-js-activity--" + act_id);
	if ($ai.length > 0) {
		var contents = jQuery(".cstream-edit textarea", $ai).val();
		jQuery(".cstream-edit textarea", $ai).attr("disabled", "disabled");
		jQuery(".ps-edit-loading", $ai).show();
		jQuery(".cstream-edit button", $ai).hide();

		var req = ps_observer.apply_filters("postbox_req_edit", { act_id: act_id, uid: peepsodata.currentuserid, post: contents }, jQuery(".cstream-edit textarea", $ai));

		$PeepSo.postJson("activity.savepost", req, jQuery.proxy(function(json) {
			jQuery(".cstream-edit", $ai).remove();				// remove the post edit form elements

			if (json.success) {
				this.is_editing[ 'edit-post-' + act_id ] = false;
				jQuery(".ps-stream-body .cstream-attachment", $ai).html(json.data.html);	// reset contents of the activity stream item
				jQuery(".ps-stream-body .cstream-attachments", $ai).html(json.data.attachments);	// reset contents of the activity stream item
				jQuery(".ps-stream-actions", $ai).html(json.data.actions);
			} else
				psmessage.show("", json.errors[0]);

			jQuery(".ps-stream-body .cstream-attachment, .ps-stream-body .cstream-attachments", $ai).show();

			jQuery(document).trigger("peepso_post_edit_saved");


		}, this ));
	}

	return (false);
};

/**
 * Saves button responder for editing a description
 * @param {int} act_id The activity ID
 * @param  {string} type The activity type.
 * @param  {int} object_id
 */
PsActivity.prototype.option_save_description = function(act_id, type, object_id)
{
	var $ai = jQuery(".ps-js-modal-attachment--" + act_id);

	if ($ai.length > 0) {
		var $textarea = jQuery(".cstream-edit textarea", $ai);
		var contents = $textarea.val();

		jQuery(".cstream-edit textarea", $ai).attr("disabled", "disabled");
		jQuery(".ps-edit-loading", $ai).show();
		jQuery(".cstream-edit button", $ai).hide();

		var req = { act_id: act_id, type: type, object_id: object_id, uid: peepsodata.currentuserid, description: contents };

		req = ps_observer.apply_filters("caption_req", req, $textarea);

		$PeepSo.postJson("activity.save_description", req, function(json) {
			jQuery(".cstream-edit", $ai).remove();				// remove the post edit form elements
			jQuery(".ps-stream-attachment", $ai).html(json.data.html).show();	// reset contents of the activity stream item
		});
	}

	return (false);
};

/**
 * Hides a post from user's view
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.option_hide = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	$PeepSo.postJson("activity.hidepost", req, function(json) {
		if (json.success)
			jQuery(".ps-js-activity--" + act_id).remove();
	});

	return (false);
};

/**
 * Adds user to block list
 * @param {int} post_id ID of post content
 * @param {int} user_id ID of user
 */
PsActivity.prototype.option_block = function(post_id, user_id)
{
	var req = { uid: peepsodata.currentuserid, user_id: user_id };
	$PeepSo.postJson("activity.blockuser", req, function(json) {
		if (json.success)
			jQuery(".ps-js-activity--" + post_id).remove();
	});

	return (false);
};

/**
 * Changes the privacy setting on a post
 * @param {jQuery|HTMLElement} $a An <a> tag object that was clicked
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.change_post_privacy = function( $a, act_id ) {
	var $container, $btn, $placeholder, oldClass, newClass, req;

	$a = jQuery( $a );
	$container = jQuery('.ps-js-privacy--' + act_id );
	$btn = $container.find('.ps-dropdown-toggle');
	$placeholder = $btn.find('a');
	oldClass = $btn.find('i').attr('class');
	newClass = $a.find('i').attr('class');

	$btn.find('i').attr('class', newClass );
	$btn.css('opacity', .5 );

	req = {
		uid: peepsodata.currentuserid,
		user_id: peepsodata.userid,
		act_id: act_id,
		acc: $a.data('option-value'),
		_wpnonce: peepsodata.peepso_nonce
	};

	var fn = this.change_post_privacy;
	fn._xhr || (fn._xhr = {});
	fn._xhr[ act_id ] && fn._xhr[ act_id ].ret.abort();
	fn._xhr[ act_id ] = peepso.postJson('activity.change_post_privacy', req, function( response ) {
		$btn.css('opacity', '');
		if ( response.has_errors ) {
			$btn.find('i').attr('class', oldClass ); // reset the icon
			psmessage.show('', response.errors[0]).fade_out( psmessage.fade_time );
		}
	});

	return false;
};

/**
 * Shows/hides comment box
 * @param {int} post_id Post Id
 * @param {boolean} has_max_comments Either max comments reached or not
 */
PsActivity.prototype.toggle_comment_box = function(post_id, has_max_comments)
{
	var new_comment = jQuery('#act-new-comment-' + post_id);
	if (new_comment.length <= 0) {
		var item = jQuery("#comment-item-" + post_id);
		if (item.length <= 0)
			item = jQuery(".ps-js-activity--" + post_id);
		if (item.length > 0) {
			new_comment = item.parent();
			var id = (item.parent().attr('id') + '').split('-').pop();
			new_comment = jQuery('#act-new-comment-' + id);
		}
	}
	if (new_comment.length <= 0)
		return (false);

	if (has_max_comments)
		new_comment.hide();
	else
		new_comment.show();

	return (false);
};

/**
 * Deletes an activity via ajax
 * @param  {int} act_id The activity ID to delete
 * @param {object} extra Optional data to be sent back via ajax
 */
PsActivity.prototype.delete_activity = function(act_id, extra)
{
	var req = jQuery.extend({
		act_id: act_id,
		uid: peepsodata.currentuserid,
		_wpnonce: jQuery("#_delete_nonce").val()
	}, extra || {});

	var $act_delete_div_msg = jQuery("[data-act-delete-id=" + act_id + "]");
	var confirm_delete_message = "";
	if ($act_delete_div_msg.length > 0)
		confirm_delete_message = $act_delete_div_msg.text();

	pswindow.confirm_delete(
		function() {
			$PeepSo.postJson("activity.ajax_delete_activity", req, function(json) {
				if (json.success) {
					window.location.reload();
				} else {
					psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
				}
			});
		},
		confirm_delete_message
	);

	return (false);
};

/**
 * Fetches the act_description from the server and shows the edit UI.
 * @param  {int} act_id The activity to add a description to.
 * @param  {string} type The activity type.
 * @param  {int} object_id
 */
PsActivity.prototype.edit_activity_description = function(act_id, type, object_id) {
	var $ai = jQuery(".ps-js-modal-attachment--" + act_id);

	if ($ai.find(".cstream-edit textarea").length > 0) return;

	var req = { act_id: act_id, type: type, object_id: object_id, uid: peepsodata.currentuserid };

	this.edit_activity_description_xhr && this.edit_activity_description_xhr.ret && this.edit_activity_description_xhr.ret.abort();
	this.edit_activity_description_xhr = $PeepSo.postJson("activity.edit_description", req, function(json) {
		if (json.success) {
			// hide current container of post information
			var html = jQuery(json.data.html);
			$ai.find(".ps-stream-attachment").first().hide()
				.after(html);			// add new <div> with edit form
			jQuery("#peepso-wrap").trigger("post_edit.shown", [json.data.act_id, html]);
			$ai.find(".cstream-edit textarea")
				.on('input propertychange', function() {
					if (jQuery(this).val().length > peepsodata.postsize)
						jQuery(this).val(jQuery(this).val().substring(0, peepsodata.postsize));
				})
				.autosize()
				.focus();
		}
	});

	return (false);
};

/**
 *
 */
PsActivity.prototype.remove_broken_thumbnails = function() {
	jQuery('.ps-media-thumbnail img').each(function() {
		var tester = new Image();
		var img = this;
		tester.onerror = function() {  jQuery(img).closest('.ps-media-thumbnail').remove(); };
		tester.src = img.src;
	});
};

PsActivity.prototype.update_pinned_color = function() {
	var $pinned = jQuery('.ps-stream__post-pin span'),
		$link;

	if ($pinned.length) {
		$link = $pinned.eq(0).closest('.ps-stream').find('a').eq(0);
		$pinned.css('backgroundColor', $link.css('color'));
	}
};

(function( $, factory ) {

	$.extend( PsActivity.prototype, factory( $ ));

	// initialize PsActivity
	activity = new PsActivity();

	// initialize on page load
	$(function() {
		// TODO remove legacy code
		activity.remove_broken_thumbnails();
		activity.init();

		activity.__constructor();
	});

})( jQuery, function( $ ) {

	return {

		__constructor: function() {
			this.isPermalink = peepsodata.activity && +peepsodata.activity.is_permalink;
			this.enableLoadMoreButton = +peepsodata.loadmore_enable;

			$( $.proxy( this.onDocumentLoaded, this ) );
		},

		onDocumentLoaded: function() {
			this.$el = $('#ps-activitystream');

			// trigger autoscroll
			if ( this.$el.length && ! this.isPermalink ) {
				this.onWindowScroll();
			}
		},

		/**
		 * Parse XFBML tags found in the document.
		 */
		parseXFBML: _.throttle(function() {
			peepso.util.fbParseXFBML();
		}, 2000 ),

		/**
		 * Load more items in the current activity stream.
		 * @return jQuery.Deferred
		 */
		load_more: function() {
			return $.Deferred( $.proxy(function( defer ) {
				var uid = +peepsodata.currentuserid;
				var $container = $('#ps-activitystream');
				var $loading = $('.post-ajax-loader');
				var stream_id = $('#peepso_stream_id').val();
				var page = +( this._load_more_page || peepsodata.activity_limit_page_load ) + 1;

				var req = ps_observer.apply_filters('show_more_posts', {
					uid: uid,
					user_id: peepsodata.userid,
					stream_id: stream_id,
					page: page
				});

				$loading.removeClass('hidden');
				this._load_more_loading = true;
				peepso.disableAuth().disableError().postJson('activity.show_posts_per_page', req, $.proxy(function( json ) {
					var $new_posts;

					if ( json.data.found_posts > 0 ) {
						$new_posts = $( json.data.posts );
						$new_posts.appendTo( $container ).hide().fadeIn( 1000, function() {
							$( document ).trigger('ps_activitystream_loaded');
						});
						$('textarea[name=comment]', $new_posts ).autosize();
						$('#div-show-more-posts').remove();

						// disregard max_num_pages as it has invalid value on repost activity
						// if ( json.data.max_num_pages > page ) {
							this._load_more_page = page;
						// } else {
						// 	this._load_more_end = true;
						// }
					} else {
						this._load_more_end = true;
					}

					this._load_more_loading = false;
					$loading.addClass('hidden');

					if ( this._load_more_end ) {
						$('#ps-no-more-posts').show();
					}

					if ( ! $new_posts ) {
						$( document ).trigger('ps_activitystream_loaded');
					}
				}, this )).ret.done( $.proxy(function() {
					defer.resolveWith( this, arguments );
				}, this )).fail( $.proxy(function() {
					defer.rejectWith( this, arguments );
				}, this ));

				$(document).on('peepso_login_shown', function() {
					$('.post-ajax-loader').toggleClass('hidden');
				});

			}, this ));
		},

		onWindowScroll: _.throttle(function() {
			var $ct = $('#ps-activitystream'),
				$lastActs, limit, position;

			if ( $ct.length ) {
				limit = +peepsodata.activity_limit_below_fold;
				limit = limit > 0 ? limit : 3;

				// Get oldest visible N activities as reference whether we should load more older activities or not,
				// where N is the minimum limit of invisible activities below viewport.
				$lastActs = $ct.children('.ps-js-activity').slice( 0 - limit );
				$lastActs = $lastActs.eq( 0 );
				if ( $lastActs.length ) {

					// Calculate element from viewport, or from top of the page if "load more" button is enabled.
					if ( this.enableLoadMoreButton ) {
						position = $lastActs.eq( 0 ).offset();
					} else {
						position = $lastActs.get( 0 ).getBoundingClientRect();
					}

					// Load more older activities if element is still visible.
					if ( position.top < ( window.innerHeight || document.documentElement.clientHeight )) {
						if ( ! this._load_more_loading && ! this._load_more_end ) {
							this.load_more().done(function() {
								this.onWindowScroll();
							});
						}

					// Show "load more" button if enabled.
					} else if ( this.enableLoadMoreButton ) {
						this.$btnKeepLoading = $( peepsodata.activity.template_load_more ).insertAfter( $ct );
						this.$btnKeepLoading.one('click', $.proxy(function() {
							this.$btnKeepLoading.remove();
							this.enableLoadMoreButton = false;
							this.onWindowScroll();
						}, this ));

					// Add event listener if it is not already added.
					} else if ( ! this._onWindowScrollEnabled ) {
						this._onWindowScrollEnabled = true;
						$( window ).on('scroll', $.proxy( this.onWindowScroll, this ))
							.trigger('scroll');
					}
				}
			}

		}, 1000 )

	};

});
