(function( $ ) {

	var $win = $( window ),
		$container = $('.ps-js-extensions'),
		$list = $container.find('.ps-js-list'),
		$tabs = $container.find('.ps-js-tabs'),
		$input = $tabs.find('input[type=text]'),
		$allInstalled = $container.find('.ps-js-all-installed'),
		data = window.peepsoextdata && window.peepsoextdata.extensions || [],
		spinner = window.peepsoextdata && window.peepsoextdata.spinner,
		installedIsHidden = false,
		col = 3,
		key;

	for ( key in data ) {
		data[ key ].title = data[ key ].title.toLowerCase();
		data[ key ].content = data[ key ].content.toLowerCase();
	}

	var $notfound = $('<div>Sorry, no extensions matched your criteria.</div>').css({ paddingBottom: 20 }).hide();
	var $loading = $('<div>' + ( spinner ? ('<img src="' + spinner + '" />') : 'Loading...' ) + '</div>').css({ paddingBottom: 20, textAlign: 'center' }).hide();
	$list.after( $notfound ).after( $loading );

	// filter extensions
	var filter = _.debounce(function( value, type ) {
		var ids = [],
			allInstalled = true,
			isTranslation, shouldAdd;

		value = ( value || '' ).toLowerCase();
		for ( key in data ) {
			isTranslation = data[ key ].categories.indexOf('translations') >= 0;
			shouldAdd = false;
			if ( type === 'category' ) {
				if ( isTranslation && value !== 'translations' ) {
					shouldAdd = false;
				} else if ( value === 'all' || data[ key ].categories.indexOf( value ) >= 0 ) {
					shouldAdd = true;
				}
			} else if ( ! isTranslation ) {
				if (( ! value ) || data[ key ].title.match( value ) || data[ key ].content.match( value ) || data[ key ].tags.indexOf( value ) >= 0 ) {
					shouldAdd = true;
				}
			}
			if ( shouldAdd ) {
				ids.push( +data[ key ].id );
			}
		}
		if ( !ids.length ) {
			$loading.hide();
			$list.stop().hide();
			$notfound.show();
			return;
		}
		toggleInstalled('show'); // show all
		$list.children('.ps-js-extension').each(function() {
			var $el = $( this ),
				id = $el.data('id');

			if ( ids.indexOf( +id ) >= 0 ) {
				$el.removeClass('ps-js-hidden').css({ display: ''}).find('.ps-js-description').css({ height: '' });
				if ( ! $el.hasClass('ps-js-installed') ) {
					allInstalled = false;
				}
			} else {
				$el.addClass('ps-js-hidden').css({ display: 'none' });
			}
		});

		$allInstalled.css({ display: allInstalled ? '' : 'none' });
		$loading.hide();
		$notfound.hide();
		$list.show();
		equalHeight();
	}, 400 );

	// match extensions height
	var equalHeight = _.debounce(function() {
		var $elems = $list.children('.ps-js-extension').not('.ps-js-hidden'),
			browserWidth = $win.outerWidth(),
			needRecalculate = browserWidth > 991,
			height;

		// exclude installed if hidden
		if ( installedIsHidden ) {
			$elems = $elems.not('.ps-js-installed');
		}

		if ( needRecalculate ) {
			$list.children('.ps-js-break').remove();
			$elems.each(function( index ) {
				if (( index % col === col - 1 ) || ( index === $elems.length - 1 )) {
					$( this ).after('<div class="ps-js-break" style="clear:both" />');
				}
			});
			$elems.each(function( index ) {
				var $el = $( this );
				if ( index % col === 0 ) {
					height = 0;
				}
				height = Math.max( height, $el.find('.ps-js-description').outerHeight() );
				if (( index % col === col - 1 ) || ( index === $elems.length - 1 )) {
					$el.prevUntil('.ps-js-break').andSelf().find('.ps-js-description').css({ height: height });
				}
			});
		}
	}, 10 );

	function toggleInstalled( action ) {
		var $items = $container.find('.ps-js-installed').not('.ps-js-hidden'),
			$btn = $container.find('.ps-js-toggle-installed');

		if ( $items.length ) {
			if ( action === 'hide' ) {
				installedIsHidden = true;
				$btn.find('span').html( peepsoadminextdata.label_show_installed );
				$items.hide();
				equalHeight();
			} else {
				installedIsHidden = false;
				$btn.find('span').html( peepsoadminextdata.label_hide_installed );
				$items.show();
				equalHeight();
			}
		}
	}

	// search by keyword
	$input.on('input', function( e ) {
		var value = $.trim( e.target.value );
		$tabs.find('li').removeClass('active');
		$list.stop().hide();
		$notfound.hide();
		$loading.show();
		filter( value );
	});

	// select category
	$tabs.on('click', '.ps-js-tab', function() {
		var $tab = $( this ),
			slug = $tab.data('slug');

		$input.val('');
		$tabs.find('li').removeClass('active');
		$tab.closest('li').addClass('active');
		$list.stop().hide();
		$notfound.hide();
		$loading.show();
		filter( slug, 'category' );
	});

	// toggle installed
	$container.on('click', '.ps-js-toggle-installed', _.throttle(function() {
		toggleInstalled( installedIsHidden ? 'show' : 'hide' );
	}, 400 ));

	$(function() {
		filter();
		// equalHeight();
	});

})( jQuery );
