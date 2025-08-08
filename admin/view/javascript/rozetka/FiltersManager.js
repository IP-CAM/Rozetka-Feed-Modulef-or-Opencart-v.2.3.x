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
		$(Config.SELECTORS.categoriesSearch).on('input',
			Utils.debounce((e) => this.filterItems(Config.SELECTORS.categoriesTree, e.target.value), 300)
		);

		// Поиск в производителях
		$(Config.SELECTORS.manufacturersSearch).on('input',
			Utils.debounce((e) => this.filterItems(Config.SELECTORS.manufacturersTree, e.target.value), 300)
		);

		// Кнопки выбора всех/снятия всех
		$('#select-all-categories').on('click', () => this.selectAll(Config.SELECTORS.categoriesTree, true));
		$('#deselect-all-categories').on('click', () => this.selectAll(Config.SELECTORS.categoriesTree, false));
		$('#select-all-manufacturers').on('click', () => this.selectAll(Config.SELECTORS.manufacturersTree, true));
		$('#deselect-all-manufacturers').on('click', () => this.selectAll(Config.SELECTORS.manufacturersTree, false));

		// Обновление счетчиков при изменении чекбоксов
		$(Config.SELECTORS.categoriesTree).on('change', 'input[type="checkbox"]', () =>
			this.updateCounter(Config.SELECTORS.categoriesCounter, `${Config.SELECTORS.categoriesTree} input[type="checkbox"]:checked`)
		);

		$(Config.SELECTORS.manufacturersTree).on('change', 'input[type="checkbox"]', () =>
			this.updateCounter(Config.SELECTORS.manufacturersCounter, `${Config.SELECTORS.manufacturersTree} input[type="checkbox"]:checked`)
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
		this.updateCounter(Config.SELECTORS.categoriesCounter, `${Config.SELECTORS.categoriesTree} input[type="checkbox"]:checked`);
		this.updateCounter(Config.SELECTORS.manufacturersCounter, `${Config.SELECTORS.manufacturersTree} input[type="checkbox"]:checked`);
	}
};