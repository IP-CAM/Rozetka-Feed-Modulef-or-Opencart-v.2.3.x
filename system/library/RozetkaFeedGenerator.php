<?php

use Rozetka\CacheManager;
use Rozetka\DataProvider;
use Rozetka\Logger;
use Rozetka\Statistics;
use Rozetka\XmlBuilder;

/**
 * Основной класс генератора фидов Rozetka
 * Отвечает за координацию работы всех компонентов
 */
class RozetkaFeedGenerator {

	private Registry $registry;
	private Config $config;
	private array $settings;
	private Logger $logger;
	private Statistics $statistics;
	private DataProvider $dataProvider;
	private XmlBuilder $xmlBuilder;
	private CacheManager $cacheManager;

	// Метрики текущей генерации
	private array $metrics = array(
		'start_time' => 0,
		'start_memory' => 0,
		'peak_memory' => 0,
		'products_processed' => 0,
		'offers_generated' => 0,
		'errors_count' => 0,
		'warnings' => array()
	);

	public function __construct($registry) {
		$this->registry = $registry;
		$this->config = $registry->get('config');

		// Инициализируем компоненты
		$this->initializeComponents();

		// Загружаем настройки
		$this->loadSettings();

		// Инициализируем метрики
		$this->initializeMetrics();
	}

	/**
	 * Инициализация всех компонентов
	 */
	private function initializeComponents() {
		require_once(DIR_SYSTEM . 'library/Rozetka/Logger.php');
		require_once(DIR_SYSTEM . 'library/Rozetka/Statistics.php');
		require_once(DIR_SYSTEM . 'library/Rozetka/DataProvider.php');
		require_once(DIR_SYSTEM . 'library/Rozetka/XmlBuilder.php');
		require_once(DIR_SYSTEM . 'library/Rozetka/CacheManager.php');

		$this->logger = new Logger($this->registry);
		$this->statistics = new Statistics($this->registry);
		$this->dataProvider = new DataProvider($this->registry);
		$this->xmlBuilder = new XmlBuilder($this->registry);
		$this->cacheManager = new CacheManager($this->registry);
	}

	/**
	 * Загрузка настроек фида
	 */
	private function loadSettings() {
		$default_settings = array(
			'status' => false,
			'company' => '',
			'shop_name' => '',
			'currency' => 'UAH',
			'image_width' => 800,
			'image_height' => 800,
			'image_quality' => 90,
			'description_length' => 3000,
			'description_strip_tags' => true,
			'include_options' => false,
			'include_attributes' => false,
			'stock_status' => false,
			'min_price' => 0,
			'max_price' => 0,
			'exclude_categories' => array(),
			'exclude_manufacturers' => array(),
			'update_frequency' => 'daily',
			'compress_xml' => false
		);

		$this->settings = array();

		foreach ($default_settings as $key => $default) {
			$config_key = 'feed_rozetka_' . $key;
			$this->settings[$key] = $this->config->get($config_key) ?? $default;
		}

		// Установка значений по умолчанию из общих настроек
		if (empty($this->settings['shop_name'])) {
			$this->settings['shop_name'] = $this->config->get('config_name');
		}

		if (empty($this->settings['company'])) {
			$this->settings['company'] = $this->config->get('config_name');
		}

		if (empty($this->settings['currency'])) {
			$this->settings['currency'] = $this->config->get('config_currency');
		}
	}

	/**
	 * Инициализация метрик
	 */
	private function initializeMetrics() {
		$this->metrics['start_time'] = microtime(true);
		$this->metrics['start_memory'] = memory_get_usage(true);
		$this->metrics['peak_memory'] = memory_get_peak_usage(true);
		$this->metrics['products_processed'] = 0;
		$this->metrics['offers_generated'] = 0;
		$this->metrics['errors_count'] = 0;
		$this->metrics['warnings'] = array();
	}

