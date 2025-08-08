<?php

use Cart\User;
use Rozetka\Logger;
use Rozetka\CategoriesParser;

/**
 * @property Config $config
 * @property Document $document
 * @property Loader $load
 * @property Session $session
 * @property Response $response
 * @property Request $request
 * @property Language $language
 * @property Url $url
 * @property User $user
 * @property Log $log
 * @property ModelSettingSetting $model_setting_setting
 * @property ModelCatalogCategory $model_catalog_category
 * @property ModelCatalogManufacturer $model_catalog_manufacturer
 * @property ModelLocalisationCurrency $model_localisation_currency
 * @property ModelExtensionFeedRozetka $model_extension_feed_rozetka
 * @uses ControllerExtensionFeedRozetka
 * @uses ControllerExtensionFeedRozetka::testGeneration()
 * @uses ControllerExtensionFeedRozetka::getStatistics()
 * @uses ControllerExtensionFeedRozetka::generatePreview()
 * @uses ControllerExtensionFeedRozetka::clearCache()
 * @uses ControllerExtensionFeedRozetka::getGenerationHistory()
 * @uses ControllerExtensionFeedRozetka::importCategories()
 * @uses ControllerExtensionFeedRozetka::getShopCategories()
 * @uses ControllerExtensionFeedRozetka::getRozetkaCategories()
 * @uses ControllerExtensionFeedRozetka::getCategoryMappings()
 * @uses ControllerExtensionFeedRozetka::saveCategoryMappings()
 * @uses ControllerExtensionFeedRozetka::clearCategories()
 */

class ControllerExtensionFeedRozetka extends Controller {

	private array $error = array();
	private RozetkaFeedGenerator $feed_generator;

	public function __construct(Registry $registry) {
		parent::__construct($registry);

		// Загружаем библиотеку генератора
		require_once(DIR_SYSTEM . 'library/RozetkaFeedGenerator.php');
		$this->feed_generator = new RozetkaFeedGenerator($registry);
	}

	/**
	 * @throws Exception
	 */
	public function index() {
		$this->load->language('extension/feed/rozetka');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('feed_rozetka', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=feed', true));
		}

		$this->document->addStyle('view/stylesheet/rozetka.css');
		$this->document->addScript('view/javascript/rozetka/ApiClient.js');
		$this->document->addScript('view/javascript/rozetka/CategoryMappingManager.js');
		$this->document->addScript('view/javascript/rozetka/Config.js');
		$this->document->addScript('view/javascript/rozetka/ControlsManager.js');
		$this->document->addScript('view/javascript/rozetka/FiltersManager.js');
		$this->document->addScript('view/javascript/rozetka/HistoryManager.js');
		$this->document->addScript('view/javascript/rozetka/ImportExportManager.js');
		$this->document->addScript('view/javascript/rozetka/NotificationManager.js');
		$this->document->addScript('view/javascript/rozetka/SettingsManager.js');
		$this->document->addScript('view/javascript/rozetka/StatisticsManager.js');
		$this->document->addScript('view/javascript/rozetka/RozetkaApp.js');
		$this->document->addScript('view/javascript/rozetka.js');

		$data = $this->prepareViewData();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/feed/rozetka', $data));
	}

	/**
	 * Подготовка данных для отображения
	 *
	 * @throws Exception
	 */
	private function prepareViewData() {
		$data = array();

		// Обработка ошибок валидации
		$this->setErrorData($data);

		// Настройка breadcrumbs и URLs
		$this->setBreadcrumbsAndUrls($data);

		// Загрузка настроек модуля
		$this->loadModuleSettings($data);

		// Загрузка справочных данных
		$this->loadReferenceData($data);

		// Получение статистики через библиотеку
		$statistics = $this->feed_generator->getStatistics();
		$data = array_merge($data, $statistics);

		// URL фида
		$data['feed_url'] = HTTPS_CATALOG . 'index.php?route=extension/feed/rozetka';

		// Языковые переменные
		$this->setLanguageData($data);

		return $data;
	}

