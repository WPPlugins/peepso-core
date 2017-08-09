<?php
if (!defined('ABSPATH'))
{
	exit;
}

$confirmation_message = apply_filters('peepso_uninstall_confirmation_message', '');
?>
<script type="text/javascript">
	(function($) {
		var modalHtml =
				'<div class="ps-dialog__wrapper <?php echo empty($confirmation_message) ? ' no-confirmation-message' : ''; ?>">'
				+ '	<div class="ps-dialog">'
				+ '     <h2 class="ps-dialog__title"><?php echo __('Plugin Usage Feedback', 'peepso-core'); ?></h2>'
				+ '		<div class="ps-dialog__body">'
				+ '			<div class="ps-dialog__panel" data-panel-id="confirm"><?php echo $confirmation_message; ?></div>'
				+ '			<div class="ps-dialog__panel active" data-panel-id="reasons">'
				+ '			<h3 class="ps-dialog__subtitle"><?php echo __('Please tell us how can we make this plugin better for you?', 'peepso-core'); ?></h3>'
				+ '			<select name="peepso-deactivation-reason-dropdown">'
				+ '				<option>Select Reason</option>'
				+ '				<option value="not_for_me"><?php echo __("It\'s not what I\'m looking for", 'peepso-core'); ?></option>'
				+ '				<option value="found_alternative"><?php echo __("I Found a better alternative", 'peepso-core'); ?></option>'
				+ '				<option value="technical_problems"><?php echo __("It didn\'t work for me, technical problems", 'peepso-core'); ?></option>'
				+ '				<option value="other"><?php echo __("Other", 'peepso-core'); ?></option>'
				+ '			</select>'
				+ '			<textarea name="peepso-deactivation-reason-textarea" rows="5"></textarea>'
				+ '			<input name="plugin-slug" type="hidden" />'
				+ '			<input name="peepso-deactivation-reason" type="hidden" />'
				+ '			<p><?php echo __("If you\'re experiencing technical problems please consider contacting our support. You can open a support ticket here: ", 'peepso-core'); ?>'
				+ '			<a href="https://peepso.com/my-account/" target="_blank" style="text-decoration:none;">https://PeepSo.com/my-account/</a></p>'
				+ '			<span class="ps-form__helper"><?php echo __('Your feedback will only be used to improve this plugin', 'peepso-core'); ?></span>'
				+ '			</div>'
				+ '		</div>'
				+ '		<div class="ps-dialog__footer">'
				+ '			<a href="#" class="button button-primary button-close"><?php echo _x('Cancel', 'the text of the cancel button of the plugin deactivation dialog box.', 'peepso-core'); ?></a>'
				+ '			<a href="#" class="button button-secondary button-deactivate"></a>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
				$modal = $(modalHtml),
				$deactivateLink = $('#the-list .deactivate > i.peepso-slug').prev();

		$modal.appendTo($('body'));
		$dropdown = $('[name="peepso-deactivation-reason-dropdown"]');
		$textarea = $('[name="peepso-deactivation-reason-textarea"]');
		$reason = $('[name="peepso-deactivation-reason"]');

		$textarea.hide();
		registerEventHandlers();

		function registerEventHandlers() {
			var $currentLink;

			$deactivateLink.click(function(evt) {
				evt.preventDefault();
				var slug = jQuery(this).next().data('slug');
				$slug = $modal.find('input[type="hidden"][name="plugin-slug"]');
				$slug.val(slug);
				$currentLink = $(this);
				peepsoShowModal();
			});

			$dropdown.on('change', function() {
				$reason.val($(this).val());
				if ($(this).val() == 'other') {
					$textarea.show();
				} else {
					$textarea.hide();
				}
			});

			$textarea.on('keyup', function() {
				$reason.val($(this).val());
			});

			$modal.on('click', '.button', function(evt) {
				evt.preventDefault();

				if ($(this).hasClass('disabled')) {
					return;
				}

				var _parent = $(this).parents('.ps-dialog__wrapper:first');
				var _this = $(this);

				if (_this.hasClass('allow-deactivate')) {
					$.ajax({
						url: ajaxurl,
						method: 'POST',
						data: {
							'action': 'submit-uninstall-reason',
							'plugin_slug': 'peepso',
							'deactivation_reason': (0 !== $reason.length) ? $reason.val().trim() : ''
						},
						beforeSend: function() {
							_parent.find('.button').addClass('disabled');
							_parent.find('.button-secondary').text('Processing...');
						},
						complete: function() {
							// Do not show the dialog box, deactivate the plugin.
							window.location.href = $currentLink.attr('href');
						}
					});
				} else if (_this.hasClass('button-deactivate')) {
					// Change the Deactivate button's text and show the reasons panel.
					_parent.find('.button-deactivate').addClass('allow-deactivate');
					peepsoShowPanel('reasons');
				}
			});

			// If the user has clicked outside the window, cancel it.
			$modal.on('click', function(evt) {
				var $target = $(evt.target);

				// If the user has clicked anywhere in the modal dialog, just return.
				if ($target.hasClass('ps-dialog__body') || $target.hasClass('ps-dialog__footer')) {
					return;
				}

				// If the user has not clicked the close button and the clicked element is inside the modal dialog, just return.
				if (!$target.hasClass('button-close') && ($target.parents('.ps-dialog__body').length > 0 || $target.parents('.ps-dialog__footer').length > 0)) {
					return;
				}

				peepsoCloseModal();
			});
		}

		function peepsoShowModal() {
			peepsoResetModal();

			// Display the dialog box.
			$modal.css("display", "block");

			$('body').addClass('has-peepso-modal');
		}

		function peepsoCloseModal() {
			$modal.css("display", "none");

			$('body').removeClass('has-peepso-modal');
		}

		function peepsoResetModal() {
			$modal.find('.button').removeClass('disabled');

			// Uncheck all radio buttons.
			$modal.find('input[type="radio"]').prop('checked', false);

			// Remove all input fields (textfield, textarea).
			$modal.find('.reason-input').remove();

			var $deactivateButton = $modal.find('.button-deactivate');

			/*
			 * If the modal dialog has no confirmation message, that is, it has only one panel, then ensure
			 * that clicking the deactivate button will actually deactivate the plugin.
			 */
			if ($modal.hasClass('no-confirmation-message')) {
				$deactivateButton.addClass('allow-deactivate');

				peepsoShowPanel('reasons');
			} else {
				$deactivateButton.removeClass('allow-deactivate');

				peepsoShowPanel('confirm');
			}
		}

		function peepsoShowPanel(panelType) {
			$modal.find('.ps-dialog__panel').removeClass('active ');
			$modal.find('[data-panel-id="' + panelType + '"]').addClass('active');

			peepsoUpdateButtonLabels();
		}

		function peepsoUpdateButtonLabels() {
			var $deactivateButton = $modal.find('.button-deactivate');

			// Reset the deactivate button's text.
			if ('confirm' === peepsoGetCurrentPanel()) {
				$deactivateButton.text('<?php echo __('Yes - Deactivate', 'peepso-core'); ?>');
			} else {
				$deactivateButton.text('<?php echo __('Deactivate', 'peepso-core'); ?>');
			}
		}

		function peepsoGetCurrentPanel() {
			return $modal.find('.ps-dialog__panel.active').attr('data-panel-id');
		}
	})(jQuery);
</script>
