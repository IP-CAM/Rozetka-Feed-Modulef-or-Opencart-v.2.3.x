<?php

/**
 * @property DB $db
 */

class ModelExtensionFeedRozetka extends Model {
	private string $dbPrefix = DB_PREFIX;

	public function getRozetkaCategories($data = array()) {
		$sql = "SELECT * FROM `{$this->dbPrefix}rozetka_categories`";

		$where = array();

		if (!empty($data['search'])) {
			$search = $this->db->escape($data['search']);
			$where[] = "(name LIKE '%{$search}%' OR full_name LIKE '%{$search}%')";
		}

		if (!empty($where)) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$sql .= " ORDER BY `level`, `name`";

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

	public function getTotalRozetkaCategories($search = '') {
		$sql = "SELECT COUNT(*) as total FROM `{$this->dbPrefix}rozetka_categories`";

		if (!empty($search)) {
			$search = $this->db->escape($search);
			$sql .= " WHERE (name LIKE '%{$search}%' OR full_name LIKE '%{$search}%')";
		}

		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	public function getCategoryMappings() {
		$query = $this->db->query("
			SELECT 
				`cm`.*,
				`cd`.`name` as `shop_category_name`,
				`rc`.`name` as `rozetka_category_name`,
				`rc`.`full_name` as `rozetka_category_full_name`
			FROM `{$this->dbPrefix}category_mapping` `cm`
			LEFT JOIN `{$this->dbPrefix}category_description` `cd` ON (`cm`.`shop_category_id` = `cd`.`category_id`)
			LEFT JOIN `{$this->dbPrefix}rozetka_categories` `rc` ON (`cm`.`rozetka_category_id` = `rc`.`category_id`)
			WHERE `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'
			ORDER BY `cd`.`name`
		");
		return $query->rows;
	}

	public function saveCategoryMappings($mappings) {
		// Clear existing mappings
		$this->db->query("TRUNCATE TABLE `{$this->dbPrefix}category_mapping`");

		// Insert new mappings
		foreach ($mappings as $mapping) {
			$this->db->query("
				INSERT INTO `{$this->dbPrefix}category_mapping` 
				SET 
					`shop_category_id` = '" . (int)$mapping['shop_category_id'] . "',
					`rozetka_category_id` = '" . $this->db->escape($mapping['rozetka_category_id']) . "'
			");
		}
	}

	/**
	 * Импорт категорий из массива данных
	 */
	public function importCategoriesFromArray(array $categories): array
	{
		$imported = 0;
		$updated = 0;

		// Начинаем транзакцию
		$this->db->query("START TRANSACTION");

		try {
			foreach ($categories as $category) {
				$categoryId = $this->db->escape($category['categoryId']);
				$name = $this->db->escape($category['name']);
				$fullName = $this->db->escape($category['fullName']);
				$url = $this->db->escape($category['url']);
				$level = (int)$category['level'];
				$parentId = isset($category['parentId']) && !empty($category['parentId']) ?
					$this->db->escape($category['parentId']) : 'NULL';

				// Проверяем, существует ли категория
				$existsQuery = $this->db->query("
                SELECT `id` FROM `{$this->dbPrefix}rozetka_categories` 
                WHERE `category_id` = '$categoryId'
            ");

				if ($existsQuery->num_rows > 0) {
					// Обновляем существующую
					$this->db->query("
                    UPDATE `{$this->dbPrefix}rozetka_categories` 
                    SET 
                        `name` = '$name',
                        `full_name` = '$fullName',
                        `url` = '$url',
                        `level` = $level,
                        `parent_id` = $parentId,
                        `updated_at` = NOW()
                    WHERE `category_id` = '$categoryId'
                ");
					$updated++;
				} else {
					// Вставляем новую
					$this->db->query("
                    INSERT INTO `{$this->dbPrefix}rozetka_categories` 
                    SET 
                        `category_id` = '$categoryId',
                        `name` = '$name',
                        `full_name` = '$fullName',
                        `url` = '$url',
                        `level` = $level,
                        `parent_id` = $parentId,
                        `created_at` = NOW(),
                        `updated_at` = NOW()
                ");
					$imported++;
				}
			}

			$this->db->query("COMMIT");

		} catch (Exception $e) {
			$this->db->query("ROLLBACK");
			throw $e;
		}

		return [
			'imported' => $imported,
			'updated' => $updated,
			'total' => $imported + $updated
		];
	}

	/**
	 * Очистка всех категорий Rozetka
	 */
	public function clearRozetkaCategories(): void
	{
		$this->db->query("TRUNCATE TABLE `{$this->dbPrefix}rozetka_categories`");
	}

	/**
	 * Получение статистики категорий
	 */
	public function getCategoriesStatistics(): array
	{
		$query = $this->db->query("SELECT COUNT(*) as total FROM `{$this->dbPrefix}rozetka_categories`");
		$total = (int)$query->row['total'];

		$levelQuery = $this->db->query("
        SELECT `level`, COUNT(*) as count 
        FROM `{$this->dbPrefix}rozetka_categories` 
        GROUP BY `level` 
        ORDER BY `level`
    ");

		$byLevel = [];
		foreach ($levelQuery->rows as $row) {
			$byLevel[$row['level']] = (int)$row['count'];
		}

		return [
			'total' => $total,
			'by_level' => $byLevel
		];
	}

	public function install()
	{
		$this->db->query("CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}rozetka_feed_log` (
            `log_id` int(11) NOT NULL AUTO_INCREMENT,
            `products_count` int(11) NOT NULL DEFAULT 0,
            `offers_count` int(11) NOT NULL DEFAULT 0,
            `file_size` varchar(20) NOT NULL DEFAULT '',
            `generation_time` float NOT NULL DEFAULT 0,
            `memory_used` varchar(20) NOT NULL DEFAULT '',
            `date_generated` datetime NOT NULL,
            `status` enum('started','success','error') NOT NULL DEFAULT 'started',
            `error_message` text,
            `warnings` text,
            PRIMARY KEY (`log_id`),
            KEY date_generated (`date_generated`),
            KEY status (`status`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}rozetka_categories` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`category_id` varchar(20) NOT NULL COMMENT 'ID категории из Rozetka',
			`name` varchar(255) NOT NULL COMMENT 'Название категории',
			`full_name` text NOT NULL COMMENT 'Полное название с иерархией',
			`url` text NOT NULL COMMENT 'URL категории',
			`level` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Уровень вложенности',
			`parent_id` varchar(20) DEFAULT NULL COMMENT 'ID родительской категории',
			`created_at` datetime DEFAULT NULL COMMENT 'Дата создания',
			`updated_at` datetime DEFAULT NULL COMMENT 'Дата обновления',
			PRIMARY KEY (`id`),
			UNIQUE KEY `category_id_unique` (`category_id`),
			KEY `idx_parent_id` (`parent_id`),
			KEY `idx_level` (`level`),
			KEY `idx_category_id` (`category_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");


		$this->db->query("CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}category_mapping` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`shop_category_id` int(11) NOT NULL COMMENT 'ID категории магазина',
			`rozetka_category_id` varchar(20) NOT NULL COMMENT 'ID категории Rozetka',
			PRIMARY KEY (`id`),
			UNIQUE KEY `shop_category_unique` (`shop_category_id`),
			KEY `idx_rozetka_category` (`rozetka_category_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
	}

	public function uninstall()
	{
		$this->db->query("DROP TABLE IF EXISTS `{$this->dbPrefix}rozetka_feed_log`");
		$this->db->query("DROP TABLE IF EXISTS `{$this->dbPrefix}rozetka_categories`");
		$this->db->query("DROP TABLE IF EXISTS `{$this->dbPrefix}category_mapping`");
	}
}