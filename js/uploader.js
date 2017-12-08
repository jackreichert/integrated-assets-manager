(function ($) {
	var addedFeatures = '.media-modal-content .compat-field-IAM_enabled, .media-modal-content .compat-field-IAM_requires_login, .media-modal-content .compat-field-IAM_begin_date, .media-modal-content .compat-field-IAM_expires_date, .media-modal-content .compat-field-IAM_members_only',
		showHideFeatures = function () {
			if ($('[name=IAM_obfuscate]').prop('checked')) {
				$(addedFeatures).css('visibility', 'visible');
			} else {
				$(addedFeatures).css('visibility', 'hidden');
			}
		},
		getUpdatedSettings = function () {
			$.post(
				IAM_Ajax.ajaxurl, {
					action: 'IAM_upload',
					asset_id: $('.attachment-details').data('id') || $('#post_ID').val(),
					iamUploadNonce: IAM_Ajax.iamUploadNonce
				},
				function (response) {
					var urlSetting = $('.media-modal').find('[data-setting=url]');
					$('[type=text]', urlSetting).val(response.url);
				}
			);
		};

	// document ready function
	$('document').ready(function () {
		showHideFeatures();

		$('body').on('change', '[name=IAM_obfuscate]', function (e) {
			showHideFeatures();
			setTimeout(getUpdatedSettings, 100);
		});

		// show hide extra features if obfuscate is enabled
		$('body').on('DOMNodeInserted', 'div', function (e) {
			if ($('.media-modal', this).length) {
				showHideFeatures();
			}
		});

		// date picker
		$('body').on('click', '.compat-field-IAM_expires_date input[type=text], .compat-field-IAM_begin_date input[type=text]', function (e) {
			$(this).datetimepicker({
				format: 'F d, Y H:i',
				step: 5
			});
			$(this).datetimepicker("show");
		});

		// clear date pickers
		$('body').on('click', '.begin_clear_date', function (e) {
			e.preventDefault();
			$('.compat-field-IAM_begin_date input[type=text]').val('').change();
		});

		$('body').on('click', '.end_clear_date', function (e) {
			e.preventDefault();
			$('.compat-field-IAM_expires_date input[type=text]').val('').change();
		});
	});
})(jQuery);
