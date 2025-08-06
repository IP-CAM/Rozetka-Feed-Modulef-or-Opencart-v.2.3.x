<?php

namespace Rozetka;

use Registry;

/**
 * Класс для сбора и предоставления статистики Rozetka фида
 * Отвечает за агрегацию данных о товарах и генерациях
 */
class Statistics {

	private Registry $registry;
	private DataProvider $dataProvider;
	private Logger $logger;
	private string $dbPrefix;

	public function __construct(Registry $registry) {
		$this->registry = $registry;
		$this->dataProvider = new DataProvider($registry);
		$this->logger = new Logger($registry);
		$this->dbPrefix = DB_PREFIX;
	}

	/**
	 * Получение полной статистики фида
	 *
	 * @return array Массив со статистикой
	 */
	public function getStatistics(): array
	{
		$stats = array();

		// Статистика товаров
		$product_stats = $this->dataProvider->getProductStatistics();
		$stats = array_merge($stats, $product_stats);

		// Товары в фиде с учетом фильтров
		$stats['total_products_feed'] = $this->getTotalProducts();

		// Статистика исключений
		$stats['excluded_categories'] = $this->getExcludedCategoriesCount();
		$stats['excluded_manufacturers'] = $this->getExcludedManufacturersCount();

		// Статистика генераций
		$generation_stats = $this->getGenerationStatistics();
		$stats = array_merge($stats, $generation_stats);

		// Информация о последней генерации
		$stats['last_generation'] = $this->logger->getLastLog();

		// Статистика логов
		$log_stats = $this->logger->getLogStatistics();

		return array_merge($stats, $log_stats);
	}

	/**
	 * Получение количества товаров в фиде с учетом всех фильтров
	 *
	 * @return int Количество товаров
	 */
	public function getTotalProducts(): int
	{
		// Загружаем настройки
		$config = $this->registry->get('config');

		$settings = array(
			'stock_status' => $config->get('feed_rozetka_stock_status'),
			'min_price' => $config->get('feed_rozetka_min_price'),
			'max_price' => $config->get('feed_rozetka_max_price'),
			'exclude_categories' => $config->get('feed_rozetka_exclude_categories') ?: array(),
			'exclude_manufacturers' => $config->get('feed_rozetka_exclude_manufacturers') ?: array()
		);

		return $this->dataProvider->getTotalProducts($settings);
	}

	/**
	 * Получение количества исключенных категорий
	 *
	 * @return int Количество исключенных категорий
	 */
	private function getExcludedCategoriesCount(): int
	{
		$config = $this->registry->get('config');
		$excluded = $config->get('feed_rozetka_exclude_categories') ?: array();
		return count($excluded);
	}

	/**
	 * Получение количества исключенных производителей
	 *
	 * @return int Количество исключенных производителей
	 */
	private function getExcludedManufacturersCount(): int
	{
		$config = $this->registry->get('config');
		$excluded = $config->get('feed_rozetka_exclude_manufacturers') ?: array();
		return count($excluded);
	}

	/**
	 * Получение статистики генераций
	 *
	 * @return array Статистика генераций
	 */
	private function getGenerationStatistics(): array
	{
		$stats = array();

		// История последних генераций
		$stats['generation_history'] = $this->logger->getLogHistory(5);

		// Статистика за последний месяц
		$monthly_stats = $this->getMonthlyStatistics();

		return array_merge($stats, $monthly_stats);
	}

