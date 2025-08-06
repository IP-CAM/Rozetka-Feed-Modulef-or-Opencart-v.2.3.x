<?php

namespace Rozetka;

use Config;
use DB;
use Registry;

/**
 * Класс для получения данных из БД для генерации Rozetka фида
 * Отвечает за все операции с базой данных
 */
class DataProvider {
	private DB $db;
	private Config $config;
	private string $dbPrefix;

	public function __construct(Registry $registry) {
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');
		$this->dbPrefix = DB_PREFIX;
	}

	/**
	 * Получение товаров для фида
	 *
	 * @param array $options Опции запроса (limit, start)
	 * @param array $settings Настройки фида
	 * @return array Массив товаров
	 */
	public function getProducts(array $options = [], array $settings = []): array
	{
		$limit = isset($options['limit']) ? (int)$options['limit'] : null;
		$start = isset($options['start']) ? (int)$options['start'] : 0;

		$sql = $this->buildProductQuery($settings);

		if ($limit) {
			$sql .= " LIMIT " . $start . ", " . $limit;
		}

		$query = $this->db->query($sql);
		return $query->rows;
	}

	/**
	 * Получение количества товаров в фиде
	 *
	 * @param array $settings Настройки фида
	 * @return int Количество товаров
	 */
	public function getTotalProducts(array $settings = []): int
	{
		$sql = str_replace(
			"SELECT DISTINCT p.product_id, pd.name, pd.description, p.model, p.sku, p.image, p.price, p.quantity, p.weight, p.status, p.manufacturer_id, m.name as manufacturer, p2c.category_id, p.date_added, p.date_modified",
			"SELECT COUNT(DISTINCT p.product_id) as total",
			$this->buildProductQuery($settings)
		);

		// Убираем ORDER BY для COUNT запроса
		$sql = preg_replace('/ORDER BY.*$/', '', $sql);

		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	/**
	 * Построение базового SQL запроса для товаров
	 *
	 * @param array $settings Настройки фида
	 * @return string SQL запрос
	 */
	private function buildProductQuery(array $settings = []): string
	{
		$sql = "SELECT DISTINCT `p`.`product_id`, `pd`.`name`, `pd`.`description`, `pd`.`meta_description`, 
                `p`.`model`, `p`.`sku`, `p`.`upc`, `p`.`ean`, `p`.`jan`, `p`.`isbn`, `p`.`mpn`, 
                `p`.`image`, `p`.`price`, `p`.`quantity`, `p`.`weight`, `p`.`weight_class_id`,
                `p`.`length`, `p`.`width`, `p`.`height`, `p`.`length_class_id`, 
                `p`.`status`, `p`.`manufacturer_id`, `m`.`name` as `manufacturer`,
                `p2c`.`category_id`, `p`.`date_added`, `p`.`date_modified`
                FROM `{$this->dbPrefix}product` `p`
                LEFT JOIN `{$this->dbPrefix}product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`)
                LEFT JOIN `{$this->dbPrefix}manufacturer` `m` ON (`p`.`manufacturer_id` = `m`.`manufacturer_id`)
                LEFT JOIN `{$this->dbPrefix}product_to_category` `p2c` ON (`p`.`product_id` = `p2c`.`product_id`)
                WHERE `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'
                AND `p`.`status` = 1
                AND `p`.`date_available` <= NOW()";

		// Фильтр по наличию
		if (isset($settings['stock_status']) && !$settings['stock_status']) {
			$sql .= " AND `p`.`quantity` > 0";
		}

		// Фильтр по цене
		if (isset($settings['min_price']) && $settings['min_price'] > 0) {
			$sql .= " AND `p`.`price` >= '" . (float)$settings['min_price'] . "'";
		}

		if (isset($settings['max_price']) && $settings['max_price'] > 0) {
			$sql .= " AND `p`.`price` <= '" . (float)$settings['max_price'] . "'";
		}

		// Фильтр по категориям
		if (!empty($settings['exclude_categories'])) {
			$categories = array_filter(array_map('intval', $settings['exclude_categories']));
			if (!empty($categories)) {
				$sql .= " AND `p2c`.`category_id` NOT IN (" . implode(',', $categories) . ")";
			}
		}

		// Фильтр по производителям
		if (!empty($settings['exclude_manufacturers'])) {
			$manufacturers = array_filter(array_map('intval', $settings['exclude_manufacturers']));
			if (!empty($manufacturers)) {
				$sql .= " AND `p`.`manufacturer_id` NOT IN (" . implode(',', $manufacturers) . ")";
			}
		}

		$sql .= " ORDER BY `p`.`product_id` ASC";

		return $sql;
	}

	/**
	 * Получение опций товара
	 *
	 * @param int $product_id ID товара
	 * @return array Массив опций
	 */
	public function getProductOptions(int $product_id): array
	{
		$query = $this->db->query("
            SELECT `po`.`product_option_id`, `po`.`option_id`, `od`.`name` as `option_name`, `po`.`required`,
                   `pov`.`product_option_value_id`, `pov`.`option_value_id`, `ovd`.`name` as `option_value_name`,
                   `pov`.`price`, `pov`.`price_prefix`, `pov`.`weight`, `pov`.`weight_prefix`, `pov`.`quantity`
            FROM `{$this->dbPrefix}product_option` `po`
            LEFT JOIN `{$this->dbPrefix}option_description` `od` ON (`po`.`option_id` = `od`.`option_id`)
            LEFT JOIN `{$this->dbPrefix}product_option_value` `pov` ON (`po`.`product_option_id` = `pov`.`product_option_id`)
            LEFT JOIN `{$this->dbPrefix}option_value_description` `ovd` ON (`pov`.`option_value_id` = `ovd`.`option_value_id`)
            WHERE `po`.`product_id` = $product_id
            AND `od`.`language_id` = " . (int)$this->config->get('config_language_id') . "
            AND `ovd`.`language_id` = " . (int)$this->config->get('config_language_id') . "
            ORDER BY `po`.`sort_order`, `pov`.`sort_order`
        ");

		$options = array();
		foreach ($query->rows as $row) {
			if (!isset($options[$row['option_id']])) {
				$options[$row['option_id']] = array(
					'option_id' => $row['option_id'],
					'name' => $row['option_name'],
					'required' => $row['required'],
					'values' => array()
				);
			}

			$options[$row['option_id']]['values'][] = array(
				'option_value_id' => $row['option_value_id'],
				'name' => $row['option_value_name'],
				'price' => $row['price'],
				'price_prefix' => $row['price_prefix'],
				'weight' => $row['weight'],
				'weight_prefix' => $row['weight_prefix'],
				'quantity' => $row['quantity']
			);
		}

		return $options;
	}

	/**
	 * Получение атрибутов товара
	 *
	 * @param int $product_id ID товара
	 * @return array Массив атрибутов
	 */
	public function getProductAttributes(int $product_id): array
	{
		$query = $this->db->query("
            SELECT `ad`.`name`, `pa`.`text` 
            FROM `{$this->dbPrefix}product_attribute` `pa`
            LEFT JOIN `{$this->dbPrefix}attribute_description` `ad` ON (`pa`.`attribute_id` = `ad`.`attribute_id`)
            WHERE `pa`.`product_id` = $product_id
            AND `ad`.`language_id` = " . (int)$this->config->get('config_language_id') . "
            AND `pa`.`text` != ''
            ORDER BY `pa`.`sort_order`
        ");

		return $query->rows;
	}

	/**
	 * Получение дополнительных изображений товара
	 *
	 * @param int $product_id ID товара
	 * @return array Массив изображений
	 */
	public function getProductImages(int $product_id): array
	{
		$query = $this->db->query("
            SELECT `image` 
            FROM `{$this->dbPrefix}product_image` 
            WHERE `product_id` = $product_id 
            ORDER BY `sort_order`
        ");

		return $query->rows;
	}

	/**
	 * Получение категорий используемых в товарах
	 *
	 * @param array $product_ids Массив ID товаров
	 * @return array Массив категорий
	 */
	public function getCategoriesForProducts(array $product_ids): array
	{
		if (empty($product_ids)) {
			return array();
		}

		$product_ids = array_map('intval', $product_ids);

		$query = $this->db->query("
            SELECT DISTINCT `c`.`category_id`, `cd`.`name`, `c`.`parent_id` 
            FROM `{$this->dbPrefix}category` `c` 
            LEFT JOIN `{$this->dbPrefix}category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) 
            LEFT JOIN `{$this->dbPrefix}product_to_category` `p2c` ON (`c`.`category_id` = `p2c`.`category_id`)
            WHERE `p2c`.`product_id` IN (" . implode(',', $product_ids) . ") 
            AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY `c`.`sort_order`, `cd`.`name`
        ");

