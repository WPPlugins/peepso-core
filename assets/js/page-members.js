(function( $, factory ) {

	var PsPageMembers = factory( $ );
	var ps_page_members = new PsPageMembers('.ps-js-members');

})( jQuery, function( $ ) {

function PsPageMembers() {
	PsPageMembers.super_.apply( this, arguments );
	$( $.proxy( this.init_page, this ) );
}

// inherit from `PsPageAutoload`
peepso.npm.inherits( PsPageMembers, PsPageAutoload );

peepso.npm.objectAssign( PsPageMembers.prototype, {

	init_page: function() {
		this._search_$query = $('.ps-js-members-query').on('input', $.proxy( this._filter, this ));
		this._search_$gender = $('.ps-js-members-gender').on('change', $.proxy( this._filter, this ));
		this._search_$sortby = $('.ps-js-members-sortby').on('change', $.proxy( this._filter, this ));
		this._search_$avatar = $('.ps-js-members-avatar').on('click', $.proxy( this._filter, this ));

		// toggle search filter form
		$('.ps-form-search-opt').on('click', $.proxy( this._toggle, this ));
		this._filter();
	},

	_search_url: 'membersearch.search',

	_search_params: {
		uid: peepsodata.currentuserid,
		user_id: peepsodata.userid,
		query: undefined,
		order_by: undefined,
		order: undefined,
		peepso_gender: undefined,
		peepso_avatar: undefined,
		limit: 2,
		page: 1
	},

	_search_render_html: function( data ) {
		if ( data.members && data.members.length ) {
			return data.members.join('');
		}
		return '';
	},

	_search_get_items: function() {
		return this._search_$ct.children('.ps-members-item-wrapper');
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
			this._fetch_xhr = peepso.disableAuth().disableError().getJson( this._search_url, params, $.proxy(function( response ) {
				if ( response.success ) {
					defer.resolveWith( this, [ response.data ]);
				} else {
					defer.rejectWith( this, [ response.errors ]);
				}
			}, this ));
		}, this ));
	},

	/**
	 * Filter search based on selected elements.
	 */
	_filter: function() {
		var query = $.trim( this._search_$query.val() ),
			sortby = this._search_$sortby.val().split('|'),
			gender = this._search_$gender.val(),
			avatar = this._search_$avatar[0].checked ? 1 : 0;

		// abort current request
		this._fetch_xhr && this._fetch_xhr.abort();

		this._search_params.query = query || undefined;
		this._search_params.order_by = sortby[0] || undefined;
		this._search_params.order = sortby[1] || undefined;
		this._search_params.peepso_gender = gender || undefined;
		this._search_params.peepso_avatar = avatar || undefined;
		this._search_params.page = 1;
		this._search();
	},

	/**
	 * Toggle search filter form.
	 */
	_toggle: function() {
		$('.ps-js-page-filters').stop().slideToggle();
	}

});

return PsPageMembers;

});
