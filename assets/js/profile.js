/*
 * Handlers for user profile page
 * @package PeepSo
 * @author PeepSo
 */

//$PeepSo.log("profile.js");

// declare class
function PsProfile()
{
	this.cover = {};
	this.cover.x_position_percent = 0;
	this.cover.y_position_percent = 0;

	this.$cover_ct = jQuery(".js-focus-cover");
	this.$cover_image = jQuery("img#" + peepsodata.userid);
	this.initial_cover_position = this.$cover_image.attr('style');

	this.avatar_use_gravatar = false;
}

var profile = new PsProfile();

/**
 * Initializes this instance's container and selector reference to a postbox instance.
 */
PsProfile.prototype.init = function()
{
	// initialize the "About Me" collapsible area
	var coll = jQuery(".js-collapse-about-btn");
	if (0 !== coll.length) {
		coll.on("click", function(e) {
			e.preventDefault();
			var about = jQuery(".js-collapse-about");
			var disp = about.css("display");
			if ("none" === disp) {
				about.show();
			} else {
				about.hide();
			}
		});
	}

	jQuery(".js-focus-cover").hover(
		function() {
			if (false === pswindow.is_visible)
				jQuery(".js-focus-change-cover").show();
		},
		function() {
			jQuery(".js-focus-change-cover").hide();
		}
	);

	// removed the jquery event handlers in favor of onclick= attributes
//	jQuery(".ps-tab__bar a").click(function(e) {
//		e.preventDefault();
//		jQuery(this).tab("show");
//	});
	// remove Divi event handlers from the activity/about me tabs
	jQuery(".ps-tab__bar").unbind("click");

	// fix horizontal padding
	var that = this;
	this.$cover_image.one('load', function() {
		that.fix_horizontal_padding();
	}).each(function() {
		if ( this.complete ) {
			jQuery( this ).load();
		}
	});
	jQuery( window ).on('resize.focus-image', jQuery.proxy(this.fix_horizontal_padding_debounced, this));

	var $pref = jQuery('.ps-page--preferences');
	if ( $pref.length ) {
		$pref.find('input[type=checkbox]').on('click.savepref', profile.save_preference );
		$pref.find('select').on('change', profile.save_preference );
		$pref.find('.ps-dropdown input[type=hidden]').on('change', profile.save_preference );
		$pref.find('.ps-list-alerts-check').find('input[type=checkbox]').off('click.savepref').on('click.savepref', profile.save_notification );
	}
};

/**
 * event callback for switching tabs between Activity Stream and About Me
 * @param Event e Current event
 * @param string name Name of tab to activate
 * @param string hide Name of tab to hide
 * @returns Boolean To prevent continuing execution
 */
PsProfile.prototype.activate_tab = function(e, name, hide)
{
	e.preventDefault();

	jQuery(e.target).addClass('active')
		.siblings('[data-toggle=tab]').removeClass('active');

	jQuery(hide).hide();
	jQuery(name).show();
	return (false);
};

/**
 * Likes a profile
 * @return {boolean} Always returns FALSE
 */
PsProfile.prototype.new_like = function() {
	peepso.postJson( 'profile.like', { user_id: peepsodata.userid }, function( json ) {
		if ( json.success ) {
			jQuery( '.profile-social.profile-interactions' ).html( json.data.html );
		} else {
			psmessage.show( '', json.errors[0] ).fade_out( psmessage.fade_time );
		}
	});

	return false;
};

/**
 * Performs unblock user operation
 */
PsProfile.prototype.unblock_user = function(user_id, elem)
{
	if (this.unblocking_user) {
		return;
	}

	if (elem) {
		elem = jQuery(elem);
		elem.find('img').css('display', 'inline');
	}

	var req = { uid: peepsodata.currentuserid, user_id: user_id };

	this.unblocking_user = true;
	$PeepSo.postJson("activity.unblockuser", req, jQuery.proxy(function(json) {
		this.unblocking_user = false;
		if (json.success) {
			jQuery('.ps-js-focus--' + user_id).find('.ps-focus-actions, .ps-focus-actions-mobile').html(json.data.actions);
			psmessage.show(json.data.header, json.data.message, psmessage.fade_time);
		}
	}, this ));
};