		return $query->rows;
	}

	/**
	 * Получение полной информации о товаре
	 *
	 * @param int $product_id ID товара
	 * @return array|null Информация о товаре
	 */
	public function getProductInfo(int $product_id): ?array
	{
		$query = $this->db->query("
            SELECT `p`.*, `pd`.`name`, `pd`.`description`, `pd`.`meta_description`, `pd`.`meta_keyword`, `pd`.`tag`,
                   `m`.`name` as `manufacturer`, `ss`.`name` as `stock_status`, `wc`.`title` as `weight_class`,
                   `lc`.`title` as `length_class`, `p2c`.`category_id`
            FROM `{$this->dbPrefix}product` `p`
            LEFT JOIN `{$this->dbPrefix}product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`)
            LEFT JOIN `{$this->dbPrefix}manufacturer` `m` ON (`p`.`manufacturer_id` = `m`.`manufacturer_id`)
            LEFT JOIN `{$this->dbPrefix}stock_status` `ss` ON (`p`.`stock_status_id` = `ss`.`stock_status_id`)
            LEFT JOIN `{$this->dbPrefix}weight_class_description` `wc` ON (`p`.`weight_class_id` = `wc`.`weight_class_id`)
            LEFT JOIN `{$this->dbPrefix}length_class_description` `lc` ON (`p`.`length_class_id` = `lc`.`length_class_id`)
            LEFT JOIN `{$this->dbPrefix}product_to_category` `p2c` ON (`p`.`product_id` = `p2c`.`product_id`)
            WHERE `p`.`product_id` = $product_id
            AND `pd`.`language_id` = " . (int)$this->config->get('config_language_id') . "
            AND (`ss`.`language_id` = " . (int)$this->config->get('config_language_id') . " OR `ss`.`language_id` IS NULL)
            AND (`wc`.`language_id` = " . (int)$this->config->get('config_language_id') . " OR `wc`.`language_id` IS NULL)
            AND (`lc`.`language_id` = " . (int)$this->config->get('config_language_id') . " OR `lc`.`language_id` IS NULL)
            LIMIT 1
        ");

		return $query->num_rows ? $query->row : null;
	}

	/**
	 * Получение статистики товаров
	 *
	 * @return array Массив со статистикой
	 */
	public function getProductStatistics(): array
	{
		$stats = array();

		// Общее количество активных товаров
		$query = $this->db->query("SELECT COUNT(*) as total FROM {$this->dbPrefix}product WHERE status = '1'");
		$stats['total_products_shop'] = (int)$query->row['total'];

		// Товары с изображениями
		$query = $this->db->query("SELECT COUNT(*) as total FROM {$this->dbPrefix}product WHERE status = '1' AND image != ''");
		$stats['products_with_images'] = (int)$query->row['total'];

		// Товары в наличии
		$query = $this->db->query("SELECT COUNT(*) as total FROM {$this->dbPrefix}product WHERE status = '1' AND quantity > 0");
		$stats['products_in_stock'] = (int)$query->row['total'];

		// Вычисляемые значения
		$stats['products_without_images'] = $stats['total_products_shop'] - $stats['products_with_images'];
		$stats['products_out_of_stock'] = $stats['total_products_shop'] - $stats['products_in_stock'];

		return $stats;
	}
}