(function( $, peepso, factory ) {

/**
 * PsComment global instance.
 * @name peepso.comment
 * @type {PsComment}
 */
peepso.comment = new (factory( $, peepso ));

})( jQuery, peepso, function( $, peepso ) {

/**
 * Handle commenting.
 * @class PsComment
 */
function PsComment() {
	this.init();
}

peepso.npm.objectAssign( PsComment.prototype, /** @lends PsComment.prototype */ {
	/**
	 * Initialize commenting.
	 */
	init: function() {
		this.ajax = {};

		// reveal comment on single activity view which ids are defined in the url hash
		// for example `#comment=00.11.22.33` will be translated as follow:
		//   00 = post's act_id
		//   22 = comment's post_id
		//   11 = comment's act_id
		//   33 = reply's act_id (optional, if you want to show reply)
		$( $.proxy(function() {
			var hash = window.location.hash || '',
				reComment = /[#&]comment=(\d+)\.(\d+)(?:\.(\d+)(?:\.(\d+))?)?/,
				data = hash.match( reComment );

			if ( data && data[2] ) {
				this.reveal( data[1], data[2], data[3], data[4] );
			}
		}, this ));
	},

	/**
	 * TODO: docblock
	 */
	add: function() {},

	/**
	 * TODO: docblock
	 */
	edit: function() {},

	/**
	 * Reply to a comment.
	 */
	reply: function( act_id, post_id, elem, data ) {
		var $comment, $btn, $container, $textarea, nested,
			parentID = '#comment-item-'+ post_id;

		if ( elem ) {
			$comment = $( elem ).closest( parentID );
		} else {
			$comment = $( parentID );
		}

		$btn = $comment.find('.actaction-reply');
		nested = $btn.closest('.ps-comment').hasClass('ps-comment-nested');

		if ( nested ) {
			$container = $btn.closest('.ps-comment').children('.ps-comment-reply');
			$textarea = $container.find('textarea');
		} else {
			$container = $btn.closest('.ps-comment-item').next('.ps-comment-nested').children('.ps-comment-reply');
			$textarea = $container.find('textarea');
		}

		if ( $container.not(':visible') ) {
			$container.show();
		}

		$textarea.focus();

		data = data || {};
		ps_observer.apply_filters('comment.reply', $textarea, $.extend({}, data, { act_id: act_id, post_id: post_id }));
	},

	/**
	 * TODO: docblock
	 */
	show_previous: function( act_id, elem ) {
		var $ct, $more, $loading,
			parentID = '.ps-js-comment-container--' + act_id;

		if ( elem ) {
			$ct = $( elem ).closest( parentID );
		} else {
			$ct = $( parentID );
		}

		$more = $ct.find('.ps-js-comment-more');
		$loading = $more.find('.ps-js-loading');

		function getPrevious( callback ) {
			var $first = $ct.children('.cstream-comment:first');

			$loading.removeClass('hidden');
			peepso.postJson('activity.show_previous_comments', {
				act_id: act_id,
				uid: peepsodata.currentuserid,
				first: $first.data('comment-id')
			}, function( json ) {
				$loading.addClass('hidden');
				$first.before( json.data.html );
				if ( json.data.comments_remain > 0 ) {
					$more.find('a').html( json.data.comments_remain_caption );
				} else {
					$more.remove();
				}
				$( document ).trigger('ps_comment_added');
				callback();
			});
		}

		return $.Deferred(function( defer ) {
			getPrevious(function() {
				defer.resolve();
			});
		});
	},

	/**
	 * TODO: docblock
	 */
	show_all: function( act_id ) {
		var $ct = $('.ps-js-comment-container--' + act_id ),
			$more = $ct.children('.ps-js-comment-more'),
			$loading = $more.find('.ps-js-loading');

		function getPrevious( callback ) {
			var $first = $ct.children('.cstream-comment:first');

			$loading.removeClass('hidden');
			peepso.postJson('activity.show_previous_comments', {
				act_id: act_id,
				uid: peepsodata.currentuserid,
				all: 1,
				first: $first.data('comment-id')
			}, function( json ) {
				$loading.addClass('hidden');
				$first.before( json.data.html );
				if ( json.data.comments_remain > 0 ) {
					$more.find('a').html( json.data.comments_remain_caption );
					getPrevious( callback );
				} else {
					$more.remove();
					$( document ).trigger('ps_comment_added');
					callback();
				}
			});
		}

		return $.Deferred(function( defer ) {
			getPrevious(function() {
				defer.resolve();
			});
		});
	},

	/**
	 * TODO: docblock
	 */
	reveal_comment: function( container_id, comment_id ) {
		return $.Deferred( $.proxy(function( defer ) {
			var $comment = $('#comment-item-' + comment_id );
			if ( $comment.length ) {
				defer.resolve();
			} else {
				this.show_all( container_id ).done( $.proxy(function() {
					defer.resolve();
				}, this ));
			}
		}, this ));
	},

	/**
	 * TODO: docblock
	 */
	reveal: function( post_act_id, comment_post_id, comment_act_id, reply_act_id ) {
		// hightligh and scroll to particular comment
		function highlight( $comment ) {
			var color = $comment.find('a.ps-comment-user').eq(0).css('color'),
				scrollTop = $comment.offset().top - (( $( window ).height() - $comment.outerHeight() ) / 2 );
			$comment.css({ backgroundColor: color });
			$comment.css({ transition: 'background-color 2s ease' });
			$('html, body').delay( 50 ).animate({ scrollTop: scrollTop }, 500, function() {
				$comment.css({ backgroundColor: '' });
			});
		}

		this.reveal_comment( post_act_id, comment_post_id ).done( $.proxy(function() {
			var $comment;

			if ( ! reply_act_id ) {
				$comment = $('#comment-item-' + comment_post_id );
				highlight( $comment );
			} else {
				this.reveal_comment( comment_act_id, reply_act_id ).done(function() {
					$comment = $('#comment-item-' + reply_act_id );
					highlight( $comment );
				});
			}
		}, this ));
	}
});

return PsComment;

});