/**
 * Reports profile as inappropriate
 */
PsProfile.prototype.report_user = function() {
	var filterAction, filterParams,
		title = jQuery("#activity-report-title").html();
		content = jQuery("#activity-report-content").html();

	content = content.replace("{post-content}", "Profile");
	content = content.replace("{post-id}", peepsodata.userid + "");

	ps_observer.add_filter( 'activity_report_action', filterAction = function( action ) {
		return 'profile.report';
	}, 10, 1 );

	ps_observer.add_filter( 'activity_report_params', filterParams = function( params ) {
		var newParams = jQuery.extend( {}, params );
		newParams.user_id = newParams.act_id;
		newParams.act_id = undefined;
		newParams.uid = undefined;
		return newParams;
	}, 10, 1 );

	actions = jQuery('#activity-report-actions').html();
	pswindow.show(title, content).set_actions(actions).refresh();

	jQuery('#ps-window').one('pswindow.hidden', function() {
		ps_observer.remove_filter('activity_report_action', filterAction, 10 );
		ps_observer.remove_filter('activity_report_params', filterParams, 10 );
	});

	jQuery(window).one('report.submitted', function(e, response) {
		pswindow.show('Profile Reported', response.notices[0]);
	});
};

/**
 * Confirms remove cover photo request
 */
PsProfile.prototype.confirm_remove_cover_photo = function()
{
	var title = jQuery("#delete-confirmation #delete-title").html();
	var content = jQuery("#delete-confirmation #delete-content").html();

	pswindow.show(title, content);
};

/**
 * Performs remove cover photo operation
 */
PsProfile.prototype.remove_cover_photo = function(user_id)
{
	var req = { uid: peepsodata.currentuserid, user_id: user_id, _wpnonce: jQuery("#_covernonce").val() };
	$PeepSo.postJson("profile.remove_cover_photo", req, function(json) {
		if (json.success) {
			window.location.reload();
		}
	});
};

/**
 * Applies jquery draggable and saves the dragged position to this.cover
 */
PsProfile.prototype.reposition_cover = function()
{
	jQuery(".js-focus-gradient", ".js-focus-cover").hide();
	jQuery(".js-focus-change-cover > a", ".js-focus-cover").hide();
	jQuery(".reposition-cover-actions", ".js-focus-cover").show();
	jQuery(".js-focus-cover").addClass("ps-focus-cover-edit");

	var that = this;
	var g = jQuery(".js-focus-cover").height() - jQuery("img#" + peepsodata.userid).height();
	var w = jQuery(".js-focus-cover").width() - jQuery("img#" + peepsodata.userid).width();

	jQuery("img#" + peepsodata.userid).draggable({
		cursor: "move",
		drag: function (a, b) {
			b.position.top < g && (b.position.top = g), b.position.top > 0 && (b.position.top = 0),
			b.position.left < w && (b.position.left = w), b.position.left > 0 && (b.position.left = 0);
		},
		stop: function (a, c) {
			var d = jQuery("img#" + peepsodata.userid),
				e = d.closest(".js-focus-cover"),
				x = 100 * c.position.top / e.height();
			x = Math.round(1e4 * x) / 1e4;
			y = (100 * c.position.left / e.width());
			y = Math.round(1e4 * y) / 1e4;

			that.cover.x_position_percent = x;
			that.cover.y_position_percent = y;
			d.css("top", x + "%");
			d.css("left", y + "%");
		}
	});
};

/**
 * Performs when reposition cover is cancelled
 */
