<?php
/**
 * Сервис для административной части модуля Rozetka.
 *
 * Данный класс инкапсулирует бизнес‑логику из контроллера,
 * чтобы упростить контроллер и сделать код более поддерживаемым.
 * Все методы возвращают массив с результатом, который потом
 * преобразуется в JSON в контроллере. Исключения перехватываются
 * и превращаются в элементы массива 'error'.
 */

use Rozetka\Logger;

class RozetkaAdminService {
	/**
	 * @var Registry
	 */
	private $registry;

	/**
	 * @var RozetkaFeedGenerator
	 */
	private $feed_generator;

	/**
	 * Конструктор. Получает общий реестр и инициализирует
	 * генератор фида. Генератор используется для тестовой
	 * генерации, предпросмотра, статистики и очистки кеша.
	 *
	 * @param Registry $registry
	 */
	public function __construct($registry)
	{
		$this->registry = $registry;
		require_once(DIR_SYSTEM . 'library/Rozetka/Logger.php');
		// генератор фида уже используется в контроллере, но нам нужен
		// собственный экземпляр в сервисе для вызова методов
		$this->feed_generator = new RozetkaFeedGenerator($registry);
	}

	/**
	 * Позволяет обращаться к компонентам реестра как к свойствам.
	 * Например: $this->load, $this->log, $this->model_setting_setting и т.д.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->registry->get($name);
	}

	/**
	 * Сохраняет настройки конкретной вкладки. Метод принимает
	 * название вкладки и массив настроек без префикса feed_rozetka_.
	 * Валидация выполняется здесь же, результат либо содержит
	 * массив ошибок, либо успешно сохраняет параметры.
	 *
	 * @param string $tab
	 * @param array  $settings
	 * @return array
	 */
	public function saveTabSettings(string $tab, array $settings): array
	{
		$result = array();
		// Выполняем валидацию настроек конкретной вкладки
		$validation_errors = $this->validateTabSettings($tab, $settings);
		if (!empty($validation_errors)) {
			$result['errors'] = $validation_errors;
			return $result;
		}

		try {
			if ($tab === 'mapping') {
				// Для вкладки маппинга сохраняем связи категорий
				$this->load->model('extension/feed/rozetka');
				$mappings = isset($settings['category_mappings']) && is_array($settings['category_mappings'])
					? $settings['category_mappings'] : array();
				$this->model_extension_feed_rozetka->saveCategoryMappings($mappings);
				$result['success'] = true;
				$result['message'] = 'Связи категорий успешно сохранены';
				$this->log->write('Rozetka Feed: Связи категорий сохранены, количество: ' . count($mappings));
			} else {
				// Для вкладок настроек и фильтров сохраняем конфигурацию
				$this->load->model('setting/setting');
				$current_settings = $this->model_setting_setting->getSetting('feed_rozetka');
				foreach ($settings as $key => $value) {
					$setting_key = 'feed_rozetka_' . $key;
					// всегда сохраняем значение, даже пустой массив
					$current_settings[$setting_key] = $value;
				}
				// Гарантируем наличие массивов исключений для фильтров
				if ($tab === 'filters') {
					if (!isset($settings['exclude_categories'])) {
						$current_settings['feed_rozetka_exclude_categories'] = array();
					}
					if (!isset($settings['exclude_manufacturers'])) {
						$current_settings['feed_rozetka_exclude_manufacturers'] = array();
					}
				}
				$this->model_setting_setting->editSetting('feed_rozetka', $current_settings);
				$result['success'] = true;
				if ($tab === 'settings') {
					$result['message'] = 'Основные настройки успешно сохранены';
				} elseif ($tab === 'filters') {
					$result['message'] = 'Фильтры товаров успешно сохранены';
				}
			}
		} catch (Exception $e) {
			$result['error'] = 'Ошибка при сохранении: ' . $e->getMessage();
			$this->log->write('Rozetka Feed Save Error: ' . $e->getMessage());
		}
		return $result;
	}

