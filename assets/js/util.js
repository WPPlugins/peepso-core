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