PsProfile.prototype.cancel_reposition_cover = function()
{
	jQuery(".js-focus-gradient", ".js-focus-cover").show();
	jQuery(".js-focus-change-cover > a", ".js-focus-cover").show();
	jQuery(".reposition-cover-actions", ".js-focus-cover").hide();
	jQuery(".js-focus-cover").removeClass("ps-focus-cover-edit");

	jQuery("img#" + peepsodata.userid).attr('style', this.initial_cover_position);
	jQuery("img#" + peepsodata.userid).draggable('destroy');
};

/**
 * Saves the cover images position after repositioning
 */
PsProfile.prototype.save_reposition_cover = function()
{
	var req = {
		user_id: peepsodata.userid,
		x: this.cover.x_position_percent,
		y: this.cover.y_position_percent,
		_wpnonce: jQuery("#_photononce").val()
	};

	var that = this;

	jQuery(".reposition-cover-actions", ".js-focus-cover").hide();
	jQuery(".ps-reposition-loading", ".js-focus-cover").show();
	jQuery(".js-focus-cover").removeClass("ps-focus-cover-edit");

	$PeepSo.postJson("profile.reposition_cover", req, function(json) {
		jQuery(".ps-reposition-loading", ".js-focus-cover").hide();
		that.initial_cover_position = jQuery("img#" + peepsodata.userid).attr('style');
		that.cancel_reposition_cover();
	});
};

/**
 * Confirms remove avatar photo request
 */
PsProfile.prototype.confirm_remove_avatar = function()
{
	var title = jQuery("#delete-confirmation #delete-title").html();
	var content = jQuery("#delete-confirmation #delete-content").html();

	pswindow.show(title, content);
};

/**
 * Performs remove avatar photo operation
 */
PsProfile.prototype.remove_avatar = function(user_id)
{
	var req = { uid: peepsodata.currentuserid, user_id: user_id, _wpnonce: jQuery("#_photononce").val() };
	$PeepSo.postJson("profile.remove_avatar", req, function(json) {
		if (json.success) {
			window.location.reload();
		}
	});
};

/**
 * Deletes profile operation
 */
PsProfile.prototype.delete_profile = function()
{
	var title = jQuery("#profile-delete-title").html();
	var content = jQuery("#profile-delete-content").html();

	pswindow.show(title, content);
};

/**
 * Performs the delete operation
 */
PsProfile.prototype.delete_profile_action = function()
{
	$req = { };
	var req = { uid: peepsodata.currentuserid };
	$PeepSo.postJson("profile.delete_profile", req, function(json) {
		if (json.success)
			window.location = json.data.url;
		else
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
	});
};

/**
 * Shows avatar dialog to upload/change avatar
 */
PsProfile.prototype.show_avatar_dialog = function()
{
	jQuery(".ps-js-error").html("");	// clear any remaining error messages
	var $dialog = jQuery("#dialog-upload-avatar");
	var title = $dialog.find("#dialog-upload-avatar-title").html();
	var content = $dialog.find("#dialog-upload-avatar-content").html();
	var actions = $dialog.find(".dialog-action").html();

	var inst = pswindow.show(title, content).set_actions(actions);
	var elem = inst.$container.find('.ps-dialog');

	elem.addClass('ps-dialog-wide');
	ps_observer.add_filter('pswindow_close', function() {
		elem.removeClass('ps-dialog-wide');
	}, 10, 1);

	this.init_avatar_fileupload();

	jQuery("#ps-window").on('pswindow.hidden', function() {
		jQuery('.upload-avatar .fileupload:visible').fileupload('destroy');
	});
};

/**
 * Initializes avatar file upload
 */
