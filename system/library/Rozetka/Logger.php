<?php

namespace Rozetka;

use DB;
use Log;
use Registry;

/**
 * Класс для логирования процесса генерации Rozetka фида
 * Отвечает за запись логов в БД и файлы
 */
class Logger {

	private DB $db;
	private Log $log;
	private int $current_log_id;
	private string $dbPrefix;

	public function __construct(Registry $registry) {
		$this->db = $registry->get('db');
		$this->log = $registry->get('log');
		$this->dbPrefix = DB_PREFIX;
	}

	/**
	 * Логирование начала генерации
	 *
	 * @return int ID созданной записи
	 */
	public function logStart(): int
	{
		$this->db->query("INSERT INTO `{$this->dbPrefix}rozetka_feed_log` SET 
            `status` = 'started',
            `date_generated` = NOW()");

		$this->current_log_id = $this->db->getLastId();
		$this->log->write('Rozetka Feed: Начата генерация фида (ID: ' . $this->current_log_id . ')');

		return $this->current_log_id;
	}

	/**
	 * Логирование успешного завершения
	 *
	 * @param array $data Данные о генерации
	 */
	public function logSuccess(array $data) {
		$warnings_json = !empty($data['warnings']) ? json_encode($data['warnings']) : '';

		$sql = "UPDATE `{$this->dbPrefix}rozetka_feed_log` SET 
            `products_count` = " . (int)$data['products_count'] . ",
            `offers_count` = " . (int)$data['offers_count'] . ",
            `file_size` = '" . $this->db->escape($data['file_size']) . "',
            `generation_time` = '" . (float)$data['generation_time'] . "',
            `memory_used` = '" . $this->db->escape($data['memory_used']) . "',
            `status` = 'success',
            `warnings` = '" . $this->db->escape($warnings_json) . "'
            WHERE `log_id` = $this->current_log_id";

		$this->db->query($sql);

		$message = "Rozetka Feed: Фид успешно сгенерирован. " .
			"Товаров: {$data['products_count']}, " .
			"Предложений: {$data['offers_count']}, " .
			"Время: {$data['generation_time']}с, " .
			"Память: {$data['memory_used']}";

		$this->log->write($message);

		// Очистка старых логов (старше 30 дней)
		$this->cleanOldLogs(30);
	}

	/**
	 * Логирование ошибки
	 *
	 * @param array $data Данные об ошибке
	 */
	public function logError(array $data) {
		$sql = "UPDATE `{$this->dbPrefix}rozetka_feed_log` SET 
            `products_count` = " . (int)$data['products_count'] . ",
            `generation_time` = '" . (float)$data['generation_time'] . "',
            `status` = 'error',
            `error_message` = '" . $this->db->escape($data['error_message']) . "'
            WHERE log_id = $this->current_log_id";

		$this->db->query($sql);

		$message = "Rozetka Feed: Ошибка генерации - " . $data['error_message'];
		$this->log->write($message);
	}

	/**
	 * Получение истории логов
	 *
	 * @param int $limit Количество записей
	 * @return array Массив записей логов
	 */
	public function getLogHistory(int $limit = 20): array
	{
		$query = $this->db->query("SELECT * FROM `{$this->dbPrefix}rozetka_feed_log` 
            ORDER BY `date_generated` DESC 
            LIMIT $limit");

		return $query->rows;
	}

	/**
	 * Получение последнего лога
	 *
	 * @return array|null Последняя запись или null
	 */
	public function getLastLog(): ?array
	{
		$query = $this->db->query("SELECT * FROM `{$this->dbPrefix}rozetka_feed_log` 
            ORDER BY `date_generated` DESC 
            LIMIT 1");

		return $query->num_rows ? $query->row : null;
	}

	/**
	 * Очистка старых логов
	 *
	 * @param int $days Количество дней для хранения
	 * @return int Количество удаленных записей
	 */
	public function cleanOldLogs(int $days = 30): int
	{
		$this->db->query("DELETE FROM `{$this->dbPrefix}rozetka_feed_log` 
            WHERE date_generated < DATE_SUB(NOW(), INTERVAL $days DAY)");

		return $this->db->countAffected();
	}

	/**
	 * Получение статистики логов
	 *
	 * @return array Статистика
	 */
	public function getLogStatistics(): array
	{
		$stats = array();

		// Общее количество генераций
		$query = $this->db->query("SELECT COUNT(*) as `total` FROM `{$this->dbPrefix}rozetka_feed_log` 
            WHERE `status` IN ('success', 'error')");
		$stats['total_generations'] = (int)$query->row['total'];

		// Успешные генерации
		$query = $this->db->query("SELECT COUNT(*) as `success` FROM `{$this->dbPrefix}rozetka_feed_log` 
            WHERE `status` = 'success'");
		$stats['successful_generations'] = (int)$query->row['success'];

		// Процент успешности
		if ($stats['total_generations'] > 0) {
			$stats['success_rate'] = round(($stats['successful_generations'] / $stats['total_generations']) * 100, 1);
		} else {
			$stats['success_rate'] = 100;
		}

		// Среднее время генерации
		$query = $this->db->query("SELECT AVG(`generation_time`) as `avg_time` FROM `{$this->dbPrefix}rozetka_feed_log` 
            WHERE `status` = 'success' AND `date_generated` >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
		$stats['avg_generation_time'] = $query->row['avg_time'] ? round($query->row['avg_time'], 2) : 0;

		return $stats;
	}
}