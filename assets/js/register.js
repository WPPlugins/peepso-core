(function( $ ) {

function validate( ct ) {
	var $ct = $( ct ),
		$input = $ct.find('input[type=text],input[type=password],input[type=checkbox],input[type=radio],textarea,select'),
		id, name, type, value, core, url, req;

	if ( ! $input.length ) {
		return;
	}

	id = $input.eq(0).data('id');
	name = ( $input.eq(0).attr('name') || '' );
	type = ( $input.eq(0).attr('type') || '' ).toLowerCase();

	if ( type === 'checkbox' ) {
		value = $input.filter(':checked').map(function() { return this.value });
		value = $.makeArray( value );
		value = JSON.stringify( value );
	} else if ( type === 'radio' ) {
		value = $input.filter(':checked').val();
	} else if ( $input.hasClass('datepicker') ) {
		value = $input.data('value');
	} else {
		value = ps_observer.apply_filters('profile_field_save', $input.val(), $input );
	}

	// validate core fields
	if ([ 'username', 'email', 'password', 'password2' ].indexOf( name ) >= 0 ) {
		core = true;
		url = peepsodata.ajaxurl_legacy + 'profilefieldsajax.validate_register';
		req = {};
		req['name'] = name;
		req[ name ] = value;

		// verify password
		if ( name === 'password2' ) {
			$input = $input.closest('.ps-register-form form').find('[name=password]');
			req['password'] = $input.val();
		}

	// validate extra fields
	} else {
		core = false;
		url = peepsodata.ajaxurl_legacy + 'profilefieldsajax.validate';
		req = {
			user_id: peepsodata.currentuserid,
			view_user_id: peepsodata.userid,
			id: id,
			value: value
		};
	}

	return $.ajax({
		url: url,
		type: 'post',
		dataType: 'json',
		data: req
	}).done(function( json ) {
		var $err = $ct.find('.ps-form-error'),
			errors;

		if ( json.errors && json.errors.length ) {
			$err.empty();
			errors = core ? json.errors : json.errors[0];
			_.each( errors, function( error ) {
				$err.append('<li>' + error + '</li>');
			});
			$err.show();
		} else {
			$err.hide();
		}
	});
}

function doSubmit( form ) {
	var $form = $( form ),
		$fields = $form.find('.ps-form-field'),
		$dps = $fields.find('input.datepicker'),
		$submit = $form.find('button[type=submit]'),
		deferreds = [];
		errors = [];

	// prevent repeated click
	if ( $form.data('ps-submitting') ) {
		return;
	}

	$form.data('ps-submitting', true );

	// validate all fields
	$fields.each(function() {
		var xhr = validate( this );
		if ( xhr ) {
			deferreds.push( xhr );
		}
	});

	// submit when all validation done
	$submit.find('img').show();
	$.when.apply( $, deferreds ).done(function() {
		var json, i;

		$submit.find('img').hide();

		// prevent submit if errors detected
		for ( i in arguments ) {
			json = arguments[i][0];
			if ( json.errors ) {
				$form.removeData('ps-submitting');
				return;
			}
		}

		// convert datepicker values before submitting
		if ( $dps.length ) {
			$dps.each(function() {
				var $dp = $( this ),
					val = $dp.data('value');
				if ( val ) {
					$hidden = $('<input type="hidden" name="' + $dp.attr('name') + '" />');
					$dp.removeAttr('name');
					$hidden.insertAfter( $dp );
					$hidden.val( val );
				}
			});
		}

		// let plugins convert field input values before submitting
		if ( $fields.length ) {
			$fields.each(function() {
				$input = $( this ).find('input[type=text],input[type=password],input[type=checkbox],input[type=radio],textarea,select');
				ps_observer.do_action('profile_field_save_register', $input );
			});
		}

		// submit form
		$form.off('submit.ps-register');
		setTimeout(function() {
			$submit.click();
		}, 100 );
	});
}

var $form = $( document ).find('.ps-register-form form');

$form
	// on change input text
	.on('input', 'input[type=text]:not(.ps-js-field-location),input[type=password],textarea', _.debounce(function( e ) {
		validate( $( e.target ).closest('.ps-form-field') );
	}, 500 ))
	// on change checkbox/radio input
	.on('click', 'input[type=checkbox],input[type=radio]', _.debounce(function( e ) {
		validate( $( e.target ).closest('.ps-form-field') );
	}, 100 ))
	// on change selectbox
	.on('change', 'select', _.debounce(function( e ) {
		validate( $( e.target ).closest('.ps-form-field') );
	}, 100 ))
	// on form submit
	.on('submit.ps-register', function( e ) {
		e.preventDefault();
		e.stopPropagation();
		doSubmit( this );
		return false;
	});

// wait for the location element to be available
$(function() {
	setTimeout(function() {
		$form.find('.ps-js-location-wrapper .ps-btn').on('mousedown', _.debounce(function( e ) {
			validate( $( e.target ).closest('.ps-form-field') );
		}, 100 ));
	}, 1000 );
});

})( jQuery );