PsProfile.prototype.init_avatar_fileupload = function()
{
	var that = this;

	jQuery(".upload-avatar .fileupload:visible").fileupload({
		formData: {user_id: peepsodata.userid, _wpnonce: jQuery("#_photononce").val()},
		dataType: 'json',
		url: peepsodata.ajaxurl_legacy + 'profile.upload_avatar?avatar',
		add: function(e, data) {
			var acceptFileTypes = /(\.|\/)(jpe?g|png)$/i;
			if (data.files[0]['type'].length && !acceptFileTypes.test(data.files[0]['type'])) {
				var error_filetype = jQuery("#profile-avatar-error-filetype").text();
				jQuery(".ps-js-error").html(error_filetype);
			} else if (parseInt(data.files[0]['size']) > peepsodata.upload_size) {
				var error_filesize = jQuery("#profile-avatar-error-filesize").text();
				jQuery(".ps-js-error").html(error_filesize);
			} else {
				jQuery("#ps-window .ps-loading-image").show();
				jQuery("#ps-window .show-avatar, #ps-window .show-thumbnail, #ps-window .upload-avatar").hide();
				jQuery(".ps-js-error").hide();
				pswindow.refresh();
				data.submit();
			}
		},
		done: function (e, data) {
			var response = data.result;

			if (response.success) {
				var content_html = jQuery('#dialog-upload-avatar-content', jQuery(response.data.html));
				var actions = jQuery("#dialog-upload-avatar .dialog-action").html();
				var rand = "?" + Math.random();

				jQuery(".js-focus-avatar img", content_html).attr("src", response.data.image_url + rand);
				jQuery(".imagePreview img", content_html).attr("src", response.data.orig_image_url + rand);
				jQuery(".imagePreview", content_html).after('<input type="hidden" name="is_tmp" value="1"/>');
				jQuery(".ps-js-has-avatar", content_html).show();
				jQuery(".ps-js-no-avatar", content_html).hide();

				pswindow.set_content(content_html);
				pswindow.set_actions(actions);

				jQuery("#imagePreview img").one("load", function() {
					pswindow.refresh();
				});

				that.init_avatar_fileupload();
				jQuery("#ps-window button[name=rep_submit]").removeAttr("disabled").addClass("ps-btn-primary");
				that.invalid_avatar_upload = false;
				that.avatar_use_gravatar = false;
			} else {
				jQuery("#ps-window .show-avatar, #ps-window .show-thumbnail, #ps-window .upload-avatar").show();
				jQuery(".ps-js-error").html(response.errors).show();
				jQuery("#ps-window .ps-loading-image").hide();
				jQuery("#ps-window button[name=rep_submit]").attr("disabled", "disabled").removeClass("ps-btn-primary");
				that.invalid_avatar_upload = true;
			}
		}
	});
};

/**
 * Finalize avatar upload
 */
PsProfile.prototype.confirm_avatar = function( elem ) {
	var fn, req;

	if ( this.invalid_avatar_upload ) {
		return;
	}

	// prevent repeated call
	fn = this.confirm_avatar;
	if ( fn._loading ) {
		return;
	}
	fn._loading = true;

	// disable button on loading
	if ( elem ) {
		elem = jQuery( elem );
		elem.attr('disabled', 'disabled');
	}

	req = {
		user_id: peepsodata.userid,
		use_gravatar: this.avatar_use_gravatar ? 1 : 0,
		_wpnonce: jQuery('#_photononce').val()
	};

	peepso.postJson('profile.confirm_avatar', req, function( json ) {
		if ( json && json.success ) {
			window.location.reload();
			return;
		}

		fn._loading = false;
		if ( elem ) {
			elem.removeAttr('disabled');
		}
	});
};

/**
 * Shows cover dialog to change/upload cover content
 */
PsProfile.prototype.show_cover_dialog = function()
{
	jQuery(".ps-js-error").html("");	// clear any remaining error messages
	var $dialog = jQuery("#dialog-upload-cover");
	var title = $dialog.find("#dialog-upload-cover-title").html();
	var content = $dialog.find("#dialog-upload-cover-content").html();

	pswindow.show(title, content);

	this.init_cover_fileupload();

	jQuery("#ps-window").on('pswindow.hidden', function() {
		jQuery('.upload-cover .fileupload:visible').fileupload('destroy');
	});
};

