(function( $, factory ) {

	ps_notification = new (factory( $ ));

})( jQuery, function( $ ) {

function PsNotification() {

	// auto-start notification if peepso wrapper or peepso adminbar notification icon is detected
	$( $.proxy(function() {
		_.defer( $.proxy(function() {
			var wrapperExist = $('#peepso-wrap').length,
				adminbarExist = $('#wpadminbar').find('.psnotification-toggle').length;
			if ( wrapperExist || adminbarExist ) {
				this.start();
			}
		}, this ));
	}, this ));

	// allow widgets to manually start notification polls
	ps_observer.add_action('notification_start', $.proxy( this.start, this ));
}

PsNotification.prototype = {

	_get_latest_interval: 30 * 1000, // default poll interval

	_get_latest_count: function() {
		peepso.disableAuth().disableError().postJson('notificationsajax.get_latest_count', null, $.proxy(function( json ) {
			var count_title;

			if ( json.success && !json.session_timeout ) {
				count_title = 0;
				$.each( json.data, function( key, value ) {
					var $el = $( '.' + key ),
						count = Math.max( 0, value.count ),
						prev_count, $counter;

					// append to titlebar counter value
					count_title += count;

					// update notification icon counter
					if ( $el.length ) {
						$counter = $el.find('.ps-js-counter');
						prev_count = +$counter.eq(0).text();
						if ( $counter.length && prev_count !== count ) {
							$counter.html( count ).css('display', count > 0 ? '' : 'none' );
							if ( count > 0 ) {
								$el.psnotification('clear_cache');
							}
						}
					}
				});

				this._update_titlebar( count_title );

				ps_observer.do_action('notification_update', json );
			}
		}, this ));
	},

	_update_titlebar: function( count ) {
		var title = ( document.title || '' ).replace(/^\(\d+\)\s*/, '');
		if ( count > 0 ) {
			title = '(' + count + ') ' + title;
		}
		if ( document.title !== title ) {
			document.title = title;
		}
	},

	hide: function( note_id ) {
		var $elems, fn, flag;

		if ( typeof note_id === 'undefined' ) {
			return;
		}

		// prevent repeated call
		fn = this.hide;
		flag = '_progress_' + note_id;
		if ( fn[ flag ] ) {
			return;
		}
		fn[ flag ] = true;

		$elems = $('.ps-js-notifications').find('.ps-js-notification--' + note_id ).map(function() {
			return $( this ).parent('.ps-notification__wrapper').get( 0 );
		});

		$elems.css('opacity', 0.5 );
		peepso.postJson('notificationsajax.hide', { note_id: note_id }, $.proxy(function( json ) {
			delete fn[ flag ];
			if ( json.success ) {
				$elems.remove();
				ps_observer.do_action('notification_restart');
			} else {
				$elems.css('opacity', '');
			}
		}, this ));
	},

	mark_as_read: function( note_id ) {
		var req = null,
			fn, flag;

		if ( note_id ) {
			req = { note_id: note_id };
		}

		// prevent repeated call
		fn = this.mark_as_read;
		flag = '_progress_' + ( note_id || '');
		if ( fn[ flag ] ) {
			return;
		}
		fn[ flag ] = true;

		$elems = $('.ps-js-notifications').find('.ps-js-notification' + ( note_id ? ( '--' + note_id ) : '')).map(function() {
			return $( this ).parent('.ps-notification__wrapper').get( 0 );
		});

		$elems.css('opacity', 0.5 );
		peepso.postJson('notificationsajax.mark_as_read', req, $.proxy(function( json ) {
			delete fn[ flag ];
			$elems.css('opacity', '');
			if ( json.success ) {
				$elems.find('.ps-notification--unread').removeClass('ps-notification--unread');
				$elems.find('.ps-js-btn-markasread').remove();
				ps_observer.do_action('notification_restart');
			}
		}, this ));
	},

	mark_all_as_read: function() {
		this.mark_as_read(null);
	},

	start: function() {
		if ( !this._started && +peepsodata.currentuserid ) {
			this._started = true;
			this._get_latest_count();
			this._get_latest_timer = setInterval( $.proxy( this._get_latest_count, this ), this._get_latest_interval );

			// stop notification on login popup
			$( window ).on('peepso_auth_required', $.proxy(function() {
				clearInterval( this._get_latest_timer );
			}, this ));

			// restart notification on peepso-core-message's mark-as-read
			ps_observer.add_filter('pschat_mark_as_read', this.restart, 10, 1, this );
			ps_observer.add_action('notification_restart', this.restart, 10, 1, this );
		}
	},

	restart: function() {
		if ( +peepsodata.currentuserid ) {
			clearInterval( this._get_latest_timer );
			this._get_latest_count();
			this._get_latest_timer = setInterval( $.proxy( this._get_latest_count, this ), this._get_latest_interval );
		}
	}

};

return PsNotification;

});


