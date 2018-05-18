define([
    "jquery",
    "jquery/ui"
], function ($) {

    $.widget('mage.amsmtpConfig', {
        options: {
            selectors: {},
            providers: [],
            successClass: 'success',
            failClass: 'fail',
            loadingClass: 'loading'
        },

        elements: {
        },
        
        detailsElement: null,

        _create: function () {
            for (var field in this.options.selectors) {
                this.elements[field] = $(this.options.selectors[field]);
            }

            this.detailsElement = this.element.find('[data-role=details]');

            this.detailsElement.find('[data-role=toggle]').click(function () {
                $(this).parent('[data-role=details]').toggleClass('collapsed');
            });

            var fillTrigger = $(this.element).find('[data-role="amsmtp-fill-button"]');
            if (fillTrigger) {
                fillTrigger.click($.proxy(this.fill, this))
            }
            var checkTrigger = $(this.element).find('[data-role="amsmtp-check-button"]');
            if (checkTrigger) {
                this.checkTrigger = checkTrigger;
                checkTrigger.click($.proxy(this.check, this))
            }
        },

        fill: function () {
            var index = +this.elements.provider.val();
            var provider = this.options.providers[index];

            this.elements.server.val(provider.server);
            this.elements.port.val(provider.port);
            this.elements.auth.val(provider.auth);
            this.elements.encryption.val(provider.encryption);
        },

        check: function () {

            var widget = this;

            var params = {
                server:     this.elements.server.val(),
                port:       this.elements.port.val(),
                auth:       this.elements.auth.val(),
                login:      this.elements.login.val(),
                passw:      this.elements.password.val(),
                security:   this.elements.encryption.val(),
                test_email: this.elements.test_email.val(),

                form_key:   FORM_KEY
            };

            this.detailsElement.hide();

            $.ajax(this.options.check_url, {
                data: params,
                dataType: "json",
                success: function (response) {
                    widget.checkTrigger.addClass(
                        response.success ? widget.options.successClass : widget.options.failClass
                    );
                    widget.checkTrigger.children('span').html(response.message);

                    if (response.details) {
                        widget.detailsElement.find('[data-role=message]').html(
                            response.details.error_message + '<br/><br/>' + response.details.trace
                        );
                        widget.detailsElement.addClass('collapsed');
                        widget.detailsElement.show();
                    }
                },
                complete: function () {
                    widget.checkTrigger.removeClass(widget.options.loadingClass);
                },
                beforeSend: function () {
                    widget.checkTrigger.addClass(widget.options.loadingClass);
                    widget.checkTrigger.removeClass(widget.options.failClass);
                    widget.checkTrigger.removeClass(widget.options.successClass);
                }
            });
        },
    });

    return $.mage.amsmtpConfig;
});
