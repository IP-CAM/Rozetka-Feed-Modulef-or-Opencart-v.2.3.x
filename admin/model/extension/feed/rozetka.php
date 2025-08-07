<?php

/**
 * @property DB $db
 */

class ModelExtensionFeedRozetka extends Model {
	private string $dbPrefix = DB_PREFIX;

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
	}

	public function uninstall()
	{
		$this->db->query("DROP TABLE IF EXISTS `{$this->dbPrefix}rozetka_feed_log`");
		$this->db->query("DROP TABLE IF EXISTS `{$this->dbPrefix}rozetka_categories`");
	}
}