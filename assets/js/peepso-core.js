/**
 * PeepSo namespace initialization.
 * Requires jQuery and Underscore to be loaded.
 */
(function( root, $, _ ) {

	/**
	 * PeepSo global namespace.
	 * @namespace peepso
	 */
	var peepso = {};

	/**
	 * PeepSo class creation wrapper.
	 * @memberof peepso
	 * @param {String} name Class name, will be used as a class identifier.
	 * @param {...Object} [mixins] List of mixin objects in which created class should be mixed from.
	 * @param {Object} properties Properties and methods to be attached into created class.
	 * @return {Function} PeepSo class.
	 */
	peepso.createClass = function( name, mixins, properties ) {
		var i;

		function PsObject() {
			this.__constructor.apply( this, arguments );
		}

		PsObject.prototype = {};

		// Copy mixins properties.
		for ( i = 1; i < arguments.length - 1; i++ ) {
			$.extend( PsObject.prototype, arguments[ i ].prototype );
		}

		// Copy properties.
		$.extend( PsObject.prototype, {
			__name: name,
			__constructor: function() {},
		}, arguments[ i ] || {} );

		return PsObject;
	};

	// Assign namespace to global object.
	root.peepso = peepso;

})( window, jQuery, _ );

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

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

function EventEmitter() {
  this._events = this._events || {};
  this._maxListeners = this._maxListeners || undefined;
}
module.exports = EventEmitter;

// Backwards-compat with node 0.10.x
EventEmitter.EventEmitter = EventEmitter;

EventEmitter.prototype._events = undefined;
EventEmitter.prototype._maxListeners = undefined;

// By default EventEmitters will print a warning if more than 10 listeners are
// added to it. This is a useful default which helps finding memory leaks.
EventEmitter.defaultMaxListeners = 10;

// Obviously not all Emitters should be limited to 10. This function allows
// that to be increased. Set to zero for unlimited.
EventEmitter.prototype.setMaxListeners = function(n) {
  if (!isNumber(n) || n < 0 || isNaN(n))
    throw TypeError('n must be a positive number');
  this._maxListeners = n;
  return this;
};

EventEmitter.prototype.emit = function(type) {
  var er, handler, len, args, i, listeners;

  if (!this._events)
    this._events = {};

  // If there is no 'error' event listener then throw.
  if (type === 'error') {
    if (!this._events.error ||
        (isObject(this._events.error) && !this._events.error.length)) {
      er = arguments[1];
      if (er instanceof Error) {
        throw er; // Unhandled 'error' event
      }
      throw TypeError('Uncaught, unspecified "error" event.');
    }
  }

  handler = this._events[type];

  if (isUndefined(handler))
    return false;

  if (isFunction(handler)) {
    switch (arguments.length) {
      // fast cases
      case 1:
        handler.call(this);
        break;
      case 2:
        handler.call(this, arguments[1]);
        break;
      case 3:
        handler.call(this, arguments[1], arguments[2]);
        break;
      // slower
      default:
        args = Array.prototype.slice.call(arguments, 1);
        handler.apply(this, args);
    }
  } else if (isObject(handler)) {
    args = Array.prototype.slice.call(arguments, 1);
    listeners = handler.slice();
    len = listeners.length;
    for (i = 0; i < len; i++)
      listeners[i].apply(this, args);
  }

  return true;
};

EventEmitter.prototype.addListener = function(type, listener) {
  var m;

  if (!isFunction(listener))
    throw TypeError('listener must be a function');

  if (!this._events)
    this._events = {};

  // To avoid recursion in the case that type === "newListener"! Before
  // adding it to the listeners, first emit "newListener".
  if (this._events.newListener)
    this.emit('newListener', type,
              isFunction(listener.listener) ?
              listener.listener : listener);

  if (!this._events[type])
    // Optimize the case of one listener. Don't need the extra array object.
    this._events[type] = listener;
  else if (isObject(this._events[type]))
    // If we've already got an array, just append.
    this._events[type].push(listener);
  else
    // Adding the second element, need to change to array.
    this._events[type] = [this._events[type], listener];

  // Check for listener leak
  if (isObject(this._events[type]) && !this._events[type].warned) {
    if (!isUndefined(this._maxListeners)) {
      m = this._maxListeners;
    } else {
      m = EventEmitter.defaultMaxListeners;
    }

    if (m && m > 0 && this._events[type].length > m) {
      this._events[type].warned = true;
      console.error('(node) warning: possible EventEmitter memory ' +
                    'leak detected. %d listeners added. ' +
                    'Use emitter.setMaxListeners() to increase limit.',
                    this._events[type].length);
      if (typeof console.trace === 'function') {
        // not supported in IE 10
        console.trace();
      }
    }
  }

  return this;
};

EventEmitter.prototype.on = EventEmitter.prototype.addListener;

EventEmitter.prototype.once = function(type, listener) {
  if (!isFunction(listener))
    throw TypeError('listener must be a function');

  var fired = false;

  function g() {
    this.removeListener(type, g);

    if (!fired) {
      fired = true;
      listener.apply(this, arguments);
    }
  }

  g.listener = listener;
  this.on(type, g);

  return this;
};

