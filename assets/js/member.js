(function( root, $, factory ) {

	var PsMember = factory( root, $ );
	ps_member = new PsMember();

})( window, jQuery, function( window, $ ) {

/**
 * User managements.
 * @class PsMember
 */
function PsMember() {
}

/**
 * Block specific user.
 * @param {number} user_id User ID to be blocked.
 * @param {HTMLElement=} elem Block button.
 */
PsMember.prototype.block_user = function( user_id, elem ) {
	if ( this.blocking_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	this.blocking_user = true;
	peepso.postJson('activity.blockuser', { uid: peepsodata.currentuserid, user_id: user_id }, $.proxy(function( json ) {
		this.blocking_user = false;
		if ( json.success ) {
			$( '.ps-js-focus--' + user_id ).find('.ps-focus-actions, .ps-focus-actions-mobile').html( json.data.actions );
			ps_observer.apply_filters('ps_member_user_blocked', user_id, json.data );
			psmessage.show(json.data.header, json.data.message, psmessage.fade_time);
			if (json.data.redirect) {
				setTimeout(function() {
					window.location = json.data.redirect;
				}, Math.min( 1000, psmessage.fade_time));
			}
		}
	}, this ));
};

/**
 * Unblock specific user.
 * @param {number} user_id User ID to be unblocked.
 * @param {HTMLElement=} elem Unblock button.
 */
PsMember.prototype.unblock_user = function( user_id, elem ) {
	if ( this.unblocking_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	this.unblocking_user = true;
	peepso.postJson('activity.unblockuser', { uid: peepsodata.currentuserid, user_id: user_id }, $.proxy(function( json ) {
		this.unblocking_user = false;
		if ( json.success ) {
			jQuery('.ps-js-focus--' + user_id ).find('.ps-focus-actions, .ps-focus-actions-mobile').html( json.data.actions );
			ps_observer.apply_filters('ps_member_user_unblocked', user_id, json.data );
			psmessage.show( json.data.header, json.data.message, psmessage.fade_time );
		}
	}, this ));
};

/**
 * Ban specific user.
 * @param {number} user_id User ID to be banned.
 * @param {HTMLElement=} elem Ban button.
 */
PsMember.prototype.ban_user = function( user_id, elem ) {
	if ( this.banning_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	var title = peepsomemberdata.ban_popup_title;
	var content = peepsomemberdata.ban_popup_content;
	var actions = [
		'<button type="button" class="ps-btn ps-btn-small ps-button-cancel" onclick="return pswindow.do_no_confirm();">', peepsomemberdata.ban_popup_cancel, '</button>',
		'<button type="button" class="ps-btn ps-btn-small ps-button-action" onclick="return ps_member.do_ban_user('+user_id+');">', peepsomemberdata.ban_popup_save, '</button>'
	].join(' ');

	var popup = pswindow.show(title, content).set_actions(actions);
	var $ct = popup.$container;

	$ct.find('#ban-forever').on('focus', function() {
		$('#ban-period-empty').hide();
	});

	// init datepicker
	ps_datepicker.init( $ct.find('[name=ban_period_date]') );
};

/**
 * Confirm to Ban specific user.
 * @param {number} user_id User ID to be banned.
 * @param {HTMLElement=} elem Ban button.
 */
PsMember.prototype.do_ban_user = function(user_id) {
	var $form = $('#form_ban_user'),
		ban_type = $form.find('input[name=ban_type]:checked').val(),
		ban_period_date;

	if ( ban_type === 'ban_period' ) {
		ban_period_date = $form.find('input[name=ban_period_date]').data('value');
		if ( !ban_period_date ) {
			$form.find('#ban-period-empty').show();
			return false;
		}
	}

	var req = {
		user_id: user_id,
		ban_status: 1,
		ban_type: ban_type,
		ban_period_date: ban_period_date
	};

	this.banning_user = true;
	peepso.postJson('activity.set_ban_status', req, $.proxy(function( json ) {
		this.banning_user = false;
		if ( json.success ) {
			ps_observer.apply_filters('ps_member_user_banned', user_id, json.data );
			psmessage.show( json.data.header, json.data.message, psmessage.fade_time );
			setTimeout(function() {
				window.location.reload();
			}, Math.min( 1000, psmessage.fade_time));
		}
	}, this ));
};

/**
 * Unban specific user.
 * @param {number} user_id User ID to be unbanned.
 * @param {HTMLElement=} elem Unban button.
 */
PsMember.prototype.unban_user = function( user_id, elem ) {
	if ( this.unbanning_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	this.unbanning_user = true;
	peepso.postJson('activity.set_ban_status', { user_id: user_id, ban_status: 0 }, $.proxy(function( json ) {
		this.unbanning_user = false;
		if ( json.success ) {
			ps_observer.apply_filters('ps_member_user_unbanned', user_id, json.data );
			psmessage.show( json.data.header, json.data.message, psmessage.fade_time );
			setTimeout(function() {
				window.location.reload();
			}, Math.min( 1000, psmessage.fade_time));
		}
	}, this ));
};

return PsMember;

});
