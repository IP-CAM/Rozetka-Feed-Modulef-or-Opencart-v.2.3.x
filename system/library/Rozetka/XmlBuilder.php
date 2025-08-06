<?php
namespace Rozetka;
use Config;
use DOMDocument;
use DOMElement;
use DOMException;
use Exception;
use Registry;

/**
 * Класс для построения XML документа Rozetka фида
 * Отвечает за создание валидного XML согласно требованиям Rozetka
 */
class XmlBuilder {

	private Registry $registry;
	private Config $config;
	private DataProvider $dataProvider;
	private OptionsProcessor $optionsProcessor;

	public function __construct(Registry $registry) {
		$this->registry = $registry;
		$this->config = $registry->get('config');

		// Подключаем обработчик опций
		require_once(DIR_SYSTEM . 'library/Rozetka/OptionsProcessor.php');
		$this->optionsProcessor = new OptionsProcessor($registry);

		require_once(DIR_SYSTEM . 'library/Rozetka/XmlBuilder.php');
		$this->dataProvider = new DataProvider($registry);
	}

	/**
	 * Построение XML документа
	 *
	 * @param array  $products Массив товаров
	 * @param array  $settings Настройки фида
	 * @param array &$metrics  Ссылка на метрики (для обновления)
	 *
	 * @return string XML контент
	 * @throws DOMException
	 */
	public function buildXML(array $products, array $settings, array &$metrics): string
	{
		// Создаем XML документ
		$xml = new DOMDocument('1.0', 'UTF-8');
		$xml->formatOutput = true;

		// Корневой элемент
		$yml_catalog = $xml->createElement('yml_catalog');
		$yml_catalog->setAttribute('date', date('Y-m-d H:i'));
		$xml->appendChild($yml_catalog);

		// Элемент shop
		$shop = $xml->createElement('shop');
		$yml_catalog->appendChild($shop);

		// Добавляем информацию о магазине
		$this->addShopInfo($xml, $shop, $settings);

		// Добавляем валюты
		$this->addCurrencies($xml, $shop, $settings);

		// Добавляем категории
		$this->addCategories($xml, $shop, $products);

		// Добавляем товарные предложения
		$this->addOffers($xml, $shop, $products, $settings, $metrics);

		return $xml->saveXML();
	}

	/**
	 * Добавление информации о магазине
	 *
	 * @throws DOMException
	 */
	private function addShopInfo(DOMDocument $xml, DomElement $shop, array $settings) {
		$shop->appendChild($xml->createElement('name', htmlspecialchars($settings['shop_name'])));
		$shop->appendChild($xml->createElement('company', htmlspecialchars($settings['company'])));
		$shop->appendChild($xml->createElement('url', $this->config->get('config_url')));
	}

	/**
	 * Добавление валют
	 *
	 * @throws DOMException
	 */
	private function addCurrencies(DOMDocument $xml, DOMElement $shop, array $settings) {
		$currencies = $xml->createElement('currencies');
		$shop->appendChild($currencies);

		$currency = $xml->createElement('currency');
		$currency->setAttribute('id', $settings['currency']);
		$currency->setAttribute('rate', '1');
		$currencies->appendChild($currency);
	}

	/**
	 * Добавление категорий
	 *
	 * @throws DOMException
	 */
	private function addCategories(DOMDocument $xml, DOMElement $shop, array $products) {
		$categories_xml = $xml->createElement('categories');
		$shop->appendChild($categories_xml);

		// Получаем уникальные ID товаров
		$product_ids = array();
		foreach ($products as $product) {
			$product_ids[] = $product['product_id'];
		}

		$categories = $this->dataProvider->getCategoriesForProducts($product_ids);

		foreach ($categories as $category) {
			$category_xml = $xml->createElement('category', htmlspecialchars($category['name']));
			$category_xml->setAttribute('id', $category['category_id']);
			if ($category['parent_id'] > 0) {
				$category_xml->setAttribute('parentId', $category['parent_id']);
			}
			$categories_xml->appendChild($category_xml);
		}
	}

	/**
	 * Добавление товарных предложений
	 *
	 * @throws DOMException
	 */
	private function addOffers(DOMDocument $xml, DOMElement $shop, array $products, array $settings, array &$metrics) {
		$offers_xml = $xml->createElement('offers');
		$shop->appendChild($offers_xml);

		foreach ($products as $product) {
			$metrics['products_processed']++;

			try {
				// Получаем полную информацию о товаре
				$product_info = $this->dataProvider->getProductInfo($product['product_id']);
				if (!$product_info) continue;

				// Обрабатываем товар с учетом опций
				if ($settings['include_options']) {
					$offers = $this->optionsProcessor->processProductWithOptions($xml, $product_info, $settings);
				} else {
					$offers = array($this->createBaseOffer($xml, $product_info, $settings));
				}

				// Добавляем все созданные предложения
				foreach ($offers as $offer) {
					if ($offer) {
						$offers_xml->appendChild($offer);
						$metrics['offers_generated']++;
					}
				}

			} catch (Exception $e) {
				$metrics['errors_count']++;
				$metrics['warnings'][] = "Ошибка обработки товара {$product['product_id']}: " . $e->getMessage();
			}
		}
	}

	/**
	 * Создание базового предложения
	 *
	 * @throws DOMException
	 */
	private function createBaseOffer(DOMDocument $xml, array $product, array $settings) {
		// Проверка доступности товара
		if ($product['quantity'] <= 0 && !$settings['stock_status']) {
			return false;
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
		$description = $this->prepareDescription($product['description'], $settings);
		if ($description) {
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

		// Артикул
		if ($product['sku']) {
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
	 * Добавление штрихкодов
	 *
	 * @throws DOMException
	 */
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

	/**
	 * Подготовка описания товара
	 */
	private function prepareDescription(string $description, array $settings) {
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

	/**
	 * Добавление изображений товара
	 *
	 * @throws DOMException
	 */
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

	/**
	 * Получение URL изображения с учетом размеров
	 */
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

	/**
	 * Добавление атрибутов товара
	 *
	 * @throws DOMException
	 */
	private function addProductAttributes(DOMDocument $xml, DOMElement $offer, int $product_id) {
		$attributes = $this->dataProvider->getProductAttributes($product_id);

		foreach ($attributes as $attribute) {
			$param = $xml->createElement('param', htmlspecialchars(trim($attribute['text'])));
			$param->setAttribute('name', htmlspecialchars(trim($attribute['name'])));
			$offer->appendChild($param);
		}
	}
}