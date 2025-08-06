let historyData = [];
let currentPage = 1;
let itemsPerPage = 10;

$(document).ready(function() {
	// Initialize tooltips
	$('[data-toggle="tooltip"]').tooltip();

	// Quality slider update
	$('#quality-slider').on('input', function() {
		$('#quality-value').text($(this).val() + '%');
	});

	$('#btn-export-settings').click(function() {
		var btn = $(this);
		var originalHtml = btn.html();

		btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Экспорт...');

		// Create download link for current settings
		var settings = {};
		$('form#form-rozetka').find('input, select, textarea').each(function() {
			var name = $(this).attr('name');
			var value = $(this).val();

			if (name && name.startsWith('feed_rozetka_')) {
				if ($(this).attr('type') === 'checkbox') {
					settings[name] = $(this).is(':checked') ? '1' : '0';
				} else {
					settings[name] = value;
				}
			}
		});

		var dataStr = JSON.stringify(settings, null, 2);
		var dataBlob = new Blob([dataStr], {type: 'application/json'});
		var url = URL.createObjectURL(dataBlob);
		var link = document.createElement('a');
		link.href = url;
		link.download = 'rozetka_feed_settings_' + new Date().toISOString().slice(0,10) + '.json';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
		URL.revokeObjectURL(url);

		setTimeout(function() {
			btn.prop('disabled', false).html(originalHtml);
			showNotification('success', 'Настройки экспортированы', 'fa-download');
		}, 1000);
	});

	$('#btn-select-file').click(function() {
		$('#import-file').click();
	});

	$('#import-file').change(function() {
		var file = this.files[0];
		if (file) {
			$('#selected-file-name').text(file.name).show();
			$('#btn-import-settings').show();
		} else {
			$('#selected-file-name').hide();
			$('#btn-import-settings').hide();
		}
	});

	$('#btn-import-settings').click(function() {
		var file = $('#import-file')[0].files[0];
		if (!file) {
			showNotification('danger', 'Выберите файл для импорта', 'fa-exclamation-triangle');
			return;
		}

		var btn = $(this);
		var originalHtml = btn.html();
		btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Импорт...');

		var reader = new FileReader();
		reader.onload = function(e) {
			try {
				var settings = JSON.parse(e.target.result);

				// Apply imported settings to form
				$.each(settings, function(name, value) {
					var field = $('[name="' + name + '"]');
					if (field.length) {
						if (field.attr('type') === 'checkbox') {
							field.prop('checked', value === '1' || value === true);
						} else {
							field.val(value);
						}
					}
				});

				// Update toggle switches text
				$('input[type="checkbox"]').trigger('change');

				showNotification('success', 'Настройки успешно импортированы', 'fa-upload');

			} catch (error) {
				showNotification('danger', 'Ошибка чтения файла: неверный формат JSON', 'fa-exclamation-triangle');
			}

			btn.prop('disabled', false).html(originalHtml);
		};

		reader.readAsText(file);
	});

	// Enhanced statistics refresh
	$('#btn-refresh-stats').click(function() {
		var btn = $(this);
		var icon = btn.find('i');

		btn.prop('disabled', true);
		icon.removeClass('fa-refresh').addClass('fa-spinner fa-spin');
		btn.text(' Загрузка...');
		btn.prepend(icon);

		$.ajax({
			url: 'index.php?route=extension/feed/rozetka/getStatistics&token=' + getURLVar('token'),
			type: 'get',
			dataType: 'json',
			success: function(json) {
				if (json.status === 'success') {
					updateStatistics(json);
					showNotification('success', 'Статистика обновлена', 'fa-check-circle');
				} else if (json.error) {
					showNotification('danger', json.error, 'fa-exclamation-triangle');
				}
			},
			error: function() {
				showNotification('danger', 'Ошибка при получении статистики', 'fa-exclamation-triangle');
			},
			complete: function() {
				btn.prop('disabled', false);
				icon.removeClass('fa-spinner fa-spin').addClass('fa-refresh');
				btn.html('<i class="fa fa-refresh"></i> Обновить');
			}
		});
	});

	// Enhanced test generation
	$('#btn-test-generation').click(function() {
		var btn = $(this);
		setButtonLoading(btn, 'Тестирование...');

		$.ajax({
			url: 'index.php?route=extension/feed/rozetka/testGeneration&token=' + getURLVar('token') + '&limit=10',
			type: 'get',
			dataType: 'json',
			success: function(json) {
				if (json.status === 'success') {
					showTestResults(json);
				} else if (json.error || json.error_message) {
					showNotification('danger', json.error || json.error_message, 'fa-exclamation-triangle');
				}
			},
			error: function() {
				showNotification('danger', 'Ошибка при выполнении теста', 'fa-exclamation-triangle');
			},
			complete: function() {
				resetButton(btn, '<i class="fa fa-flask"></i>', 'Тест генерации', 'Проверка на 10 товарах');
			}
		});
	});

	// Enhanced preview generation
	$('#btn-generate-preview').click(function() {
		var btn = $(this);
		setButtonLoading(btn, 'Генерация...');

		$.ajax({
			url: 'index.php?route=extension/feed/rozetka/generatePreview&token=' + getURLVar('token') + '&limit=20',
			type: 'get',
			dataType: 'json',
			success: function(json) {
				if (json.status === 'success') {
					showPreview(json);
				} else if (json.error || json.error_message) {
					showNotification('danger', json.error || json.error_message, 'fa-exclamation-triangle');
				}
			},
			error: function() {
				showNotification('danger', 'Ошибка при генерации предпросмотра', 'fa-exclamation-triangle');
			},
			complete: function() {
				resetButton(btn, '<i class="fa fa-eye"></i>', 'Предпросмотр', 'XML первых товаров');
			}
		});
	});

	// Enhanced cache clearing
	$('#btn-clear-cache').click(function() {
		if (!confirm('Вы уверены, что хотите очистить кэш?')) {
			return;
		}

		var btn = $(this);
		setButtonLoading(btn, 'Очистка...');

		$.ajax({
			url: 'index.php?route=extension/feed/rozetka/clearCache&token=' + getURLVar('token'),
			type: 'get',
			dataType: 'json',
			success: function(json) {
				if (json.status === 'success') {
					showNotification('success', json.success, 'fa-check-circle');
				} else if (json.error) {
					showNotification('danger', json.error, 'fa-exclamation-triangle');
				}
			},
			error: function() {
				showNotification('danger', 'Ошибка при очистке кэша', 'fa-exclamation-triangle');
			},
			complete: function() {
				resetButton(btn, '<i class="fa fa-trash"></i>', 'Очистить кэш', 'Удалить временные файлы');
			}
		});
	});

	$('.modern-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var target = $(e.target).attr("href");

		// Load content for specific tabs
		if (target === '#tab-history') {
			loadHistoryData();
		}

		// Update counters for filters tab
		if (target === '#tab-filters') {
			updateFilterCounters();
		}
	});

	// === FILTERS TAB FUNCTIONALITY ===

	// Categories filter
	$('#categories-search').on('input', function() {
		filterTreeItems('#categories-tree', $(this).val());
	});

	$('#select-all-categories').click(function() {
		$('#categories-tree input[type="checkbox"]').prop('checked', true).trigger('change');
	});

	$('#deselect-all-categories').click(function() {
		$('#categories-tree input[type="checkbox"]').prop('checked', false).trigger('change');
	});

	// Manufacturers filter
	$('#manufacturers-search').on('input', function() {
		filterTreeItems('#manufacturers-tree', $(this).val());
	});

	$('#select-all-manufacturers').click(function() {
		$('#manufacturers-tree input[type="checkbox"]').prop('checked', true).trigger('change');
	});

	$('#deselect-all-manufacturers').click(function() {
		$('#manufacturers-tree input[type="checkbox"]').prop('checked', false).trigger('change');
	});

	// Update counters when checkboxes change
	$('#categories-tree input[type="checkbox"]').change(function() {
		updateCounter('#categories-counter', '#categories-tree input[type="checkbox"]:checked');
	});

	$('#manufacturers-tree input[type="checkbox"]').change(function() {
		updateCounter('#manufacturers-counter', '#manufacturers-tree input[type="checkbox"]:checked');
	});

	$('#btn-refresh-history').click(function() {
		var btn = $(this);
		var icon = btn.find('i');

		btn.prop('disabled', true);
		icon.removeClass('fa-refresh').addClass('fa-spinner fa-spin');

		loadHistoryData();

		setTimeout(function() {
			btn.prop('disabled', false);
			icon.removeClass('fa-spinner fa-spin').addClass('fa-refresh');
			showNotification('success', 'История обновлена', 'fa-refresh');
		}, 1000);
	});

	$('#btn-clear-history').click(function() {
		if (!confirm('Вы уверены, что хотите очистить всю историю генераций? Это действие необратимо.')) {
			return;
		}

		var btn = $(this);
		var originalHtml = btn.html();

		btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Очистка...');

		// Here you would make AJAX call to clear history
		// For now, just simulate
		setTimeout(function() {
			historyData = [];
			updateHistoryStats();
			renderHistoryTable();
			renderHistoryPagination();

			btn.prop('disabled', false).html(originalHtml);
			showNotification('success', 'История очищена', 'fa-trash');
		}, 1500);
	});

	const $priceInput = $('.price-input');

	$priceInput.on('input', function() {
		var value = $(this).val().replace(/[^\d.,]/g, '');
		$(this).val(value);
	});

	$priceInput.on('blur', function() {
		var value = parseFloat($(this).val().replace(',', '.'));
		if (isNaN(value) || value < 0) {
			$(this).val('');
		} else {
			$(this).val(value.toFixed(2));
		}

		validatePriceRange();
	});

	$('#form-rozetka').on('submit', function(e) {
		var hasErrors = false;

		// Clear previous validation errors
		$('.validation-error').remove();

		// Validate price range
		validatePriceRange();
		if ($('.price-error').length > 0) {
			hasErrors = true;
		}

		// Validate image dimensions
		var imageWidth = parseInt($('input[name="feed_rozetka_image_width"]').val()) || 0;
		var imageHeight = parseInt($('input[name="feed_rozetka_image_height"]').val()) || 0;

		if (imageWidth > 0 && (imageWidth < 100 || imageWidth > 2000)) {
			showFieldError('input[name="feed_rozetka_image_width"]', 'Ширина должна быть от 100 до 2000 пикселей');
			hasErrors = true;
		}

		if (imageHeight > 0 && (imageHeight < 100 || imageHeight > 2000)) {
			showFieldError('input[name="feed_rozetka_image_height"]', 'Высота должна быть от 100 до 2000 пикселей');
			hasErrors = true;
		}

		if (hasErrors) {
			e.preventDefault();
			showNotification('warning', 'Пожалуйста, исправьте ошибки в форме', 'fa-exclamation-triangle');

			// Switch to settings tab if there are errors
			$('a[href="#tab-settings"]').tab('show');
		}
	});

	if ($('#tab-history').hasClass('active')) {
		loadHistoryData();
	}

	$('input[type="checkbox"]').each(function() {
		var toggleText = $(this).closest('.toggle-switch').find('.toggle-text');
		if (toggleText.length) {
			var isChecked = $(this).is(':checked');
			if ($(this).attr('name') === 'feed_rozetka_stock_status') {
				toggleText.text(isChecked ? 'Включать товары без наличия' : 'Только товары в наличии');
			}
		}
	});

	updateFilterCounters();
});

