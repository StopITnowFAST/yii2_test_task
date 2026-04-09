(function ($) {
    'use strict';

    function getConfig() {
        return window.shortLinkConfig || {};
    }

    function setLoading($btn, loading) {
        var $form = $('#short-link-form');
        $form.find('input, button').prop('disabled', loading);
        if (loading) {
            if (typeof $btn.data('orig-html') === 'undefined') {
                $btn.data('orig-html', $btn.html());
            }
            $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Проверка…');
        } else {
            $btn.html($btn.data('orig-html') || 'OK');
        }
    }

    $(function () {
        var $form = $('#short-link-form');
        if (!$form.length) {
            return;
        }

        var $btn = $('#short-link-submit');
        var $msg = $('#short-link-message');
        var $result = $('#short-link-result');
        var $img = $('#short-link-qr');
        var $link = $('#short-link-url');

        $form.on('submit', function (e) {
            e.preventDefault();

            var cfg = getConfig();
            if (!cfg.shortenUrl) {
                return;
            }

            $msg.addClass('d-none').removeClass('alert-danger alert-success').text('');
            $result.addClass('d-none');
            $img.attr('src', '');
            $link.attr('href', '#').text('');

            var payload = {
                url: $.trim($('#short-link-input').val())
            };
            payload[cfg.csrfParam] = cfg.csrfToken;

            setLoading($btn, true);

            $.ajax({
                url: cfg.shortenUrl,
                method: 'POST',
                data: payload,
                dataType: 'json'
            })
                .done(function (data) {
                    if (data.success) {
                        $img.attr('src', data.qrDataUri);
                        $img.attr('alt', 'QR: ' + data.shortUrl);
                        $link.attr('href', data.shortUrl).text(data.shortUrl);
                        $result.removeClass('d-none');
                    } else {
                        $msg.removeClass('d-none').addClass('alert-danger').text(data.message || 'Ошибка');
                    }
                })
                .fail(function (xhr) {
                    var text = 'Ошибка сети или сервера.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        text = xhr.responseJSON.message;
                    }
                    $msg.removeClass('d-none').addClass('alert-danger').text(text);
                })
                .always(function () {
                    setLoading($btn, false);
                });
        });
    });
})(jQuery);
