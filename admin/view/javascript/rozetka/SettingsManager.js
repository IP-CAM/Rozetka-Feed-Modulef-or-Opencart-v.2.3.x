/**
 * Управление настройками формы
 */
const SettingsManager = {
	init() {
		this.bindEvents();
		this.initializeControls();
	},

	bindEvents() {
		$(Config.SELECTORS.qualitySlider).on('input', this.updateQualityValue);
		$(Config.SELECTORS.priceInputs).on('input blur', this.handlePriceInput);
		$(Config.SELECTORS.form).on('submit', (e) => this.validateForm(e));
	},

	initializeControls() {
		// Инициализация toggle переключателей
		$('input[type="checkbox"]').each(function() {
			const toggleText = $(this).closest('.toggle-switch').find('.toggle-text');
			if (toggleText.length && $(this).attr('name') === 'feed_rozetka_stock_status') {
				const isChecked = $(this).is(':checked');
				toggleText.text(isChecked ? 'Включать товары без наличия' : 'Только товары в наличии');
			}
		});

		// Инициализация tooltips
		$('[data-toggle="tooltip"]').tooltip();
	},

	updateQualityValue() {
		const value = $(this).val();
		$(Config.SELECTORS.qualityValue).text(value + '%');
	},

	handlePriceInput(e) {
		const $input = $(e.target);
		const value = $input.val().replace(/[^\d.,]/g, '');
		$input.val(value);

		if (e.type === 'blur') {
			const numValue = parseFloat(value.replace(',', '.'));
			if (isNaN(numValue) || numValue < 0) {
				$input.val('');
			} else {
				$input.val(numValue.toFixed(2));
			}
			this.validatePriceRange();
		}
	},

	validatePriceRange() {
		const minPrice = parseFloat($('input[name="feed_rozetka_min_price"]').val()) || 0;
		const maxPrice = parseFloat($('input[name="feed_rozetka_max_price"]').val()) || 0;

		$('.price-error').remove();

		if (minPrice > 0 && maxPrice > 0 && minPrice >= maxPrice) {
			$('input[name="feed_rozetka_max_price"]').closest('.form-group').append(`
                    <div class="text-danger price-error">
                        <i class="fa fa-exclamation-triangle"></i> 
                        Минимальная цена не может быть больше максимальной
                    </div>
                `);
		}
	},

	validateForm(e) {
		let hasErrors = false;

		// Очистка предыдущих ошибок
		$('.validation-error').remove();

		// Валидация цен
		this.validatePriceRange();
		if ($('.price-error').length > 0) {
			hasErrors = true;
		}

		// Валидация размеров изображений
		const imageWidth = parseInt($('input[name="feed_rozetka_image_width"]').val()) || 0;
		const imageHeight = parseInt($('input[name="feed_rozetka_image_height"]').val()) || 0;

		if (imageWidth > 0 && (imageWidth < 100 || imageWidth > 2000)) {
			this.showFieldError('input[name="feed_rozetka_image_width"]', 'Ширина должна быть от 100 до 2000 пикселей');
			hasErrors = true;
		}

		if (imageHeight > 0 && (imageHeight < 100 || imageHeight > 2000)) {
			this.showFieldError('input[name="feed_rozetka_image_height"]', 'Высота должна быть от 100 до 2000 пикселей');
			hasErrors = true;
		}

		if (hasErrors) {
			e.preventDefault();
			NotificationManager.warning('Пожалуйста, исправьте ошибки в форме');
			$('a[href="#tab-settings"]').tab('show');
		}
	},

	showFieldError(fieldSelector, message) {
		$(fieldSelector).after(`
                <div class="text-danger validation-error">
                    <i class="fa fa-exclamation-triangle"></i> ${message}
                </div>
            `);
	}
};