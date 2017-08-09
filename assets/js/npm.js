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
