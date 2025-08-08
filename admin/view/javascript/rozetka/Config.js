// Конфигурация и константы
const Config = {
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
		autoMapCategories: 'extension/feed/rozetka/autoMapCategories',
		getStatistics: 'extension/feed/rozetka/getStatistics',
		testGeneration: 'extension/feed/rozetka/testGeneration',
		generatePreview: 'extension/feed/rozetka/generatePreview',
		clearCache: 'extension/feed/rozetka/clearCache',
		getHistory: 'extension/feed/rozetka/getGenerationHistory',
		getShopCategories: 'extension/feed/rozetka/getShopCategories',
		getRozetkaCategories: 'extension/feed/rozetka/getRozetkaCategories',
		getCategoryMappings: 'extension/feed/rozetka/getCategoryMappings',
		saveCategoryMappings: 'extension/feed/rozetka/saveCategoryMappings',
		removeCategoryMapping: 'extension/feed/rozetka/removeCategoryMapping',
		clearAllMappings: 'extension/feed/rozetka/clearAllMappings',
		importCategories: 'extension/feed/rozetka/importCategories',
		clearCategories: 'extension/feed/rozetka/clearCategories'
	}
};