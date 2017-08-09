(function( $, _, factory ) {

	var PsFocusArea = factory( $, _ );
	ps_focus_area = new PsFocusArea();

})( jQuery, _, function( $, _ ) {

function PsFocusArea() {
	$( $.proxy( this.init_page, this ) );
}

PsFocusArea.prototype = {

	init_page: function() {
		this.$focus = $('.ps-js-focus').eq( 0 );
		if ( ! this.$focus.length ) {
			return;
		}

		this.$focus_avatar = this.$focus.find('.ps-js-focus-avatar-button').on('click', $.proxy( this.onClickAvatar, this ));
		this.$focus_interactions = this.$focus.find('.ps-js-focus-interactions');
		this.$focus_links = this.$focus.find('.ps-js-focus-links');
		this.$focus_link_more = this.$focus_links.find('.ps-js-focus-link-more').on('click', $.proxy( this.onClickFocusMore, this ));
		this.$focus_link_dropdown = this.$focus_links.find('.ps-js-focus-link-dropdown');

		$( window ).on('resize.ps-focus-area', $.proxy( this.fix_focus_links_debounced, this ));
		this.fix_focus_links();
	},

	fix_focus_links_debounced: _.debounce(function() {
		this.fix_focus_links();
	}, 1000 ),

	fix_focus_links: function() {
		var $last, parentWidth,
			$ct = this.$focus_links,
			$more = this.$focus_link_more,
			$dropdown = this.$focus_link_dropdown,
			isWide = [ 'medium', 'large' ].indexOf( peepso.screenSize() ) >= 0,
			counter = 0;

		// Reset links visibility.
		$dropdown.hide().empty();
		$more.detach();
		$ct.find( '.ps-focus__menu-item:hidden' ).css( 'display', '' );

		parentWidth = $ct.parent().width();
		if ( isWide ) {
			parentWidth -= this.$focus_interactions.width();
		}

		// Loop until container width is below it's parent width.
		// Set to max 20 iterations to prevent potential measurement error due to external styling issue.
		while ( ++counter <= 20 && $ct.width() > parentWidth ) {

			// Attach "more" dropdown on first iteration.
			if ( counter === 1 ) {
				$more.insertBefore( $dropdown.parent() ).show();
			}

			// Move last visible item into dropdown.
			$last = $more.prevAll( '.ps-focus__menu-item:visible' ).first();
			if ( $last.length ) {
				$last = $last.hide().clone();
				$dropdown.prepend( $last.css( 'display', '' ) );
			}
		}
	},

	/**
	 *
	 */
	onClickFocusMore: function( e ) {
		var evtName = 'click.ps-focus-link-more',
			$more = this.$focus_link_more,
			$dropdown = this.$focus_link_dropdown,
			$doc = $( document );

		e.preventDefault();
		e.stopPropagation();

		if ( $dropdown.is( ':visible' ) ) {
			$dropdown.hide();
			$doc.off( evtName );
		} else {
			$dropdown.show();
			$doc.one( evtName, function( e ) {
				$dropdown.hide();
			})
		}
	},

	/**
	 * Show avatar dialog for user.
	 */
	showAvatarDialog: function() {
		var data;

		if ( peepsodata.profile ) {
			if ( ! this.avatarDialog ) {
				data = _.extend({}, peepsodata.profile );
				if ( ! data.hasAvatar ) {
					data.imgOriginal = '';
				}
				this.avatarDialog = new PsAvatarDialog( data );
			}
			this.avatarDialog.show();
		}
	},

	/**
	 * Handle click avatar on focus area.
	 * @param {HTMLEvent} e
	 */
	onClickAvatar: function( e ) {
		var handler;

		e.preventDefault();
		e.stopPropagation();

		handler = ps_observer.apply_filters('show_avatar_dialog', $.proxy( this.showAvatarDialog, this ));
		if ( _.isFunction( handler ) ) {
			handler();
		}
	}

};

return PsFocusArea;

});
