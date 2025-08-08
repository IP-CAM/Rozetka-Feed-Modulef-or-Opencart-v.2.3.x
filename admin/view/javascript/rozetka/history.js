(function(window, $){
    'use strict';
    const CONFIG = window.Rozetka.CONFIG;
    const NotificationManager = window.Rozetka.NotificationManager;
    const ApiClient = window.Rozetka.ApiClient;
    const Utils = window.Rozetka.Utils;
    const ControlsManager = window.Rozetka.ControlsManager;
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
                        <i class="fa fa-times"></i> Ошибка
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
            html += `<li${this.currentPage === 1 ? ' class="disabled"' : ''}>`;
            html += `<a href="#" onclick="RozetkaApp.HistoryManager.goToPage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'onclick="return false;"' : ''}>`;
            html += '<i class="fa fa-chevron-left"></i></a></li>';

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
            const warningsList = warnings.map(warning => `<li>${Utils.escapeHtml(warning)}</li>`).join('');
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
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.HistoryManager = HistoryManager;
})(window, jQuery);
