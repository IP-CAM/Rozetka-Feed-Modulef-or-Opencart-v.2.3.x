(function(window, $){
    'use strict';
    const CONFIG = window.Rozetka.CONFIG;
    const Utils = window.Rozetka.Utils;
    const FiltersManager = {
        init() {
            this.bindEvents();
            this.updateCounters();
        },

        bindEvents() {
            $(CONFIG.SELECTORS.categoriesSearch).on('input',
                Utils.debounce((e) => this.filterItems(CONFIG.SELECTORS.categoriesTree, e.target.value), 300)
            );

            $(CONFIG.SELECTORS.manufacturersSearch).on('input',
                Utils.debounce((e) => this.filterItems(CONFIG.SELECTORS.manufacturersTree, e.target.value), 300)
            );

            $('#select-all-categories').on('click', () => this.selectAll(CONFIG.SELECTORS.categoriesTree, true));
            $('#deselect-all-categories').on('click', () => this.selectAll(CONFIG.SELECTORS.categoriesTree, false));
            $('#select-all-manufacturers').on('click', () => this.selectAll(CONFIG.SELECTORS.manufacturersTree, true));
            $('#deselect-all-manufacturers').on('click', () => this.selectAll(CONFIG.SELECTORS.manufacturersTree, false));

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
    window.Rozetka = window.Rozetka || {};
    window.Rozetka.FiltersManager = FiltersManager;
})(window, jQuery);
