<?php

namespace Rozetka;

use DOMDocument;
use DOMXPath;
use Exception;

/**
 * RozetkaCategoriesParser - Парсер категорий с сайта Rozetka
 *
 * Получает HTML по ссылке и извлекает все категории с правильной иерархией
 *
 * @author Maguto
 * @version 1.0
 */
class RozetkaCategoriesParser
{
	private array $categories = [];
	private array $categoryMap = [];
	private int $idCounter = 0;

	/**
	 * Получает HTML содержимое по URL
	 *
	 * @param string $url URL для загрузки
	 *
	 * @return string|false HTML содержимое или false при ошибке
	 * @throws Exception
	 */
	private function fetchHtml(string $url): ?string
	{
		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'header' => [
					'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
					'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
					'Accept-Language: uk-UA,uk;q=0.9,en;q=0.8',
					'Accept-Encoding: gzip, deflate, br',
					'DNT: 1',
					'Connection: keep-alive',
					'Upgrade-Insecure-Requests: 1'
				],
				'timeout' => 30
			]
		]);

		$html = @file_get_contents($url, false, $context);

		if ($html === false) {
			// Попробуем через cURL, если file_get_contents не работает
			if (function_exists('curl_init')) {
				return $this->fetchWithCurl($url);
			}
			throw new Exception("Не удалось загрузить страницу: $url");
		}

		return $html;
	}

	/**
	 * Альтернативный метод загрузки через cURL
	 *
	 * @param string $url URL для загрузки
	 *
	 * @return string|false HTML содержимое или false при ошибке
	 * @throws Exception
	 */
	private function fetchWithCurl(string $url): ?string
	{
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
			CURLOPT_HTTPHEADER => [
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Accept-Language: uk-UA,uk;q=0.9,en;q=0.8',
				'Accept-Encoding: gzip, deflate, br',
				'DNT: 1',
				'Connection: keep-alive',
				'Upgrade-Insecure-Requests: 1'
			],
			CURLOPT_ENCODING => '', // Автоматическое декодирование gzip
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		]);

		$html = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($html === false || $httpCode >= 400) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception("Ошибка cURL: $error (HTTP $httpCode)");
		}

		curl_close($ch);
		return $html;
	}

	/**
	 * Извлекает ID категории из URL
	 *
	 * @param string $url URL категории
	 * @return string|null ID категории без префикса 'c'
	 */
	private function extractCategoryId(string $url): ?string
	{
		if (preg_match('/\/c(\d+)\//', $url, $matches)) {
			return $matches[1];
		}
		return null;
	}

	/**
	 * Очищает текст от лишних символов и пробелов
	 *
	 * @param string $text Исходный текст
	 * @return string Очищенный текст
	 */
	private function cleanText(string $text): string
	{
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = strip_tags($text);
		$text = preg_replace('/\s+/', ' ', $text);
		return trim($text);
	}

	/**
	 * Добавляет категорию в список с проверкой дубликатов
	 *
	 * @param array $categoryData Данные категории
	 * @return bool true если категория добавлена, false если дубликат
	 */
	private function addCategory(array $categoryData): bool
	{
		$categoryId = $categoryData['categoryId'] ?? null;

		if (!$categoryId || isset($this->categoryMap[$categoryId])) {
			return false; // Дубликат или нет ID
		}

		$categoryData['id'] = $this->idCounter++;
		$this->categories[] = $categoryData;
		$this->categoryMap[$categoryId] = count($this->categories) - 1;

		return true;
	}

	/**
	 * Находит родительскую категорию по названию или контексту
	 *
	 * @param string $childName Название дочерней категории
	 * @param string $context Контекст (например, название секции)
	 * @return int|null ID родительской категории
	 */
	private function findParentId(string $childName, string $context = ''): ?int
	{
		// Простая логика поиска родителя по контексту
		// Можно расширить по необходимости

		foreach ($this->categories as $category) {
			if (!empty($context) &&
				strpos($category['name'], $context) !== false &&
				$category['level'] < 3) {
				return $category['id'];
			}
		}

		return null;
	}

	/**
	 * Парсит HTML и извлекает все категории
	 *
	 * @param string $html HTML содержимое страницы
	 * @return void
	 */
	private function parseHtml(string $html)
	{
		$dom = new DOMDocument();
		@$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
		$xpath = new DOMXPath($dom);

		// 1. Основные категории из главного меню
		$this->parseMainCategories($xpath);

		// 2. Популярные категории
		$this->parsePopularCategories($xpath);

		// 3. Подкategории из развернутого меню
		$this->parseSubCategories($xpath);

		// 4. Все остальные ссылки с категориями
		$this->parseAdditionalCategories($xpath);

		// 5. Устанавливаем правильные parentId
		$this->establishParentRelationships();
	}

	/**
	 * Парсит основные категории меню
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	private function parseMainCategories(DOMXPath $xpath)
	{
		$mainLinks = $xpath->query('//a[@data-testid="fat_menu_category_link"]');

		foreach ($mainLinks as $link) {
			$url = $link->getAttribute('href');
			$categoryId = $this->extractCategoryId($url);
			$name = $this->cleanText($link->textContent);

			if ($categoryId && $name) {
				$this->addCategory([
					'categoryId' => $categoryId,
					'name' => $name,
					'fullName' => $name,
					'url' => $url,
					'level' => 1,
					'parentId' => null,
					'type' => 'main'
				]);
			}
		}
	}

	/**
	 * Парсит популярные категории
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	private function parsePopularCategories(DOMXPath $xpath)
	{
		$popularLinks = $xpath->query('//a[@data-testid="fat_menu_popular_categories_link"]');

		foreach ($popularLinks as $link) {
			$url = $link->getAttribute('href');
			$categoryId = $this->extractCategoryId($url);
			$name = $this->cleanText($link->textContent);

			if ($categoryId && $name) {
				$this->addCategory([
					'categoryId' => $categoryId,
					'name' => $name,
					'fullName' => "Популярні категорії > $name",
					'url' => $url,
					'level' => 2,
					'parentId' => null,
					'type' => 'popular'
				]);
			}
		}
	}

	/**
	 * Парсит подкategории из развернутого меню
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	private function parseSubCategories(DOMXPath $xpath)
	{
		// Заголовки подкategorий
		$subTitleLinks = $xpath->query('//a[@data-testid="fat_menu_sub_title"]');

		foreach ($subTitleLinks as $titleLink) {
			$url = $titleLink->getAttribute('href');
			$categoryId = $this->extractCategoryId($url);
			$name = $this->cleanText($titleLink->textContent);

			if ($categoryId && $name) {
				$parentCategoryId = $this->addCategory([
					'categoryId' => $categoryId,
					'name' => $name,
					'fullName' => $name,
					'url' => $url,
					'level' => 2,
					'parentId' => null,
					'type' => 'subcategory-title'
				]);

				// Ищем дочерние элементы этого заголовка
				$parent = $titleLink->parentNode;
				while ($parent && $parent->nodeName !== 'li') {
					$parent = $parent->parentNode;
				}

				if ($parent) {
					$subLinks = $xpath->query('.//a[not(@data-testid)]', $parent);

					foreach ($subLinks as $subLink) {
						$subUrl = $subLink->getAttribute('href');
						$subCategoryId = $this->extractCategoryId($subUrl);
						$subName = $this->cleanText($subLink->textContent);

						if ($subCategoryId && $subName && $subCategoryId !== $categoryId) {
							$this->addCategory([
								'categoryId' => $subCategoryId,
								'name' => $subName,
								'fullName' => "$name > $subName",
								'url' => $subUrl,
								'level' => 3,
								'parentId' => $this->categoryMap[$categoryId] ?? null,
								'type' => 'subcategory'
							]);
						}
					}
				}
			}
		}
	}

	/**
	 * Парсит дополнительные категории (все остальные ссылки)
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	private function parseAdditionalCategories(DOMXPath $xpath)
	{
		$allLinks = $xpath->query('//a[contains(@href, "rozetka.com.ua") and contains(@href, "/c")]');

		foreach ($allLinks as $link) {
			$url = $link->getAttribute('href');
			$categoryId = $this->extractCategoryId($url);
			$name = $this->cleanText($link->textContent);

			if ($categoryId && $name && !isset($this->categoryMap[$categoryId])) {
				$this->addCategory([
					'categoryId' => $categoryId,
					'name' => $name,
					'fullName' => $name,
					'url' => $url,
					'level' => 1,
					'parentId' => null,
					'type' => 'additional'
				]);
			}
		}
	}

	/**
	 * Устанавливает правильные связи parent-child
	 *
	 * @return void
	 */
	private function establishParentRelationships()
	{
		foreach ($this->categories as $index => &$category) {
			if ($category['parentId'] === null && $category['level'] > 1) {
				// Пытаемся найти родителя по полному имени
				if (strpos($category['fullName'], ' > ') !== false) {
					$parts = explode(' > ', $category['fullName']);
					if (count($parts) > 1) {
						$parentName = $parts[0];

						// Ищем категорию с таким названием
						foreach ($this->categories as $potentialParent) {
							if ($potentialParent['name'] === $parentName &&
								$potentialParent['level'] < $category['level']) {
								$category['parentId'] = $potentialParent['id'];
								break;
							}
						}
					}
				}
			}
		}
		unset($category);
	}

	/**
	 * Парсит категории с указанного URL
	 *
	 * @param string $url URL для парсинга
	 * @return array Массив категорий
	 * @throws Exception При ошибках загрузки или парсинга
	 */
	public function parseFromUrl(string $url): array
	{
		// Очищаем предыдущие результаты
		$this->categories = [];
		$this->categoryMap = [];
		$this->idCounter = 0;

		// Загружаем HTML
		$html = $this->fetchHtml($url);

		if (empty($html)) {
			throw new Exception("Получен пустой HTML от $url");
		}

		// Парсим HTML
		$this->parseHtml($html);

		// Сортируем по categoryId
		usort($this->categories, function($a, $b) {
			return (int)$a['categoryId'] - (int)$b['categoryId'];
		});

		// Пересчитываем ID после сортировки
		foreach ($this->categories as $index => &$category) {
			$category['id'] = $index;
		}
		unset($category);

		return $this->categories;
	}

	/**
	 * Возвращает категории в формате JSON
	 *
	 * @param string $url URL для парсинга
	 * @return string JSON строка с категориями
	 * @throws Exception При ошибках
	 */
	public function getCategoriesAsJson(string $url): string
	{
		$categories = $this->parseFromUrl($url);
		return json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Возвращает статистику по категориям
	 *
	 * @return array Статистика
	 */
	public function getStatistics(): array
	{
		$stats = [
			'total' => count($this->categories),
			'byType' => [],
			'byLevel' => [],
			'withParent' => 0,
			'withoutParent' => 0
		];

		foreach ($this->categories as $category) {
			// По типам
			$type = $category['type'] ?? 'unknown';
			$stats['byType'][$type] = ($stats['byType'][$type] ?? 0) + 1;

			// По уровням
			$level = $category['level'] ?? 0;
			$stats['byLevel'][$level] = ($stats['byLevel'][$level] ?? 0) + 1;

			// С родителями / без родителей
			if ($category['parentId'] !== null) {
				$stats['withParent']++;
			} else {
				$stats['withoutParent']++;
			}
		}

		return $stats;
	}
}