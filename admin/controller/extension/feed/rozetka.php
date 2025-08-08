<?php
/**
 * Контроллер административной части модуля Rozetka для OpenCart 2.3.
 *
 * В новой версии большая часть бизнес‑логики перенесена в сервис
 * RozetkaAdminService (system/library/RozetkaAdminService.php). Контроллер
 * отвечает за загрузку языковых файлов, проверку прав, подготовку данных
 * для шаблона и маршрутизацию AJAX‑запросов. Маршруты и названия
 * методов сохранены для совместимости с существующим JavaScript.
 */

use Cart\User;

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
	/**
	 * @var array
	 */
	private $error = array();

	/**
	 * @var RozetkaFeedGenerator
	 */
	private $feed_generator;

	/**
	 * @var RozetkaAdminService
	 */
	private $adminService;

	/**
	 * Конструктор. Инициализирует генератор фида и сервис административной логики.
	 *
	 * @param Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);
		require_once(DIR_SYSTEM . 'library/RozetkaFeedGenerator.php');
		require_once(DIR_SYSTEM . 'library/RozetkaAdminService.php');
		// Используем собственный экземпляр генератора фида
		$this->feed_generator = new RozetkaFeedGenerator($registry);
		// Инициализируем сервис, который инкапсулирует бизнес‑логику
		$this->adminService = new RozetkaAdminService($registry);
	}

	/**
	 * Главная страница настроек модуля. Загружает стили и скрипты, выполняет
	 * сохранение настроек при POST‑запросе и выводит шаблон.
	 *
	 * @throws Exception
	 */
	public function index() {
		$this->load->language('extension/feed/rozetka');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		// Сохраняем настройки через стандартный механизм OpenCart
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('feed_rozetka', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=feed', true));
			return;
		}
		// Подключаем стили и javascript для UI
		$this->document->addStyle('view/stylesheet/rozetka.css');
		// Список скриптов для загрузки. Если требуется добавить новый файл, достаточно
		// включить его в этот массив.
		$scripts = array(
			'ApiClient.js',
			'CategoryMappingManager.js',
			'Config.js',
			'ControlsManager.js',
			'FiltersManager.js',
			'HistoryManager.js',
			'ImportExportManager.js',
			'NotificationManager.js',
			'SettingsManager.js',
			'StatisticsManager.js',
			'Utils.js',
			'TabSaveManager.js',
			'RozetkaApp.js'
		);
		foreach ($scripts as $script) {
			$this->document->addScript('view/javascript/rozetka/' . $script);
		}

		$this->document->addScript('view/javascript/rozetka.js');
		// Подготовка данных для шаблона
		$data = $this->prepareViewData();
		// Добавляем стандартные компоненты
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		// Выводим шаблон
		$this->response->setOutput($this->load->view('extension/feed/rozetka', $data));
	}

	/**
	 * Подготавливает набор данных для передачи в шаблон.
	 *
	 * @return array
	 * @throws Exception
	 */
	private function prepareViewData(): array
	{
		$data = array();
		// Обрабатываем ошибки валидации формы
		$this->setErrorData($data);
		// Хлебные крошки и URL действия/отмены
		$this->setBreadcrumbsAndUrls($data);
		// Загружаем значения настроек
		$this->loadModuleSettings($data);
		// Загружаем справочные данные (категории, производители, валюты)
		$this->loadReferenceData($data);
		// Получаем статистику через генератор
		$statistics = $this->feed_generator->getStatistics();
		$data = array_merge($data, $statistics);
		// URL публичного фида
		$data['feed_url'] = HTTPS_CATALOG . 'index.php?route=extension/feed/rozetka';
		// Загружаем языковые переменные
		$this->setLanguageData($data);
		return $data;
	}

	/**
	 * Заполняет массив данными об ошибках из $this->error.
	 *
	 * @param array $data
	 */
	private function setErrorData(array &$data): void
	{
		$error_fields = array('warning', 'image_width', 'image_height', 'price_range', 'description_length');
		foreach ($error_fields as $field) {
			$data['error_' . $field] = $this->error[$field] ?? '';
		}
	}

	/**
	 * Формирует хлебные крошки и URL действия/отмены для шаблона.
	 *
	 * @param array $data
	 */
	private function setBreadcrumbsAndUrls(array &$data): void
	{
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
	 * Загружает сохранённые настройки модуля в массив $data.
	 * При наличии POST‑значений они имеют приоритет над сохранёнными.
	 *
	 * @param array $data
	 */
	private function loadModuleSettings(array &$data): void
	{
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
	 * Загружает справочные данные (категории, производители, валюты) в $data.
	 *
	 * @param array $data
	 */
	private function loadReferenceData(array &$data): void
	{
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('localisation/currency');
		$data['categories'] = $this->model_catalog_category->getCategories();
		$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
	}

	/**
	 * Обработчик AJAX‑сохранения настроек конкретной вкладки. Проверяет права,
	 * передаёт работу сервису и возвращает JSON‑ответ.
	 */
	public function saveTabSettings() {
		$this->load->language('extension/feed/rozetka');
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$tab = $this->request->post['tab'] ?? '';
			$settings = $this->request->post['settings'] ?? array();
			if (empty($tab) || empty($settings)) {
				$json['error'] = 'Неверные данные для сохранения';
			} else {
				$json = $this->adminService->saveTabSettings($tab, $settings);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Обёртка над сервисом для валидации настроек вкладки. Остаётся
	 * приватной, как и в исходном контроллере. Нужна для совместимости,
	 * если где‑то в контроллере будет использоваться.
	 *
	 * @param string $tab
	 * @param array  $settings
	 * @return array
	 */
	private function validateTabSettings(string $tab, array $settings): array
	{
		return $this->adminService->validateTabSettings($tab, $settings);
	}

	/**
	 * Обработчик удаления одной связи категории. Использует сервис.
	 */
	public function removeCategoryMapping() {
		$this->load->language('extension/feed/rozetka');
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$shop_category_id = $this->request->post['shop_category_id'] ?? 0;
			if (empty($shop_category_id)) {
				$json['error'] = 'Некорректный ID категории';
			} else {
				$json = $this->adminService->removeCategoryMapping((int)$shop_category_id);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Обработчик удаления всех связей категорий.
	 */
	public function clearAllMappings() {
		$this->load->language('extension/feed/rozetka');
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json = $this->adminService->clearAllMappings();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Выполняет тестовую генерацию фида и возвращает результат.
	 */
	public function testGeneration() {
		$this->load->language('extension/feed/rozetka');
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$test_limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
			$json = $this->adminService->testGeneration($test_limit);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Возвращает историю генераций фида.
	 */
	public function getGenerationHistory() {
		$this->load->language('extension/feed/rozetka');
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;
			$json = $this->adminService->getGenerationHistory($limit);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Загружает языковые строки для шаблона.
	 *
	 * @param array $data
	 */
	private function setLanguageData(array &$data): void
	{
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
	 * Общая валидация формы (POST‑запрос при сохранении настроек).
	 * Использует генератор фида для проверки параметров.
	 *
	 * @return bool
	 */
	protected function validate(): bool
	{
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		// Подготавливаем массив настроек для проверки (убираем префикс)
		$settings = array();
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
	 * Устанавливает таблицы модуля при установке.
	 *
	 * @throws Exception
	 */
	public function install() {
		$this->load->model('extension/feed/rozetka');
		$this->model_extension_feed_rozetka->install();
	}

	/**
	 * Удаляет настройки и таблицы модуля при удалении.
	 *
	 * @throws Exception
	 */
	public function uninstall() {
		$this->load->model('setting/setting');
		// Удаляем все настройки
		$this->model_setting_setting->deleteSetting('feed_rozetka');
		// Очищаем кэш
		$this->feed_generator->clearCache();
		$this->load->model('extension/feed/rozetka');
		$this->model_extension_feed_rozetka->uninstall();
	}

	/**
	 * Возвращает категории магазина для автодополнения.
	 */
	public function getShopCategories() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$search = $this->request->get['search'] ?? '';
			$limit = 10;
			$json = $this->adminService->getShopCategories($search, $limit);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Возвращает категории Rozetka для автодополнения.
	 */
	public function getRozetkaCategories() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$search = $this->request->get['search'] ?? '';
			$limit = 10;
			$json = $this->adminService->getRozetkaCategories($search, $limit);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Вычисляет уровень категории магазина. Оставлен для совместимости,
	 * но вся логика находится в сервисе.
	 */
	private function calculateCategoryLevel($category_id, $level = 1) {
		return $this->adminService->calculateCategoryLevel($category_id, $level);
	}

	/**
	 * Построение пути категории. Использует сервис.
	 */
	private function buildCategoryPath($category_id) {
		return $this->adminService->buildCategoryPath($category_id);
	}

	/**
	 * Автоматическое сопоставление категорий магазина с Rozetka.
	 */
	public function autoMapCategories() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json = $this->adminService->autoMapCategories();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Импорт категорий из JSON файла. Передаёт файл в сервис, который
	 * производит все проверки и импорт.
	 */
	public function importCategories() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['success'] = false;
			$json['message'] = $this->language->get('error_permission');
		} else {
			$file = $_FILES['categories_file'] ?? null;
			if (!$file) {
				$json['success'] = false;
				$json['message'] = 'Файл не был загружен или отсутствует';
			} else {
				$json = $this->adminService->importCategories($file);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Возвращает текущие сопоставления категорий.
	 */
	public function getCategoryMappings() {
		$json = array();
		try {
			$json = $this->adminService->getCategoryMappings();
		} catch (Exception $e) {
			$json['status'] = 'error';
			$json['error'] = $e->getMessage();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Сохраняет массив сопоставлений категорий, полученных от клиента.
	 */
	public function saveCategoryMappings() {
		$json = array();
		// Логируем полученные данные для совместимости с исходным контроллером
		$this->log->write($this->request->post);
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$mappings = $this->request->post['mappings'] ?? [];
			if (!is_array($mappings)) {
				$json['error'] = 'Неверный формат данных';
			} else {
				$json = $this->adminService->saveCategoryMappings($mappings);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Удаляет все категории Rozetka.
	 */
	public function clearCategories() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json = $this->adminService->clearCategories();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Генерирует предварительный просмотр фида.
	 */
	public function generatePreview() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 5;
			$json = $this->adminService->generatePreview($limit);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Возвращает статистику фида.
	 */
	public function getStatistics() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json = $this->adminService->getStatistics();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Очищает кэш данных генератора.
	 */
	public function clearCache() {
		$json = array();
		if (!$this->user->hasPermission('modify', 'extension/feed/rozetka')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json = $this->adminService->clearCache();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
