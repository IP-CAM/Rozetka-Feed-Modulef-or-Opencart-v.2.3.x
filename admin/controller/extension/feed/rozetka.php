<?php

use Cart\User;
use Rozetka\Logger;

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