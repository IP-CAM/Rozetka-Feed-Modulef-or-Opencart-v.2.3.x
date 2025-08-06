<?php

namespace Rozetka;

use DOMDocument;
use DOMElement;
use Registry;

/**
 * Класс для обработки опций товаров
 * Отвечает за генерацию вариаций товаров с опциями
 */
class OptionsProcessor {

	private Registry $registry;
	private DataProvider $dataProvider;

	// Максимальное количество комбинаций для одного товара
	private int $max_combinations = 100;

	public function __construct(Registry $registry) {
		$this->registry = $registry;
		$this->dataProvider = new DataProvider($registry);
	}

	/**
	 * Обработка товара с опциями
	 *
	 * @param DOMDocument $xml XML документ
	 * @param array $product Информация о товаре
	 * @param array $settings Настройки фида
	 * @return array Массив созданных предложений
	 */
	public function processProductWithOptions(DOMDocument $xml, array $product, array $settings): array
	{
		// Получаем опции товара
		$options = $this->dataProvider->getProductOptions($product['product_id']);

		if (empty($options)) {
			// Нет опций - создаем базовое предложение
			return array($this->createBaseOffer($xml, $product, $settings));
		}

		// Генерируем комбинации опций
		$combinations = $this->generateOptionCombinations($options);

		if (empty($combinations)) {
			// Не удалось сгенерировать комбинации - создаем базовое предложение
			return array($this->createBaseOffer($xml, $product, $settings));
		}

		$offers = array();

		foreach ($combinations as $combination) {
			$offer = $this->createOptionOffer($xml, $product, $combination, $settings);
			if ($offer) {
				$offers[] = $offer;
			}
		}

		return $offers;
	}

	/**
	 * Генерация комбинаций опций
	 *
	 * @param array $options Опции товара
	 * @return array Массив комбинаций
	 */
	private function generateOptionCombinations(array $options): array
	{
		$option_arrays = array();

		// Подготавливаем массивы значений для каждой опции
		foreach ($options as $option_id => $option) {
			$values = array();
			foreach ($option['values'] as $value) {
				$values[] = array(
					'option_id' => $option_id,
					'option_name' => $option['name'],
					'option_value_id' => $value['option_value_id'],
					'value_name' => $value['name'],
					'price' => $value['price'],
					'price_prefix' => $value['price_prefix'],
					'weight' => $value['weight'] ?? 0,
					'weight_prefix' => $value['weight_prefix'] ?? '+',
					'quantity' => $value['quantity'] ?? 0
				);
			}
			if (!empty($values)) {
				$option_arrays[] = $values;
			}
		}

		// Проверяем количество возможных комбинаций
		$total_combinations = 1;
		foreach ($option_arrays as $array) {
			$total_combinations *= count($array);
		}

		if ($total_combinations > $this->max_combinations) {
			// Слишком много комбинаций - берем только первые значения каждой опции
			return $this->generateLimitedCombinations($option_arrays);
		}

		// Генерируем все возможные комбинации
		if (empty($option_arrays)) {
			return array();
		}

		return $this->cartesianProduct($option_arrays);
	}

	/**
	 * Генерация ограниченного набора комбинаций
	 *
	 * @param array $option_arrays Массивы значений опций
	 * @return array Ограниченный набор комбинаций
	 */
	private function generateLimitedCombinations(array $option_arrays): array
	{
		$combinations = array();

		// Берем первое значение каждой опции
		$combination = array();
		foreach ($option_arrays as $values) {
			if (!empty($values)) {
				$combination[] = $values[0];
			}
		}
		if (!empty($combination)) {
			$combinations[] = $combination;
		}

		// Добавляем несколько дополнительных комбинаций если возможно
		$max_additional = min(10, $this->max_combinations - 1);
		$added = 0;

		foreach ($option_arrays as $i => $values) {
			if ($added >= $max_additional) break;

			foreach ($values as $j => $value) {
				if ($j == 0) continue; // Первое значение уже добавлено
				if ($added >= $max_additional) break;

				$combination = array();
				foreach ($option_arrays as $k => $vals) {
					if ($k == $i) {
						$combination[] = $value;
					} else {
						$combination[] = $vals[0];
					}
				}
				$combinations[] = $combination;
				$added++;
			}
		}

		return $combinations;
	}

