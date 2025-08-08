/**
 * Управление статистикой
 */
const StatisticsManager = {
	init() {
		this.bindEvents();
	},

	bindEvents() {
		$(Config.SELECTORS.refreshStatsBtn).on('click', () => this.refresh());
	},

	async refresh() {
		const btn = $(Config.SELECTORS.refreshStatsBtn);
		const icon = btn.find('i');

		btn.prop('disabled', true);
		icon.removeClass('fa-refresh').addClass('fa-spinner fa-spin');

		try {
			const response = await ApiClient.get(Config.ENDPOINTS.getStatistics);

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
		const cards = $(Config.SELECTORS.statisticsCards);

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
		const statusCard = $(Config.SELECTORS.lastGenerationCard);
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