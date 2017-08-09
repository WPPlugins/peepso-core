(function( peepso, factory ) {

	var moduleName = 'PsObserver';
	var moduleObject = factory( moduleName );

	// Deprecated, do not use these functions!
	_.extend( moduleObject.prototype, /** @lends peepso.PsObserver.prototype */ {

		/**
		 * @deprecated Kept for backward compatibility. Use addFilter instead.
		 * @function
		 */
		add_filter: moduleObject.prototype.addFilter,

		/**
		 * @deprecated Kept for backward compatibility. Use removeFilter instead.
		 * @function
		 */
		remove_filter: moduleObject.prototype.removeFilter,

		/**
		 * @deprecated Kept for backward compatibility. Use applyFilters instead.
		 * @function
		 */
		apply_filters: moduleObject.prototype.applyFilters,

		/**
		 * @deprecated Kept for backward compatibility. Use addAction instead.
		 * @function
		 */
		add_action: moduleObject.prototype.addAction,

		/**
		 * @deprecated Kept for backward compatibility. Use removeAction instead.
		 * @function
		 */
		remove_action: moduleObject.prototype.removeAction,

		/**
		 * @deprecated Kept for backward compatibility. Use doAction instead.
		 * @function
		 */
		do_action: moduleObject.prototype.doAction
	});

	// Export module to peepso namespace.
	peepso[ moduleName ] = moduleObject;

	/**
	 * System-wide observer object.
	 * @type {peepso.PsObserver}
	 */
	peepso.observer = new moduleObject();

	// deprecated, do not use these!
	PsObserver = moduleObject;
	ps_observer = peepso.observer;

})( peepso, function( moduleName ) {

	/**
	 * PsObserver class.
	 * @class peepso.PsObserver
	 */
	return peepso.createClass( moduleName, /** @lends peepso.PsObserver.prototype */ {

		/**
		 * Filter and action functions cache.
	     * @type {Object|undefined}
	     * @private
		 */
		_filters: undefined,

		/**
		 * Incremental function identification.
		 * @type {Number}
		 * @private
		 */
		_guid: 1,

		/**
		 * Add filter hook to allow peepso extensions to modify various types of internal data at runtime.
		 * @param {String} name The name of the filter to hook the <code>fn</code> callback to.
		 * @param {Function} fn The callback to be run when the filter is applied.
		 * @param {Number} [priority=10] Used to specify the order in which the functions associated with a particular action are executed.
		 * Lower numbers correspond with earlier execution, and functions with the same priority are executed
		 * in the order in which they were added to the action.
		 * @param {Number} [numParam=0] The number of parameters the function accepts.
		 * @param {Object} [context] The context in which the <code>fn</code> callback will be called.
		 */
		addFilter: function( name, fn, priority, numParam, context ) {
			var guid, filter;

			if ( typeof fn !== 'function' ) {
				return;
			}

			priority = priority || 10;
			guid = fn.psObserverID = fn.psObserverID || this._guid++;

			filter = {
				fn: fn,
				priority: priority,
				numParam: numParam,
				context: context
			};

			if ( ! this._filters ) {
				this._filters = {};
			}

			if ( ! this._filters[ name ] ) {
				this._filters[ name ] = {};
			}

			if ( ! this._filters[ name ][ priority ] ) {
				this._filters[ name ][ priority ] = {};
			}

			this._filters[ name ][ priority ][ guid ] = filter;
		},

		/**
		 * Remove filter hook previously added via <code>addFilter</code> method.
		 * @param {String} name The action hook to which the function to be removed is hooked.
		 * @param {Function} fn The callback for the function which should be removed.
		 * @param {Number} [priority=10] The priority of the function (as defined when the function was originally hooked).
		 */
		removeFilter: function( name, fn, priority ) {
			var guid;

			if ( typeof fn !== 'function' ) {
				return;
			}

			priority = priority || 10;
			guid = fn.psObserverID;

			if ( guid && this._filters && this._filters[ name ] && this._filters[ name ][ priority ] && this._filters[ name ][ priority ][ guid ] ) {
				delete this._filters[ name ][ priority ][ guid ];
			}
		},

		/**
		 * Call the functions added to a filter hook.
		 * @param {String} name The action hook to which the function to be removed is hooked.
		 * @param {mixed} value The value on which the filters hooked to <code>name</code> are applied on.
		 * @param {...mixed} [vars] Additional variables passed to the functions hooked to <code>name</code>.
		 * @return {mixed} The filtered value after all hooked functions are applied to it.
		 */
		applyFilters: function( name ) {
			var args = arguments,
				data = '',
				filters = this._filters && this._filters[ name ],
				priority, guid, filter, fn_args, index;

			if ( ! filters ) {
				return args[1];
			}

			for ( priority in filters ) {
				for ( guid in filters[ priority ] ) {
					filter = filters[ priority ][ guid ];
					if ( filter.numParam ) {
						fn_args = [];
						index = 1;
						while ( index <= filter.numParam ) {
							fn_args.push( args[ index ] );
							index++;
						}
						try {
							data = filter.fn.apply( filter.context, fn_args );
							args[1] = data;
						} catch ( e ) {}
					} else {
						try {
							data = filter.fn();
						} catch ( e ) {}
					}
				}
			}

			return data;
		},

		/**
		 * Add action hook to allow peepso extensions to listen when specific events occur at runtime.
		 * @param {String} name The name of the action to hook the <code>fn</code> callback to.
		 * @param {Function} fn The callback to be run when the action is applied.
		 * @param {Number} [priority=10] Used to specify the order in which the functions associated with a particular action are executed.
		 * Lower numbers correspond with earlier execution, and functions with the same priority are executed
		 * in the order in which they were added to the action.
		 * @param {Number} [numParam=0] The number of parameters the function accepts.
		 * @param {Object} [context] The context in which the <code>fn</code> callback will be called.
		 */
		addAction: function( name, fn, priority, numParam, context ) {
			this.addFilter( name, fn, priority, numParam, context );
		},

		/**
		 * Remove action hook previously added via <code>addAction</code> method.
		 * @param {String} name The action hook to which the function to be removed is hooked.
		 * @param {Function} fn The callback for the function which should be removed.
		 * @param {Number} [priority=10] The priority of the function (as defined when the function was originally hooked).
		 */
		removeAction: function( name, fn, priority ) {
			this.removeFilter( name, fn, priority );
		},

		/**
		 * Call the functions added to a action hook.
		 * @param {String} name The action hook to which the function to be removed is hooked.
		 * @param {mixed} value The value on which the actions hooked to <code>name</code> are applied on.
		 * @param {...mixed} [vars] Additional variables passed to the functions hooked to <code>name</code>.
		 */
		doAction: function( name ) {
			var args = arguments,
				actions = this._filters && this._filters[ name ],
				priority, guid, action, fn_args, index;

			if ( ! actions ) {
				return;
			}

			for ( priority in actions ) {
				for ( guid in actions[ priority ] ) {
					action = actions[ priority ][ guid ];
					fn_args = [];
					if ( action.numParam ) {
						index = 1;
						while ( index <= action.numParam ) {
							fn_args.push( args[ index ] );
							index++;
						}
					}
					try {
						action.fn.apply( action.context, fn_args );
					} catch ( e ) {}
				}
			}
		}

	});

});
