(function(window, $){
    'use strict';
    const NotificationManager = window.Rozetka.NotificationManager;
    const RozetkaApp = {
        StatisticsManager: window.Rozetka.StatisticsManager,
        ControlsManager: window.Rozetka.ControlsManager,
        SettingsManager: window.Rozetka.SettingsManager,
        FiltersManager: window.Rozetka.FiltersManager,
        HistoryManager: window.Rozetka.HistoryManager,
        CategoryMappingManager: window.Rozetka.CategoryMappingManager,
        ImportExportManager: window.Rozetka.ImportExportManager,

        init() {
            $(document).ready(() => {
                this.initializeManagers();
                this.bindGlobalEvents();
                this.setupTabHandling();
            });
        },

        initializeManagers() {
            this.StatisticsManager.init();
            this.ControlsManager.init();
            this.SettingsManager.init();
            this.FiltersManager.init();
            this.HistoryManager.init();
            this.CategoryMappingManager.init();
            this.ImportExportManager.init();
        },

        bindGlobalEvents() {
            $('[data-toggle="tooltip"]').tooltip();
            window.copyToClipboard = this.copyToClipboard.bind(this);
        },

        setupTabHandling() {
            $('.modern-tabs a[data-toggle="tab"]').on('shown.bs.tab', (e) => {
                const target = $(e.target).attr('href');
                switch (target) {
                    case '#tab-history':
                        this.HistoryManager.loadData();
                        break;
                    case '#tab-filters':
                        this.FiltersManager.updateCounters();
                        break;
                    case '#tab-mapping':
                        this.CategoryMappingManager.loadData();
                        break;
                }
            });
            const activeTab = $('.modern-tabs li.active a').attr('href');
            if (activeTab === '#tab-history') {
                this.HistoryManager.loadData();
            } else if (activeTab === '#tab-mapping') {
                this.CategoryMappingManager.loadData();
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
    RozetkaApp.init();
})(window, jQuery);
