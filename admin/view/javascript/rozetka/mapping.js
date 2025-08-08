(function(window, $){
    'use strict';
    const CONFIG = window.Rozetka.CONFIG;
    const NotificationManager = window.Rozetka.NotificationManager;
    const ApiClient = window.Rozetka.ApiClient;
    const Utils = window.Rozetka.Utils;
    const CategoryMappingManager = {
        mappings: [],
        selectedShopCategory: null,
        selectedRozetkaCategory: null,
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

            displayArea.on('click', () => fileInput.click());

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

            $('#btn-remove-file').on('click', () => {
                fileInput.val('');
                selectedInfo.hide();
                displayArea.show();
                $('#btn-upload-categories').prop('disabled', true);
            });
        },

        bindEvents() {
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

            $('#categories-file-input').on('change', (e) => {
                if (e.target.files.length > 0) {
                    this.handleFileSelection(e.target.files[0]);
                }
            });

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
                console.error('Mapping load error:', error);
            }
        },

        async loadShopCategories(search = '') {
            $('#shop-categories-list').html('<div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i></div>');
            try {
                const response = await ApiClient.get(CONFIG.ENDPOINTS.getShopCategories, { search });
                if (response.status === 'success') {
                    this.renderCategories('shop', response.categories);
                }
            } catch (error) {
                NotificationManager.error('Ошибка загрузки категорий магазина');
            }
        },

        async loadRozetkaCategories(search = '') {
            $('#rozetka-categories-list').html('<div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i></div>');
            try {
                const response = await ApiClient.get(CONFIG.ENDPOINTS.getRozetkaCategories, { search });
                if (response.status === 'success') {
                    this.renderCategories('rozetka', response.categories);
                }
            } catch (error) {
                NotificationManager.error('Ошибка загрузки категорий Rozetka');
            }
        },

        renderCategories(type, categories) {
            const list = type === 'shop' ? '#shop-categories-list' : '#rozetka-categories-list';
            if (!categories || categories.length === 0) {
                $(list).html(`
                    <div class="search-prompt">
                        <i class="fa fa-search fa-2x text-muted"></i>
                        <p class="text-muted">Введите название категории для поиска</p>
                    </div>
                `);
                return;
            }

            const items = categories.map(cat => `
                <li class="list-group-item" data-id="${cat.category_id}" data-name="${Utils.escapeHtml(cat.name)}">
                    <div class="category-item">
                        <span class="cat-name">${Utils.escapeHtml(cat.name)}</span>
                        <small class="cat-path text-muted">${Utils.escapeHtml(cat.path || '')}</small>
                    </div>
                </li>
            `).join('');
            $(list).html(`<ul class="list-group">${items}</ul>`);

            $(list).find('li').on('click', (e) => {
                const li = $(e.currentTarget);
                const id = li.data('id');
                const name = li.data('name');
                if (type === 'shop') {
                    this.selectedShopCategory = { id, name };
                    $('#shop-categories-list li').removeClass('active');
                } else {
                    this.selectedRozetkaCategory = { id, name };
                    $('#rozetka-categories-list li').removeClass('active');
                }
                li.addClass('active');
            });
        },

        addMapping() {
            if (!this.selectedShopCategory) {
                NotificationManager.warning('Сначала выберите категорию магазина');
                return;
            }
            if (!this.selectedRozetkaCategory) {
                NotificationManager.warning('Выберите категорию Rozetka для сопоставления');
                return;
            }

            const exists = this.mappings.some(m => m.shop_id === this.selectedShopCategory.id);
            if (exists) {
                NotificationManager.warning('Эта категория уже привязана');
                return;
            }

            this.mappings.push({
                shop_id: this.selectedShopCategory.id,
                shop_name: this.selectedShopCategory.name,
                rozetka_id: this.selectedRozetkaCategory.id,
                rozetka_name: this.selectedRozetkaCategory.name
            });
            this.renderMappings();
        },

        removeMapping(index) {
            this.mappings.splice(index, 1);
            this.renderMappings();
            NotificationManager.info('Связь удалена');
        },

        renderMappings() {
            if (this.mappings.length === 0) {
                $(CONFIG.SELECTORS.mappingsTable).html('<tr><td colspan="3" class="text-center text-muted">Нет связей</td></tr>');
                return;
            }

            const rows = this.mappings.map((m, index) => `
                <tr>
                    <td>${Utils.escapeHtml(m.shop_name)}</td>
                    <td>${Utils.escapeHtml(m.rozetka_name)}</td>
                    <td>
                        <button class="btn btn-xs btn-danger" onclick="RozetkaApp.CategoryMappingManager.removeMapping(${index})">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            $(CONFIG.SELECTORS.mappingsTable).html(rows);
        },

        async autoMapCategories() {
            const btn = $('#btn-auto-map');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>Поиск...</span>');
            try {
                const response = await ApiClient.get(CONFIG.ENDPOINTS.autoMapCategories);
                if (response.status === 'success' && response.mappings) {
                    const newMappings = response.mappings.filter(m => !this.mappings.some(existing => existing.shop_id === m.shop_id));
                    this.mappings = this.mappings.concat(newMappings);
                    this.renderMappings();
                    NotificationManager.success(`Автоматически найдено ${newMappings.length} соответствий`);
                } else {
                    throw new Error(response.error || 'Не удалось выполнить автоматический маппинг');
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
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>Сохранение...</span>');
            try {
                const response = await ApiClient.post(CONFIG.ENDPOINTS.saveCategoryMappings, { mappings: this.mappings });
                if (response.status === 'success') {
                    NotificationManager.success('Связи категорий успешно сохранены');
                } else {
                    throw new Error(response.error || 'Ошибка сохранения маппинга');
                }
            } catch (error) {
                NotificationManager.error('Ошибка при сохранении связей');
                console.error('Save mappings error:', error);
            } finally {
                btn.prop('disabled', false).html(originalHtml);
            }
        },

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
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.CategoryMappingManager = CategoryMappingManager;
})(window, jQuery);
