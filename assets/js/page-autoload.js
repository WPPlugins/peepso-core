(function( $, factory ) {

	PsPageAutoload = factory( $ );

})( jQuery, function( $ ) {

function PsPageAutoload() {
	return this.init.apply( this, arguments );
}

PsPageAutoload.prototype = {

	/**
	 *
	 */
	init: function( prefix ) {
		if ( !prefix ) {
			throw new Error('CSS prefix is not supplied!');
		}

		this._css_prefix = prefix;
		this._config_loadmore_enable = +peepsodata.loadmore_enable;

		$( _.bind( this.onDocumentLoaded, this ));

		return this;
	},

	/**
	 *
	 */
	onDocumentLoaded: function() {
		this._search_$ct = $( this._css_prefix ).eq(0);
		this._search_$trigger = $( this._css_prefix + '-triggerscroll');
		this._search_$loading = $( this._css_prefix + '-loading');
		this._search_$nomore = $( peepsodata.activity.template_no_more ).hide().insertBefore( this._search_$trigger );

		if ( !this._search_$ct.length ) {
			return false;
		}

		this._search();
	},

	/**
	 *
	 */
	_search_url: '',

	/**
	 *
	 */
	_search_params: {},

	/**
	 *
	 */
	_search_loadmore_enable: null,

	/**
	 *
	 */
	_search: function() {
		this._search_toggle_autoscroll('off');
		this._search_toggle_loading('show');
		this._search_$ct.empty();
		this._search_$nomore.hide();

		// reset "load more" setting on first page
		if ( this._search_params.page <= 1 ) {
			this._search_loadmore_enable = this._config_loadmore_enable;
			if ( this._search_$loadmore ) {
				this._search_$loadmore.remove();
				this._search_$loadmore = null;
			}
		}

		this._search_debounced();
	},

	/**
	 *
	 */
	_search_next: function() {
		this._search_toggle_autoscroll('off');
		this._search_toggle_loading('show');
		this._search_params.page++;
		this._search_debounced();
	},

	/**
	 *
	 */
	_search_debounced: _.debounce(function() {
		this._fetch( this._search_params ).done(function( data ) {
			var html = this._search_render_html( data );
			this._search_toggle_loading('hide');
			if ( html ) {
				this._search_$ct.append( html );
				this._search_toggle_autoscroll('on');
			} else {
				this._search_$nomore.show();
			}
		}).fail(function( errors ) {
			this._search_toggle_loading('hide');
			if ( this._search_params.page <= 1 ) {
				this._search_$ct.html( errors.join('<br>') );
			} else {
				this._search_$nomore.show();
			}
		});
	}, 500 ),

	/**
	 * @returns {string|null} html
	 */
	_search_render_html: function() {
		throw new Error('This method must be implemented by subclass!');
	},

	/**
	 * @param {string} method
	 */
	_search_toggle_loading: function( method ) {
		if ( method === 'show' ) {
			clearTimeout( this._search_toggle_loading_timer );
			this._search_$loading.show();
		} else if ( method === 'hide' ) {
			this._search_toggle_loading_timer = setTimeout( $.proxy( function() {
				this._search_$loading.hide();
			}, this ), 1000 );
		}
	},

	/**
	 * @param {string} method
	 */
	_search_toggle_autoscroll: function( method ) {
		var evtName = 'scroll' + this._css_prefix,
			$win = $( window ),
			$btn;

		if ( method === 'off' ) {
			$win.off( evtName );
		} else if ( method === 'on' && this._search_$trigger.length ) {
			if ( this._search_loadmore_enable ) {
				if ( this._search_should_load_more() ) {
					this._search_next();
				} else {
					this._search_$loadmore = $( peepsodata.activity.template_load_more ).insertAfter( this._search_$ct );
					this._search_$loadmore.one('click', $.proxy(function( e ) {
						this._search_$loadmore.remove();
						this._search_$loadmore = null;
						this._search_loadmore_enable = false;
						this._search_next();
					}, this ));
				}
			} else {
				$win.off( evtName ).on( evtName, $.proxy(function() {
					if ( this._search_should_load_more() ) {
						this._search_next();
					}
				}, this )).trigger( evtName );
			}
		}
	},

	/**
	 * @returns jQuery
	 */
	_search_get_items: function() {
		return $();
	},

	/**
	 * @returns boolean
	 */
	_search_should_load_more: function() {
		var limit = +peepsodata.activity_limit_below_fold,
			$items = this._search_get_items(),
			$lastItem, position;

		limit = limit > 0 ? limit : 3;
		if ( this._search_params.limit ) {
			limit = limit * this._search_params.limit;
		}

		$lastItem = $items.slice( 0 - limit ).eq( 0 );
		if ( $lastItem.length ) {
			if ( this._search_loadmore_enable ) {
				position = $lastItem.eq( 0 ).offset();
			} else {
				position = $lastItem.get( 0 ).getBoundingClientRect();
			}
			if ( position.top < ( window.innerHeight || document.documentElement.clientHeight )) {
				return true;
			}
		}

		return false;
	},

	/**
	 * @param {object} params
	 * @returns jQuery.Deferred
	 */
	_fetch: function( params ) {
		return $.Deferred( $.proxy(function( defer ) {

			// Multiply limit value by 2 which translate to 2 rows each call.
			params = $.extend({}, params );
			if ( ! _.isUndefined( params.limit ) ) {
				params.limit *= 2;
			}

			this._fetch_xhr && this._fetch_xhr.abort();
			this._fetch_xhr = peepso.getJson( this._search_url, params, $.proxy(function( response ) {
				if ( response.success ) {
					defer.resolveWith( this, [ response.data ]);
				} else {
					defer.rejectWith( this, [ response.errors ]);
				}
			}, this ));
		}, this ));
	}

};

return PsPageAutoload;

});