/**
 * Initializes cover file upload
 */
PsProfile.prototype.init_cover_fileupload = function()
{
	var that = this;

	jQuery(".upload-cover .fileupload:visible").fileupload({
		formData: {user_id: peepsodata.userid, _wpnonce: jQuery("#_photononce").val()},
		dataType: 'json',
		url: peepsodata.ajaxurl_legacy + 'profile.upload_cover?cover',
		add: function(e, data) {
			var acceptFileTypes = /(\.|\/)(jpe?g|png)$/i;
			if (data.files[0]['type'].length && !acceptFileTypes.test(data.files[0]['type'])) {
				var error_filetype = jQuery("#profile-cover-error-filetype").text();
				jQuery(".ps-js-error").html(error_filetype);
			} else if (parseInt(data.files[0]['size']) > peepsodata.upload_size) {
				var error_filesize = jQuery("#profile-cover-error-filesize").text();
				jQuery(".ps-js-error").html(error_filesize);
			} else {
				jQuery("#ps-window .ps-loading-image").show();
				jQuery("#ps-window .upload-cover").hide();
				data.submit();
			}
		},
		done: function (e, data) {
			var response = data.result;
			jQuery("#ps-window .ps-loading-image").hide();
			jQuery("#ps-window .upload-cover").show();
			if (response.success) {
				jQuery(".cover-image")
					.attr("src", response.data.image_url + "?" + Math.random())
					.css("top", "0")
					.css("left", "0")
					.removeClass("default")
					.addClass("has-cover");

				jQuery(".ps-focus-image-mobile")
					.attr('style', 'background:url('+response.data.image_url + "?" + Math.random()+') no-repeat center center;');

				pswindow.fade_out("slow");
				jQuery("#profile-reposition-cover").show();
				jQuery('#dialog-upload-cover-content').html(response.data.html);
				pswindow.set_content(jQuery('#dialog-upload-cover-content', response.data.html).html());
				that.fix_horizontal_padding_debounced();
				that.init_cover_fileupload();
			} else {
				jQuery(".ps-js-error").html(response.errors);
			}
		}
	});
};

/**
 * Fix horizontal padding on lanscape image.
 */
PsProfile.prototype.fix_horizontal_padding = function() {
	var ctWidth, ctHeight, imgHeight;

	// reset
	this.$cover_image.css({
		height: 'auto',
		width: '100%',
		minWidth: '100%',
		maxWidth: '100%'
	});

	ctHeight = this.$cover_ct.width() * 0.375; // 0.375 is from css height percentage from its width;
	ctHeight = Math.max( ctHeight, this.$cover_ct.height() );
	imgHeight = this.$cover_image.height();

	// horizontal
	if ( imgHeight < ctHeight ) {
		this.$cover_image.css({
			height: ctHeight,
			width: 'auto',
			minWidth: '100%',
			maxWidth: 'none'
		});
	}

	this.initial_cover_position = this.$cover_image.attr('style');
};

PsProfile.prototype.fix_horizontal_padding_debounced = _.debounce(function() {
	this.fix_horizontal_padding();
}, 300 );

PsProfile.prototype.edit_field = function( elem )
{
	var $elem = jQuery( elem ),
		$ct = $elem.closest('.ps-list-info-content'),
		$input = $ct.find('input[type=text],input[type=checkbox],input[type=radio],textarea,select').eq(0),
		type = ( $input.attr('type') || '' ).toLowerCase(),
		value;

	// show form
	$ct.find('.ps-list-info-content-text').hide();
	$ct.find('.ps-list-info-content-form').show();
	$ct.find('.ps-list-info-content-error').hide();
	$ct.find('.ps-js-validation').removeClass('ps-alert-danger');

	// save initial data
	if ( type === 'checkbox' ) {
		$input = $ct.find('input[type=checkbox]:checked');
		value = $input.map(function() { return this.value });
		value = jQuery.makeArray( value ).join(',');
		$ct.data('original-value', value );
	} else if ( type === 'radio' ) {
		$input = $ct.find('input[type=radio]:checked');
		$ct.data('original-value', $input.val() );
	} else if ( $input.hasClass('datepicker') ) {
		$ct.data('original-value', $input.data('value') );
		$ct.data('original-formatted', $input.val() );
	} else {
		$ct.data('original-value', $input.val() );
	}

	return (false);
};

