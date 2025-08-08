/**
 * Управление сохранением настроек по табам
 */
const TabSaveManager = {
	init() {
		this.bindEvents();
	},

	bindEvents() {
		$('.btn-save-tab').on('click', (e) => {
			const tab = $(e.currentTarget).data('tab');
			this.saveTabSettings(tab);
		});
	},

	async saveTabSettings(tab) {
		const btn = $(`.btn-save-tab[data-tab="${tab}"]`);
		const originalHtml = btn.html();

		this.setButtonLoading(btn);

		try {
			const settings = this.collectTabSettings(tab);

			const response = await ApiClient.post('extension/feed/rozetka/saveTabSettings', {
				tab: tab,
				settings: settings
			});

			if (response.success) {
				NotificationManager.success(response.message);
				this.highlightSavedFields(tab);
			} else if (response.errors) {
				this.showValidationErrors(response.errors);
				NotificationManager.error('Пожалуйста, исправьте ошибки в форме');
			} else {
				throw new Error(response.error || 'Неизвестная ошибка');
			}
		} catch (error) {
			NotificationManager.error('Ошибка при сохранении настроек');
			console.error('Tab save error:', error);
		} finally {
			this.resetButton(btn, originalHtml);
		}
	},

	collectTabSettings(tab) {
		const settings = {};
		const tabSelector = `#tab-${tab}`;

		// Собираем все поля в табе
		$(tabSelector).find('input, select, textarea').each(function() {
			const $field = $(this);
			const name = $field.attr('name');

			if (!name || !name.startsWith('feed_rozetka_')) return;

			// Убираем префикс feed_rozetka_
			const settingKey = name.replace('feed_rozetka_', '');

			if (name.endsWith('[]')) {
				// Массивы обрабатываем отдельно, пропускаем здесь
				return;
			} else if ($field.attr('type') === 'checkbox') {
				settings[settingKey] = $field.is(':checked') ? 1 : 0;
			} else if ($field.attr('type') === 'radio') {
				if ($field.is(':checked')) {
					settings[settingKey] = $field.val();
				}
			} else {
				settings[settingKey] = $field.val();
			}
		});

		// ВСЕГДА обрабатываем массивы исключений для таба фильтров
		if (tab === 'filters') {
			// Собираем исключенные категории (всегда, даже если пустой массив)
			const excludedCategories = [];
			$(`${tabSelector} input[name="feed_rozetka_exclude_categories[]"]:checked`).each(function() {
				excludedCategories.push($(this).val());
			});
			settings['exclude_categories'] = excludedCategories;

			// Собираем исключенных производителей (всегда, даже если пустой массив)
			const excludedManufacturers = [];
			$(`${tabSelector} input[name="feed_rozetka_exclude_manufacturers[]"]:checked`).each(function() {
				excludedManufacturers.push($(this).val());
			});
			settings['exclude_manufacturers'] = excludedManufacturers;
		} else if (tab === 'mapping') {
			// Для маппинга собираем связи из CategoryMappingManager
			settings['category_mappings'] = CategoryMappingManager.mappings || [];
		}

		console.log('Collected settings for tab:', tab, settings); // Для отладки
		return settings;
	},

	setButtonLoading(btn) {
		btn.prop('disabled', true).addClass('saving');
		btn.find('i').removeClass('fa-save').addClass('fa-spinner fa-spin');
		btn.find('span').text(' Сохранение...');
	},

	resetButton(btn, originalHtml) {
		btn.prop('disabled', false).removeClass('saving');
		btn.html(originalHtml);
	},

	highlightSavedFields(tab) {
		const tabSelector = `#tab-${tab}`;
		const fields = $(tabSelector).find('input, select, textarea');

		fields.addClass('field-saved');
		setTimeout(() => {
			fields.removeClass('field-saved');
		}, 2000);
	},

	showValidationErrors(errors) {
		// Очищаем предыдущие ошибки
		$('.validation-error').remove();

		Object.entries(errors).forEach(([field, message]) => {
			const fieldName = `feed_rozetka_${field}`;
			const $field = $(`[name="${fieldName}"]`);

			if ($field.length) {
				$field.after(`
                    <div class="text-danger validation-error">
                        <i class="fa fa-exclamation-triangle"></i> ${message}
                    </div>
                `);
			}
		});
	}
};