/**
 * @copyright: Copyright В© 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define([
    'jquery',
    'Firebear_ImportExport/js/modal/modal-component',
    'mage/storage',
    'uiRegistry',
    'mage/translate'
], function ($, Parent, storage, reg, $t) {
    'use strict';

    return Parent.extend({
        ajaxSend: function(file) {
            this.end = 0;
            var job = reg.get(this.job).data.entity_id;
            if (localStorage.getItem('jobId')) {
                job = localStorage.getItem('jobId');
            }
            var object = reg.get(this.name + '.debugger.debug');
            var url = this.url + '?form_key='+ window.FORM_KEY;
            this.currentAjax = this.urlAjax + '?file=' + file;
            var urlAjax = this.currentAjax ;
            $('.run').attr("disabled", true);
            var self = this;
            this.loading(true);
            storage.post(
                url,
                JSON.stringify({id: job, file: file})
            ).done(
                function (response) {
                    object.value(response.result);
                    $(".run").attr("disabled", false);
                    self.loading(false);
                    self.isNotice(response.result);
                    self.notice($t('The process is over'));
                    self.isError(!response.result);
                    if (response.file) {
                        self.isHref(response.result);
                        self.href(response.file);
                    }
                    self.end = 1;
                }
            ).fail(
                function (response) {
                    $(".run").attr("disabled", false);
                    self.loading(false);
                    self.isNotice(false);
                    self.isError(true);
                    self.end = 1;
                }
               
            );
            if (self.end != 1) {
                setTimeout(function () {self.getDebug(urlAjax)}, 1500);
            }
        },
        toggleModal: function () {
            this._super();
            var object = reg.get(this.name + '.debugger.debug');
            object.showDebug(false);
        },
        getDebug: function(urlAjax) {
            var object = reg.get(this.name + '.debugger.debug');
            var self = this;
            $.get(urlAjax).done( function (response) {
                var text = response.console;
                var array = text.split('<span text="item"></span><br/>');
                    urlAjax = self.currentAjax + '&number=0';
                if (text.length > 0) {
                    $('#debug-run').html(text);
                    $(".debug").scrollTop($(".debug")[0].scrollHeight);
                }
                if (self.end != 1) {
                    setTimeout(self.getDebug(urlAjax), 3500);
                }
            }).fail(function(response) {
                self.finish(false);
                self.error(response.responseText);
            });
        },
    });
});