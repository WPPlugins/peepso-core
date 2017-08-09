(function( $, factory ) {

	$(function() {
		ps_widgetme = new (factory( $ ));
	});

})( jQuery, function( $ ) {

function PsWidgetMe() {
	this.$el = $('.ps-widget--profile');
	if ( this.$el.length ) {
		this.init();
	}
}

PsWidgetMe.prototype = {

	init: function() {
		var $notification = this.$el.find('.ps-widget--profile__notifications');
		if ( $notification.length ) {
			ps_observer.do_action('notification_start');
		}
	}

};

return PsWidgetMe;

});