PsProfile.prototype.cancel_field = function( elem )
{
	var $elem = jQuery( elem ),
		$ct = $elem.closest('.ps-list-info-content'),
		$input = $ct.find('input[type=text],input[type=checkbox],input[type=radio],textarea,select').eq(0),
		type = ( $input.attr('type') || '' ).toLowerCase(),
		id = $input.data('id'),
		value, index;

	// hide form
	$ct.find('.ps-list-info-content-form').hide();
	$ct.find('.ps-list-info-content-text').show();
	$ct.find('.ps-list-info-content-error').hide();

	// restore initial data
	if ( type === 'checkbox' ) {
		value = $ct.data('original-value').split(',');
		$input = $ct.find('input[type=checkbox]');
		$input.each(function() {
			this.checked = value.indexOf( this.value ) > -1 ? true : false;
		});
	} else if ( type === 'radio' ) {
		value = $ct.data('original-value');
		$input = $ct.find('input[type=radio]');
		$input.each(function() {
			this.checked = this.value === value ? true : false;
		});
	} else if ( $input.hasClass('datepicker') ) {
		$input.data('value', $ct.data('original-value') );
		$input.val( $ct.data('original-formatted') );
	} else {
		$input.val( $ct.data('original-value') );
	}

	if ( this.field_changed_list && this.field_changed_list.length ) {
		index = this.field_changed_list.indexOf( id );
		if ( index > -1 ) {
			this.field_changed_list.splice( index, 1 );
		}
	}

	return (false);
};

