/**
 * Управление импортом/экспортом настроек
 */
const ImportExportManager = {
	init() {
		this.bindEvents();
	},

	bindEvents() {
		$('#btn-export-settings').on('click', () => this.exportSettings());
		$('#btn-select-file').on('click', () => $('#import-file').click());
		$('#import-file').on('change', (e) => this.handleImportFileSelect(e));
		$('#btn-import-settings').on('click', () => this.importSettings());
	},

	exportSettings() {
		const btn = $('#btn-export-settings');
		const originalHtml = btn.html();

		btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Экспорт...');

		try {
			const settings = this.collectFormSettings();
			const dataStr = JSON.stringify(settings, null, 2);
			const dataBlob = new Blob([dataStr], { type: 'application/json' });
			const url = URL.createObjectURL(dataBlob);

			const link = document.createElement('a');
			link.href = url;
			link.download = `rozetka_feed_settings_${new Date().toISOString().slice(0,10)}.json`;
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			URL.revokeObjectURL(url);

			NotificationManager.success('Настройки экспортированы');
		} catch (error) {
			NotificationManager.error('Ошибка экспорта настроек');
			console.error('Export settings error:', error);
		} finally {
			setTimeout(() => {
				btn.prop('disabled', false).html(originalHtml);
			}, 1000);
		}
	},

	collectFormSettings() {
		const settings = {};

		$(Config.SELECTORS.form).find('input, select, textarea').each(function() {
			const name = $(this).attr('name');
			if (!name || !name.startsWith('feed_rozetka_')) return;

			if ($(this).attr('type') === 'checkbox') {
				settings[name] = $(this).is(':checked') ? '1' : '0';
			} else {
				settings[name] = $(this).val();
			}
		});

		return settings;
	},

	handleImportFileSelect(e) {
		const file = e.target.files[0];

		if (file) {
			$('#selected-file-name').text(file.name).show();
			$('#btn-import-settings').show();
		} else {
			$('#selected-file-name').hide();
			$('#btn-import-settings').hide();
		}
	},

	importSettings() {
		const file = $('#import-file')[0].files[0];

		if (!file) {
			NotificationManager.error('Выберите файл для импорта');
			return;
		}

		const btn = $('#btn-import-settings');
		const originalHtml = btn.html();

		btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Импорт...');

		const reader = new FileReader();
		reader.onload = (e) => {
			try {
				const settings = JSON.parse(e.target.result);
				this.applySettings(settings);
				NotificationManager.success('Настройки успешно импортированы');
			} catch (error) {
				NotificationManager.error('Ошибка чтения файла: неверный формат JSON');
				console.error('Import settings error:', error);
			} finally {
				btn.prop('disabled', false).html(originalHtml);
			}
		};

		reader.onerror = () => {
			NotificationManager.error('Ошибка чтения файла');
			btn.prop('disabled', false).html(originalHtml);
		};

		reader.readAsText(file);
	},

	applySettings(settings) {
		Object.entries(settings).forEach(([name, value]) => {
			const field = $(`[name="${name}"]`);
			if (!field.length) return;

			if (field.attr('type') === 'checkbox') {
				field.prop('checked', value === '1' || value === true);
			} else {
				field.val(value);
			}
		});

		// Обновляем toggle переключатели
		$('input[type="checkbox"]').trigger('change');
	}
};