	/**
	 * Выполняет валидацию настроек вкладки. Возвращает массив
	 * ошибок, где ключ — название поля, а значение — текст ошибки.
	 * Возвращает пустой массив, если ошибок нет.
	 *
	 * @param string $tab
	 * @param array  $settings
	 * @return array
	 */
	public function validateTabSettings(string $tab, array $settings): array
	{
		$errors = array();
		if ($tab === 'settings') {
			// Валидация основных настроек
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
			if (isset($settings['description_length'])) {
				$length = (int)$settings['description_length'];
				if ($length < 100 || $length > 10000) {
					$errors['description_length'] = 'Длина описания должна быть от 100 до 10000 символов';
				}
			}
		} elseif ($tab === 'filters') {
			// Валидация фильтров
			if (isset($settings['min_price']) && isset($settings['max_price'])) {
				$min_price = (float)$settings['min_price'];
				$max_price = (float)$settings['max_price'];
				if ($min_price > 0 && $max_price > 0 && $min_price >= $max_price) {
					$errors['price_range'] = 'Минимальная цена не может быть больше максимальной';
				}
			}
		} elseif ($tab === 'mapping') {
			// Валидация маппинга категорий
			if (!isset($settings['category_mappings']) || !is_array($settings['category_mappings'])) {
				$errors['category_mappings'] = 'Некорректные данные маппинга категорий';
			}
		}
		return $errors;
	}

	/**
	 * Удаляет одну связь категории по идентификатору категории магазина.
	 *
	 * @param int $shop_category_id
	 * @return array
	 */
	public function removeCategoryMapping($shop_category_id): array
	{
		$result = array();
		try {
			$this->load->model('extension/feed/rozetka');
			$mappings = $this->model_extension_feed_rozetka->getCategoryMappings();
			// отфильтровываем ненужную связь
			$filtered = array_filter($mappings, function ($mapping) use ($shop_category_id) {
				return $mapping['shop_category_id'] != $shop_category_id;
			});
			// Сохраняем массив без пересборки индексов, как в исходном контроллере
			$this->model_extension_feed_rozetka->saveCategoryMappings($filtered);
			$result['success'] = true;
			$result['message'] = 'Связь категории удалена';
			$this->log->write('Rozetka Feed: Удалена связь категории ID: ' . $shop_category_id);
		} catch (Exception $e) {
			$result['error'] = 'Ошибка при удалении связи: ' . $e->getMessage();
			$this->log->write('Rozetka Feed Remove Mapping Error: ' . $e->getMessage());
		}
		return $result;
	}

	/**
	 * Удаляет все связи категорий.
	 *
	 * @return array
	 */
	public function clearAllMappings(): array
	{
		$result = array();
		try {
			$this->load->model('extension/feed/rozetka');
			$this->model_extension_feed_rozetka->saveCategoryMappings(array());
			$result['success'] = true;
			$result['message'] = 'Все связи категорий удалены';
			$this->log->write('Rozetka Feed: Все связи категорий удалены администратором');
		} catch (Exception $e) {
			$result['error'] = 'Ошибка при удалении всех связей: ' . $e->getMessage();
			$this->log->write('Rozetka Feed Clear All Mappings Error: ' . $e->getMessage());
		}
		return $result;
	}

	/**
	 * Выполняет тестовую генерацию фида и возвращает результат.
	 * В случае успеха добавляет информацию о настройках PHP.
	 *
	 * @param int $limit
	 * @return array
	 */
	public function testGeneration(int $limit = 10): array
	{
		$result = $this->feed_generator->testGeneration($limit);
		if (is_array($result) && isset($result['status']) && $result['status'] === 'success') {
			$result['php_info'] = array(
				'memory_limit' => ini_get('memory_limit'),
				'max_execution_time' => ini_get('max_execution_time'),
				'php_version' => PHP_VERSION
			);
		}
		return $result;
	}

