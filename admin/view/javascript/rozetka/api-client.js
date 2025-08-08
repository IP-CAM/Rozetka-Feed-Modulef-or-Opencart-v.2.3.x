(function(window, $){
    'use strict';
    const Utils = window.Rozetka.Utils;
    const ApiClient = {
        request(endpoint, options = {}) {
            const {
                method = 'GET',
                data = null,
                timeout = 30000,
                processData = true,
                contentType = 'application/x-www-form-urlencoded; charset=UTF-8',
                params = {}
            } = options;

            return $.ajax({
                url: Utils.buildUrl(endpoint, params),
                type: method,
                data: data,
                timeout: timeout,
                processData: processData,
                contentType: contentType,
                dataType: 'json'
            });
        },

        get(endpoint, params = {}) {
            return this.request(endpoint, { params });
        },

        post(endpoint, data = {}) {
            return this.request(endpoint, { method: 'POST', data });
        },

        upload(endpoint, formData) {
            return this.request(endpoint, {
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 60000
            });
        }
    };
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.ApiClient = ApiClient;
})(window, jQuery);