// Available options:
// 	view_all_text, string
// 	view_all_link, string
// 	source, // string - the URL to retrieve the view
// 	request, // json - additional parameters to send to opts.source via ajax
// 	paging, // boolean - enables the scroll pagination
//

// TODO: reimplement using prototype

(function($){
	function PsPopoverNotification(elem, options)
	{
		var _self = this;
		this.popover = null;
		this.popover_list = null;
		this.popover_footer = null;
		this.popover_header = null;
		this._notifications = {}; // array of HTML to be inserted to the dropdown list

		this.init = function(opts) {
			_opts = {
				view_all_text: peepsodata.view_all_text,
				view_all_link: null,
				source: null, // the URL to retrieve the view
				request: { // additional parameters to send to opts.source via ajax
					per_page: 10,
					page: 1
				},
				header: null,  // HTML to be displayed on the top section of the notification
				paging: false, // set  this to true if you want to enable scrolling pagination
				fetch: null // Function used to modify the request data. Returning false will prevent the fetch operation
			};

			this.opts = ps_observer.apply_filters('peepso_notification_plugin_options', _opts);

			this._content_is_fetched = false;
			$.extend(true, this.opts, opts);

			$(elem).addClass("psnotification-toggle");
			this.popover = $("<div>");
			this.popover_list = $("<div>").css({ maxHeight: '40vh', overflow: 'auto' });
			this.popover_list.bind('mousewheel', $.proxy(function( e, d ) {
				var t = $( e.currentTarget );
				if ( d > 0 && t.scrollTop() === 0 ) {
					e.preventDefault();
				} else if ( d < 0 && ( t.scrollTop() == t.get(0).scrollHeight - t.innerHeight() ) ) {
					e.preventDefault();
				}
			}, this ));

			$(elem).append(this.popover);

			// Add header
			if (false === _.isNull(this.opts.header)) {
				this.popover_header = $("<div/>");
				this.popover_header
					.addClass("ps-popover-header app-box-header")
					.append(this.opts.header)
					.append("<div class='clearfix' />");
				this.popover.append(this.popover_header);
			}

			// Add list container
			this.popover.append(this.popover_list);
			this.popover_list.addClass("ps-notifications ps-notifications--empty");
			this.popover.addClass("ps-popover app-box").hide();

			if (this.opts.paging)
				this.init_pagination();

			// Add view all link
			if (false === _.isNull(this.opts.view_all_link)) {
				this.popover_footer = $("<div/>");
				this.popover_footer
					.addClass("ps-popover-footer app-box-footer")
					.append("<a href='" + this.opts.view_all_link + "'>" + this.opts.view_all_text + "</a>");

				this.popover.append(this.popover_footer);
			}
		};

		this.fetch = function( callback ) {
			var req = this.opts.request,
				method = ( this.opts.method || '' ).toLowerCase();

			// Allow scripts to customize the request further
			if (_.isFunction(this.opts.fetch)) {
				req = this.opts.fetch.call(this, req);

				if (false === req)
					return;
			}

			this._notifications = {};
			this.fetch_stop();
			this.fetch_xhr = $PeepSo[ method === 'get' ? 'getJson' : 'postJson' ](this.opts.source, req, function(response) {
				if (response.success) {
					_self._content_is_fetched = true;
					_self._data = response.data;
					_self._notifications = response.data.notifications;
					_self._errors = false;

					if (_self._notifications.length > 0)
						_self.opts.request.page++; // locks in to the last page that had available data, so when new data comes in we have the correct offset
				} else if (response.errors) {
					_self._content_is_fetched = true;
					_self._errors = response.errors;
				}
				if (typeof callback === "function") {
					callback();
				}
			});
		};

		this.fetch_stop = function() {
			if ( this.fetch_xhr ) {
				if ( this.fetch_xhr.abort ) {
					this.fetch_xhr.abort();
				} else if ( this.fetch_xhr.ret && this.fetch_xhr.ret.abort ) {
					this.fetch_xhr.ret.abort();
				}
			}
		};

		this.refresh = function() {
			this.popover_list.find(".ps-notification__wrapper").remove();
			this._content_is_fetched = false;
			this.load_page(function() {
				if (_self.opts.paging)
					_self.popover_list.trigger("scroll");
			});
		};

		this.onClick = function(e) {
			if (_.isFunction(_self.opts.before_click) && (_self.opts.before_click() === false)) {
				return;
			}

			if (_self.popover.has($(e.target)).length > 0)
				return;

			e.preventDefault();

			var isLazy = _self.opts.lazy;
			var isVisible = _self.popover.is(':visible');

			_self.show();
			!isLazy && !isVisible && _self.load_page(function() {
				if (_self.opts.paging)
					_self.popover_list.trigger("scroll");
			});
		};

		this.render = function() {
			$.each(this._notifications, function(i, not) {
				var notification = $("<div class='ps-notification__wrapper'></div>");
				notification.html(not).hide();
				notification.appendTo(_self.popover_list).fadeIn('slow');
			});

			$(elem).trigger("notifications.shown", [$.extend(elem, this)]);
			// open in a new tab if opened page is backend page
			if ( $(document.body).hasClass("wp-admin") ) {
				$(elem).find("a").attr("target", "_blank");
			}
			this.popover_list.toggleClass("ps-notifications--empty", 0 === this.popover_list.find('.ps-notification__wrapper').length);
		};

		this.show = function() {
			this.popover.slideToggle({
				duration: "fast",
				start: function() {
					_self.popover.position({
						my: "right top",
						at: "bottom",
						of: $('i', elem),
						using: function(position, data) {
							if ('right' === data.horizontal) {
								$(this).removeClass('flipped');
								position.left += 40;
							} else {
								$(this).addClass('flipped');
								position.left -= 40;
							}

							position.top += 10;

							$(this).css(position);
						}
					});
				},
				done: function() {
					$(document).on("mouseup.notification_click", function(e) {
						if (!$(elem).is(e.target) && 0 === $(elem).has(e.target).length) {
							_self.popover.hide();
							$(document).off("mouseup.notification_click");
						}
					});
				}
			});
		};

		this.init_pagination = function() {
			this.popover_list.on('scroll', function() {
				if (_self._content_is_fetched && $(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
					_self._content_is_fetched = false;
					_self.load_page(function() {
						if ( _self._notifications && _.isEmpty( _self._notifications ) ) { // Check empty array.
							_self.popover_list.off('scroll');
						} else {
							_self.popover_list.trigger("scroll");
						}
					});
				}
			});
		};

		this.load_page = function(callback) {
			if (false === this._content_is_fetched) {
				var error = this.popover_list.nextAll('.ps-popover-error'),
					loading = this.popover_list.nextAll('.ps-popover-loading');

				if (error.length) {
					error.remove();
				}

				if (!loading.length) {
					loading = $("<div class='ps-popover-loading'><img src='" + peepsodata.loading_gif + "'/></div>");
					this.popover_list.after(loading);
				}

				this.fetch_stop();
				setTimeout(
					function() {
						_self.fetch(function() {
							loading.remove();

							if (_self._errors) {
								error = $('<div class=ps-popover-error />');
								$.each(_self._errors, function(i, msg) {
									$('<div />').html(msg).appendTo(error);
								});
								_self.popover_list.after(error);
							}

							_self.render();

							if (typeof(callback) === typeof(Function))
								callback();

							if (typeof _self.opts.after_load === "function")
								_self.opts.after_load.apply(_self);
						});
					},
					500
				);
			}
		};

		this.clear_cache = function() {
			this.popover_list.find('.ps-notification__wrapper').remove();
			this.popover.hide();
			this.opts.request.page = 1;
			this._content_is_fetched = false;
		};

		this.init(options);
		$(elem).on("click", this.onClick);

		return this;
	}


	$.fn.psnotification = function(methodOrOptions) {
		return this.each(function () {
            if (!$.data(this, 'plugin_psnotification')) {
                $.data(this, 'plugin_psnotification',
                new PsPopoverNotification( this, methodOrOptions ));
            } else {
            	var _self = $.data(this, 'plugin_psnotification');

            	if (_.isFunction(_self[methodOrOptions]))
            		return _self[methodOrOptions].call(_self);
            }
        });
	};

	ps_observer.add_action("notification_clear_cache", function( key ) {
		key = key || 'ps-js-notifications';
		$("." + key ).psnotification("clear_cache");
	}, 10, 1);

})(jQuery);

// initialise notification dropdowns
jQuery(function( $ ) {
	$('.dropdown-notification').psnotification({
		// header: peepsodata.notifications_title + " <a href='javascript:' onclick='ps_notification.mark_all_as_read(); return false;'>" + peepsodata.mark_all_as_read_text + "</a>",
		view_all_link: peepsodata.notifications_page,
		source: 'notificationsajax.get_latest',
		request: {
			per_page: 5
		},
		paging: true
	});
});