	/**
	 * Возвращает историю генераций фида из логов.
	 * Форматирует даты и разбирает поле warnings из JSON.
	 *
	 * @param int $limit
	 * @return array
	 */
	public function getGenerationHistory(int $limit = 20): array
	{
		$result = array();
		try {
			$logger = new Logger($this->registry);
			$history = $logger->getLogHistory($limit);
			$formatted = array();
			foreach ($history as $log) {
				$formatted[] = array(
					'id' => $log['log_id'],
					'date' => date('d.m.Y H:i:s', strtotime($log['date_generated'])),
					'status' => $log['status'],
					'products_count' => $log['products_count'],
					'offers_count' => $log['offers_count'],
					'generation_time' => $log['generation_time'],
					'file_size' => $log['file_size'],
					'memory_used' => $log['memory_used'],
					'error_message' => $log['error_message'],
					'warnings' => $log['warnings'] ? json_decode($log['warnings'], true) : array()
				);
			}
			$result['status'] = 'success';
			$result['history'] = $formatted;
		} catch (Exception $e) {
			$result['status'] = 'error';
			$result['error'] = $e->getMessage();
		}
		return $result;
	}

	/**
	 * Получает категории магазина с поиском и отдаёт уровень и путь каждой категории.
	 *
	 * @param string $search
	 * @param int    $limit
	 * @return array
	 */
	public function getShopCategories(string $search, int $limit = 10): array
	{
		$this->load->model('catalog/category');
		if ($search === '') {
			return array(
				'status' => 'success',
				'categories' => array(),
				'total' => 0
			);
		}
		$filter_data = array(
			'filter_name' => $search,
			'start' => 0,
			'limit' => $limit
		);
		$categories = $this->model_catalog_category->getCategories($filter_data);
		$total = $this->model_catalog_category->getTotalCategories($filter_data);
		// Дополняем категории уровнем и путем
		$enriched = array();
		foreach ($categories as $category) {
			$category['level'] = $this->calculateCategoryLevel($category['category_id']);
			$category['path'] = $this->buildCategoryPath($category['category_id']);
			$enriched[] = $category;
		}
		return array(
			'status' => 'success',
			'categories' => $enriched,
			'total' => $total
		);
	}

	/**
	 * Получает категории Rozetka из базы с поиском и лимитом.
	 *
	 * @param string $search
	 * @param int    $limit
	 * @return array
	 */
	public function getRozetkaCategories(string $search, int $limit = 10): array
	{
		$this->load->model('extension/feed/rozetka');
		if ($search === '') {
			return array(
				'status' => 'success',
				'categories' => array(),
				'total' => 0
			);
		}
		$categories = $this->model_extension_feed_rozetka->getRozetkaCategories(array(
			'search' => $search,
			'start' => 0,
			'limit' => $limit
		));
		$total = $this->model_extension_feed_rozetka->getTotalRozetkaCategories($search);
		return array(
			'status' => 'success',
			'categories' => $categories,
			'total' => $total
		);
	}

	/**
	 * Рекурсивно вычисляет уровень категории магазина.
	 *
	 * @param int $category_id
	 * @param int $level
	 * @return int
	 */
	public function calculateCategoryLevel($category_id, $level = 1)
	{
		$this->load->model('catalog/category');
		$category = $this->model_catalog_category->getCategory($category_id);
		if (!$category || !$category['parent_id']) {
			return $level;
		}
		return $this->calculateCategoryLevel($category['parent_id'], $level + 1);
	}

	/**
	 * Строит путь категории (названия разделяются через " > ").
	 *
	 * @param int $category_id
	 * @return string
	 */
	public function buildCategoryPath($category_id): string
	{
		$this->load->model('catalog/category');
		$path_parts = array();
		$current_id = $category_id;
		while ($current_id) {
			$category = $this->model_catalog_category->getCategory($current_id);
			if (!$category) {
				break;
			}
			array_unshift($path_parts, $category['name']);
			$current_id = $category['parent_id'];
		}
		return implode(' > ', $path_parts);
	}

