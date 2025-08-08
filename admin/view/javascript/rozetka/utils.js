(function(window){
    'use strict';
    const CONFIG = window.Rozetka.CONFIG;
    const Utils = {
        /**
         * Получить URL с токеном
         */
        buildUrl(endpoint, params = {}) {
            const token = this.getURLVar('token');
            const baseUrl = `index.php?route=${endpoint}&token=${token}`;

            if (Object.keys(params).length === 0) {
                return baseUrl;
            }

            const queryString = Object.entries(params)
                .map(([key, value]) => `${key}=${encodeURIComponent(value)}`)
                .join('&');

            return `${baseUrl}&${queryString}`;
        },

        /**
         * Получить параметр из URL
         */
        getURLVar(key) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(key) || '';
        },

        /**
         * Форматирование чисел с разделителями
         */
        numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        /**
         * Экранирование HTML
         */
        escapeHtml(str) {
            if (typeof str !== 'string') return str;
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Debounce функция
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Валидация файла
         */
        validateFile(file, options = {}) {
            const {
                maxSize = CONFIG.DEFAULTS.maxFileSize,
                allowedTypes = ['application/json'],
                allowedExtensions = ['.json']
            } = options;

            if (file.size > maxSize) {
                throw new Error(`Размер файла превышает ${Math.round(maxSize / 1024 / 1024)}MB`);
            }

            const isValidType = allowedTypes.some(type => file.type === type);
            const isValidExtension = allowedExtensions.some(ext => file.name.toLowerCase().endsWith(ext));

            if (!isValidType && !isValidExtension) {
                throw new Error(`Поддерживаются только файлы: ${allowedExtensions.join(', ')}`);
            }

            return true;
        }
    };
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.Utils = Utils;
})(window);
