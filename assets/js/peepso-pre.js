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