	/**
	 * Декартово произведение массивов
	 *
	 * @param array $arrays Массивы для произведения
	 * @return array Результат декартового произведения
	 */
	private function cartesianProduct(array $arrays): array
	{
		$result = array(array());

		foreach ($arrays as $array) {
			$temp = array();
			foreach ($result as $result_item) {
				foreach ($array as $array_item) {
					$temp[] = array_merge($result_item, array($array_item));
				}
			}
			$result = $temp;
		}

		return $result;
	}

	/**
	 * Создание предложения с опциями
	 *
	 * @param DOMDocument $xml         XML документ
	 * @param array       $product     Информация о товаре
	 * @param array       $combination Комбинация опций
	 * @param array       $settings    Настройки фида
	 *
	 * @return DOMElement|false Элемент предложения или false
	 * @throws \DOMException
	 */
	private function createOptionOffer(DOMDocument $xml, array $product, array $combination, array $settings): ?DOMElement
	{
		// Проверка наличия товара
		if ($product['quantity'] <= 0 && !$settings['stock_status']) {
			return false;
		}

		// Создаем уникальный ID и модификаторы
		$option_ids = array();
		$name_suffix = array();
		$price_modifier = 0;
		$weight_modifier = 0;

		foreach ($combination as $option) {
			$option_ids[] = $option['option_value_id'];
			$name_suffix[] = $option['option_name'] . ': ' . $option['value_name'];

			// Модификатор цены
			if ($option['price_prefix'] == '+') {
				$price_modifier += (float)$option['price'];
			} elseif ($option['price_prefix'] == '-') {
				$price_modifier -= (float)$option['price'];
			}

			// Модификатор веса
			if (isset($option['weight']) && isset($option['weight_prefix'])) {
				if ($option['weight_prefix'] == '+') {
					$weight_modifier += (float)$option['weight'];
				} elseif ($option['weight_prefix'] == '-') {
					$weight_modifier -= (float)$option['weight'];
				}
			}
		}

		$offer_id = $product['product_id'] . '_' . implode('_', $option_ids);

		$offer = $xml->createElement('offer');
		$offer->setAttribute('id', $offer_id);
		$offer->setAttribute('group_id', $product['product_id']);
		$offer->setAttribute('available', $product['quantity'] > 0 ? 'true' : 'false');

		// Модифицированное название
		$name = $product['name'] . ' (' . implode(', ', $name_suffix) . ')';
		$offer->appendChild($xml->createElement('name', htmlspecialchars($name)));

		// Модифицированная цена
		$final_price = $product['price'] + $price_modifier;
		$offer->appendChild($xml->createElement('price', number_format($final_price, 2, '.', '')));
		$offer->appendChild($xml->createElement('currencyId', $settings['currency']));

		// Категория
		if ($product['category_id']) {
			$offer->appendChild($xml->createElement('categoryId', $product['category_id']));
		}

		// Описание товара (базовое)
		if (!empty($product['description'])) {
			$description = $this->prepareDescription($product['description'], $settings);
			if ($description) {
				$offer->appendChild($xml->createElement('description', htmlspecialchars($description)));
			}
		}

		// Производитель
		if (!empty($product['manufacturer'])) {
			$offer->appendChild($xml->createElement('vendor', htmlspecialchars($product['manufacturer'])));
		}

		// Модель
		if (!empty($product['model'])) {
			$offer->appendChild($xml->createElement('model', htmlspecialchars($product['model'])));
		}

		// Артикул
		if (!empty($product['sku'])) {
			$offer->appendChild($xml->createElement('vendorCode', htmlspecialchars($product['sku'])));
		}

		// Штрихкоды
		$this->addBarcodes($xml, $offer, $product);

		// Изображения товара (базовые)
		$this->addProductImages($xml, $offer, $product, $settings);

		// Атрибуты товара (базовые)
		if ($settings['include_attributes']) {
			$this->addProductAttributes($xml, $offer, $product['product_id']);
		}

		// Параметры опций как отдельные param элементы
		foreach ($combination as $option) {
			$param = $xml->createElement('param', htmlspecialchars($option['value_name']));
			$param->setAttribute('name', htmlspecialchars($option['option_name']));
			$offer->appendChild($param);
		}

		return $offer;
	}

