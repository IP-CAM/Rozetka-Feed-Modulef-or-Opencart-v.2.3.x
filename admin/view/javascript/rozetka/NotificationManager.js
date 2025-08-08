/**
 * Управление уведомлениями
 */
const NotificationManager = {
	show(type, message, icon = null, autoHide = true) {
		const iconClass = icon || this.getDefaultIcon(type);
		const duration = Config.DEFAULTS.notificationDuration[type] || 5000;

		const notification = $(`
                <div class="alert alert-${type} alert-dismissible fade in rozetka-notification">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fa ${iconClass}"></i> ${message}
                </div>
            `);

		// Добавляем стили для позиционирования
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

	success(message) {
		return this.show('success', message);
	},

	error(message) {
		return this.show('danger', message);
	},

	warning(message) {
		return this.show('warning', message);
	},

	info(message) {
		return this.show('info', message);
	}
};