	/**
	 * Автоматически подбирает соответствия категорий магазина и Rozetka
	 * по схожести названий. Использует алгоритм Левенштейна.
	 *
	 * @return array
	 */
	public function autoMapCategories(): array
	{
		$result = array();
		try {
			$this->load->model('extension/feed/rozetka');
			$this->load->model('catalog/category');
			// Получаем список категорий магазина и Rozetka с безопасными лимитами
			$shopCategories = $this->model_catalog_category->getCategories(array('limit' => 1000));
			$rozetkaCategories = $this->model_extension_feed_rozetka->getRozetkaCategories(array('limit' => 10000));
			$mappings = array();
			$threshold = 0.7;
			foreach ($shopCategories as $shopCategory) {
				$bestMatch = null;
				$bestScore = 0;
				foreach ($rozetkaCategories as $rozetkaCategory) {
					$score = $this->calculateSimilarity($shopCategory['name'], $rozetkaCategory['name']);
					if ($score > $bestScore && $score >= $threshold) {
						$bestScore = $score;
						$bestMatch = $rozetkaCategory;
					}
				}
				if ($bestMatch) {
					$mappings[] = array(
						'shop_category_id' => $shopCategory['category_id'],
						'shop_category_name' => $shopCategory['name'],
						'rozetka_category_id' => $bestMatch['category_id'],
						'rozetka_category_name' => $bestMatch['name'],
						'rozetka_category_full_name' => $bestMatch['full_name'],
						'confidence' => round($bestScore * 100)
					);
				}
			}
			$result['status'] = 'success';
			$result['mappings'] = $mappings;
			$result['total_found'] = count($mappings);
		} catch (Exception $e) {
			$result['status'] = 'error';
			$result['error'] = $e->getMessage();
		}
		return $result;
	}

	/**
	 * Вычисляет коэффициент схожести двух строк на основе расстояния Левенштейна.
	 * Возвращает число от 0 до 1, где 1 — полное совпадение.
	 *
	 * @param string $str1
	 * @param string $str2
	 * @return float
	 */
	private function calculateSimilarity($str1, $str2): float
	{
		$s1 = mb_strtolower(trim($str1), 'UTF-8');
		$s2 = mb_strtolower(trim($str2), 'UTF-8');
		if ($s1 === $s2) {
			return 1.0;
		}
		$maxLen = max(mb_strlen($s1, 'UTF-8'), mb_strlen($s2, 'UTF-8'));
		if ($maxLen === 0) {
			return 0.0;
		}
		$distance = levenshtein($s1, $s2);
		return 1 - ($distance / $maxLen);
	}

	/**
	 * Импортирует категории Rozetka из загруженного файла.
	 * Проводит проверки размера, расширения и формата.
	 *
	 * @param array $file
	 * @return array
	 */
	public function importCategories(array $file): array
	{
		$result = array();
		try {
			if (!isset($file['tmp_name']) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
				throw new Exception('Файл не был загружен или произошла ошибка при загрузке');
			}
			if ($file['size'] > 10 * 1024 * 1024) {
				throw new Exception('Размер файла превышает 10MB');
			}
			$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			if ($fileExtension !== 'json') {
				throw new Exception('Поддерживаются только JSON файлы');
			}
			$content = file_get_contents($file['tmp_name']);
			if ($content === false) {
				throw new Exception('Не удалось прочитать содержимое файла');
			}
			$categories = json_decode($content, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('Некорректный JSON формат: ' . json_last_error_msg());
			}
			// Валидируем структуру данных
			$validation = $this->validateCategoriesData($categories);
			if (!$validation['valid']) {
				throw new Exception($validation['error']);
			}
			// Импортируем данные
			$this->load->model('extension/feed/rozetka');
			$importResult = $this->model_extension_feed_rozetka->importCategoriesFromArray($categories);
			$result['success'] = true;
			$result['total_categories'] = count($categories);
			$result['imported_categories'] = $importResult['imported'];
			$result['updated_categories'] = $importResult['updated'];
			$result['message'] = 'Успешно импортировано категорий: ' . $importResult['imported'] . ', обновлено: ' . $importResult['updated'];
			$this->log->write('Rozetka Categories: Импортировано ' . count($categories) . ' категорий из JSON файла');
		} catch (Exception $e) {
			$result['success'] = false;
			$result['message'] = $e->getMessage();
			$this->log->write('Rozetka Categories Import Error: ' . $e->getMessage());
		}
		return $result;
	}