	/**
	 * Установка данных об ошибках
	 */
	private function setErrorData(&$data) {
		$error_fields = array('warning', 'image_width', 'image_height', 'price_range', 'description_length');

		foreach ($error_fields as $field) {
			$data['error_' . $field] = $this->error[$field] ?? '';
		}
	}

	/**
	 * Настройка breadcrumbs и URLs
	 */
	private function setBreadcrumbsAndUrls(&$data) {
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=feed', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/feed/rozetka', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('extension/feed/rozetka', 'token=' . $this->session->data['token'], true);
		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=feed', true);
	}

	/**
	 * Загрузка настроек модуля
	 */
	private function loadModuleSettings(&$data) {
		$settings = array(
			'feed_rozetka_status',
			'feed_rozetka_company',
			'feed_rozetka_shop_name',
			'feed_rozetka_currency',
			'feed_rozetka_image_width',
			'feed_rozetka_image_height',
			'feed_rozetka_image_quality',
			'feed_rozetka_description_length',
			'feed_rozetka_description_strip_tags',
			'feed_rozetka_include_options',
			'feed_rozetka_stock_status',
			'feed_rozetka_min_price',
			'feed_rozetka_max_price',
			'feed_rozetka_exclude_categories',
			'feed_rozetka_exclude_manufacturers',
			'feed_rozetka_include_attributes',
			'feed_rozetka_update_frequency',
			'feed_rozetka_compress_xml'
		);

		foreach ($settings as $setting) {
			if (isset($this->request->post[$setting])) {
				$data[$setting] = $this->request->post[$setting];
			} else {
				$data[$setting] = $this->config->get($setting);
			}
		}
	}

	/**
	 * Загрузка справочных данных
	 *
	 * @throws Exception
	 */
	private function loadReferenceData(&$data) {
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('localisation/currency');

		$data['categories'] = $this->model_catalog_category->getCategories();
		$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
	}