	/**
	 * Создание базового предложения (дублирует метод из XmlBuilder для независимости)
	 */
	private function createBaseOffer(DOMDocument $xml, array $product, array $settings): ?DOMElement
	{
		// Проверка доступности товара
		if ($product['quantity'] <= 0 && !$settings['stock_status']) {
			return null;
		}

		$offer = $xml->createElement('offer');
		$offer->setAttribute('id', $product['product_id']);
		$offer->setAttribute('available', $product['quantity'] > 0 ? 'true' : 'false');

		// Основные элементы
		$offer->appendChild($xml->createElement('name', htmlspecialchars($product['name'])));
		$offer->appendChild($xml->createElement('price', number_format($product['price'], 2, '.', '')));
		$offer->appendChild($xml->createElement('currencyId', $settings['currency']));

		// Категория
		if ($product['category_id']) {
			$offer->appendChild($xml->createElement('categoryId', $product['category_id']));
		}

		// Описание
		if (!empty($product['description'])) {
			$description = $this->prepareDescription($product['description'], $settings);
			if ($description) {
				$offer->appendChild($xml->createElement('description', htmlspecialchars($description)));
			}
		}

		// Производитель
		if (!empty($product['manufacturer'])) {
			$offer->appendChild($xml->createElement('vendor', htmlspecialchars($product['manufacturer'])));
		}

		// Модель
		if (!empty($product['model'])) {
			$offer->appendChild($xml->createElement('model', htmlspecialchars($product['model'])));
		}

		// Артикул
		if (!empty($product['sku'])) {
			$offer->appendChild($xml->createElement('vendorCode', htmlspecialchars($product['sku'])));
		}

		// Штрихкоды
		$this->addBarcodes($xml, $offer, $product);

		// Изображения
		$this->addProductImages($xml, $offer, $product, $settings);

		// Атрибуты
		if ($settings['include_attributes']) {
			$this->addProductAttributes($xml, $offer, $product['product_id']);
		}

		return $offer;
	}

	/**
	 * Вспомогательные методы (дублируют методы из XmlBuilder для независимости)
	 */
	private function prepareDescription(string $description, array $settings): string
	{
		if (empty($description)) {
			return '';
		}

		if ($settings['description_strip_tags']) {
			$description = strip_tags($description);
		}

		$description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');

		if ($settings['description_length'] > 0) {
			$description = mb_substr($description, 0, $settings['description_length'], 'UTF-8');
		}

		return trim($description);
	}

	private function addBarcodes(DOMDocument $xml, DOMElement $offer, array $product) {
		$barcodes = array();

		if (!empty($product['upc'])) $barcodes[] = $product['upc'];
		if (!empty($product['ean'])) $barcodes[] = $product['ean'];
		if (!empty($product['jan'])) $barcodes[] = $product['jan'];
		if (!empty($product['isbn'])) $barcodes[] = $product['isbn'];
		if (!empty($product['mpn'])) $barcodes[] = $product['mpn'];

		foreach ($barcodes as $barcode) {
			$offer->appendChild($xml->createElement('barcode', htmlspecialchars($barcode)));
		}
	}

	private function addProductImages(DOMDocument $xml, DOMElement $offer, array $product, array $settings) {
		// Основное изображение
		if ($product['image']) {
			$image_url = $this->getImageUrl($product['image'], $settings);
			if ($image_url) {
				$offer->appendChild($xml->createElement('picture', htmlspecialchars($image_url)));
			}
		}

		// Дополнительные изображения
		$additional_images = $this->dataProvider->getProductImages($product['product_id']);
		foreach ($additional_images as $image) {
			$image_url = $this->getImageUrl($image['image'], $settings);
			if ($image_url) {
				$offer->appendChild($xml->createElement('picture', htmlspecialchars($image_url)));
			}
		}
	}

	private function getImageUrl(string $image, array $settings): string
	{
		if (!$image) return '';

		if (!is_file(DIR_IMAGE . $image)) {
			return '';
		}

		$width = $settings['image_width'] ?: 800;
		$height = $settings['image_height'] ?: 800;

		// Загружаем модель для ресайза изображений
		$this->registry->get('load')->model('tool/image');
		$model_tool_image = $this->registry->get('model_tool_image');

		return $model_tool_image->resize($image, $width, $height);
	}

	private function addProductAttributes(DOMDocument $xml, DOMElement $offer, int $product_id) {
		$attributes = $this->dataProvider->getProductAttributes($product_id);

		foreach ($attributes as $attribute) {
			$param = $xml->createElement('param', htmlspecialchars(trim($attribute['text'])));
			$param->setAttribute('name', htmlspecialchars(trim($attribute['name'])));
			$offer->appendChild($param);
		}
	}

	/**
	 * Установка максимального количества комбинаций
	 */
	public function setMaxCombinations(int $max) {
		$this->max_combinations = $max;
	}
}