function filterTreeItems(treeSelector, searchTerm) {
	var items = $(treeSelector + ' .tree-item');
	var searchLower = searchTerm.toLowerCase();

	items.each(function() {
		var itemName = $(this).data('name').toString();
		var itemText = $(this).find('.tree-text').text().toLowerCase();

		if (itemName.indexOf(searchLower) !== -1 || itemText.indexOf(searchLower) !== -1) {
			$(this).show();
		} else {
			$(this).hide();
		}
	});
}

function updateCounter(counterSelector, checkboxSelector) {
	var count = $(checkboxSelector).length;
	$(counterSelector).text(count);
}

function updateFilterCounters() {
	updateCounter('#categories-counter', '#categories-tree input[type="checkbox"]:checked');
	updateCounter('#manufacturers-counter', '#manufacturers-tree input[type="checkbox"]:checked');
}
function loadHistoryData() {
	$('#history-tbody').html('<tr><td colspan="8" class="text-center"><div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i> Загрузка истории...</div></td></tr>');

	$.ajax({
		url: 'index.php?route=extension/feed/rozetka/getGenerationHistory&token=' + getURLVar('token'),
		type: 'get',
		dataType: 'json',
		success: function(json) {
			if (json.status === 'success') {
				historyData = json.history || [];
				updateHistoryStats();
				renderHistoryTable();
				renderHistoryPagination();
			} else {
				$('#history-tbody').html('<tr><td colspan="8" class="text-center text-danger">Ошибка загрузки истории</td></tr>');
			}
		},
		error: function() {
			$('#history-tbody').html('<tr><td colspan="8" class="text-center text-danger">Ошибка соединения</td></tr>');
		}
	});
}