PsProfile.prototype.save_field = function( elem )
{
	var $elem = jQuery( elem ),
		$ct = $elem.closest('.ps-list-info-content').closest('.ps-js-profile-item'),
		$input = $ct.find('input[type=text],input[type=checkbox],input[type=radio],textarea,select'),
		$btns = $ct.find('button'),
		$label = $ct.find('.ps-list-info-content-data'),
		type = ( $input.attr('type') || '' ).toLowerCase(),
		id = $input.data('id'),
		value, req, index;

	if ( type === 'checkbox' ) {
		value = $input.filter(':checked').map(function() { return this.value });
		value = jQuery.makeArray( value );
		value = JSON.stringify( value );
	} else if ( type === 'radio' ) {
		value = $input.filter(':checked').val();
	} else if ( $input.hasClass('datepicker') ) {
		value = $input.data('value');
	} else {
		value = ps_observer.apply_filters('profile_field_save', $input.val(), $input );
	}

	req = {
		user_id: peepsodata.currentuserid,
		view_user_id: peepsodata.userid,
		id: id,
		value: value
	};

	this.save_field_saving = true;
	this.change_beforeunload();

	$input.attr('disabled', 'disabled');
	$btns.attr('disabled', 'disabled');
	$elem.find('img').show();

	var that = this;
	$PeepSo.postJson('profilefieldsajax.save', req, function(json) {
		var hideCount = 0,
			data;

		$elem.find('img').hide();
		$input.removeAttr('disabled');
		$btns.removeAttr('disabled');
		that.save_field_saving = false;

		if ( json.success ) {
			data = json.data || {};

			// hide form
			$ct.find('.ps-list-info-content-form').hide();
			$ct.find('.ps-list-info-content-text').show();
			$ct.find('.ps-list-info-content-error').hide();

			// update label
			if ( data.display_value != undefined ) {
				$label.html( data.display_value );
			} else if ( $input.prop('tagName').toLowerCase() === 'select' ) {
				$label.html( $input.find('option:selected').text() );
			} else {
				$label.html( $input.val() );
			}

			if ( data.profile_completeness !== undefined ) {
				if ( +data.profile_completeness >= 100 ) {
					hideCount++;
					jQuery('.ps-completeness-status').hide();
					jQuery('.ps-completeness-bar').hide();
				} else {
					jQuery('.ps-completeness-status').html( data.profile_completeness_message ).show();
					jQuery('.ps-completeness-bar').show().children('span').css({ width: +data.profile_completeness + '%' });
				}
			}

			if ( data.missing_required !== undefined ) {
				if ( +data.missing_required <= 0 ) {
					hideCount++;
					jQuery('.ps-missing-required-message').hide();
				} else {
					jQuery('.ps-missing-required-message').html( data.missing_required_message ).show();
				}
			}

			if ( hideCount >= 2 ) {
				jQuery('.ps-completeness-info').hide();
			} else {
				jQuery('.ps-completeness-info').show();
			}

			if ( that.field_changed_list && that.field_changed_list.length ) {
				index = that.field_changed_list.indexOf( id );
				if ( index > -1 ) {
					that.field_changed_list.splice( index, 1 );
				}
			}

			// highlight container
			$ct.addClass('ps-list-info-success');
			setTimeout(function() {
				$ct.removeClass('ps-list-info-success');
			}, 1000 );

		} else {
			var $form = $ct.find('.ps-list-info-content-form'),
				errors = [],
				prop, $error;

			// update specific error divs
			$form.find('.ps-js-validation').removeClass('ps-alert-danger');

			if ( json.errors[0] ) {
				for ( prop in json.errors[0] ) {
					$error = $form.find('.ps-js-validation-' + prop );
					if ( $error.length ) {
						$error.addClass('ps-alert-danger');
					} else {
						errors.push( json.errors[0][prop] );
					}
				}
			}

			// update default error div
			$error = $form.find('.ps-list-info-content-error').hide();
			if ( errors.length ) {
				errors = errors.join('<br>');
				$error.html( errors ).show();
			}

			// highlight container
			$ct.addClass('ps-list-info-error');
			setTimeout(function() {
				$ct.removeClass('ps-list-info-error');
			}, 1000 );
		}

	});

	return (false);
};

PsProfile.prototype.field_keydown = function( elem, evt )
{
	if ( evt.keyCode === 13 ) {
		setTimeout(function() {
			var $input = jQuery( elem );
			var $btn = $input.closest('.ps-list-info-content').find('.ps-js-btn-save');
			$btn.trigger('click');
		}, 1 );
		return false;
	}

	return true;
};

PsProfile.prototype.field_changed = function( elem, evt )
{
	var id = jQuery( elem ).data('id');

	this.field_changed_list || (this.field_changed_list = []);
	if ( this.field_changed_list.indexOf( id ) === -1 ) {
		this.field_changed_list.push( id );
	}

	this.change_beforeunload();
	return true;
};

PsProfile.prototype.change_beforeunload = function()
{
	if ( !this.onbeforeunload_changed ) {
		var that = this;
		this.onbeforeunload_changed = window.onbeforeunload || (function() {});
		window.onbeforeunload = function () {
			if ( that.save_field_saving ) {
				return (peepsodata.profile_saving_notice || 'The system is currently saving your changes.');
			}
			if ( that.field_changed_list.length ) {
				return (peepsodata.profile_unsaved_notice || 'There are unsaved changes on this page.');
			}
		};
	}
};

