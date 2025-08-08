(function(window, $){
    'use strict';
    const CONFIG = window.Rozetka.CONFIG;
    const Lang = window.Rozetka.LanguageManager;
    const NotificationManager = {
        show(type, messageKey, icon = null, autoHide = true) {
            const message = Lang.get(messageKey, messageKey);
            const iconClass = icon || this.getDefaultIcon(type);
            const duration = CONFIG.DEFAULTS.notificationDuration[type] || 5000;

            const notification = $(`
                <div class="alert alert-${type} alert-dismissible fade in rozetka-notification">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fa ${iconClass}"></i> ${message}
                </div>
            `);

            notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999,
                minWidth: '300px',
                maxWidth: '500px'
            });

            $('body').append(notification);

            if (autoHide) {
                setTimeout(() => {
                    notification.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, duration);
            }

            return notification;
        },

        getDefaultIcon(type) {
            const icons = {
                success: 'fa-check-circle',
                danger: 'fa-exclamation-triangle',
                warning: 'fa-warning',
                info: 'fa-info-circle'
            };
            return icons[type] || 'fa-info-circle';
        },

        success(messageKey) {
            return this.show('success', messageKey);
        },

        error(messageKey) {
            return this.show('danger', messageKey);
        },

        warning(messageKey) {
            return this.show('warning', messageKey);
        },

        info(messageKey) {
            return this.show('info', messageKey);
        }
    };
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.NotificationManager = NotificationManager;
})(window, jQuery);