	/**
	 * Генерация полного фида
	 *
	 * @param array $options Опции генерации
	 * @return array Результат генерации
	 */
	public function generateFeed(array $options = []): array
	{
		try {
			$this->initializeMetrics();
			$log_id = $this->logger->logStart();

			// Получение товаров через DataProvider
			$products = $this->dataProvider->getProducts($options, $this->settings);

			if (empty($products)) {
				throw new Exception('Нет товаров для выгрузки');
			}

			// Генерация XML через XMLBuilder
			$xml_content = $this->xmlBuilder->buildXML($products, $this->settings, $this->metrics);

			// Сохранение файла
			$file_path = $this->saveXMLFile($xml_content);

			// Финализация метрик
			$this->finalizeMetrics();

			$result = array(
				'status' => 'success',
				'file_path' => $file_path,
				'file_size' => $this->formatBytes(filesize($file_path)),
				'products_count' => $this->metrics['products_processed'],
				'offers_count' => $this->metrics['offers_generated'],
				'generation_time' => $this->metrics['generation_time'],
				'memory_used' => $this->formatBytes($this->metrics['memory_used']),
				'warnings' => $this->metrics['warnings']
			);

			$this->logger->logSuccess($result);
			$this->statistics->updateStatistics($result);

			return $result;

		} catch (Exception $e) {
			$error_result = array(
				'status' => 'error',
				'error_message' => $e->getMessage(),
				'products_count' => $this->metrics['products_processed'],
				'generation_time' => microtime(true) - $this->metrics['start_time']
			);

			$this->logger->logError($error_result);
			return $error_result;
		}
	}

	/**
	 * Тестовая генерация фида
	 *
	 * @param int $limit Количество товаров для теста
	 * @return array Результат тестирования
	 */
	public function testGeneration(int $limit = 10): array
	{
		try {
			$this->initializeMetrics();

			// Получение ограниченного количества товаров
			$products = $this->dataProvider->getProducts(array('limit' => $limit), $this->settings);

			if (empty($products)) {
				throw new Exception('Нет товаров для тестирования');
			}

			// Тестовая генерация XML
			$xml_content = $this->xmlBuilder->buildXML($products, $this->settings, $this->metrics);

			$this->finalizeMetrics();

			// Прогнозирование для полного фида
			$total_products = $this->statistics->getTotalProducts();
			$estimated_time = ($this->metrics['generation_time'] / count($products)) * $total_products;
			$estimated_memory = ($this->metrics['memory_used'] / count($products)) * $total_products;

			// Добавление накладных расходов
			$estimated_time += $total_products * 0.001;
			$estimated_memory += $total_products * 2048;

			$result = array(
				'status' => 'success',
				'test_products_count' => count($products),
				'total_products_count' => $total_products,
				'generation_time' => $this->metrics['generation_time'],
				'memory_used' => $this->formatBytes($this->metrics['memory_used']),
				'estimated_total_time' => round($estimated_time, 2),
				'estimated_memory' => $this->formatBytes($estimated_memory),
				'xml_size' => $this->formatBytes(strlen($xml_content)),
				'warnings' => $this->checkSystemLimits($estimated_time, $estimated_memory)
			);

			return $result;

		} catch (Exception $e) {
			return array(
				'status' => 'error',
				'error_message' => $e->getMessage()
			);
		}
	}

	/**
	 * Генерация предпросмотра фида
	 *
	 * @param int $limit Количество товаров для предпросмотра
	 * @return array Результат генерации предпросмотра
	 */
	public function generatePreview(int $limit = 5): array
	{
		try {
			$this->initializeMetrics();

			$products = $this->dataProvider->getProducts(array('limit' => $limit), $this->settings);

			if (empty($products)) {
				throw new Exception('Нет товаров для предпросмотра');
			}

			$xml_content = $this->xmlBuilder->buildXML($products, $this->settings, $this->metrics);

			$this->finalizeMetrics();

			return array(
				'status' => 'success',
				'xml_content' => $xml_content,
				'products_count' => count($products),
				'offers_count' => $this->metrics['offers_generated'],
				'generation_time' => $this->metrics['generation_time'],
				'memory_used' => $this->formatBytes($this->metrics['memory_used']),
				'xml_size' => $this->formatBytes(strlen($xml_content))
			);

		} catch (Exception $e) {
			return array(
				'status' => 'error',
				'error_message' => $e->getMessage()
			);
		}
	}

