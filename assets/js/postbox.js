(function( $, factory ) {

	var PsPostbox = factory('PsPostbox', $ );

	// make jQuery plugin
	$.fn.ps_postbox = function( method, params ) {
		return this.each(function() {
			var $el = $( this ),
			    postbox = $el.data('ps-postbox'),
			    opts;

			if ( postbox && typeof method === 'string' ) {
				postbox[ method ].call( postbox, params );
				if ( method === 'destroy' ) {
					$el.removeData('ps-postbox');
				}
			} else if ( !postbox ) {
				opts = method || {};
				postbox = new PsPostbox( $el, opts );
				$el.data('ps-postbox', postbox );
			}
		});
	};

})( jQuery, function( name, $ ) {

function PsPostbox() {
	this.__constructor.apply( this, arguments );
}

peepso.npm.objectAssign( PsPostbox.prototype, PsObserver.prototype, /** @lends PsPostbox.prototype */ {

	__constructor: function( el, opts ) {
		var template = peepso.template( psdata_postbox.template );

		this.$el = $( el ).html( template );
		this.$text = this.$el.find('textarea').ps_autosize();
		this.$loading = this.$el.find('.ps-postbox-loading');
		this.$btns = this.$el.find('.ps-postbox-action');
		this.$btnCancel = this.$btns.find('.ps-button-cancel');
		this.$btnSubmit = this.$btns.find('.ps-button-action');
		this.$charCounter = this.$el.find('.ps-postbox-charcount');
		this.$tabRoot = this.$el.find('div.ps-postbox-tab-root');
		this.$tabContext = this.$el.find('nav.ps-postbox-tab');
		this.$addons = this.$el.find('.ps-postbox-addons');

		this.opts = $.extend({
			maxChars: +psdata_postbox.max_chars || 4000,
			fetch: false,
			cancel: false,
			submit: false
		}, opts || {});

		this.init();
	},

	/**
	 * Init postbox.
	 */
	init: function() {
		this.$text.val('');
		this.updateButtonVisibility();
		this.updateCharCounter();

		this.add_action('refresh', this.refresh, 10, 1, this );

		// remove status tab switch, unused for now
		this.$tabContext.find('#privacy-tab').remove();
		this.$tabContext.find('#status-post').remove();

		ps_observer.do_action('postbox_init', this );

		this.fetch().done(function() {
			this.$text.on('focus.ps-postbox', $.proxy( this.onFocus, this ));
			this.$text.on('input.ps-postbox', $.proxy( this.onInput, this ));
			this.$btnSubmit.on('click.ps-postbox', $.proxy( this.onSubmit, this ));
			this.$btnCancel.on('click.ps-postbox', $.proxy( this.onCancel, this ));
			this.$tabRoot.on('click.ps-postbox', 'li[data-tab]', $.proxy( this.onTabChange, this ));
		});
	},

	/**
	 * Handle fetching content for initial data.
	 */
	fetch: function() {
		return $.Deferred( $.proxy(function( defer ) {
			if ( typeof this.opts.fetch === 'function' ) {
				this.$btns.hide();
				this.$loading.show();
				this.opts.fetch( this, $.proxy(function() {
					this.$loading.hide();
					this.$btns.css('display','flex');
					defer.resolveWith( this );
				}, this ));
			} else {
				defer.resolveWith( this );
			}
		}, this ));
	},

	/**
	 * Update postbox state based on supplied data.
	 */
	update: function( data ) {
		this.$text.val( data.content || '' ).trigger('input');
		this.updateButtonVisibility();
		this.updateCharCounter();
		this.do_action('update', data );
		ps_observer.do_action('postbox_update', this, data ); // deprecated
	},

	/**
	 * Reset postbox to an empty state.
	 */
	reset: function() {
		this.$text.val('');
		this.updateButtonVisibility();
		this.updateCharCounter();
		this.do_action('reset');
		ps_observer.do_action('postbox_reset', this ); // deprecated
	},

	/**
	 * Refresh postbox state.
	 */
	refresh: function() {
		this.renderAddons();
		this.updateButtonVisibility();
		this.updateCharCounter();
	},

	/**
	 *
	 */
	_getData: function() {
		return {
			content: this.$text.val()
		};
	},

	/**
	 *
	 */
	getData: function() {
		var data = this._getData();
		data = this.apply_filters('data', data );
		data = ps_observer.apply_filters('postbox_data', data, this ); // deprecated
		return data;
	},

	/**
	 *
	 */
	tabChange: function( id ) {
		var $tabs = this.$tabRoot.find('li[data-tab]'),
		    $tab = $tabs.filter('[data-tab="' + id + '"]');

		if ( $tab.length ) {
			$tabs.not( $tab ).removeClass('active');
			$tab.addClass('active');
			this.currentTab = id;
		}
	},

	/**
	 *
	 */
	getActiveTab: function() {
		if ( !this.currentTab ) {
			this.currentTab = this.$tabRoot.find('li.active').data('tab');
		}
		return this.currentTab;
	},

	/**
	 * Check whether current postbox state allows user to submit.
	 * @return {boolean}
	 */
	validate: function() {
		var data = this._getData(),
		    status = $.trim( data.content ),
		    valid = status ? true : false;

		return this.apply_filters('data_validate', valid, data );;
	},

	/**
	 * Handle submit action.
	 */
	submit: function() {
		if ( typeof this.opts.submit === 'function' && this.validate() ) {
			this.$btns.hide();
			this.$loading.show();
			this.opts.submit( this, this.getData(), $.proxy(function() {
				this.$loading.hide();
				this.$btns.css('display','flex');
			}, this ));
		}
	},

	/**
	 * Handle cancel action.
	 */
	cancel: function() {
		this.reset();
		this.$tabContext.hide();
		this.$tabRoot.show();
		if ( typeof this.opts.cancel === 'function' ) {
			this.opts.cancel( this );
		}
	},

	/**
	 * Update textarea char counter (rate-limited).
	 */
	updateCharCounter: _.throttle(function() {
		var length = this.$text.val().length,
		    charLeft = Math.max( 0, this.opts.maxChars - length );

		this.$charCounter.html( charLeft );
	}, 250 ),

	/**
	 * Update postbox button visibility which depends on current postbox state (rate-limited).
	 */
	updateButtonVisibility: _.throttle(function() {
		if ( this.validate() ) {
			this.$btnSubmit.show();
		} else {
			this.$btnSubmit.hide();
		}
	}, 250 ),

	/**
	 * Render addons.
	 */
	renderAddons: _.throttle(function() {
		var list = this.apply_filters('render_addons', [] ),
			html = list.join(' and '); // todo: language

		if ( html ) {
			html = '&mdash; ' + html;
		}

		this.$addons.html( html );
	}, 250 ),

	/**
	 * Destroy current postbox.
	 */
	destroy: function() {
		this.$el.empty();
	},

	/**
	 * TODO: docblock
	 */
	onFocus: function() {
		this.$tabRoot.hide();
		this.$tabContext.show();
	},

	/**
	 * TODO: docblock
	 */
	onInput: function() {
		this.updateButtonVisibility();
		this.updateCharCounter();
	},

	/**
	 * TODO: docblock
	 */
	onCancel: function() {
		this.cancel();
	},

	/**
	 * TODO: docblock
	 */
	onSubmit: function() {
		this.submit();
	},

	/**
	 * TODO: docblock
	 */
	onTabChange: function( e ) {
		var tabId = $( e.currentTarget ).data('tab');
		this.tabChange( tabId );
	}

});

return PsPostbox;

});
