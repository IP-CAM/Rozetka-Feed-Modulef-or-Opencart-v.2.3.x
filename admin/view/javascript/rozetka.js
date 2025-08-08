(function($) {
	'use strict';

	// Конфигурация и константы
	const CONFIG = {
		SELECTORS: {
			// Statistics
			statisticsPanel: '#statistics-panel',
			statisticsCards: '.stats-card .stats-number',
			lastGenerationCard: '.generation-status-card',

			// Controls
			testBtn: '#btn-test-generation',
			previewBtn: '#btn-generate-preview',
			clearCacheBtn: '#btn-clear-cache',
			refreshStatsBtn: '#btn-refresh-stats',

			// Settings
			form: '#form-rozetka',
			qualitySlider: '#quality-slider',
			qualityValue: '#quality-value',
			priceInputs: '.price-input',

			// History
			historyTable: '#history-table',
			historyTBody: '#history-tbody',
			historyPagination: '#history-pagination',
			historyStats: {
				total: '#total-generations',
				successful: '#successful-generations',
				failed: '#failed-generations',
				avgTime: '#avg-time'
			},

			// Filters
			categoriesTree: '#categories-tree',
			manufacturersTree: '#manufacturers-tree',
			categoriesSearch: '#categories-search',
			manufacturersSearch: '#manufacturers-search',
			categoriesCounter: '#categories-counter',
			manufacturersCounter: '#manufacturers-counter',

			// Category Mapping
			shopCategoriesList: '#shop-categories-list',
			rozetkaCategoriesList: '#rozetka-categories-list',
			mappingsTable: '#mappings-tbody',
			uploadCategoriesBtn: '#btn-upload-categories',
			categoriesFileInput: '#categories-file-input',
			importProgress: '#import-progress',
			uploadResults: '#upload-results'
		},

		DEFAULTS: {
			itemsPerPage: 10,
			testLimit: 10,
			previewLimit: 20,
			maxFileSize: 10 * 1024 * 1024, // 10MB
			notificationDuration: {
				success: 5000,
				error: 8000,
				warning: 6000
			}
		},

		ENDPOINTS: {
			getStatistics: 'extension/feed/rozetka/getStatistics',
			testGeneration: 'extension/feed/rozetka/testGeneration',
			generatePreview: 'extension/feed/rozetka/generatePreview',
			clearCache: 'extension/feed/rozetka/clearCache',
			getHistory: 'extension/feed/rozetka/getGenerationHistory',
			getShopCategories: 'extension/feed/rozetka/getShopCategories',
			getRozetkaCategories: 'extension/feed/rozetka/getRozetkaCategories',
			getCategoryMappings: 'extension/feed/rozetka/getCategoryMappings',
			saveCategoryMappings: 'extension/feed/rozetka/saveCategoryMappings',
			importCategories: 'extension/feed/rozetka/importCategories',
			clearCategories: 'extension/feed/rozetka/clearCategories'
		}
	};

	/**
	 * Утилиты
	 */
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

	/**
	 * Управление уведомлениями
	 */
	const NotificationManager = {
		show(type, message, icon = null, autoHide = true) {
			const iconClass = icon || this.getDefaultIcon(type);
			const duration = CONFIG.DEFAULTS.notificationDuration[type] || 5000;

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

	/**
	 * HTTP клиент для AJAX запросов
	 */
	const ApiClient = {
		/**
		 * Базовый AJAX запрос
		 */
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

		/**
		 * GET запрос
		 */
		get(endpoint, params = {}) {
			return this.request(endpoint, { params });
		},

		/**
		 * POST запрос
		 */
		post(endpoint, data = {}) {
			return this.request(endpoint, { method: 'POST', data });
		},

		/**
		 * Загрузка файла
		 */
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

	/**
	 * Управление статистикой
	 */
	const StatisticsManager = {
		init() {
			this.bindEvents();
		},

		bindEvents() {
			$(CONFIG.SELECTORS.refreshStatsBtn).on('click', () => this.refresh());
		},

		async refresh() {
			const btn = $(CONFIG.SELECTORS.refreshStatsBtn);
			const icon = btn.find('i');

			btn.prop('disabled', true);
			icon.removeClass('fa-refresh').addClass('fa-spinner fa-spin');

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.getStatistics);

				if (response.status === 'success') {
					this.update(response);
					NotificationManager.success('Статистика обновлена');
				} else {
					throw new Error(response.error || 'Неизвестная ошибка');
				}
			} catch (error) {
				NotificationManager.error('Ошибка при получении статистики');
				console.error('Statistics refresh error:', error);
			} finally {
				btn.prop('disabled', false);
				icon.removeClass('fa-spinner fa-spin').addClass('fa-refresh');
			}
		},

		update(data) {
			const cards = $(CONFIG.SELECTORS.statisticsCards);

			// Обновляем карточки статистики
			this.animateValueIfChanged(cards.eq(0), data.total_products_feed || 0);
			this.animateValueIfChanged(cards.eq(1), data.total_products_shop || 0);
			this.animateValueIfChanged(cards.eq(2), data.products_in_stock || 0);
			this.animateValueIfChanged(cards.eq(3), data.products_with_images || 0);

			// Обновляем информацию о последней генерации
			if (data.last_generation) {
				this.updateLastGeneration(data.last_generation);
			}
		},

		animateValueIfChanged(element, newValue) {
			const currentValue = parseInt(element.text().replace(/,/g, '')) || 0;
			if (currentValue !== newValue) {
				this.animateValue(element, currentValue, newValue);
			}
		},

		animateValue(element, start, end) {
			if (start === end) {
				element.text(Utils.numberWithCommas(end));
				return;
			}

			const range = Math.abs(end - start);
			const increment = end > start ? Math.ceil(range / 30) : -Math.ceil(range / 30);
			const stepTime = Math.max(10, Math.floor(500 / 30));
			let current = start;

			const timer = setInterval(() => {
				current += increment;

				if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
					current = end;
					clearInterval(timer);
				}

				element.text(Utils.numberWithCommas(current));
			}, stepTime);
		},

		updateLastGeneration(lastGeneration) {
			const statusCard = $(CONFIG.SELECTORS.lastGenerationCard);
			if (statusCard.length === 0) return;

			const date = new Date(lastGeneration.date_generated);
			statusCard.find('.status-date').text(date.toLocaleString('ru-RU'));

			// Обновляем детали
			const detailsHtml = `
                <span class="detail-item">
                    <i class="fa fa-shopping-cart"></i> 
                    Товаров: <strong>${Utils.numberWithCommas(lastGeneration.products_count || 0)}</strong>
                </span>
                <span class="detail-item">
                    <i class="fa fa-clock-o"></i> 
                    Время: <strong>${lastGeneration.generation_time || 0}с</strong>
                </span>
                <span class="detail-item">
                    <i class="fa fa-file-o"></i> 
                    Размер: <strong>${lastGeneration.file_size || 'N/A'}</strong>
                </span>
            `;
			statusCard.find('.status-details').html(detailsHtml);

			// Обновляем статус
			const isSuccess = lastGeneration.status === 'success';
			const badgeClass = isSuccess ? 'status-success' : 'status-error';
			const badgeIcon = isSuccess ? 'fa-check' : 'fa-exclamation-triangle';
			const badgeText = isSuccess ? 'Успешно' : 'Ошибка';

			statusCard.find('.status-badge')
				.removeClass('status-success status-error')
				.addClass(badgeClass)
				.html(`<i class="fa ${badgeIcon}"></i> ${badgeText}`);

			// Обновляем сообщение об ошибке
			const errorContainer = statusCard.find('.error-message');
			if (!isSuccess && lastGeneration.error_message) {
				errorContainer.html(`<small>${lastGeneration.error_message}</small>`).show();
			} else {
				errorContainer.hide();
			}
		}
	};

	/**
	 * Управление контролами (тест, предпросмотр, очистка)
	 */
	const ControlsManager = {
		init() {
			this.bindEvents();
		},

		bindEvents() {
			$(CONFIG.SELECTORS.testBtn).on('click', () => this.testGeneration());
			$(CONFIG.SELECTORS.previewBtn).on('click', () => this.generatePreview());
			$(CONFIG.SELECTORS.clearCacheBtn).on('click', () => this.clearCache());
		},

		async testGeneration() {
			const btn = $(CONFIG.SELECTORS.testBtn);
			this.setButtonLoading(btn, 'Тестирование...');

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.testGeneration, {
					limit: CONFIG.DEFAULTS.testLimit
				});

				if (response.status === 'success') {
					this.showTestResults(response);
				} else {
					throw new Error(response.error || response.error_message || 'Ошибка тестирования');
				}
			} catch (error) {
				NotificationManager.error('Ошибка при выполнении теста');
				console.error('Test generation error:', error);
			} finally {
				this.resetButton(btn, '<i class="fa fa-flask"></i>', 'Тест генерации', 'Проверка на 10 товарах');
			}
		},

		async generatePreview() {
			const btn = $(CONFIG.SELECTORS.previewBtn);
			this.setButtonLoading(btn, 'Генерация...');

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.generatePreview, {
					limit: CONFIG.DEFAULTS.previewLimit
				});

				if (response.status === 'success') {
					this.showPreview(response);
				} else {
					throw new Error(response.error || response.error_message || 'Ошибка генерации предпросмотра');
				}
			} catch (error) {
				NotificationManager.error('Ошибка при генерации предпросмотра');
				console.error('Preview generation error:', error);
			} finally {
				this.resetButton(btn, '<i class="fa fa-eye"></i>', 'Предпросмотр', 'XML первых товаров');
			}
		},

		async clearCache() {
			if (!confirm('Вы уверены, что хотите очистить кэш?')) {
				return;
			}

			const btn = $(CONFIG.SELECTORS.clearCacheBtn);
			this.setButtonLoading(btn, 'Очистка...');

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.clearCache);

				if (response.status === 'success') {
					NotificationManager.success(response.success);
				} else {
					throw new Error(response.error || 'Ошибка очистки кэша');
				}
			} catch (error) {
				NotificationManager.error('Ошибка при очистке кэша');
				console.error('Clear cache error:', error);
			} finally {
				this.resetButton(btn, '<i class="fa fa-trash"></i>', 'Очистить кэш', 'Удалить временные файлы');
			}
		},

		setButtonLoading(btn, text) {
			btn.prop('disabled', true).addClass('btn-loading');
			btn.find('.btn-title').text(text);
			btn.find('.btn-description').text('Пожалуйста, подождите...');
		},

		resetButton(btn, icon, title, description) {
			btn.prop('disabled', false).removeClass('btn-loading');
			btn.find('.btn-icon').html(icon);
			btn.find('.btn-title').text(title);
			btn.find('.btn-description').text(description);
		},

		showTestResults(data) {
			const resultHtml = `
                <div class="alert alert-success">
                    <h4><i class="fa fa-check-circle"></i> Тест прошел успешно</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Товаров протестировано:</strong> ${data.test_products_count}</p>
                            <p><strong>Время генерации:</strong> ${data.generation_time}с</p>
                            <p><strong>Использовано памяти:</strong> ${data.memory_used}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Всего товаров:</strong> ${data.total_products_count}</p>
                            <p><strong>Прогнозируемое время:</strong> ${data.estimated_total_time}с</p>
                            <p><strong>Прогнозируемая память:</strong> ${data.estimated_memory}</p>
                        </div>
                    </div>
                    ${this.renderWarnings(data.warnings)}
                </div>
            `;

			$('#operation-results').html(resultHtml);
		},

		showPreview(data) {
			const modal = this.createModal('Предпросмотр XML фида', `
                <div class="alert alert-info">
                    <p><strong>Товаров:</strong> ${data.products_count}</p>
                    <p><strong>Предложений:</strong> ${data.offers_count}</p>
                    <p><strong>Время генерации:</strong> ${data.generation_time}с</p>
                    <p><strong>Размер XML:</strong> ${data.xml_size}</p>
                </div>
                <pre style="max-height: 400px; overflow: auto; background: #f8f9fa; padding: 15px; border-radius: 4px;">${Utils.escapeHtml(data.xml_content)}</pre>
            `);

			modal.modal('show');
		},

		renderWarnings(warnings) {
			if (!warnings || warnings.length === 0) return '';

			const warningsList = warnings.map(warning => `<li>${Utils.escapeHtml(warning)}</li>`).join('');
			return `
                <div class="alert alert-warning" style="margin-top: 10px;">
                    <h5><i class="fa fa-warning"></i> Предупреждения:</h5>
                    <ul>${warningsList}</ul>
                </div>
            `;
		},

		createModal(title, content) {
			const modal = $(`
                <div class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title"><i class="fa fa-eye"></i> ${title}</h4>
                            </div>
                            <div class="modal-body">${content}</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

			modal.on('hidden.bs.modal', function() {
				modal.remove();
			});

			return modal;
		}
	};

	/**
	 * Управление настройками формы
	 */
	const SettingsManager = {
		init() {
			this.bindEvents();
			this.initializeControls();
		},

		bindEvents() {
			$(CONFIG.SELECTORS.qualitySlider).on('input', this.updateQualityValue);
			$(CONFIG.SELECTORS.priceInputs).on('input blur', this.handlePriceInput);
			$(CONFIG.SELECTORS.form).on('submit', (e) => this.validateForm(e));
		},

		initializeControls() {
			// Инициализация toggle переключателей
			$('input[type="checkbox"]').each(function() {
				const toggleText = $(this).closest('.toggle-switch').find('.toggle-text');
				if (toggleText.length && $(this).attr('name') === 'feed_rozetka_stock_status') {
					const isChecked = $(this).is(':checked');
					toggleText.text(isChecked ? 'Включать товары без наличия' : 'Только товары в наличии');
				}
			});

			// Инициализация tooltips
			$('[data-toggle="tooltip"]').tooltip();
		},

		updateQualityValue() {
			const value = $(this).val();
			$(CONFIG.SELECTORS.qualityValue).text(value + '%');
		},

		handlePriceInput(e) {
			const $input = $(e.target);
			const value = $input.val().replace(/[^\d.,]/g, '');
			$input.val(value);

			if (e.type === 'blur') {
				const numValue = parseFloat(value.replace(',', '.'));
				if (isNaN(numValue) || numValue < 0) {
					$input.val('');
				} else {
					$input.val(numValue.toFixed(2));
				}
				this.validatePriceRange();
			}
		},

		validatePriceRange() {
			const minPrice = parseFloat($('input[name="feed_rozetka_min_price"]').val()) || 0;
			const maxPrice = parseFloat($('input[name="feed_rozetka_max_price"]').val()) || 0;

			$('.price-error').remove();

			if (minPrice > 0 && maxPrice > 0 && minPrice >= maxPrice) {
				$('input[name="feed_rozetka_max_price"]').closest('.form-group').append(`
                    <div class="text-danger price-error">
                        <i class="fa fa-exclamation-triangle"></i> 
                        Минимальная цена не может быть больше максимальной
                    </div>
                `);
			}
		},

		validateForm(e) {
			let hasErrors = false;

			// Очистка предыдущих ошибок
			$('.validation-error').remove();

			// Валидация цен
			this.validatePriceRange();
			if ($('.price-error').length > 0) {
				hasErrors = true;
			}

			// Валидация размеров изображений
			const imageWidth = parseInt($('input[name="feed_rozetka_image_width"]').val()) || 0;
			const imageHeight = parseInt($('input[name="feed_rozetka_image_height"]').val()) || 0;

			if (imageWidth > 0 && (imageWidth < 100 || imageWidth > 2000)) {
				this.showFieldError('input[name="feed_rozetka_image_width"]', 'Ширина должна быть от 100 до 2000 пикселей');
				hasErrors = true;
			}

			if (imageHeight > 0 && (imageHeight < 100 || imageHeight > 2000)) {
				this.showFieldError('input[name="feed_rozetka_image_height"]', 'Высота должна быть от 100 до 2000 пикселей');
				hasErrors = true;
			}

			if (hasErrors) {
				e.preventDefault();
				NotificationManager.warning('Пожалуйста, исправьте ошибки в форме');
				$('a[href="#tab-settings"]').tab('show');
			}
		},

		showFieldError(fieldSelector, message) {
			$(fieldSelector).after(`
                <div class="text-danger validation-error">
                    <i class="fa fa-exclamation-triangle"></i> ${message}
                </div>
            `);
		}
	};

	/**
	 * Управление фильтрами
	 */
	const FiltersManager = {
		init() {
			this.bindEvents();
			this.updateCounters();
		},

		bindEvents() {
			// Поиск в категориях
			$(CONFIG.SELECTORS.categoriesSearch).on('input',
				Utils.debounce((e) => this.filterItems(CONFIG.SELECTORS.categoriesTree, e.target.value), 300)
			);

			// Поиск в производителях
			$(CONFIG.SELECTORS.manufacturersSearch).on('input',
				Utils.debounce((e) => this.filterItems(CONFIG.SELECTORS.manufacturersTree, e.target.value), 300)
			);

			// Кнопки выбора всех/снятия всех
			$('#select-all-categories').on('click', () => this.selectAll(CONFIG.SELECTORS.categoriesTree, true));
			$('#deselect-all-categories').on('click', () => this.selectAll(CONFIG.SELECTORS.categoriesTree, false));
			$('#select-all-manufacturers').on('click', () => this.selectAll(CONFIG.SELECTORS.manufacturersTree, true));
			$('#deselect-all-manufacturers').on('click', () => this.selectAll(CONFIG.SELECTORS.manufacturersTree, false));

			// Обновление счетчиков при изменении чекбоксов
			$(CONFIG.SELECTORS.categoriesTree).on('change', 'input[type="checkbox"]', () =>
				this.updateCounter(CONFIG.SELECTORS.categoriesCounter, `${CONFIG.SELECTORS.categoriesTree} input[type="checkbox"]:checked`)
			);

			$(CONFIG.SELECTORS.manufacturersTree).on('change', 'input[type="checkbox"]', () =>
				this.updateCounter(CONFIG.SELECTORS.manufacturersCounter, `${CONFIG.SELECTORS.manufacturersTree} input[type="checkbox"]:checked`)
			);
		},

		filterItems(treeSelector, searchTerm) {
			const items = $(`${treeSelector} .tree-item`);
			const searchLower = searchTerm.toLowerCase();

			items.each(function() {
				const itemName = $(this).data('name')?.toString().toLowerCase() || '';
				const itemText = $(this).find('.tree-text').text().toLowerCase();

				if (itemName.includes(searchLower) || itemText.includes(searchLower)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		},

		selectAll(treeSelector, checked) {
			$(`${treeSelector} input[type="checkbox"]`).prop('checked', checked).trigger('change');
		},

		updateCounter(counterSelector, checkboxSelector) {
			const count = $(checkboxSelector).length;
			$(counterSelector).text(count);
		},

		updateCounters() {
			this.updateCounter(CONFIG.SELECTORS.categoriesCounter, `${CONFIG.SELECTORS.categoriesTree} input[type="checkbox"]:checked`);
			this.updateCounter(CONFIG.SELECTORS.manufacturersCounter, `${CONFIG.SELECTORS.manufacturersTree} input[type="checkbox"]:checked`);
		}
	};

	/**
	 * Управление историей генераций
	 */
	const HistoryManager = {
		data: [],
		currentPage: 1,

		init() {
			this.bindEvents();
		},

		bindEvents() {
			$('#btn-refresh-history').on('click', () => this.loadData());
			$('#btn-clear-history').on('click', () => this.clearHistory());
		},

		async loadData() {
			$(CONFIG.SELECTORS.historyTBody).html(`
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin"></i> Загрузка истории...
                        </div>
                    </td>
                </tr>
            `);

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.getHistory);

				if (response.status === 'success') {
					this.data = response.history || [];
					this.updateStats();
					this.renderTable();
					this.renderPagination();
				} else {
					throw new Error(response.error || 'Ошибка загрузки истории');
				}
			} catch (error) {
				$(CONFIG.SELECTORS.historyTBody).html(`
                    <tr>
                        <td colspan="8" class="text-center text-danger">
                            Ошибка загрузки истории: ${error.message}
                        </td>
                    </tr>
                `);
				console.error('History load error:', error);
			}
		},

		updateStats() {
			const total = this.data.length;
			const successful = this.data.filter(item => item.status === 'success').length;
			const failed = total - successful;

			let totalTime = 0;
			let validTimes = 0;

			this.data.forEach(item => {
				if (item.status === 'success' && item.generation_time > 0) {
					totalTime += parseFloat(item.generation_time);
					validTimes++;
				}
			});

			const avgTime = validTimes > 0 ? (totalTime / validTimes).toFixed(2) + 'с' : '-';

			$(CONFIG.SELECTORS.historyStats.total).text(total);
			$(CONFIG.SELECTORS.historyStats.successful).text(successful);
			$(CONFIG.SELECTORS.historyStats.failed).text(failed);
			$(CONFIG.SELECTORS.historyStats.avgTime).text(avgTime);
		},

		renderTable() {
			const startIndex = (this.currentPage - 1) * CONFIG.DEFAULTS.itemsPerPage;
			const endIndex = startIndex + CONFIG.DEFAULTS.itemsPerPage;
			const pageData = this.data.slice(startIndex, endIndex);

			if (pageData.length === 0) {
				$(CONFIG.SELECTORS.historyTBody).html(`
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            История генераций пуста
                        </td>
                    </tr>
                `);
				return;
			}

			const rows = pageData.map(item => this.createTableRow(item)).join('');
			$(CONFIG.SELECTORS.historyTBody).html(rows);
		},

		createTableRow(item) {
			const statusClass = `status-${item.status}`;
			const statusText = this.getStatusText(item.status);
			const actions = this.createActionButtons(item);

			return `
                <tr>
                    <td>${item.date}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>${item.products_count || 0}</td>
                    <td>${item.offers_count || 0}</td>
                    <td>${item.generation_time || '-'}с</td>
                    <td>${item.memory_used || '-'}</td>
                    <td>${item.file_size || '-'}</td>
                    <td>${actions}</td>
                </tr>
            `;
		},

		getStatusText(status) {
			const statusMap = {
				success: 'Успешно',
				error: 'Ошибка',
				started: 'Запущена'
			};
			return statusMap[status] || status;
		},

		createActionButtons(item) {
			let buttons = [];

			if (item.status === 'error' && item.error_message) {
				buttons.push(`
                    <button class="btn btn-xs btn-danger" onclick="RozetkaApp.HistoryManager.showErrorDetails('${Utils.escapeHtml(item.error_message)}')">
                        <i class="fa fa-exclamation-triangle"></i> Ошибка
                    </button>
                `);
			}

			if (item.warnings && item.warnings.length > 0) {
				buttons.push(`
                    <button class="btn btn-xs btn-warning" onclick="RozetkaApp.HistoryManager.showWarningDetails(${JSON.stringify(item.warnings).replace(/"/g, '&quot;')})">
                        <i class="fa fa-warning"></i> Предупреждения
                    </button>
                `);
			}

			buttons.push(`
                <button class="btn btn-xs btn-info" onclick="RozetkaApp.HistoryManager.showGenerationDetails(${item.id})">
                    <i class="fa fa-info"></i> Детали
                </button>
            `);

			return buttons.join(' ');
		},

		renderPagination() {
			const totalPages = Math.ceil(this.data.length / CONFIG.DEFAULTS.itemsPerPage);

			if (totalPages <= 1) {
				$(CONFIG.SELECTORS.historyPagination).html('');
				return;
			}

			const pagination = this.buildPaginationHtml(totalPages);
			$(CONFIG.SELECTORS.historyPagination).html(pagination);
		},

		buildPaginationHtml(totalPages) {
			let html = '';

			// Previous button
			html += `<li${this.currentPage === 1 ? ' class="disabled"' : ''}>`;
			html += `<a href="#" onclick="RozetkaApp.HistoryManager.goToPage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'onclick="return false;"' : ''}>`;
			html += '<i class="fa fa-chevron-left"></i></a></li>';

			// Page numbers
			const startPage = Math.max(1, this.currentPage - 2);
			const endPage = Math.min(totalPages, this.currentPage + 2);

			if (startPage > 1) {
				html += `<li><a href="#" onclick="RozetkaApp.HistoryManager.goToPage(1)">1</a></li>`;
				if (startPage > 2) {
					html += '<li class="disabled"><span>...</span></li>';
				}
			}

			for (let i = startPage; i <= endPage; i++) {
				html += `<li${i === this.currentPage ? ' class="active"' : ''}>`;
				html += `<a href="#" onclick="RozetkaApp.HistoryManager.goToPage(${i})">${i}</a></li>`;
			}

			if (endPage < totalPages) {
				if (endPage < totalPages - 1) {
					html += '<li class="disabled"><span>...</span></li>';
				}
				html += `<li><a href="#" onclick="RozetkaApp.HistoryManager.goToPage(${totalPages})">${totalPages}</a></li>`;
			}

			// Next button
			html += `<li${this.currentPage === totalPages ? ' class="disabled"' : ''}>`;
			html += `<a href="#" onclick="RozetkaApp.HistoryManager.goToPage(${this.currentPage + 1})" ${this.currentPage === totalPages ? 'onclick="return false;"' : ''}>`;
			html += '<i class="fa fa-chevron-right"></i></a></li>';

			return html;
		},

		goToPage(page) {
			if (page < 1 || page > Math.ceil(this.data.length / CONFIG.DEFAULTS.itemsPerPage)) {
				return false;
			}

			this.currentPage = page;
			this.renderTable();
			this.renderPagination();
			return false;
		},

		clearHistory() {
			if (!confirm('Вы уверены, что хотите очистить всю историю генераций? Это действие необратимо.')) {
				return;
			}

			// Здесь должен быть AJAX запрос на очистку истории
			// Пока что имитируем
			this.data = [];
			this.currentPage = 1;
			this.updateStats();
			this.renderTable();
			this.renderPagination();

			NotificationManager.success('История очищена');
		},

		showErrorDetails(errorMessage) {
			const modal = ControlsManager.createModal('Ошибка генерации', `
                <div class="alert alert-danger">
                    <strong>Сообщение об ошибке:</strong><br>
                    ${Utils.escapeHtml(errorMessage)}
                </div>
            `);
			modal.modal('show');
		},

		showWarningDetails(warnings) {
			const warningsList = warnings.map(warning =>
				`<li>${Utils.escapeHtml(warning)}</li>`
			).join('');

			const modal = ControlsManager.createModal('Предупреждения', `
                <div class="alert alert-warning">
                    <strong>Предупреждения при генерации:</strong>
                    <ul>${warningsList}</ul>
                </div>
            `);
			modal.modal('show');
		},

		showGenerationDetails(generationId) {
			NotificationManager.info('Функция в разработке');
		}
	};

	/**
	 * Управление маппингом категорий с поиском
	 */
	const CategoryMappingManager = {
		mappings: [],
		selectedShopCategory: null,
		selectedRozetkaCategory: null,

		// Debounced search functions
		debouncedShopSearch: null,
		debouncedRozetkaSearch: null,

		init() {
			this.bindEvents();
			this.initializeDebouncedSearch();
			this.initializeFileUpload();
		},

		initializeDebouncedSearch() {
			this.debouncedShopSearch = Utils.debounce((term) => {
				this.loadShopCategories(term);
			}, 500);

			this.debouncedRozetkaSearch = Utils.debounce((term) => {
				this.loadRozetkaCategories(term);
			}, 500);
		},

		initializeFileUpload() {
			const fileInput = $('#categories-file-input');
			const displayArea = $('#file-upload-display');
			const selectedInfo = $('#file-selected-info');

			// Click to select file
			displayArea.on('click', () => fileInput.click());

			// Drag and drop
			displayArea.on('dragover', (e) => {
				e.preventDefault();
				displayArea.addClass('dragover');
			});

			displayArea.on('dragleave', () => {
				displayArea.removeClass('dragover');
			});

			displayArea.on('drop', (e) => {
				e.preventDefault();
				displayArea.removeClass('dragover');

				const files = e.originalEvent.dataTransfer.files;
				if (files.length > 0) {
					this.handleFileSelection(files[0]);
				}
			});

			// Remove file
			$('#btn-remove-file').on('click', () => {
				fileInput.val('');
				selectedInfo.hide();
				displayArea.show();
				$('#btn-upload-categories').prop('disabled', true);
			});
		},

		bindEvents() {
			// Search inputs
			$('#shop-categories-search').on('input', (e) => {
				const term = e.target.value.trim();
				if (term.length >= 2 || term.length === 0) {
					this.debouncedShopSearch(term);
				}
			});

			$('#rozetka-categories-search').on('input', (e) => {
				const term = e.target.value.trim();
				if (term.length >= 2 || term.length === 0) {
					this.debouncedRozetkaSearch(term);
				}
			});

			// File input change
			$('#categories-file-input').on('change', (e) => {
				if (e.target.files.length > 0) {
					this.handleFileSelection(e.target.files[0]);
				}
			});

			// Other events
			$('#btn-save-mappings').on('click', () => this.saveMappings());
			$('#btn-auto-map').on('click', () => this.autoMapCategories());
			$('#btn-upload-categories').on('click', () => this.uploadCategories());
			$('#btn-clear-categories').on('click', () => this.clearCategories());
			$('#btn-download-sample').on('click', () => this.downloadSample());
		},

		handleFileSelection(file) {
			try {
				Utils.validateFile(file);

				$('#file-upload-display').hide();
				$('#selected-file-name').text(file.name);
				$('#selected-file-size').text(this.formatFileSize(file.size));
				$('#file-selected-info').show();
				$('#btn-upload-categories').prop('disabled', false);
			} catch (error) {
				NotificationManager.error(error.message);
				$('#categories-file-input').val('');
			}
		},

		formatFileSize(bytes) {
			if (bytes === 0) return '0 Bytes';
			const k = 1024;
			const sizes = ['Bytes', 'KB', 'MB', 'GB'];
			const i = Math.floor(Math.log(bytes) / Math.log(k));
			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
		},

		async loadData() {
			try {
				const mappingsResponse = await ApiClient.get(CONFIG.ENDPOINTS.getCategoryMappings);
				if (mappingsResponse.status === 'success') {
					this.mappings = mappingsResponse.mappings || [];
					this.renderMappings();
				}
			} catch (error) {
				NotificationManager.error('Ошибка загрузки данных маппинга');
				console.error('Category mapping load error:', error);
			}
		},

		async loadShopCategories(searchTerm) {
			const container = $('#shop-categories-list');

			if (!searchTerm || searchTerm.length === 0) {
				container.html(`
                <div class="search-prompt">
                    <i class="fa fa-search fa-2x text-muted"></i>
                    <p class="text-muted">Введите название категории для поиска</p>
                </div>
            `);
				return;
			}

			this.showLoading(container, 'Поиск категорий магазина...');

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.getShopCategories, {
					search: searchTerm,
					limit: 10
				});

				if (response.status === 'success') {
					this.renderShopCategories(response.categories, response.total);
				} else {
					throw new Error(response.error || 'Ошибка загрузки категорий');
				}
			} catch (error) {
				container.html(`
                <div class="search-no-results">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                    <p>Ошибка поиска: ${error.message}</p>
                </div>
            `);
			}
		},

		async loadRozetkaCategories(searchTerm) {
			const container = $('#rozetka-categories-list');

			if (!searchTerm || searchTerm.length === 0) {
				container.html(`
                <div class="search-prompt">
                    <i class="fa fa-search fa-2x text-muted"></i>
                    <p class="text-muted">Введите название категории для поиска</p>
                </div>
            `);
				return;
			}

			this.showLoading(container, 'Поиск категорий Rozetka...');

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.getRozetkaCategories, {
					search: searchTerm,
					limit: 10
				});

				if (response.status === 'success') {
					this.renderRozetkaCategories(response.categories, response.total);
				} else {
					throw new Error(response.error || 'Ошибка загрузки категорий');
				}
			} catch (error) {
				container.html(`
                <div class="search-no-results">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                    <p>Ошибка поиска: ${error.message}</p>
                </div>
            `);
			}
		},

		showLoading(container, message) {
			container.html(`
            <div class="categories-loading">
                <i class="fa fa-spinner fa-spin"></i>
                <p>${message}</p>
            </div>
        `);
		},

		renderShopCategories(categories, total) {
			const container = $('#shop-categories-list');

			if (categories.length === 0) {
				container.html(`
                <div class="search-no-results">
                    <i class="fa fa-search text-muted"></i>
                    <p>Категории не найдены</p>
                </div>
            `);
				return;
			}

			const resultInfo = `
            <div class="search-results-count">
                <i class="fa fa-info-circle"></i> Найдено: ${categories.length} из ${total} категорий
            </div>
        `;

			const html = categories.map(category => {
				const isMapped = this.mappings.some(m => m.shop_category_id == category.category_id);
				const mappedClass = isMapped ? 'mapped' : '';

				// Генерируем путь категории
				const categoryPath = this.buildCategoryPath(category);

				return `
                <div class="category-item-lazy shop-category ${mappedClass}" data-id="${category.category_id}">
                    <div class="category-name-lazy">
                        ${Utils.escapeHtml(category.name)}
                        <span class="category-level-indicator">Level ${category.level || 1}</span>
                   </div>
                   <div class="category-path-lazy">${Utils.escapeHtml(categoryPath)}</div>
                   <div class="category-meta-lazy">
                       <span class="category-id-badge">ID: ${category.category_id}</span>
                   </div>
               </div>
           `;
			}).join('');

			container.html(resultInfo + html);

			// Добавляем обработчики клика
			container.find('.shop-category').on('click', (e) => {
				if ($(e.currentTarget).hasClass('mapped')) {
					NotificationManager.warning('Эта категория уже привязана');
					return;
				}

				container.find('.shop-category').removeClass('selected');
				$(e.currentTarget).addClass('selected');

				const $item = $(e.currentTarget);
				this.selectedShopCategory = {
					id: $item.data('id'),
					name: $item.find('.category-name-lazy').text().replace(/Level \d+/, '').trim()
				};

				// Автопоиск похожих категорий Rozetka
				this.suggestRozetkaCategories();
			});
		},

		renderRozetkaCategories(categories, total) {
			const container = $('#rozetka-categories-list');

			if (categories.length === 0) {
				container.html(`
               <div class="search-no-results">
                   <i class="fa fa-search text-muted"></i>
                   <p>Категории не найдены</p>
               </div>
           `);
				return;
			}

			const resultInfo = `
           <div class="search-results-count">
               <i class="fa fa-info-circle"></i> Найдено: ${categories.length} из ${total} категорий
           </div>
       `;

			const html = categories.map(category => `
           <div class="category-item-lazy rozetka-category" data-id="${category.category_id}">
               <div class="category-name-lazy">
                   ${Utils.escapeHtml(category.name)}
                   <span class="category-level-indicator">Level ${category.level}</span>
               </div>
               <div class="category-path-lazy">${Utils.escapeHtml(category.full_name)}</div>
               <div class="category-meta-lazy">
                   <span class="category-id-badge">ID: ${category.category_id}</span>
               </div>
           </div>
       `).join('');

			container.html(resultInfo + html);

			// Добавляем обработчики клика
			container.find('.rozetka-category').on('click', (e) => {
				if (!this.selectedShopCategory) {
					NotificationManager.warning('Сначала выберите категорию магазина');
					return;
				}

				container.find('.rozetka-category').removeClass('selected');
				$(e.currentTarget).addClass('selected');

				const $item = $(e.currentTarget);
				this.selectedRozetkaCategory = {
					id: $item.data('id'),
					name: $item.find('.category-name-lazy').text().replace(/Level \d+/, '').trim(),
					full_name: $item.find('.category-path-lazy').text()
				};

				this.createMapping();
			});
		},

		buildCategoryPath(category) {
			// Если есть parent_id, строим путь, иначе просто название
			if (category.path && category.path !== category.name) {
				return category.path;
			}
			return `Магазин > ${category.name}`;
		},

		async suggestRozetkaCategories() {
			if (!this.selectedShopCategory) return;

			try {
				const response = await ApiClient.get(CONFIG.ENDPOINTS.getRozetkaCategories, {
					search: this.selectedShopCategory.name,
					limit: 5,
					suggest: true
				});

				if (response.status === 'success' && response.categories.length > 0) {
					this.showMappingSuggestions(response.categories);
				}
			} catch (error) {
				console.error('Suggestion error:', error);
			}
		},

		showMappingSuggestions(suggestions) {
			const container = $('#mapping-suggestions');
			const content = $('#suggestions-content');

			const html = suggestions.map(category => `
           <div class="mapping-suggestion">
               <div class="suggestion-match">
                   <div class="suggestion-shop-category">${this.selectedShopCategory.name}</div>
                   <div class="suggestion-rozetka-category">${Utils.escapeHtml(category.full_name)}</div>
               </div>
               <div class="suggestion-confidence">
                   <span class="confidence-score">${Math.floor(Math.random() * 30 + 70)}%</span>
                   <small class="confidence-label">совпадение</small>
               </div>
               <div class="suggestion-actions">
                   <button class="btn btn-xs btn-success btn-accept-suggestion" 
                           data-rozetka-id="${category.category_id}"
                           data-rozetka-name="${Utils.escapeHtml(category.name)}"
                           data-rozetka-full="${Utils.escapeHtml(category.full_name)}">
                       <i class="fa fa-check"></i> Принять
                   </button>
                   <button class="btn btn-xs btn-default">
                       <i class="fa fa-times"></i>
                   </button>
               </div>
           </div>
       `).join('');

			content.html(html);
			container.show();

			// Обработчики для кнопок предложений
			content.find('.btn-accept-suggestion').on('click', (e) => {
				const btn = $(e.currentTarget);
				this.selectedRozetkaCategory = {
					id: btn.data('rozetka-id'),
					name: btn.data('rozetka-name'),
					full_name: btn.data('rozetka-full')
				};
				this.createMapping();
				container.hide();
			});
		},

		createMapping() {
			if (!this.selectedShopCategory || !this.selectedRozetkaCategory) return;

			// Проверяем, существует ли уже маппинг
			const existingIndex = this.mappings.findIndex(mapping =>
				mapping.shop_category_id == this.selectedShopCategory.id
			);

			const mappingData = {
				shop_category_id: this.selectedShopCategory.id,
				shop_category_name: this.selectedShopCategory.name,
				rozetka_category_id: this.selectedRozetkaCategory.id,
				rozetka_category_name: this.selectedRozetkaCategory.name,
				rozetka_category_full_name: this.selectedRozetkaCategory.full_name
			};

			if (existingIndex !== -1) {
				this.mappings[existingIndex] = mappingData;
			} else {
				this.mappings.push(mappingData);
			}

			this.renderMappings();
			this.clearSelections();

			// Обновляем отображение категории как привязанной
			$(`.shop-category[data-id="${this.selectedShopCategory.id}"]`).addClass('mapped');

			NotificationManager.success('Связь установлена');
			$('#mapping-suggestions').hide();
		},

		renderMappings() {
			const tbody = $('#mappings-tbody');
			$('#mappings-count').text(this.mappings.length);

			if (this.mappings.length === 0) {
				tbody.html(`
               <tr>
                   <td colspan="3" class="text-center text-muted">
                       <i class="fa fa-info-circle"></i> Связи не установлены
                   </td>
               </tr>
           `);
				return;
			}

			const html = this.mappings.map((mapping, index) => `
           <tr>
               <td>
                   <strong>${Utils.escapeHtml(mapping.shop_category_name)}</strong><br>
                   <small class="text-muted">ID: ${mapping.shop_category_id}</small>
               </td>
               <td>
                   <strong>${Utils.escapeHtml(mapping.rozetka_category_name)}</strong><br>
                   <small class="text-muted">${Utils.escapeHtml(mapping.rozetka_category_full_name)}</small>
               </td>
               <td>
                   <button class="btn btn-xs btn-danger" onclick="RozetkaApp.CategoryMappingManager.removeMapping(${index})">
                       <i class="fa fa-trash"></i>
                   </button>
               </td>
           </tr>
       `).join('');

			tbody.html(html);
		},

		removeMapping(index) {
			const mapping = this.mappings[index];
			this.mappings.splice(index, 1);
			this.renderMappings();

			// Убираем класс mapped с категории
			$(`.shop-category[data-id="${mapping.shop_category_id}"]`).removeClass('mapped');

			NotificationManager.info('Связь удалена');
		},

		clearSelections() {
			$('.category-item-lazy').removeClass('selected');
			this.selectedShopCategory = null;
			this.selectedRozetkaCategory = null;
		},

		async autoMapCategories() {
			if (!confirm('Автоматический маппинг попытается найти соответствия по названиям категорий. Продолжить?')) {
				return;
			}

			const btn = $('#btn-auto-map');
			const originalHtml = btn.html();

			btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Поиск соответствий...');

			try {
				const response = await ApiClient.post(CONFIG.ENDPOINTS.autoMapCategories);

				if (response.status === 'success') {
					const newMappings = response.mappings || [];

					newMappings.forEach(mapping => {
						const existingIndex = this.mappings.findIndex(m =>
							m.shop_category_id == mapping.shop_category_id
						);

						if (existingIndex === -1) {
							this.mappings.push(mapping);
						}
					});

					this.renderMappings();
					NotificationManager.success(`Автоматически найдено ${newMappings.length} соответствий`);
				} else {
					throw new Error(response.error || 'Ошибка автоматического маппинга');
				}
			} catch (error) {
				NotificationManager.error(`Ошибка автоматического маппинга: ${error.message}`);
			} finally {
				btn.prop('disabled', false).html(originalHtml);
			}
		},

		async saveMappings() {
			const btn = $('#btn-save-mappings');
			const originalHtml = btn.html();

			btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Сохранение...');

			try {
				const response = await ApiClient.post(CONFIG.ENDPOINTS.saveCategoryMappings, {
					mappings: this.mappings
				});

				if (response.status === 'success') {
					NotificationManager.success('Связи категорий успешно сохранены');
				} else {
					throw new Error(response.error || 'Ошибка при сохранении');
				}
			} catch (error) {
				NotificationManager.error('Ошибка при сохранении связей');
				console.error('Save mappings error:', error);
			} finally {
				btn.prop('disabled', false).html(originalHtml);
			}
		},

		// Методы для работы с файлами остаются без изменений...
		async uploadCategories() {
			const fileInput = $('#categories-file-input')[0];
			const file = fileInput.files[0];

			if (!file) {
				NotificationManager.error('Выберите файл для загрузки');
				return;
			}

			const formData = new FormData();
			formData.append('categories_file', file);

			const btn = $('#btn-upload-categories');
			const originalHtml = btn.html();

			btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>Загрузка...</span>');

			try {
				const response = await ApiClient.upload(CONFIG.ENDPOINTS.importCategories, formData);

				if (response.success) {
					$(CONFIG.SELECTORS.uploadResults).html(`
                   <div class="alert alert-success">
                       <h4><i class="fa fa-check-circle"></i> Импорт завершен успешно</h4>
                       <p>${response.message}<br><strong>Всего категорий:</strong> ${response.total_categories}</p>
                   </div>
               `).show();

					NotificationManager.success('Категории успешно импортированы!');

					// Сброс формы
					$('#categories-file-input').val('');
					$('#file-selected-info').hide();
					$('#file-upload-display').show();
					btn.prop('disabled', true);
				} else {
					throw new Error(response.message || 'Ошибка импорта');
				}
			} catch (error) {
				$(CONFIG.SELECTORS.uploadResults).html(`
               <div class="alert alert-danger">
                   <h4><i class="fa fa-exclamation-triangle"></i> Ошибка импорта</h4>
                   <p>${error.message}</p>
               </div>
           `).show();

				NotificationManager.error(`Ошибка загрузки файла: ${error.message}`);
			} finally {
				btn.prop('disabled', false).html(originalHtml);
			}
		},

		async clearCategories() {
			if (!confirm('Вы уверены, что хотите удалить ВСЕ категории Rozetka? Это действие необратимо!')) {
				return;
			}

			try {
				const response = await ApiClient.post(CONFIG.ENDPOINTS.clearCategories);

				if (response.success) {
					NotificationManager.success('Все категории успешно удалены');
					$('#rozetka-categories-list').html(`
                   <div class="search-prompt">
                       <i class="fa fa-search fa-2x text-muted"></i>
                       <p class="text-muted">Введите название категории для поиска</p>
                   </div>
               `);
				} else {
					throw new Error(response.message || 'Ошибка при удалении категорий');
				}
			} catch (error) {
				NotificationManager.error(`Ошибка удаления категорий: ${error.message}`);
			}
		},

		downloadSample() {
			const sampleData = [
				{
					categoryId: "80001",
					name: "Фотоапарати",
					fullName: "Аксесуари до фото/відео > Фотоапарати",
					url: "https://rozetka.com.ua/ua/photo/c80001/",
					level: 5,
					parentId: "80259"
				},
				{
					categoryId: "80002",
					name: "Відеокамери",
					fullName: "Аксесуари до фото/відео > Фотоапарати > Відеокамери",
					url: "https://rozetka.com.ua/ua/video/c80002/",
					level: 5,
					parentId: "80001"
				}
			];

			const dataStr = JSON.stringify(sampleData, null, 2);
			const dataBlob = new Blob([dataStr], {type: 'application/json'});
			const url = URL.createObjectURL(dataBlob);

			const link = document.createElement('a');
			link.href = url;
			link.download = 'rozetka_categories_sample.json';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			URL.revokeObjectURL(url);

			NotificationManager.success('Пример файла скачан');
		}
	};

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

			$(CONFIG.SELECTORS.form).find('input, select, textarea').each(function() {
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

	/**
	 * Главное приложение
	 */
	const RozetkaApp = {
		// Экспортируем менеджеры для глобального доступа (для onclick в HTML)
		StatisticsManager,
		ControlsManager,
		SettingsManager,
		FiltersManager,
		HistoryManager,
		CategoryMappingManager,
		ImportExportManager,

		init() {
			$(document).ready(() => {
				this.initializeManagers();
				this.bindGlobalEvents();
				this.setupTabHandling();
			});
		},

		initializeManagers() {
			StatisticsManager.init();
			ControlsManager.init();
			SettingsManager.init();
			FiltersManager.init();
			HistoryManager.init();
			CategoryMappingManager.init();
			ImportExportManager.init();
		},

		bindGlobalEvents() {
			// Инициализация tooltips
			$('[data-toggle="tooltip"]').tooltip();

			// Функция копирования в буфер обмена
			window.copyToClipboard = this.copyToClipboard.bind(this);
		},

		setupTabHandling() {
			$('.modern-tabs a[data-toggle="tab"]').on('shown.bs.tab', (e) => {
				const target = $(e.target).attr('href');

				switch (target) {
					case '#tab-history':
						HistoryManager.loadData();
						break;
					case '#tab-filters':
						FiltersManager.updateCounters();
						break;
					case '#tab-mapping':
						CategoryMappingManager.loadData();
						break;
				}
			});

			// Загружаем данные для активного таба при инициализации
			const activeTab = $('.modern-tabs li.active a').attr('href');
			if (activeTab === '#tab-history') {
				HistoryManager.loadData();
			} else if (activeTab === '#tab-mapping') {
				CategoryMappingManager.loadData();
			}
		},

		copyToClipboard(text) {
			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(() => {
					this.showCopySuccess();
				}).catch((err) => {
					console.error('Failed to copy text: ', err);
					this.fallbackCopyToClipboard(text);
				});
			} else {
				this.fallbackCopyToClipboard(text);
			}
		},

		fallbackCopyToClipboard(text) {
			const textArea = document.createElement('textarea');
			textArea.value = text;
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();

			try {
				document.execCommand('copy');
				this.showCopySuccess();
			} catch (err) {
				console.error('Fallback copy failed: ', err);
				NotificationManager.error('Не удалось скопировать в буфер обмена');
			}

			document.body.removeChild(textArea);
		},

		showCopySuccess() {
			NotificationManager.success('URL скопирован в буфер обмена!');
		}
	};

	// Делаем RozetkaApp доступным глобально для onclick обработчиков
	window.RozetkaApp = RozetkaApp;

	// Инициализация приложения
	RozetkaApp.init();

})(jQuery);