// emits a 'removeListener' event iff the listener was removed
EventEmitter.prototype.removeListener = function(type, listener) {
  var list, position, length, i;

  if (!isFunction(listener))
    throw TypeError('listener must be a function');

  if (!this._events || !this._events[type])
    return this;

  list = this._events[type];
  length = list.length;
  position = -1;

  if (list === listener ||
      (isFunction(list.listener) && list.listener === listener)) {
    delete this._events[type];
    if (this._events.removeListener)
      this.emit('removeListener', type, listener);

  } else if (isObject(list)) {
    for (i = length; i-- > 0;) {
      if (list[i] === listener ||
          (list[i].listener && list[i].listener === listener)) {
        position = i;
        break;
      }
    }

    if (position < 0)
      return this;

    if (list.length === 1) {
      list.length = 0;
      delete this._events[type];
    } else {
      list.splice(position, 1);
    }

    if (this._events.removeListener)
      this.emit('removeListener', type, listener);
  }

  return this;
};

EventEmitter.prototype.removeAllListeners = function(type) {
  var key, listeners;

  if (!this._events)
    return this;

  // not listening for removeListener, no need to emit
  if (!this._events.removeListener) {
    if (arguments.length === 0)
      this._events = {};
    else if (this._events[type])
      delete this._events[type];
    return this;
  }

  // emit removeListener for all listeners on all events
  if (arguments.length === 0) {
    for (key in this._events) {
      if (key === 'removeListener') continue;
      this.removeAllListeners(key);
    }
    this.removeAllListeners('removeListener');
    this._events = {};
    return this;
  }

  listeners = this._events[type];

  if (isFunction(listeners)) {
    this.removeListener(type, listeners);
  } else if (listeners) {
    // LIFO order
    while (listeners.length)
      this.removeListener(type, listeners[listeners.length - 1]);
  }
  delete this._events[type];

  return this;
};

EventEmitter.prototype.listeners = function(type) {
  var ret;
  if (!this._events || !this._events[type])
    ret = [];
  else if (isFunction(this._events[type]))
    ret = [this._events[type]];
  else
    ret = this._events[type].slice();
  return ret;
};

EventEmitter.prototype.listenerCount = function(type) {
  if (this._events) {
    var evlistener = this._events[type];

    if (isFunction(evlistener))
      return 1;
    else if (evlistener)
      return evlistener.length;
  }
  return 0;
};

EventEmitter.listenerCount = function(emitter, type) {
  return emitter.listenerCount(type);
};

function isFunction(arg) {
  return typeof arg === 'function';
}

function isNumber(arg) {
  return typeof arg === 'number';
}

function isObject(arg) {
  return typeof arg === 'object' && arg !== null;
}

function isUndefined(arg) {
  return arg === void 0;
}

},{}],2:[function(require,module,exports){
(function( peepso ) {

	/**
	 * Namespace for loaded NPM libraries.<br/>
	 * Warning: Do NOT use any of libraries listed under this namespace as they might be removed in the future without notice!
	 * @namespace peepso.npm
	 * @private
	 */
	peepso.npm = {};

	peepso.npm.EventEmitter = require( 'events' ).EventEmitter;
	peepso.npm.inherits = require( 'inherits' );
	peepso.npm.objectAssign = require( 'object-assign' );

})( peepso );

},{"events":1,"inherits":3,"object-assign":4}],3:[function(require,module,exports){
if (typeof Object.create === 'function') {
  // implementation from standard node.js 'util' module
  module.exports = function inherits(ctor, superCtor) {
    ctor.super_ = superCtor
    ctor.prototype = Object.create(superCtor.prototype, {
      constructor: {
        value: ctor,
        enumerable: false,
        writable: true,
        configurable: true
      }
    });
  };
} else {
  // old school shim for old browsers
  module.exports = function inherits(ctor, superCtor) {
    ctor.super_ = superCtor
    var TempCtor = function () {}
    TempCtor.prototype = superCtor.prototype
    ctor.prototype = new TempCtor()
    ctor.prototype.constructor = ctor
  }
}

},{}],4:[function(require,module,exports){
/*
object-assign
(c) Sindre Sorhus
@license MIT
*/

'use strict';
/* eslint-disable no-unused-vars */
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var hasOwnProperty = Object.prototype.hasOwnProperty;
var propIsEnumerable = Object.prototype.propertyIsEnumerable;

function toObject(val) {
	if (val === null || val === undefined) {
		throw new TypeError('Object.assign cannot be called with null or undefined');
	}

	return Object(val);
}

function shouldUseNative() {
	try {
		if (!Object.assign) {
			return false;
		}

		// Detect buggy property enumeration order in older V8 versions.

		// https://bugs.chromium.org/p/v8/issues/detail?id=4118
		var test1 = new String('abc');  // eslint-disable-line no-new-wrappers
		test1[5] = 'de';
		if (Object.getOwnPropertyNames(test1)[0] === '5') {
			return false;
		}

		// https://bugs.chromium.org/p/v8/issues/detail?id=3056
		var test2 = {};
		for (var i = 0; i < 10; i++) {
			test2['_' + String.fromCharCode(i)] = i;
		}
		var order2 = Object.getOwnPropertyNames(test2).map(function (n) {
			return test2[n];
		});
		if (order2.join('') !== '0123456789') {
			return false;
		}

		// https://bugs.chromium.org/p/v8/issues/detail?id=3056
		var test3 = {};
		'abcdefghijklmnopqrst'.split('').forEach(function (letter) {
			test3[letter] = letter;
		});
		if (Object.keys(Object.assign({}, test3)).join('') !==
				'abcdefghijklmnopqrst') {
			return false;
		}

		return true;
	} catch (err) {
		// We don't expect any of the above to throw, but better to be safe.
		return false;
	}
}

module.exports = shouldUseNative() ? Object.assign : function (target, source) {
	var from;
	var to = toObject(target);
	var symbols;

	for (var s = 1; s < arguments.length; s++) {
		from = Object(arguments[s]);

		for (var key in from) {
			if (hasOwnProperty.call(from, key)) {
				to[key] = from[key];
			}
		}

		if (getOwnPropertySymbols) {
			symbols = getOwnPropertySymbols(from);
			for (var i = 0; i < symbols.length; i++) {
				if (propIsEnumerable.call(from, symbols[i])) {
					to[symbols[i]] = from[symbols[i]];
				}
			}
		}
	}

	return to;
};

},{}]},{},[2]);