PsProfile.prototype.change_privacy = function( elem ) {
	var $elem = jQuery(elem),
			$ct = $elem.closest('.ps-js-dropdown'),
			$hidden = $ct.find('input[type=hidden]'),
			$button = $ct.find('button.ps-dropdown-toggle'),
			val = $elem.data('option-value'),
			id = $hidden.data('id'),
			oldVal = $hidden.val();

	var privacy_icon = {
		'10': 'ps-icon-globe',
		'20': 'ps-icon-users',
		'30': 'ps-icon-user2',
		'40': 'ps-icon-lock'
	};

	var req = {
		user_id: peepsodata.currentuserid,
		view_user_id: peepsodata.userid,
		id: id,
		acc: val
	};

	// update button
	$button.find('i').attr('class', privacy_icon[val]);

	$PeepSo.postJson('profilefieldsajax.save_acc', req, function (json) {
		if (json.success) {
			$hidden.val(val);
			$button.find('.ps-privacy-title').html($elem.find('span').html());
		} else {
			$button.find('i').attr('class', privacy_icon[oldVal]);
		}
	});
};

PsProfile.prototype.use_gravatar = function() {
	var that = this;
	$PeepSo.postJson("profile.use_gravatar", {}, function( response ) {
		if ( response.success ) {
			var content_html = jQuery('#dialog-upload-avatar-content', jQuery(response.data.html));
			var actions = jQuery("#dialog-upload-avatar .dialog-action").html();
			var rand = "?" + Math.random();
			var image_url = response.data.image_url;

			image_url = image_url + ( image_url.indexOf('?') >= 0 ? '&rand=' : '?rand=' ) + Math.random();

			jQuery(".js-focus-avatar img", content_html).attr("src", image_url);
			jQuery(".imagePreview img", content_html).attr("src", image_url);
			jQuery(".imagePreview", content_html).after('<input type="hidden" name="is_tmp" value="1"/>');
			jQuery(".ps-js-has-avatar", content_html).show();
			jQuery(".ps-js-no-avatar", content_html).hide();
			jQuery(".ps-js-crop-avatar", content_html).hide();

			pswindow.set_content(content_html);
			pswindow.set_actions(actions);

			jQuery("#imagePreview img").one("load", function() {
				pswindow.refresh();
			});

			that.init_avatar_fileupload();
			jQuery("#ps-window button[name=rep_submit]").removeAttr("disabled").addClass("ps-btn-primary");
			that.invalid_avatar_upload = false;
			that.avatar_use_gravatar = true;
		}
	});
};

PsProfile.prototype.save_preference = function( e ) {
	var $el = jQuery( e && e.target ? e.target : e ),
		$loading = $el.closest('.ps-form-controls').find('.ps-js-loading'),
		params = {};

	params.user_id = peepsodata.currentuserid;
	params.view_user_id = peepsodata.userid;
	params.meta_key = $el.attr('name');
	params.value = $el.is(':checkbox') ? ( $el[0].checked ? 1 : 0 ) : $el.val();

	$loading.find('i').stop().hide();
	$loading.find('img').show();

	$PeepSo.postJson('profilepreferencesajax.save', params, function (json) {
		$loading.find('img').hide();
		$loading.find('i').show().delay( 800 ).fadeOut();

		if (json.success) {
			// TODO
		} else {
			// TODO
		}
	});
};

PsProfile.prototype.save_notification = function( e ) {
	var $el = jQuery( e && e.target ? e.target : e ),
		$loading = $el.closest('.ps-form-controls').find('.ps-js-loading'),
		params = {};

	params.user_id = peepsodata.currentuserid;
	params.view_user_id = peepsodata.userid;
	params.fieldname = $el.attr('name');
	params.value = $el.is(':checkbox') ? ( $el[0].checked ? 1 : 0 ) : $el.val();

	$loading.find('i').stop().hide();
	$loading.find('img').show();

	$PeepSo.postJson('profilepreferencesajax.save_notifications', params, function (json) {
		$loading.find('img').hide();
		$loading.find('i').show().delay( 800 ).fadeOut();

		if (json.success) {
			// TODO
		} else {
			// TODO
		}
	});
};

jQuery(document).ready( function ()
{
	profile.init();
});

// EOF