	/**
	 * Получение статистики за последний месяц
	 *
	 * @return array Статистика за месяц
	 */
	private function getMonthlyStatistics(): array
	{
		$db = $this->registry->get('db');
		$stats = array();

		// Количество генераций за месяц
		$query = $db->query("SELECT COUNT(*) as `count` FROM `{$this->dbPrefix}rozetka_feed_log` 
            WHERE `status` IN ('success', 'error') 
            AND `date_generated` >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
		$stats['monthly_generations'] = (int)$query->row['count'];

		// Успешные генерации за месяц
		$query = $db->query("SELECT COUNT(*) as `count` FROM `{$this->dbPrefix}rozetka_feed_log` 
            WHERE `status` = 'success' 
            AND `date_generated` >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
		$stats['monthly_successful'] = (int)$query->row['count'];

		// Средний размер фида
		$query = $db->query("SELECT `file_size` FROM `{$this->dbPrefix}rozetka_feed_log` 
            WHERE `status` = 'success' 
            AND `file_size` != '' 
            AND `date_generated` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY `date_generated` DESC 
            LIMIT 5");

		$sizes = array();
		foreach ($query->rows as $row) {
			$sizes[] = $this->convertSizeToBytes($row['file_size']);
		}

		if (!empty($sizes)) {
			$avg_size_bytes = array_sum($sizes) / count($sizes);
			$stats['avg_feed_size'] = $this->formatBytes($avg_size_bytes);
		} else {
			$stats['avg_feed_size'] = '0 B';
		}

		return $stats;
	}

	/**
	 * Обновление статистики после генерации
	 *
	 * @param array $generation_data Данные о генерации
	 */
	public function updateStatistics(array $generation_data) {
		// Здесь можно добавить дополнительную обработку статистики
		// например, обновление агрегированных таблиц или кэша

		// Пока просто логируем событие
		$log = $this->registry->get('log');
		$log->write("Rozetka Feed Statistics: Updated after generation with {$generation_data['products_count']} products");
	}

	/**
	 * Получение детальной статистики по категориям
	 *
	 * @return array Статистика по категориям
	 */
	public function getCategoryStatistics(): array
	{
		$db = $this->registry->get('db');
		$config = $this->registry->get('config');

		$query = $db->query("
            SELECT `c`.`category_id`, `cd`.`name`, COUNT(`p2c`.`product_id`) as `product_count`
            FROM `{$this->dbPrefix}category` `c`
            LEFT JOIN `{$this->dbPrefix}category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`)
            LEFT JOIN `{$this->dbPrefix}product_to_category` `p2c` ON (`c`.`category_id` = `p2c`.`category_id`)
            LEFT JOIN `{$this->dbPrefix}product` `p` ON (`p2c`.`product_id` = `p`.`product_id` AND `p`.`status` = 1)
            WHERE `cd`.`language_id` = '" . (int)$config->get('config_language_id') . "'
            GROUP BY `c`.`category_id`
            HAVING `product_count` > 0
            ORDER BY `product_count` DESC, `cd`.`name`
        ");

		return $query->rows;
	}

	/**
	 * Получение детальной статистики по производителям
	 *
	 * @return array Статистика по производителям
	 */
	public function getManufacturerStatistics(): array
	{
		$db = $this->registry->get('db');

		$query = $db->query("
            SELECT `m`.`manufacturer_id`, `m`.`name`, COUNT(`p`.`product_id`) as `product_count`
            FROM `{$this->dbPrefix}manufacturer` `m`
            LEFT JOIN `{$this->dbPrefix}product` `p` ON (`m`.`manufacturer_id` = `p`.`manufacturer_id` AND `p`.`status` = 1)
            GROUP BY `m`.`manufacturer_id`
            HAVING `product_count` > 0
            ORDER BY `product_count` DESC, `m`.`name`
        ");

		return $query->rows;
	}

	/**
	 * Конвертация строкового размера в байты
	 *
	 * @param string $size Размер в читаемом формате
	 * @return int Размер в байтах
	 */
	private function convertSizeToBytes(string $size): int
	{
		if (empty($size)) return 0;

		$size = trim($size);
		preg_match('/([0-9.]+)\s*([KMGT]?B)/i', $size, $matches);

		if (!isset($matches[1])) return 0;

		$number = (float)$matches[1];
		$unit = isset($matches[2]) ? strtoupper($matches[2]) : 'B';

		switch ($unit) {
			case 'TB':
				$number *= 1024;
			case 'GB':
				$number *= 1024;
			case 'MB':
				$number *= 1024;
			case 'KB':
				$number *= 1024;
			case 'B':
			default:
				break;
		}

		return (int)$number;
	}

	/**
	 * Форматирование размера в читаемый вид
	 *
	 * @param int $size Размер в байтах
	 * @param int $precision Точность
	 * @return string Форматированный размер
	 */
	private function formatBytes(int $size, int $precision = 2): string
	{
		if ($size == 0) return '0 B';

		$base = log($size, 1024);
		$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

		return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
	}
}