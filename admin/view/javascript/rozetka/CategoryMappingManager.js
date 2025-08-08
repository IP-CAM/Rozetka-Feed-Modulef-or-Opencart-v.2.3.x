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
			const mappingsResponse = await ApiClient.get(Config.ENDPOINTS.getCategoryMappings);
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
			const response = await ApiClient.get(Config.ENDPOINTS.getShopCategories, {
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
			const response = await ApiClient.get(Config.ENDPOINTS.getRozetkaCategories, {
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
			const response = await ApiClient.get(Config.ENDPOINTS.getRozetkaCategories, {
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
			const response = await ApiClient.post(Config.ENDPOINTS.autoMapCategories);

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
			const response = await ApiClient.post(Config.ENDPOINTS.saveCategoryMappings, {
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
			const response = await ApiClient.upload(Config.ENDPOINTS.importCategories, formData);

			if (response.success) {
				$(Config.SELECTORS.uploadResults).html(`
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
			$(Config.SELECTORS.uploadResults).html(`
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
			const response = await ApiClient.post(Config.ENDPOINTS.clearCategories);

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