function updateHistoryStats() {
	var total = historyData.length;
	var successful = historyData.filter(function(item) { return item.status === 'success'; }).length;
	var failed = total - successful;

	var totalTime = 0;
	var validTimes = 0;
	historyData.forEach(function(item) {
		if (item.status === 'success' && item.generation_time > 0) {
			totalTime += parseFloat(item.generation_time);
			validTimes++;
		}
	});

	var avgTime = validTimes > 0 ? (totalTime / validTimes).toFixed(2) + 'с' : '-';

	$('#total-generations').text(total);
	$('#successful-generations').text(successful);
	$('#failed-generations').text(failed);
	$('#avg-time').text(avgTime);
}

function renderHistoryTable() {
	var startIndex = (currentPage - 1) * itemsPerPage;
	var endIndex = startIndex + itemsPerPage;
	var pageData = historyData.slice(startIndex, endIndex);

	var html = '';

	if (pageData.length === 0) {
		html = '<tr><td colspan="8" class="text-center text-muted">История генераций пуста</td></tr>';
	} else {
		pageData.forEach(function(item) {
			var statusClass = 'status-' + item.status;
			var statusText = item.status === 'success' ? 'Успешно' :
				item.status === 'error' ? 'Ошибка' : 'Запущена';

			html += '<tr>';
			html += '<td>' + item.date + '</td>';
			html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
			html += '<td>' + (item.products_count || 0) + '</td>';
			html += '<td>' + (item.offers_count || 0) + '</td>';
			html += '<td>' + (item.generation_time || '-') + 'с</td>';
			html += '<td>' + (item.memory_used || '-') + '</td>';
			html += '<td>' + (item.file_size || '-') + '</td>';
			html += '<td>';

			if (item.status === 'error' && item.error_message) {
				html += '<button class="btn btn-xs btn-danger" onclick="showErrorDetails(\'' +
					item.error_message.replace(/'/g, "\\'") + '\')">';
				html += '<i class="fa fa-exclamation-triangle"></i> Ошибка</button> ';
			}

			if (item.warnings && item.warnings.length > 0) {
				html += '<button class="btn btn-xs btn-warning" onclick="showWarningDetails(' +
					JSON.stringify(item.warnings).replace(/"/g, '&quot;') + ')">';
				html += '<i class="fa fa-warning"></i> Предупреждения</button> ';
			}

			html += '<button class="btn btn-xs btn-info" onclick="showGenerationDetails(' + item.id + ')">';
			html += '<i class="fa fa-info"></i> Детали</button>';
			html += '</td>';
			html += '</tr>';
		});
	}

	$('#history-tbody').html(html);
}

// Helper Functions
function setButtonLoading(btn, text) {
	btn.prop('disabled', true).addClass('btn-loading');
	btn.find('.btn-title').text(text);
	btn.find('.btn-description').text('Пожалуйста, подождите...');
}

function resetButton(btn, icon, title, description) {
	btn.prop('disabled', false).removeClass('btn-loading');
	btn.find('.btn-icon').html(icon);
	btn.find('.btn-title').text(title);
	btn.find('.btn-description').text(description);
}

function updateStatistics(data) {
	var cards = $('#statistics-panel .stats-card');

	// Animate number changes only if values actually changed
	animateValueIfChanged(cards.eq(0).find('.stats-number'), data.total_products_feed || 0);
	animateValueIfChanged(cards.eq(1).find('.stats-number'), data.total_products_shop || 0);
	animateValueIfChanged(cards.eq(2).find('.stats-number'), data.products_in_stock || 0);
	animateValueIfChanged(cards.eq(3).find('.stats-number'), data.products_with_images || 0);

	// Update last generation info if exists
	if (data.last_generation) {
		updateLastGeneration(data.last_generation);
	}
}

function animateValueIfChanged(element, newValue) {
	var currentValue = parseInt(element.text().replace(/,/g, '')) || 0;

	if (currentValue !== newValue) {
		animateValue(element, currentValue, newValue);
	}
}

function animateValue(element, start, end) {
	if (start === end) {
		element.text(numberWithCommas(end));
		return;
	}

	var range = Math.abs(end - start);
	var increment = end > start ? Math.ceil(range / 30) : -Math.ceil(range / 30);
	var stepTime = Math.max(10, Math.floor(500 / 30));
	var current = start;

	var timer = setInterval(function() {
		current += increment;

		// Ensure we don't overshoot
		if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
			current = end;
			clearInterval(timer);
		}

		element.text(numberWithCommas(current));
	}, stepTime);
}

function updateLastGeneration(lastGeneration) {
	var statusCard = $('.generation-status-card');

	if (statusCard.length > 0) {
		// Update existing status card
		var date = new Date(lastGeneration.date_generated);
		statusCard.find('.status-date').text(date.toLocaleString('ru-RU'));

		// Update details
		var detailsHtml = '<span class="detail-item">' +
			'<i class="fa fa-shopping-cart"></i> Товаров: <strong>' + numberWithCommas(lastGeneration.products_count || 0) + '</strong>' +
			'</span>' +
			'<span class="detail-item">' +
			'<i class="fa fa-clock-o"></i> Время: <strong>' + (lastGeneration.generation_time || 0) + 'с</strong>' +
			'</span>' +
			'<span class="detail-item">' +
			'<i class="fa fa-file-o"></i> Размер: <strong>' + (lastGeneration.file_size || 'N/A') + '</strong>' +
			'</span>';
		statusCard.find('.status-details').html(detailsHtml);

		// Update badge
		var badgeClass = lastGeneration.status === 'success' ? 'status-success' : 'status-error';
		var badgeIcon = lastGeneration.status === 'success' ? 'fa-check' : 'fa-exclamation-triangle';
		var badgeText = lastGeneration.status === 'success' ? 'Успешно' : 'Ошибка';

		statusCard.find('.status-badge')
			.removeClass('status-success status-error')
			.addClass(badgeClass)
			.html('<i class="fa ' + badgeIcon + '"></i> ' + badgeText);

		// Update error message
		var errorContainer = statusCard.find('.error-message');
		if (lastGeneration.status === 'error' && lastGeneration.error_message) {
			errorContainer.html('<small>' + lastGeneration.error_message + '</small>').show();
		} else {
			errorContainer.hide();
		}
	}
}

function numberWithCommas(x) {
	return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function showNotification(type, message, icon) {
	var alertClass = 'alert-' + type;
	var html = '<div class="alert ' + alertClass + ' alert-dismissible fade in">';
	html += '<button type="button" class="close" data-dismiss="alert">&times;</button>';
	html += '<i class="fa ' + icon + '"></i> ' + message;
	html += '</div>';

	$('#operation-results').html(html);

	setTimeout(function() {
		$('#operation-results .alert').fadeOut(500, function() {
			$(this).remove();
		});
	}, 5000);
}

function showTestResults(json) {
	var html = '<div class="alert alert-success">';
	html += '<h4><i class="fa fa-check-circle"></i> Тест прошел успешно</h4>';
	html += '<div class="row">';
	html += '<div class="col-md-6">';
	html += '<p><strong>Товаров протестировано:</strong> ' + json.test_products_count + '</p>';
	html += '<p><strong>Время генерации:</strong> ' + json.generation_time + 'с</p>';
	html += '<p><strong>Использовано памяти:</strong> ' + json.memory_used + '</p>';
	html += '</div>';
	html += '<div class="col-md-6">';
	html += '<p><strong>Всего товаров:</strong> ' + json.total_products_count + '</p>';
	html += '<p><strong>Прогнозируемое время:</strong> ' + json.estimated_total_time + 'с</p>';
	html += '<p><strong>Прогнозируемая память:</strong> ' + json.estimated_memory + '</p>';
	html += '</div>';
	html += '</div>';

	if (json.warnings && json.warnings.length > 0) {
		html += '<div class="alert alert-warning" style="margin-top: 10px;">';
		html += '<h5><i class="fa fa-warning"></i> Предупреждения:</h5>';
		html += '<ul>';
		$.each(json.warnings, function(i, warning) {
			html += '<li>' + warning + '</li>';
		});
		html += '</ul>';
		html += '</div>';
	}

	html += '</div>';
	$('#operation-results').html(html);
}

function showPreview(json) {
	var modal = $('<div class="modal fade" tabindex="-1">');
	modal.html(
		'<div class="modal-dialog modal-lg">' +
		'<div class="modal-content">' +
		'<div class="modal-header">' +
		'<button type="button" class="close" data-dismiss="modal">&times;</button>' +
		'<h4 class="modal-title"><i class="fa fa-eye"></i> Предпросмотр XML фида</h4>' +
		'</div>' +
		'<div class="modal-body">' +
		'<div class="alert alert-info">' +
		'<p><strong>Товаров:</strong> ' + json.products_count + '</p>' +
		'<p><strong>Предложений:</strong> ' + json.offers_count + '</p>' +
		'<p><strong>Время генерации:</strong> ' + json.generation_time + 'с</p>' +
		'<p><strong>Размер XML:</strong> ' + json.xml_size + '</p>' +
		'</div>' +
		'<pre style="max-height: 400px; overflow: auto; background: #f8f9fa; padding: 15px; border-radius: 4px;">' +
		$('<div>').text(json.xml_content).html() +
		'</pre>' +
		'</div>' +
		'<div class="modal-footer">' +
		'<button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>' +
		'</div>' +
		'</div>' +
		'</div>'
	);

	modal.modal('show');
	modal.on('hidden.bs.modal', function() {
		modal.remove();
	});
}

// Copy to clipboard function
function copyToClipboard(text) {
	if (navigator.clipboard) {
		navigator.clipboard.writeText(text).then(function() {
			showCopySuccess();
		});
	} else {
		// Fallback for older browsers
		var textArea = document.createElement("textarea");
		textArea.value = text;
		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();
		try {
			document.execCommand('copy');
			showCopySuccess();
		} catch (err) {
			console.error('Failed to copy text: ', err);
		}
		document.body.removeChild(textArea);
	}
}

function showCopySuccess() {
	var notification = $('<div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">');
	notification.html('<i class="fa fa-check"></i> URL скопирован в буфер обмена!');
	$('body').append(notification);

	setTimeout(function() {
		notification.fadeOut(500, function() {
			$(this).remove();
		});
	}, 2000);
}

function renderHistoryPagination() {
	var totalPages = Math.ceil(historyData.length / itemsPerPage);
	var html = '';

	if (totalPages <= 1) {
		$('#history-pagination').html('');
		return;
	}

	// Previous button
	html += '<li' + (currentPage === 1 ? ' class="disabled"' : '') + '>';
	html += '<a href="#" onclick="' + (currentPage === 1 ? 'return false;' : 'goToHistoryPage(' + (currentPage - 1) + ')') + '">';
	html += '<i class="fa fa-chevron-left"></i></a></li>';

	// Page numbers
	var startPage = Math.max(1, currentPage - 2);
	var endPage = Math.min(totalPages, currentPage + 2);

	if (startPage > 1) {
		html += '<li><a href="#" onclick="goToHistoryPage(1)">1</a></li>';
		if (startPage > 2) {
			html += '<li class="disabled"><span>...</span></li>';
		}
	}

	for (var i = startPage; i <= endPage; i++) {
		html += '<li' + (i === currentPage ? ' class="active"' : '') + '>';
		html += '<a href="#" onclick="goToHistoryPage(' + i + ')">' + i + '</a></li>';
	}

	if (endPage < totalPages) {
		if (endPage < totalPages - 1) {
			html += '<li class="disabled"><span>...</span></li>';
		}
		html += '<li><a href="#" onclick="goToHistoryPage(' + totalPages + ')">' + totalPages + '</a></li>';
	}

	// Next button
	html += '<li' + (currentPage === totalPages ? ' class="disabled"' : '') + '>';
	html += '<a href="#" onclick="' + (currentPage === totalPages ? 'return false;' : 'goToHistoryPage(' + (currentPage + 1) + ')') + '">';
	html += '<i class="fa fa-chevron-right"></i></a></li>';

	$('#history-pagination').html(html);
}

function goToHistoryPage(page) {
	currentPage = page;
	renderHistoryTable();
	renderHistoryPagination();
	return false;
}

function showErrorDetails(errorMessage) {
	var modal = $('<div class="modal fade" tabindex="-1">');
	modal.html(
		'<div class="modal-dialog">' +
		'<div class="modal-content">' +
		'<div class="modal-header">' +
		'<button type="button" class="close" data-dismiss="modal">&times;</button>' +
		'<h4 class="modal-title"><i class="fa fa-exclamation-triangle text-danger"></i> Ошибка генерации</h4>' +
		'</div>' +
		'<div class="modal-body">' +
		'<div class="alert alert-danger">' +
		'<strong>Сообщение об ошибке:</strong><br>' +
		htmlspecialchars(errorMessage) +
		'</div>' +
		'</div>' +
		'<div class="modal-footer">' +
		'<button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>' +
		'</div>' +
		'</div>' +
		'</div>'
	);

	modal.modal('show');
	modal.on('hidden.bs.modal', function() {
		modal.remove();
	});
}

function showWarningDetails(warnings) {
	var warningsHtml = '';
	warnings.forEach(function(warning) {
		warningsHtml += '<li>' + htmlspecialchars(warning) + '</li>';
	});

	var modal = $('<div class="modal fade" tabindex="-1">');
	modal.html(
		'<div class="modal-dialog">' +
		'<div class="modal-content">' +
		'<div class="modal-header">' +
		'<button type="button" class="close" data-dismiss="modal">&times;</button>' +
		'<h4 class="modal-title"><i class="fa fa-warning text-warning"></i> Предупреждения</h4>' +
		'</div>' +
		'<div class="modal-body">' +
		'<div class="alert alert-warning">' +
		'<strong>Предупреждения при генерации:</strong>' +
		'<ul>' + warningsHtml + '</ul>' +
		'</div>' +
		'</div>' +
		'<div class="modal-footer">' +
		'<button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>' +
		'</div>' +
		'</div>' +
		'</div>'
	);

	modal.modal('show');
	modal.on('hidden.bs.modal', function() {
		modal.remove();
	});
}

function showGenerationDetails(generationId) {
	showNotification('info', 'Функция в разработке', 'fa-info-circle');
}

// Utility function for HTML escaping
function htmlspecialchars(str) {
	if (typeof str !== 'string') return str;
	return str.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function validatePriceRange() {
	var minPrice = parseFloat($('input[name="feed_rozetka_min_price"]').val()) || 0;
	var maxPrice = parseFloat($('input[name="feed_rozetka_max_price"]').val()) || 0;

	var errorContainer = $('.text-danger').filter(':contains("Минимальная цена")');

	if (minPrice > 0 && maxPrice > 0 && minPrice >= maxPrice) {
		if (errorContainer.length === 0) {
			$('input[name="feed_rozetka_max_price"]').closest('.form-group').append(
				'<div class="text-danger price-error"><i class="fa fa-exclamation-triangle"></i> Минимальная цена не может быть больше максимальной</div>'
			);
		}
	} else {
		$('.price-error').remove();
	}
}

function showFieldError(fieldSelector, message) {
	$(fieldSelector).after(
		'<div class="text-danger validation-error">' +
		'<i class="fa fa-exclamation-triangle"></i> ' + message +
		'</div>'
	);
}