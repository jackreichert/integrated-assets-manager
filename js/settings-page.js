(function ($) {
    $('document').ready(function () {
        $('#generate_hash').click(function (e) {
            e.preventDefault();
            $.post(
                IAM_Ajax.ajaxurl,
                {
                    action: 'IAM_settings',
                    iamSettingsNonce: IAM_Ajax.iamSettingsNonce
                },
                function (response) {
                    var td = $('#asset_hash').closest('td');
                    $('#asset_hash').val(response);

                    if (!$('p', td).length) {
                        $("#regenHashNotice").addClass("updated notice").text('Save changes to implement new hash.');
                    }
                }
            );

        });
    });
})(jQuery);
