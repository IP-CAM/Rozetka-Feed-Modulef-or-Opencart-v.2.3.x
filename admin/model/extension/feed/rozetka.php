<?php

/**
 * @property DB $db
 */

class ModelExtensionFeedRozetka extends Model {
	private string $dbPrefix = DB_PREFIX;

	public function getRozetkaCategories() {
		$query = $this->db->query("SELECT * FROM `{$this->dbPrefix}rozetka_categories` ORDER BY `level`, `name`");
		return $query->rows;
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

	public function importCategories(array $categories)
	{
		$this->db->query("TRUNCATE TABLE {$this->dbPrefix}rozetka_categories");

		foreach ($categories as $category) {
			$this->db->query("
				INSERT INTO `{$this->dbPrefix}rozetka_categories` 
				SET 
					`category_id` = " . (int)$category['categoryId'] . ", 
					`name` = '{$this->db->escape($category['name'])}', 
					`full_name` = '{$this->db->escape($category['fullName'])}', 
					`url` = '{$this->db->escape($category['url'])}', 
					`level` = " . (int)$category['level'] . ", 
					`parent_id` = " . (int)$category['parent_id'] . "
				ON DUPLICATE KEY UPDATE 
					`name` = '{$this->db->escape($category['name'])}', 
					`full_name` = '{$this->db->escape($category['fullName'])}', 
					`url` = '{$this->db->escape($category['url'])}', 
					`level` = " . (int)$category['level'] . ", 
					`parent_id` = " . (int)$category['parent_id'] . "
			");
		}
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
			`parent_id` int(11) DEFAULT NULL COMMENT 'ID родительской категории'
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