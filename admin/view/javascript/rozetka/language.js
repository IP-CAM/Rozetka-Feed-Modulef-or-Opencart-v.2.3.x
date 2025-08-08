(function(window){
    'use strict';
    const STORAGE_KEY = 'rozetkaLang';
    const LanguageManager = {
        get(key, defaultValue = '') {
            try {
                const data = JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
                return data[key] !== undefined ? data[key] : defaultValue;
            } catch (e) {
                console.warn('Language storage parse error', e);
                return defaultValue;
            }
        },
        set(data = {}) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            } catch (e) {
                console.warn('Language storage save error', e);
            }
        }
    };
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.LanguageManager = LanguageManager;
})(window);
