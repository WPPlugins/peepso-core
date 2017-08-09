(function( $, _ ) {

var autosize = _.debounce(function( textarea ) {
	textarea.style.height = '';
	textarea.style.height = +textarea.scrollHeight + 2 + 'px';
}, 1 );

$.fn.ps_autosize = function() {
	return this.each(function() {
		autosize( this );
		$( this ).off('input.ps-autosize').on('input.ps-autosize', function() {
			autosize( this );
		});
	});
};

})( jQuery, _ );
