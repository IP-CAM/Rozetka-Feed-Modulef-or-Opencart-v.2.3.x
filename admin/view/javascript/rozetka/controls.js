(function(window, $){
    'use strict';
    const CONFIG = window.Rozetka.CONFIG;
    const NotificationManager = window.Rozetka.NotificationManager;
    const ApiClient = window.Rozetka.ApiClient;
    const Utils = window.Rozetka.Utils;
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
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.ControlsManager = ControlsManager;
})(window, jQuery);
