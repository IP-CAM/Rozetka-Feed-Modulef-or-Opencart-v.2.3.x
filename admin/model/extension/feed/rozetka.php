<?php
class ModelExtensionFeedRozetka extends Model {

	public function getTotalProducts() {
		$exclude_categories = $this->config->get('feed_rozetka_exclude_categories') ?: array();
		$exclude_manufacturers = $this->config->get('feed_rozetka_exclude_manufacturers') ?: array();
		$min_price = (float)$this->config->get('feed_rozetka_min_price');
		$max_price = (float)$this->config->get('feed_rozetka_max_price');
		$stock_status = $this->config->get('feed_rozetka_stock_status');

		$sql = "SELECT COUNT(DISTINCT p.product_id) as total
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND p.status = '1'
                AND p.date_available <= NOW()";

		if (!$stock_status) {
			$sql .= " AND p.quantity > 0";
		}

		if ($min_price > 0) {
			$sql .= " AND p.price >= '" . (float)$min_price . "'";
		}

		if ($max_price > 0) {
			$sql .= " AND p.price <= '" . (float)$max_price . "'";
		}

		if (!empty($exclude_categories)) {
			$sql .= " AND p2c.category_id NOT IN (" . implode(',', array_map('intval', $exclude_categories)) . ")";
		}

		if (!empty($exclude_manufacturers)) {
			$sql .= " AND p.manufacturer_id NOT IN (" . implode(',', array_map('intval', $exclude_manufacturers)) . ")";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getProducts($data = array()) {
		$exclude_categories = $this->config->get('feed_rozetka_exclude_categories') ?: array();
		$exclude_manufacturers = $this->config->get('feed_rozetka_exclude_manufacturers') ?: array();
		$min_price = (float)$this->config->get('feed_rozetka_min_price');
		$max_price = (float)$this->config->get('feed_rozetka_max_price');
		$stock_status = $this->config->get('feed_rozetka_stock_status');

		$sql = "SELECT DISTINCT p.product_id, pd.name, pd.description, p.model, p.sku, p.image, p.price, p.quantity, p.weight, p.weight_class_id, p.length, p.width, p.height, p.length_class_id, p.status, p.manufacturer_id, m.name as manufacturer, p2c.category_id, p.date_added, p.date_modified
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND p.status = '1'
                AND p.date_available <= NOW()";

		if (!$stock_status) {
			$sql .= " AND p.quantity > 0";
		}

		if ($min_price > 0) {
			$sql .= " AND p.price >= '" . (float)$min_price . "'";
		}

		if ($max_price > 0) {
			$sql .= " AND p.price <= '" . (float)$max_price . "'";
		}

		if (!empty($exclude_categories)) {
			$sql .= " AND p2c.category_id NOT IN (" . implode(',', array_map('intval', $exclude_categories)) . ")";
		}

		if (!empty($exclude_manufacturers)) {
			$sql .= " AND p.manufacturer_id NOT IN (" . implode(',', array_map('intval', $exclude_manufacturers)) . ")";
		}

		$sql .= " ORDER BY p.product_id ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getStatistics() {
		$stats = array();

		// Общее количество товаров в магазине
		$query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE status = '1'");
		$stats['total_products_shop'] = $query->row['total'];

		// Товары в фиде
		$stats['total_products_feed'] = $this->getTotalProducts();

		// Товары с изображениями
		$query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE status = '1' AND image != ''");
		$stats['products_with_images'] = $query->row['total'];

		// Товары без изображений
		$stats['products_without_images'] = $stats['total_products_shop'] - $stats['products_with_images'];

		// Товары в наличии
		$query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE status = '1' AND quantity > 0");
		$stats['products_in_stock'] = $query->row['total'];

		// Товары без наличия
		$stats['products_out_of_stock'] = $stats['total_products_shop'] - $stats['products_in_stock'];

		// Исключенные категории
		$exclude_categories = $this->config->get('feed_rozetka_exclude_categories') ?: array();
		$stats['excluded_categories'] = count($exclude_categories);

		// Исключенные производители
		$exclude_manufacturers = $this->config->get('feed_rozetka_exclude_manufacturers') ?: array();
		$stats['excluded_manufacturers'] = count($exclude_manufacturers);

		// Последняя генерация
		$last_generation = $this->getLastFeedGeneration();
		$stats['last_generation'] = $last_generation;

		return $stats;
	}

	public function getCategoriesWithProductCount() {
		$sql = "SELECT c.category_id, cd.name, COUNT(p2c.product_id) as product_count
                FROM " . DB_PREFIX . "category c
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (c.category_id = p2c.category_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id AND p.status = '1')
                WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                GROUP BY c.category_id
                ORDER BY cd.name ASC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getManufacturersWithProductCount() {
		$sql = "SELECT m.manufacturer_id, m.name, COUNT(p.product_id) as product_count
                FROM " . DB_PREFIX . "manufacturer m
                LEFT JOIN " . DB_PREFIX . "product p ON (m.manufacturer_id = p.manufacturer_id AND p.status = '1')
                GROUP BY m.manufacturer_id
                HAVING product_count > 0
                ORDER BY m.name ASC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function createLogTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "rozetka_feed_log (
            log_id int(11) NOT NULL AUTO_INCREMENT,
            products_count int(11) NOT NULL DEFAULT '0',
            file_size varchar(20) NOT NULL DEFAULT '',
            generation_time float NOT NULL DEFAULT '0',
            memory_used varchar(20) NOT NULL DEFAULT '',
            date_generated datetime NOT NULL,
            status enum('success','error') NOT NULL DEFAULT 'success',
            error_message text,
            PRIMARY KEY (log_id),
            KEY date_generated (date_generated)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
	}

	public function logFeedGeneration($data) {
		$this->createLogTable();

		$this->db->query("INSERT INTO " . DB_PREFIX . "rozetka_feed_log SET 
            products_count = '" . (int)$data['products_count'] . "',
            file_size = '" . $this->db->escape($data['file_size']) . "',
            generation_time = '" . (float)$data['generation_time'] . "',
            memory_used = '" . $this->db->escape($data['memory_used']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            error_message = '" . $this->db->escape(isset($data['error_message']) ? $data['error_message'] : '') . "',
            date_generated = NOW()");
	}

	public function getLastFeedGeneration() {
		$this->createLogTable();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "rozetka_feed_log ORDER BY date_generated DESC LIMIT 1");

		if ($query->num_rows) {
			return $query->row;
		}

		return false;
	}

	public function getFeedGenerationHistory($limit = 10) {
		$this->createLogTable();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "rozetka_feed_log ORDER BY date_generated DESC LIMIT " . (int)$limit);

		return $query->rows;
	}


	public function testFeedGeneration() {
		// Принудительная очистка памяти для точного измерения
		if (function_exists('gc_collect_cycles')) {
			gc_collect_cycles();
		}

		$start_time = microtime(true);
		$memory_start = memory_get_usage(true);
		$memory_peak_start = memory_get_peak_usage(true);

		try {
			// Получаем ограниченное количество товаров для тестирования
			$this->load->model('catalog/product');
			$this->load->model('tool/image');

			$products = $this->getProducts(array('start' => 0, 'limit' => 10));

			// Симулируем процесс создания XML для более точного измерения памяти
			$test_data = array();
			foreach ($products as $product) {
				// Загружаем дополнительные данные как в реальной генерации
				$product_info = $this->model_catalog_product->getProduct($product['product_id']);
				if ($product_info) {
					// Получаем изображения
					$images = $this->model_catalog_product->getProductImages($product['product_id']);

					// Получаем опции если включены
					if ($this->config->get('feed_rozetka_include_options')) {
						$options = $this->model_catalog_product->getProductOptions($product['product_id']);
						$product_info['options'] = $options;
					}

					// Получаем атрибуты если включены
					if ($this->config->get('feed_rozetka_include_attributes')) {
						$attributes = $this->model_catalog_product->getProductAttributes($product['product_id']);
						$product_info['attributes'] = $attributes;
					}

					$product_info['images'] = $images;
					$test_data[] = $product_info;
				}
			}

			// Дополнительная обработка для имитации XML генерации
			$xml_simulation = array();
			foreach ($test_data as $product) {
				$offer_data = array(
					'id' => $product['product_id'],
					'name' => $product['name'],
					'description' => isset($product['description']) ? substr(strip_tags($product['description']), 0, 1000) : '',
					'price' => $product['price'],
					'images' => isset($product['images']) ? $product['images'] : array(),
					'options' => isset($product['options']) ? $product['options'] : array(),
					'attributes' => isset($product['attributes']) ? $product['attributes'] : array()
				);
				$xml_simulation[] = $offer_data;
			}

			$generation_time = microtime(true) - $start_time;
			$memory_end = memory_get_usage(true);
			$memory_peak_end = memory_get_peak_usage(true);

			// Вычисляем использованную память
			$memory_used = max($memory_end - $memory_start, $memory_peak_end - $memory_peak_start);

			// Если память всё ещё 0, используем пиковое значение
			if ($memory_used <= 0) {
				$memory_used = $memory_peak_end - $memory_peak_start;
			}

			// Минимальное значение памяти для реалистичной оценки
			if ($memory_used < 1024) {
				$memory_used = 1024 * count($test_data); // 1KB на товар минимум
			}

			$total_products = $this->getTotalProducts();

			// Более консервативная оценка времени и памяти
			$estimated_time = ($generation_time / count($test_data)) * $total_products;
			$estimated_memory = ($memory_used / count($test_data)) * $total_products;

			// Добавляем накладные расходы на XML структуру
			$xml_overhead = $total_products * 0.1; // 10% накладных расходов
			$estimated_time += $xml_overhead;
			$estimated_memory += ($total_products * 2048); // +2KB на товар для XML структуры

			return array(
				'status' => 'success',
				'products_count' => count($test_data),
				'generation_time' => round($generation_time, 4),
				'memory_used' => $this->formatBytes($memory_used),
				'estimated_total_time' => round($estimated_time, 2),
				'estimated_memory' => $this->formatBytes($estimated_memory),
				'total_products' => $total_products,
				'memory_details' => array(
					'start' => $this->formatBytes($memory_start),
					'end' => $this->formatBytes($memory_end),
					'peak_start' => $this->formatBytes($memory_peak_start),
					'peak_end' => $this->formatBytes($memory_peak_end),
					'calculated' => $this->formatBytes($memory_used)
				)
			);

		} catch (Exception $e) {
			return array(
				'status' => 'error',
				'error_message' => $e->getMessage()
			);
		}
	}

	public function generatePreview() {
		$start_time = microtime(true);
		$memory_start = memory_get_usage(true);

		try {
			$this->load->model('catalog/product');
			$this->load->model('catalog/category');
			$this->load->model('tool/image');

			// Получаем первые 5 товаров для предпросмотра
			$products = $this->getProducts(array('start' => 0, 'limit' => 5));

			$xml = new DOMDocument('1.0', 'UTF-8');
			$xml->formatOutput = true;

			// Корневой элемент
			$yml_catalog = $xml->createElement('yml_catalog');
			$yml_catalog->setAttribute('date', date('Y-m-d H:i'));
			$xml->appendChild($yml_catalog);

			// Элемент shop
			$shop = $xml->createElement('shop');
			$yml_catalog->appendChild($shop);

			// Информация о магазине
			$shop->appendChild($xml->createElement('name', htmlspecialchars($this->config->get('feed_rozetka_shop_name') ?: $this->config->get('config_name'))));
			$shop->appendChild($xml->createElement('company', htmlspecialchars($this->config->get('feed_rozetka_company') ?: $this->config->get('config_name'))));
			$shop->appendChild($xml->createElement('url', $this->config->get('config_url')));

			// Валюты
			$currencies = $xml->createElement('currencies');
			$shop->appendChild($currencies);

			$currency_code = $this->config->get('feed_rozetka_currency') ?: $this->config->get('config_currency');
			$currency = $xml->createElement('currency');
			$currency->setAttribute('id', $currency_code);
			$currency->setAttribute('rate', '1');
			$currencies->appendChild($currency);

			// Категории (только используемые)
			$categories_xml = $xml->createElement('categories');
			$shop->appendChild($categories_xml);

			$used_categories = array();
			foreach ($products as $product) {
				if ($product['category_id'] && !in_array($product['category_id'], $used_categories)) {
					$used_categories[] = $product['category_id'];
				}
			}

			if (!empty($used_categories)) {
				$category_query = $this->db->query("SELECT c.category_id, cd.name, c.parent_id 
                    FROM " . DB_PREFIX . "category c 
                    LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
                    WHERE c.category_id IN (" . implode(',', $used_categories) . ") 
                    AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

				foreach ($category_query->rows as $category) {
					$category_xml = $xml->createElement('category', htmlspecialchars($category['name']));
					$category_xml->setAttribute('id', $category['category_id']);
					if ($category['parent_id']) {
						$category_xml->setAttribute('parentId', $category['parent_id']);
					}
					$categories_xml->appendChild($category_xml);
				}
			}

			// Товары
			$offers = $xml->createElement('offers');
			$shop->appendChild($offers);

			foreach ($products as $product) {
				$offer = $this->createOfferPreview($xml, $product);
				if ($offer) {
					$offers->appendChild($offer);
				}
			}

			$generation_time = microtime(true) - $start_time;
			$memory_used = memory_get_usage(true) - $memory_start;

			return array(
				'status' => 'success',
				'xml_content' => $xml->saveXML(),
				'products_count' => count($products),
				'generation_time' => round($generation_time, 4),
				'memory_used' => $this->formatBytes($memory_used),
				'xml_size' => $this->formatBytes(strlen($xml->saveXML()))
			);

		} catch (Exception $e) {
			return array(
				'status' => 'error',
				'error_message' => $e->getMessage()
			);
		}
	}

	private function createOfferPreview($xml, $product) {
		if ($product['quantity'] <= 0 && !$this->config->get('feed_rozetka_stock_status')) {
			return false;
		}

		$offer = $xml->createElement('offer');
		$offer->setAttribute('id', $product['product_id']);
		$offer->setAttribute('available', $product['quantity'] > 0 ? 'true' : 'false');

		// Основная информация
		$offer->appendChild($xml->createElement('name', htmlspecialchars($product['name'])));

		// Цена
		$price = $product['price'];
		$offer->appendChild($xml->createElement('price', number_format($price, 2, '.', '')));

		$currency_code = $this->config->get('feed_rozetka_currency') ?: $this->config->get('config_currency');
		$offer->appendChild($xml->createElement('currencyId', $currency_code));

		// Категория
		if ($product['category_id']) {
			$offer->appendChild($xml->createElement('categoryId', $product['category_id']));
		}

		// Описание (укороченное для предпросмотра)
		if ($product['description']) {
			$description = strip_tags($product['description']);
			$description = substr($description, 0, 200) . '...';
			$offer->appendChild($xml->createElement('description', htmlspecialchars($description)));
		}

		// Производитель
		if ($product['manufacturer']) {
			$offer->appendChild($xml->createElement('vendor', htmlspecialchars($product['manufacturer'])));
		}

		// Модель
		if ($product['model']) {
			$offer->appendChild($xml->createElement('model', htmlspecialchars($product['model'])));
		}

		return $offer;
	}

	private function formatBytes($size, $precision = 2) {
		if ($size == 0) return '0 B';

		$base = log($size, 1024);
		$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

		return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
	}

	public function validateSettings($settings) {
		$errors = array();

		// Проверка размеров изображений
		if (isset($settings['feed_rozetka_image_width'])) {
			$width = (int)$settings['feed_rozetka_image_width'];
			if ($width < 100 || $width > 2000) {
				$errors['image_width'] = 'Ширина изображения должна быть от 100 до 2000 пикселей';
			}
		}

		if (isset($settings['feed_rozetka_image_height'])) {
			$height = (int)$settings['feed_rozetka_image_height'];
			if ($height < 100 || $height > 2000) {
				$errors['image_height'] = 'Высота изображения должна быть от 100 до 2000 пикселей';
			}
		}

		// Проверка диапазона цен
		if (isset($settings['feed_rozetka_min_price']) && isset($settings['feed_rozetka_max_price'])) {
			$min_price = (float)$settings['feed_rozetka_min_price'];
			$max_price = (float)$settings['feed_rozetka_max_price'];

			if ($min_price > 0 && $max_price > 0 && $min_price >= $max_price) {
				$errors['price_range'] = 'Минимальная цена не может быть больше максимальной';
			}
		}

		// Проверка длины описания
		if (isset($settings['feed_rozetka_description_length'])) {
			$length = (int)$settings['feed_rozetka_description_length'];
			if ($length < 100 || $length > 10000) {
				$errors['description_length'] = 'Длина описания должна быть от 100 до 10000 символов';
			}
		}

		return $errors;
	}

	public function clearFeedCache() {
		// Очистка кэша если используется
		if (method_exists($this, 'cache')) {
			$this->cache->delete('rozetka.feed');
		}

		// Удаление временных файлов
		$temp_files = glob(DIR_SYSTEM . 'storage/cache/rozetka_*');
		foreach ($temp_files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}

		return true;
	}
}