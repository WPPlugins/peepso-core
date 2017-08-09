(function( root, factory ) {

	var moduleName = 'PsAvatarDialog';
	var moduleObject = factory( moduleName, root.jQuery, root.PsAvatar );

	// export module
	if ( typeof module === 'object' && module.exports ) {
		module.exports = moduleObject;
	} else {
		root[ moduleName ] = moduleObject;
	}

})( window, function( moduleName, $, PsAvatar ) {

	return peepso.createClass( moduleName, {

		/**
		 * Class constructor.
		 * @param {Object} data
		 */
		__constructor: function( data ) {
			this._data = _.extend({}, data );
			this._isCurrent = true;
			this._canFinalize = false;

			this.$el = null;
		},

		/**
		 * Avatar dialog template.
		 * @type {String}
		 */
		template: peepsodata.avatar.templateDialog,

		/**
		 * Initialize avatar dialog.
		 * @return {PsAvatarDialog}
		 */
		init: function() {
			var data, html;

			if ( ! this.$el ) {
				data = _.extend({}, this._data, { myId: +peepsodata.currentuserid });
				html = peepso.template( this.template || '' )( data );
				this.$el = $( html ).hide();
				this.$file = this.$el.find('input[type=file]');
				this.$hasAvatar = this.$el.find('.ps-js-has-avatar');
				this.$noAvatar = this.$el.find('.ps-js-no-avatar');
				this.$preview = this.$el.find('.ps-js-preview');
				this.$avatar = this.$el.find('.ps-js-avatar');
				this.$error = this.$el.find('.ps-js-error');
				this.$btnRemove = this.$el.find('.ps-js-btn-remove');
				this.$btnCrop = this.$el.find('.ps-js-btn-crop');
				this.$btnCropCancel = this.$el.find('.ps-js-btn-crop-cancel');
				this.$btnCropConfirm = this.$el.find('.ps-js-btn-crop-save');
				this.$btnFinalize = this.$el.find('.ps-js-btn-finalize').attr('disabled', 'disabled');
				this.$disabler = this.$el.find('.ps-js-disabler').hide();

				// handle user interaction
				this.$el.on('click', '.ps-js-btn-upload', $.proxy( this.onUpload, this ));
				this.$el.on('click', '.ps-js-btn-remove', $.proxy( this.onRemove, this ));
				this.$el.on('click', '.ps-js-btn-gravatar', $.proxy( this.onUseGravatar, this ));
				this.$el.on('click', '.ps-js-btn-crop', $.proxy( this.onCrop, this ));
				this.$el.on('click', '.ps-js-btn-crop-cancel', $.proxy( this.onCropCancel, this ));
				this.$el.on('click', '.ps-js-btn-crop-save', $.proxy( this.onCropConfirm, this ));
				this.$el.on('click', '.ps-js-btn-finalize', $.proxy( this.onFinalize, this ));
				this.$el.on('click', '.ps-js-btn-close', $.proxy( this.onClose, this ));

				this.$el.appendTo( document.body );

				// Initialize avatar editing class.
				this.avatar = new PsAvatar( this.$file );
				this.avatar
					// Handle upload submit.
					.on('uploadsubmit', $.proxy(function() {
						this.cropDetach();
						this.disable();
					}, this))
					// Handle upload success.
					.on('uploaddone', $.proxy(function( imgAvatar, imgOriginal ) {
						this.updateAvatar( imgAvatar, imgOriginal );
						this._isCurrent = false;
						this.canFinalize( true );
						this.enable();
					}, this ))
					// Handle upload error.
					.on('uploadfail', $.proxy(function( error ) {
						this.canFinalize( false );
						if ( _.isArray( error ) ) {
							error = error.join('<br />');
						}
						this.$error.html( error ).show();
						this.enable();
					}, this ));
			}

			return this;
		},

		/**
		 * Show avatar dialog.
		 * @return {PsAvatarDialog}
		 */
		show: function() {
			if ( this.init() ) {
				this.$el.show();
			}
			return this;
		},

		/**
		 * Hide avatar dialog.
		 * @return {PsAvatarDialog}
		 */
		hide: function() {
			if ( this.$el ) {
				this.$el.remove();
				this.$el = null;
			}
			return this;
		},

		/**
		 * Disable user to interact with avatar dialog.
		 * @return {PsAvatarDialog}
		 */
		disable: function() {
			if ( this.$el ) {
				clearTimeout( this._enableTimer );
				this.$disabler.stop().show();
				this.freezeFinalize();
			}
			return this;
		},

		/**
		 * Enable user to interact with avatar dialog.
		 * @return {PsAvatarDialog}
		 */
		enable: function() {
			if ( this.$el ) {
				clearTimeout( this._enableTimer );
				this._enableTimer = setTimeout( $.proxy(function() {
					this.$disabler.stop().fadeOut();
					this.resetFinalize();
				}, this ), 500 );
			}
			return this;
		},

		/**
		 * Update avatar image on avatar dialog.
		 * @param {String} imgAvatar
		 * @param {String} [imgOriginal]
		 * @return {PsAvatarDialog}
		 */
		updateAvatar: function( imgAvatar, imgOriginal ) {
			var $img,
				ts = '?_t=' + (new Date).getTime();

			this.$avatar.find('img').attr('src', imgAvatar + ts );
			if ( imgOriginal !== false ) {
				if ( imgOriginal ) {
					$img = this.$preview.find('img');
					$img.attr('src', imgOriginal + ts );
				}
				this.$btnRemove.show();
				this.$noAvatar.hide();
				this.$hasAvatar.show();
			} else {
				this.$preview.find('img').removeAttr('src');
				this.$btnRemove.hide();
				this.$hasAvatar.hide();
				this.$noAvatar.show();
			}
		},

		/**
		 * Handle click event on upload button.
		 * @param {HTMLEvent} e
		 */
		onUpload: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.$error.hide();
			this.$file.click();
		},

		/**
		 * Handle remove avatar button on avatar dialog.
		 * @param {HTMLEvent} e
		 */
		onRemove: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.disable();
			this.avatar.remove().done( $.proxy(function( imgAvatar ) {
				this.updateAvatar( imgAvatar, false );
				// TODO: Should utilise peepso-observer instead of reloading the page.
				window.location.reload();
			}, this )).always( $.proxy(function() {
				this.enable();
			}, this ));
		},

		/**
		 * Handle use gravatar button on avatar dialog.
		 * @param {HTMLEvent} e
		 */
		onUseGravatar: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.disable();
			this.avatar.useGravatar().done( $.proxy(function( imgAvatar ) {
				this.updateAvatar( imgAvatar, false );
				this._isCurrent = false;
				this.canFinalize( true );
			}, this )).always( $.proxy(function() {
				this.enable();
			}, this ));
		},

		/**
		 * Handle done button on avatar dialog.
		 * @param {HTMLEvent} e
		 */
		onFinalize: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.disable();
			this.avatar.finalize().done( $.proxy(function( imgAvatar ) {
				// TODO: Should utilise peepso-observer instead of reloading the page.
				window.location.reload();
			}, this )).always( $.proxy(function() {
				this.enable();
			}, this ));
		},

		/**
		 * Handle close button on avatar dialog.
		 * @param {HTMLEvent} e
		 */
		onClose: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.cropDetach();
			this.hide();
		},

		// -----------------------------
		// Handle cropping functionality
		// -----------------------------

		/*
		 * Attach cropping layer.
		 */
		cropAttach: function() {
			var $img = this.$preview.find('img');

			// Attach cropping layer.
			ps_crop.init({
				elem: $img,
				change: $.proxy(function( coords ) {
					this._cropCoords = coords;
				}, this )
			});

			// Toggle cropping buttons.
			this.$btnCrop.hide();
			this.$btnCropCancel.show();
			this.$btnCropConfirm.show();

			// Freeze finalize button on cropping mode.
			this.freezeFinalize();
		},

		/**
		 * Detach cropping layer.
		 */
		cropDetach: function() {
			var $img = this.$preview.find('img');

			// Detach cropping layer.
			ps_crop.detach( $img );

			// Toggle cropping buttons.
			this.$btnCropCancel.hide();
			this.$btnCropConfirm.hide();
			this.$btnCrop.show();

			// Reset finalize button to its previous state.
			this.resetFinalize();
		},

		/**
		 * Get cropping layer position.
		 * @return {Object}
		 */
		cropCoords: function() {
			var width, height, params,
				$img = this.$preview.find('img'),
				coords = this._cropCoords,
				ratio = 1,
				maxWH = 800,
				resize = false;

			// Calculate ratio of resized image on this dialog relative to its actual dimension.
			if ( $img[0].naturalWidth ) {
				width = $img[0].naturalWidth || $img.width();
				height = $img[0].naturalHeight || $img.height();

				// Reduce large dimension images.
				if (( width > maxWH ) || ( height > maxWH )) {
					ratio = maxWH / Math.max( width, height );
					width = width * ratio;
					height = height * ratio;
					resize = true;
				}

				ratio = width / $img.width();
			}

			params = {
				x1: Math.floor( ratio * coords.x ),
				y1: Math.floor( ratio * coords.y ),
				x2: Math.floor( ratio * ( coords.x + coords.width ) ),
				y2: Math.floor( ratio * ( coords.y + coords.height ) ),
			};

			if ( resize ) {
				params.width = width;
				params.height = height;
			}

			return params;
		},

		/**
		 * Confirm crop avatar image.
		 */
		cropConfirm: function() {
			var coords = this.cropCoords();

			this.disable();
			this.avatar.crop(
				coords.x1,
				coords.y1,
				coords.x2,
				coords.y2,
				coords.width,
				coords.height
			).done( $.proxy(function( imgAvatar ) {
				this.cropDetach();
				this.updateAvatar( imgAvatar );
				// TODO: Should utilise peepso-observer instead of reloading the page.
				if ( this._isCurrent ) {
					window.location.reload();
				}
			}, this )).always( $.proxy(function() {
				this.enable();
			}, this ));
		},

		/**
		 * Handle crop button on avatar dialog.
		 * @param {HTMLEvent} e
		 */
		onCrop: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.cropAttach();
		},

		/**
		 * Handle cancel crop button on avatar dialog.
		 * @param {HTMLEvent} e
		 */
		onCropCancel: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.cropDetach();
		},

		/**
		 * Handle confirm crop button on avatar dialog.
		 * @param {HTMLEvent} e
		 */
		onCropConfirm: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.cropConfirm();
		},

		// ----------------------------
		// Handle finalize button state
		// ----------------------------

		/**
		 * Set finalize button enable or disable. Omitting parameter will return current state.
		 * @param {Boolean} [state]
		 * @return {Boolean}
		 */
		canFinalize: function( state ) {
			if ( ! _.isUndefined( state ) ) {
				if ( state ) {
					this._canFinalize = true;
					this.$btnFinalize.removeAttr('disabled');
				} else {
					this._canFinalize = false;
					this.$btnFinalize.attr('disabled', 'disabled');
				}
			}

			return this._canFinalize;
		},

		/**
		 * Freeze finalize button temporarily.
		 */
		freezeFinalize: function() {
			this.$btnFinalize.attr('disabled', 'disabled');
		},

		/**
		 * Reset finalize button to its actual state.
		 */
		resetFinalize: function() {
			this.canFinalize( this.canFinalize() );
		}

	});

});