	/**
	 * Сохранение XML файла
	 *
	 * @throws Exception
	 */
	private function saveXMLFile(string $xml_content): string
	{
		$filename = 'rozetka_feed.xml';
		$file_path = DIR_SYSTEM . 'storage/download/' . $filename;

		// Создание директории если не существует
		$dir = dirname($file_path);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		// Сжатие если включено
		if ($this->settings['compress_xml']) {
			$xml_content = gzencode($xml_content, 9);
			$filename = 'rozetka_feed.xml.gz';
			$file_path = DIR_SYSTEM . 'storage/download/' . $filename;
		}

		if (file_put_contents($file_path, $xml_content) === false) {
			throw new Exception('Не удалось сохранить файл фида');
		}

		return $file_path;
	}

	/**
	 * Финализация метрик
	 */
	private function finalizeMetrics() {
		$this->metrics['generation_time'] = microtime(true) - $this->metrics['start_time'];
		$this->metrics['memory_used'] = memory_get_peak_usage(true) - $this->metrics['start_memory'];
		$this->metrics['peak_memory'] = memory_get_peak_usage(true);
	}

	/**
	 * Проверка системных ограничений
	 */
	private function checkSystemLimits(int $estimated_time, int $estimated_memory): array
	{
		$warnings = array();

		$max_execution_time = ini_get('max_execution_time');
		if ($max_execution_time > 0 && $estimated_time > $max_execution_time) {
			$warnings[] = "Прогнозируемое время генерации ({$estimated_time}с) превышает max_execution_time ({$max_execution_time}с)";
		}

		$memory_limit = $this->convertToBytes(ini_get('memory_limit'));
		if ($memory_limit > 0 && $estimated_memory > $memory_limit) {
			$memory_limit_formatted = $this->formatBytes($memory_limit);
			$estimated_memory_formatted = $this->formatBytes($estimated_memory);
			$warnings[] = "Прогнозируемая память ({$estimated_memory_formatted}) превышает memory_limit ({$memory_limit_formatted})";
		}

		return $warnings;
	}

	/**
	 * Получение статистики
	 */
	public function getStatistics(): array
	{
		return $this->statistics->getStatistics();
	}

	/**
	 * Очистка кэша
	 */
	public function clearCache(): bool
	{
		return $this->cacheManager->clearCache();
	}

	/**
	 * Валидация настроек
	 */
	public function validateSettings(array $settings): array
	{
		$errors = array();

		// Проверка размеров изображений
		if (isset($settings['image_width'])) {
			$width = (int)$settings['image_width'];
			if ($width < 100 || $width > 2000) {
				$errors['image_width'] = 'Ширина изображения должна быть от 100 до 2000 пикселей';
			}
		}

		if (isset($settings['image_height'])) {
			$height = (int)$settings['image_height'];
			if ($height < 100 || $height > 2000) {
				$errors['image_height'] = 'Высота изображения должна быть от 100 до 2000 пикселей';
			}
		}

		// Проверка диапазона цен
		if (isset($settings['min_price']) && isset($settings['max_price'])) {
			$min_price = (float)$settings['min_price'];
			$max_price = (float)$settings['max_price'];

			if ($min_price > 0 && $max_price > 0 && $min_price >= $max_price) {
				$errors['price_range'] = 'Минимальная цена не может быть больше максимальной';
			}
		}

		// Проверка длины описания
		if (isset($settings['description_length'])) {
			$length = (int)$settings['description_length'];
			if ($length < 100 || $length > 10000) {
				$errors['description_length'] = 'Длина описания должна быть от 100 до 10000 символов';
			}
		}

		return $errors;
	}

	/**
	 * Получение настроек
	 */
	public function getSettings(): array
	{
		return $this->settings;
	}

	/**
	 * Обновление настроек
	 */
	public function updateSettings(array $new_settings) {
		$this->settings = array_merge($this->settings, $new_settings);
	}

	/**
	 * Конвертация строки размера памяти в байты
	 */
	private function convertToBytes(string $val): int
	{
		$val = trim($val);
		if (empty($val)) return 0;

		$last = strtolower($val[strlen($val)-1]);
		$val = (int)$val;

		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	/**
	 * Форматирование размера в читаемый вид
	 */
	private function formatBytes(int $size, int $precision = 2):string
	{
		if ($size == 0) return '0 B';

		$base = log($size, 1024);
		$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

		return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
	}
}