(function( peepso, $, factory ) {

	/**
	 * Utility functions namaspace.
	 * @namespace peepso.util
	 */
	peepso.util = factory( $ );

})( peepso, jQuery, function( $ ) {

	return /** @lends peepso.util */ {

		/**
		 * Checks whether an element is fully visible in viewport.
		 * @param {HTMLElement} el
		 * @return {Boolean}
		 */
		isElementInViewport: function( el ) {
			var rect = el.getBoundingClientRect();

			return (
				rect.top >= 0 &&
				rect.left >= 0 &&
				rect.bottom <= ( window.innerHeight || document.documentElement.clientHeight ) &&
				rect.right <= ( window.innerWidth || document.documentElement.clientWidth )
			);
		},

		/**
		 * Checks whether an element is partly visible in viewport.
		 * @param {HTMLElement} el
		 * @return {Boolean}
		 */
		isElementPartlyInViewport: function( el ) {
			var rect = el.getBoundingClientRect();

			return (
				rect.top < ( window.innerHeight || document.documentElement.clientHeight ) &&
				rect.left < ( window.innerWidth || document.documentElement.clientWidth ) &&
				rect.bottom > 0 &&
				rect.right > 0
			);
		},

		/**
		 * Scroll element into top of the viewport.
		 * @param {HTMLElement} el
		 * @param {Number} [duration=1000]
		 */
		scrollIntoView: function( el, duration ) {
			el = $( el );
			if ( el.length && ! this.isElementInViewport( el[0] ) ) {
				$( 'html, body' ).animate({
					scrollTop: Math.max( 0, el.offset().top - 10 )
				}, duration || 1000 );
			}
		},

		/**
		 * Scroll element into top of the viewport if it is not already visible.
		 * @param {HTMLElement} el
		 * @param {Number} [duration=1000]
		 */
		scrollIntoViewIfNeeded: function( el, duration ) {
			el = $( el );
			if ( el.length && ! this.isElementPartlyInViewport( el[0] ) ) {
				$( 'html, body' ).animate({
					scrollTop: Math.max( 0, el.offset().top - 10 )
				}, duration || 1000 );
			}
		},

		/**
		 * Load Facebook SDK for Javacript.
		 * @return {Promise}
		 */
		fbLoadSDK: function() {
			var js, fjs, timer, count,
				d = document,
				s = 'script',
				id = 'facebook-jssdk';

			return $.Deferred(function( defer ) {
				if ( d.getElementById( id ) ) {
					count = 0;
					timer = setInterval(function() {
						if ( ++count > 20 || window.FB ) {
							clearInterval( timer );
							if ( window.FB ) {
								defer.resolve();
							}
						}
					}, 1000 );
					return;
				}

				// Set callback handler.
				window.fbAsyncInit = function() {
					FB.init({
						version: 'v2.9', // https://developers.facebook.com/docs/apps/changelog/#versions
						status: false,
						xfbml: false
					});
					defer.resolve( FB );
					delete window.fbAsyncInit;
				};

				// Attach script to the document.
				fjs = d.getElementsByTagName( s )[ 0 ];
				js = d.createElement( s );
				js.id = id;
				js.src = '//connect.facebook.net/en_US/sdk.js';
				fjs.parentNode.insertBefore( js, fjs );
			});
		},

		/**
		 * Parse Facebook XFBML tags found in the document.
		 */
		fbParseXFBML: function() {
			var unrenderedLength = $('.fb-post, .fb-video').not('[fb-xfbml-state=rendered]').length;

			// Parse unrendered XFBML tag.
			if ( unrenderedLength ) {
				this.fbLoadSDK().done(function() {
					FB.XFBML.parse();
				});
			}
		}

	};

});