	/**
	 * Тест генерации фида через библиотеку
	 */
	public function testGeneration() {
		$this->load->language('extension/feed/rozetka');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			// Используем библиотеку для тестирования
			$test_limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
			$result = $this->feed_generator->testGeneration($test_limit);

			// Добавляем информацию о PHP настройках
			if ($result['status'] == 'success') {
				$result['php_info'] = array(
					'memory_limit' => ini_get('memory_limit'),
					'max_execution_time' => ini_get('max_execution_time'),
					'php_version' => PHP_VERSION
				);
			}

			$json = $result;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Получение истории генераций
	 */
	public function getGenerationHistory() {
		$this->load->language('extension/feed/rozetka');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;

			// Получаем логгер из библиотеки через рефлексию или создаем новый экземпляр
			$logger = new Logger($this->registry);
			$history = $logger->getLogHistory($limit);

			// Форматируем данные для отображения
			$formatted_history = array();
			foreach ($history as $log) {
				$formatted_history[] = array(
					'id' => $log['log_id'],
					'date' => date('d.m.Y H:i:s', strtotime($log['date_generated'])),
					'status' => $log['status'],
					'products_count' => $log['products_count'],
					'offers_count' => $log['offers_count'],
					'generation_time' => $log['generation_time'],
					'file_size' => $log['file_size'],
					'memory_used' => $log['memory_used'],
					'error_message' => $log['error_message'],
					'warnings' => $log['warnings'] ? json_decode($log['warnings'], true) : array()
				);
			}

			$json = array(
				'status' => 'success',
				'history' => $formatted_history
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Настройка языковых переменных
	 */
	private function setLanguageData(&$data) {
		$language_keys = array(
			'heading_title', 'text_edit', 'text_enabled', 'text_disabled',
			'button_save', 'button_cancel', 'entry_status', 'entry_shop_name',
			'entry_company', 'entry_currency', 'entry_image_width', 'entry_image_height',
			'entry_image_quality', 'entry_description', 'entry_description_tags',
			'entry_options', 'entry_attributes', 'entry_stock', 'entry_min_price',
			'entry_max_price', 'entry_categories', 'entry_manufacturers',
			'entry_update_frequency', 'entry_compress_xml'
		);

		$help_keys = array(
			'help_shop_name', 'help_company', 'help_currency', 'help_image_width',
			'help_image_height', 'help_image_quality', 'help_description',
			'help_description_tags', 'help_options', 'help_attributes', 'help_stock',
			'help_min_price', 'help_max_price', 'help_categories', 'help_manufacturers',
			'help_update_frequency', 'help_compress_xml', 'help_feed_url'
		);

		$button_keys = array(
			'button_test', 'button_preview', 'button_generate', 'button_clear_cache',
			'button_export', 'button_import', 'button_history'
		);

		foreach (array_merge($language_keys, $help_keys, $button_keys) as $key) {
			$data[$key] = $this->language->get($key);
		}
	}

	/**
	 * Валидация формы
	 */
	protected function validate(): bool
	{
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// Используем валидацию из библиотеки
		$settings = array();

		// Подготавливаем настройки для валидации (убираем префикс feed_rozetka_)
		foreach ($this->request->post as $key => $value) {
			if (strpos($key, 'feed_rozetka_') === 0) {
				$clean_key = str_replace('feed_rozetka_', '', $key);
				$settings[$clean_key] = $value;
			}
		}

		$validation_errors = $this->feed_generator->validateSettings($settings);

		foreach ($validation_errors as $field => $message) {
			$this->error[$field] = $message;
		}

		return !$this->error;
	}

	/**
	 * Установка модуля (создание необходимых таблиц)
	 *
	 * @throws Exception
	 */
	public function install() {
		$this->load->model('extension/feed/rozetka');
		$this->model_extension_feed_rozetka->install();
	}

	/**
	 * Удаление модуля
	 *
	 * @throws Exception
	 */
	public function uninstall() {
		$this->load->model('setting/setting');

		// Удаляем все настройки модуля
		$this->model_setting_setting->deleteSetting('feed_rozetka');

		// Очищаем кэш
		$this->feed_generator->clearCache();

		$this->load->model('extension/feed/rozetka');

		$this->model_extension_feed_rozetka->uninstall();
	}

	/**
	 * Получение категорий магазина с поиском (лимит 10)
	 */
	public function getShopCategories() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('catalog/category');

			$search = $this->request->get['search'] ?? '';
			$limit = 10; // Фиксированный лимит

			if (empty($search)) {
				$json = array(
					'status' => 'success',
					'categories' => array(),
					'total' => 0
				);
			} else {
				// Получаем категории с поиском
				$filter_data = array(
					'filter_name' => $search,
					'start' => 0,
					'limit' => $limit
				);

				$categories = $this->model_catalog_category->getCategories($filter_data);
				$total = $this->model_catalog_category->getTotalCategories($filter_data);

				// Дополняем категории информацией о пути и уровне
				$enriched_categories = array();
				foreach ($categories as $category) {
					$category['level'] = $this->calculateCategoryLevel($category['category_id']);
					$category['path'] = $this->buildCategoryPath($category['category_id']);
					$enriched_categories[] = $category;
				}

				$json = array(
					'status' => 'success',
					'categories' => $enriched_categories,
					'total' => $total
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Получение категорий Rozetka с поиском (лимит 10)
	 */
	public function getRozetkaCategories() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/feed/rozetka');

			$search = $this->request->get['search'] ?? '';
			$limit = 10; // Фиксированный лимит

			if (empty($search)) {
				$json = array(
					'status' => 'success',
					'categories' => array(),
					'total' => 0
				);
			} else {
				$categories = $this->model_extension_feed_rozetka->getRozetkaCategories(array(
					'search' => $search,
					'start' => 0,
					'limit' => $limit
				));

				$total = $this->model_extension_feed_rozetka->getTotalRozetkaCategories($search);

				$json = array(
					'status' => 'success',
					'categories' => $categories,
					'total' => $total
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Вычисление уровня категории
	 */
	private function calculateCategoryLevel($category_id, $level = 1) {
		$this->load->model('catalog/category');
		$category = $this->model_catalog_category->getCategory($category_id);

		if (!$category || !$category['parent_id']) {
			return $level;
		}

		return $this->calculateCategoryLevel($category['parent_id'], $level + 1);
	}

	/**
	 * Построение пути категории
	 */
	private function buildCategoryPath($category_id) {
		$this->load->model('catalog/category');
		$path_parts = array();
		$current_id = $category_id;

		while ($current_id) {
			$category = $this->model_catalog_category->getCategory($current_id);
			if (!$category) break;

			array_unshift($path_parts, $category['name']);
			$current_id = $category['parent_id'];
		}

		return implode(' > ', $path_parts);
	}

	/**
	 * Автоматический маппинг категорий
	 */
	public function autoMapCategories() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			try {
				$this->load->model('extension/feed/rozetka');
				$this->load->model('catalog/category');

				// Получаем все категории магазина
				$shopCategories = $this->model_catalog_category->getCategories(array('limit' => 1000));

				// Получаем все категории Rozetka
				$rozetkaCategories = $this->model_extension_feed_rozetka->getRozetkaCategories(array('limit' => 10000));

				$mappings = array();
				$threshold = 0.7; // Порог схожести

				foreach ($shopCategories as $shopCategory) {
					$bestMatch = null;
					$bestScore = 0;

					foreach ($rozetkaCategories as $rozetkaCategory) {
						// Вычисляем схожесть названий
						$score = $this->calculateSimilarity($shopCategory['name'], $rozetkaCategory['name']);

						if ($score > $bestScore && $score >= $threshold) {
							$bestScore = $score;
							$bestMatch = $rozetkaCategory;
						}
					}

					if ($bestMatch) {
						$mappings[] = array(
							'shop_category_id' => $shopCategory['category_id'],
							'shop_category_name' => $shopCategory['name'],
							'rozetka_category_id' => $bestMatch['category_id'],
							'rozetka_category_name' => $bestMatch['name'],
							'rozetka_category_full_name' => $bestMatch['full_name'],
							'confidence' => round($bestScore * 100)
						);
					}
				}

				$json = array(
					'status' => 'success',
					'mappings' => $mappings,
					'total_found' => count($mappings)
				);

			} catch (Exception $e) {
				$json = array(
					'status' => 'error',
					'error' => $e->getMessage()
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Вычисление схожести строк
	 */
	private function calculateSimilarity($str1, $str2) {
		$str1 = mb_strtolower(trim($str1), 'UTF-8');
		$str2 = mb_strtolower(trim($str2), 'UTF-8');

		if ($str1 === $str2) {
			return 1.0;
		}

		// Используем алгоритм Левенштейна
		$maxLen = max(mb_strlen($str1, 'UTF-8'), mb_strlen($str2, 'UTF-8'));
		if ($maxLen === 0) {
			return 0;
		}

		$distance = levenshtein($str1, $str2);
		return 1 - ($distance / $maxLen);
	}

	public function importCategories()
	{
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json = array(
				'success' => false,
				'message' => $this->language->get('error_permission')
			);
		} else {
			try {
				// Проверяем, что файл был загружен
				if (!isset($_FILES['categories_file']) || $_FILES['categories_file']['error'] !== UPLOAD_ERR_OK) {
					throw new Exception('Файл не был загружен или произошла ошибка при загрузке');
				}

				$uploadedFile = $_FILES['categories_file'];

				// Проверяем размер файла (максимум 10MB)
				if ($uploadedFile['size'] > 10 * 1024 * 1024) {
					throw new Exception('Размер файла превышает 10MB');
				}

				// Проверяем расширение
				$fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
				if ($fileExtension !== 'json') {
					throw new Exception('Поддерживаются только JSON файлы');
				}

				// Читаем содержимое файла
				$fileContent = file_get_contents($uploadedFile['tmp_name']);
				if ($fileContent === false) {
					throw new Exception('Не удалось прочитать содержимое файла');
				}

				// Парсим JSON
				$categories = json_decode($fileContent, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					throw new Exception('Некорректный JSON формат: ' . json_last_error_msg());
				}

				// Валидируем структуру данных
				$validationResult = $this->validateCategoriesData($categories);
				if (!$validationResult['valid']) {
					throw new Exception($validationResult['error']);
				}

				// Импортируем в базу данных
				$this->load->model('extension/feed/rozetka');
				$importResult = $this->model_extension_feed_rozetka->importCategoriesFromArray($categories);

				$json = array(
					'success' => true,
					'total_categories' => count($categories),
					'imported_categories' => $importResult['imported'],
					'updated_categories' => $importResult['updated'],
					'message' => "Успешно импортировано категорий: {$importResult['imported']}, обновлено: {$importResult['updated']}"
				);

				// Логируем успешный импорт
				$this->log->write('Rozetka Categories: Импортировано ' . count($categories) . ' категорий из JSON файла');

			} catch (Exception $e) {
				$json = array(
					'success' => false,
					'message' => $e->getMessage()
				);

				// Логируем ошибку
				$this->log->write('Rozetka Categories Import Error: ' . $e->getMessage());
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Валидация данных категорий
	 */
	private function validateCategoriesData(array $categories): array
	{
		if (empty($categories)) {
			return ['valid' => false, 'error' => 'Файл не содержит категорий'];
		}

		if (!is_array($categories)) {
			return ['valid' => false, 'error' => 'Неверная структура данных - ожидается массив'];
		}

		$requiredFields = ['categoryId', 'name', 'fullName', 'url', 'level'];

		foreach ($categories as $index => $category) {
			if (!is_array($category)) {
				return ['valid' => false, 'error' => "Категория #$index имеет неверный формат"];
			}

			foreach ($requiredFields as $field) {
				if (!isset($category[$field]) || empty($category[$field])) {
					return ['valid' => false, 'error' => "Категория #$index: отсутствует поле '$field'"];
				}
			}

			// Проверяем типы данных
			if (!is_string($category['categoryId']) && !is_numeric($category['categoryId'])) {
				return ['valid' => false, 'error' => "Категория #$index: categoryId должен быть строкой или числом"];
			}

			if (!is_int($category['level']) || $category['level'] < 1 || $category['level'] > 10) {
				return ['valid' => false, 'error' => "Категория #$index: level должен быть числом от 1 до 10"];
			}

			if (!filter_var($category['url'], FILTER_VALIDATE_URL)) {
				return ['valid' => false, 'error' => "Категория #$index: некорректный URL"];
			}
		}

		return ['valid' => true];
	}

	/**
	 * @throws Exception
	 */
	public function getCategoryMappings() {
		$this->load->model('extension/feed/rozetka');
		$mappings = $this->model_extension_feed_rozetka->getCategoryMappings();

		$json = array(
			'status' => 'success',
			'mappings' => $mappings
		);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * @throws Exception
	 */
	public function saveCategoryMappings() {
		$json = array();

		$this->log->write($this->request->post);

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$mappings = $this->request->post['mappings'] ?? [];

			if (is_array($mappings)) {
				$this->load->model('extension/feed/rozetka');
				$this->model_extension_feed_rozetka->saveCategoryMappings($mappings);
				$json['status'] = 'success';
			} else {
				$json['error'] = 'Неверный формат данных';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function clearCategories()
	{
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			try {
				$this->load->model('extension/feed/rozetka');
				$this->model_extension_feed_rozetka->clearRozetkaCategories();

				$json['success'] = true;
				$json['message'] = 'Все категории успешно удалены';

				$this->log->write('Rozetka Categories: Все категории удалены администратором');
			} catch (Exception $e) {
				$json['error'] = 'Ошибка при удалении категорий: ' . $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function generatePreview() {
		$this->load->language('extension/feed/rozetka');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$preview_limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 5;
			$json = $this->feed_generator->generatePreview($preview_limit);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Получение статистики через библиотеку
	 */
	public function getStatistics() {
		$this->load->language('extension/feed/rozetka');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json = $this->feed_generator->getStatistics();
			$json['status'] = 'success';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Очистка кэша через библиотеку
	 */
	public function clearCache()
	{
		$this->load->language('extension/feed/rozetka');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$deleted_files = $this->feed_generator->clearCache();

			if ($deleted_files >= 0) {
				$json['success'] = "Кэш успешно очищен. Удалено файлов: $deleted_files";
				$json['status'] = 'success';
				$json['deleted_files'] = $deleted_files;

				$this->log->write('Rozetka Feed: Кеш очищен администратором');
			} else {
				$json['error'] = 'Ошибка при очистке кэша';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}