	/**
	 * Проверяет структуру массива категорий из JSON.
	 *
	 * @param array $categories
	 * @return array
	 */
	public function validateCategoriesData(array $categories): array
	{
		if (empty($categories)) {
			return array('valid' => false, 'error' => 'Файл не содержит категорий');
		}
		if (!is_array($categories)) {
			return array('valid' => false, 'error' => 'Неверная структура данных - ожидается массив');
		}
		$requiredFields = array('categoryId', 'name', 'fullName', 'url', 'level');
		foreach ($categories as $index => $category) {
			if (!is_array($category)) {
				return array('valid' => false, 'error' => 'Категория #' . $index . ' имеет неверный формат');
			}
			foreach ($requiredFields as $field) {
				if (!isset($category[$field]) || empty($category[$field])) {
					return array('valid' => false, 'error' => 'Категория #' . $index . ': отсутствует поле \'' . $field . '\'');
				}
			}
			if (!is_string($category['categoryId']) && !is_numeric($category['categoryId'])) {
				return array('valid' => false, 'error' => 'Категория #' . $index . ': categoryId должен быть строкой или числом');
			}
			if (!is_int($category['level']) || $category['level'] < 1 || $category['level'] > 10) {
				return array('valid' => false, 'error' => 'Категория #' . $index . ': level должен быть числом от 1 до 10');
			}
			if (!filter_var($category['url'], FILTER_VALIDATE_URL)) {
				return array('valid' => false, 'error' => 'Категория #' . $index . ': некорректный URL');
			}
		}
		return array('valid' => true);
	}

	/**
	 * Возвращает текущие сопоставления категорий из базы.
	 *
	 * @return array
	 */
	public function getCategoryMappings(): array
	{
		$this->load->model('extension/feed/rozetka');
		$mappings = $this->model_extension_feed_rozetka->getCategoryMappings();
		return array(
			'status' => 'success',
			'mappings' => $mappings
		);
	}

	/**
	 * Сохраняет массив сопоставлений категорий.
	 *
	 * @param array $mappings
	 * @return array
	 */
	public function saveCategoryMappings(array $mappings): array
	{
		$result = array();
		try {
			$this->load->model('extension/feed/rozetka');
			$this->model_extension_feed_rozetka->saveCategoryMappings($mappings);
			$result['status'] = 'success';
		} catch (Exception $e) {
			$result['error'] = 'Неверный формат данных';
		}
		return $result;
	}

	/**
	 * Удаляет все категории Rozetka из базы.
	 *
	 * @return array
	 */
	public function clearCategories(): array
	{
		$result = array();
		try {
			$this->load->model('extension/feed/rozetka');
			$this->model_extension_feed_rozetka->clearRozetkaCategories();
			$result['success'] = true;
			$result['message'] = 'Все категории успешно удалены';
			$this->log->write('Rozetka Categories: Все категории удалены администратором');
		} catch (Exception $e) {
			$result['error'] = 'Ошибка при удалении категорий: ' . $e->getMessage();
		}
		return $result;
	}

	/**
	 * Генерирует предварительный просмотр фида на указанное количество товаров.
	 *
	 * @param int $limit
	 * @return array
	 */
	public function generatePreview(int $limit = 5): array
	{
		return $this->feed_generator->generatePreview($limit);
	}

	/**
	 * Возвращает статистику фида через генератор.
	 *
	 * @return array
	 */
	public function getStatistics(): array
	{
		$stats = $this->feed_generator->getStatistics();
		$stats['status'] = 'success';
		return $stats;
	}

	/**
	 * Очищает кэш фида.
	 *
	 * @return array
	 */
	public function clearCache(): array
	{
		$result = array();
		$deleted_files = $this->feed_generator->clearCache();
		// В оригинальной реализации ключ "success" содержит текст сообщения,
		// а "status" указывает на успешность операции. Сохраняем такую структуру.
		if ($deleted_files >= 0) {
			$result['success'] = 'Кэш успешно очищен. Удалено файлов: ' . $deleted_files;
			$result['status'] = 'success';
			$result['deleted_files'] = $deleted_files;
			$this->log->write('Rozetka Feed: Кеш очищен администратором');
		} else {
			$result['error'] = 'Ошибка при очистке кэша';
		}
		return $result;
	}
}
