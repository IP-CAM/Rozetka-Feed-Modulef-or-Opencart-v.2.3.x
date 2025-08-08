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
	TabSaveManager,

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
		TabSaveManager.init();
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

window.RozetkaApp = RozetkaApp;