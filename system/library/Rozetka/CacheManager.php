<?php

namespace Rozetka;

use Config;
use Registry;

/**
 * Класс для управления кэшем Rozetka фида
 * Отвечает за кэширование данных и временных файлов
 */
class CacheManager {

	private string $cache_dir;
	private Config $config;

	public function __construct(Registry $registry) {
		$this->config = $registry->get('config');
		$this->cache_dir = DIR_SYSTEM . 'storage/cache/rozetka/';
		$this->createCacheDir();
	}

	/**
	 * Создание директории кэша
	 */
	private function createCacheDir() {
		if (!is_dir($this->cache_dir)) {
			mkdir($this->cache_dir, 0755, true);
		}
	}

	/**
	 * Получение данных из кэша
	 *
	 * @param string $key Ключ кэша
	 * @return mixed|null Данные или null если не найдено
	 */
	public function get(string $key) {
		$file = $this->cache_dir . $this->sanitizeKey($key) . '.cache';

		if (!is_file($file)) {
			return null;
		}

		$content = file_get_contents($file);
		if ($content === false) {
			return null;
		}

		$data = unserialize($content);

		// Проверка времени жизни
		if (isset($data['expire']) && $data['expire'] < time()) {
			unlink($file);
			return null;
		}

		return $data['value'] ?? null;
	}

	/**
	 * Сохранение данных в кэш
	 *
	 * @param string $key Ключ кэша
	 * @param mixed $value Значение
	 * @param int $expire Время жизни в секундах (по умолчанию 1 час)
	 * @return bool Успешность операции
	 */
	public function set(string $key, $value, int $expire = 3600): bool
	{
		$file = $this->cache_dir . $this->sanitizeKey($key) . '.cache';

		$data = array(
			'value' => $value,
			'expire' => time() + $expire,
			'created' => time()
		);

		return file_put_contents($file, serialize($data)) !== false;
	}

	/**
	 * Удаление данных из кэша
	 *
	 * @param string $key Ключ кэша
	 * @return bool Успешность операции
	 */
	public function delete(string $key): bool
	{
		$file = $this->cache_dir . $this->sanitizeKey($key) . '.cache';

		if (is_file($file)) {
			return unlink($file);
		}

		return true;
	}

	/**
	 * Проверка существования ключа в кэше
	 *
	 * @param string $key Ключ кэша
	 * @return bool Существует ли ключ
	 */
	public function exists(string $key): bool
	{
		return $this->get($key) !== null;
	}

	/**
	 * Очистка всего кэша
	 *
	 * @return int Количество удаленных файлов
	 */
	public function clearCache(): int
	{
		$deleted = 0;

		// Удаляем файлы кэша
		$cache_files = glob($this->cache_dir . '*.cache');
		foreach ($cache_files as $file) {
			if (is_file($file) && unlink($file)) {
				$deleted++;
			}
		}

		// Удаляем временные файлы фида
		$temp_files = glob(DIR_SYSTEM . 'storage/download/rozetka_feed_*.xml*');
		foreach ($temp_files as $file) {
			if (is_file($file) && unlink($file)) {
				$deleted++;
			}
		}

		// Удаляем старые файлы фида (кроме последнего)
		$feed_files = glob(DIR_SYSTEM . 'storage/download/rozetka_feed.xml*');
		if (count($feed_files) > 1) {
			// Сортируем по времени модификации
			usort($feed_files, function($a, $b) {
				return filemtime($b) - filemtime($a);
			});

			// Удаляем все кроме первого (самого нового)
			for ($i = 1; $i < count($feed_files); $i++) {
				if (unlink($feed_files[$i])) {
					$deleted++;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Очистка устаревшего кэша
	 *
	 * @return int Количество удаленных файлов
	 */
	public function clearExpired(): int
	{
		$deleted = 0;
		$cache_files = glob($this->cache_dir . '*.cache');

		foreach ($cache_files as $file) {
			if (!is_file($file)) continue;

			$content = file_get_contents($file);
			if ($content === false) continue;

			$data = unserialize($content);
			if (isset($data['expire']) && $data['expire'] < time()) {
				if (unlink($file)) {
					$deleted++;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Получение информации о кэше
	 *
	 * @return array Информация о кэше
	 */
	public function getCacheInfo(): array
	{
		$info = array(
			'cache_dir' => $this->cache_dir,
			'total_size' => 0,
			'expired_files' => 0
		);

		$cache_files = glob($this->cache_dir . '*.cache');
		$info['total_files'] = count($cache_files);

		foreach ($cache_files as $file) {
			if (is_file($file)) {
				$info['total_size'] += filesize($file);

				$content = file_get_contents($file);
				if ($content !== false) {
					$data = unserialize($content);
					if (isset($data['expire']) && $data['expire'] < time()) {
						$info['expired_files']++;
					}
				}
			}
		}

		$info['total_size_formatted'] = $this->formatBytes($info['total_size']);

		return $info;
	}

	/**
	 * Кэширование списка товаров
	 *
	 * @param array $products Список товаров
	 * @param array $settings Настройки фида
	 * @param int $expire Время жизни кэша
	 */
	public function cacheProducts(array $products, array $settings, int $expire = 1800) {
		$key = 'products_' . md5(serialize($settings));
		$this->set($key, $products, $expire);
	}

	/**
	 * Получение списка товаров из кэша
	 *
	 * @param array $settings Настройки фида
	 * @return array|null Список товаров или null
	 */
	public function getCachedProducts(array $settings): ?array
	{
		$key = 'products_' . md5(serialize($settings));
		return $this->get($key);
	}

	/**
	 * Кэширование статистики
	 *
	 * @param array $statistics Статистика
	 * @param int $expire Время жизни кэша
	 */
	public function cacheStatistics(array $statistics, int $expire = 300) {
		$this->set('statistics', $statistics, $expire);
	}

	/**
	 * Получение статистики из кэша
	 *
	 * @return array|null Статистика или null
	 */
	public function getCachedStatistics(): ?array
	{
		return $this->get('statistics');
	}

	/**
	 * Санитизация ключа кэша
	 *
	 * @param string $key Исходный ключ
	 * @return string Безопасный ключ
	 */
	private function sanitizeKey(string $key): string
	{
		// Удаляем небезопасные символы и ограничиваем длину
		$key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
		return substr($key, 0, 200);
	}

	/**
	 * Форматирование размера файла
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