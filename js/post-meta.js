var $ = jQuery,
	WPAM = {
		UI: {
			renderAssetForm: function (assets) {
				'use strict';

				$.each(assets, function (i, asset) {
					$('#filelist').append(WPAM.UI.buildAttachmentForm(asset));
					$('#asset_attach_button').show();
				});
			},
			buildAttachmentForm: function (asset) {
				'use strict';
				var removeBtn = '<span class="dashicons dashicons-trash remove corner"></span>',
					editLink = '<span class="dashicons dashicons-edit edit corner"></span>',
					hiddenId = '<input type="hidden" class="asset_id" name="asset_id[]" value="' + asset.id + '" />',
					linkToAsset = '<a href="' + asset.link + '" target="_blank">' + asset.title + '</a>';

				return '<li id="pending_' + asset.id + '" class="asset ' + asset.status + '">' + hiddenId + linkToAsset + editLink + removeBtn + '</li>';
			}
		},
		MediaUploader: {
			render: function (id) {
				'use strict';
				var file_frame, assets;

				if (undefined !== file_frame) {
					file_frame.open();
					return;
				}

				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select assets to attach',
					frame: 'select',
					multiple: true
				});

				if ('undefined' !== typeof id) {
					file_frame.on('open', function () {
						var selection = file_frame.state().get('selection');
						var attachment = wp.media.attachment(id);
						attachment.fetch();
						selection.add(attachment ? [attachment] : []);
					});
				}

				file_frame.on('select', function () {

					// Read the JSON data returned from the Media Uploader
					assets = file_frame.state().get('selection').toJSON();

					WPAM.UI.renderAssetForm(assets);
				});

				file_frame.open();
			}
		}
	};


(function ($) {
	'use strict';
	$(document).ready(function () {
		$('#asset_attach_button').on('click', function (e) {
			e.preventDefault();
			WPAM.MediaUploader.render();
		});

		$('#filelist').on('click', '.edit', function (e) {
			var elem = $(e.target).closest('li'),
				asset_id = $('.asset_id', elem).val();
			WPAM.MediaUploader.render(asset_id);
		});

		$('#filelist').on('click', '.remove.corner', function () {
			$(this).closest('li').remove();
		});

		$('#attached_assets .assets ul').sortable();
	});
})(jQuery);