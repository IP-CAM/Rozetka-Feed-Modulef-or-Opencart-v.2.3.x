<?php
// Heading
$_['heading_title']           = 'Rozetka XML Feed';

// Text
$_['text_extension']          = 'Каналы продвижения';
$_['text_success']            = 'Настройки успешно изменены!';
$_['text_edit']               = 'Редактирование Rozetka Feed';
$_['text_enabled']            = 'Включено';
$_['text_disabled']           = 'Отключено';
$_['text_yes']                = 'Да';
$_['text_no']                 = 'Нет';
$_['text_loading']            = 'Загрузка...';
$_['text_none']               = 'Нет';

// Entry
$_['entry_status']            = 'Статус';
$_['entry_shop_name']         = 'Название магазина';
$_['entry_company']           = 'Компания';
$_['entry_currency']          = 'Валюта';
$_['entry_image_width']       = 'Ширина изображений';
$_['entry_image_height']      = 'Высота изображений';
$_['entry_image_quality']     = 'Качество изображений';
$_['entry_description']       = 'Длина описания';
$_['entry_description_tags']  = 'Очистка HTML тегов';
$_['entry_options']           = 'Включать опции';
$_['entry_attributes']        = 'Включать характеристики';
$_['entry_stock']             = 'Товары без наличия';
$_['entry_min_price']         = 'Минимальная цена';
$_['entry_max_price']         = 'Максимальная цена';
$_['entry_categories']        = 'Исключить категории';
$_['entry_manufacturers']     = 'Исключить производителей';
$_['entry_update_frequency']  = 'Частота обновления';
$_['entry_compress_xml']      = 'Сжимать XML';
$_['entry_api_key']           = 'API ключ';
$_['entry_allowed_ips']       = 'Разрешенные IP адреса';
$_['entry_webhook_secret']    = 'Секрет для webhook';

// Help
$_['help_shop_name']          = 'Если не заполнено, будет использовано название из настроек магазина';
$_['help_company']            = 'Юридическое название компании';
$_['help_currency']           = 'Валюта для выгрузки цен в фид';
$_['help_image_width']        = 'Ширина изображений в пикселях (рекомендуется 800-1200)';
$_['help_image_height']       = 'Высота изображений в пикселях (рекомендуется 800-1200)';
$_['help_image_quality']      = 'Качество сжатия JPEG изображений';
$_['help_description']        = 'Максимальная длина описания товара в символах';
$_['help_description_tags']   = 'Удалять HTML теги из описания товаров';
$_['help_options']            = 'Создавать отдельные предложения для каждой опции товара';
$_['help_attributes']         = 'Добавлять характеристики товаров в параметры фида';
$_['help_stock']              = 'Включать товары с нулевым остатком в фид';
$_['help_min_price']          = 'Исключать товары дешевле указанной цены (0 - без ограничений)';
$_['help_max_price']          = 'Исключать товары дороже указанной цены (0 - без ограничений)';
$_['help_categories']         = 'Выберите категории, которые нужно исключить из фида';
$_['help_manufacturers']      = 'Выберите производителей, которых нужно исключить из фида';
$_['help_update_frequency']   = 'Рекомендуемая частота обновления фида для Rozetka';
$_['help_compress_xml']       = 'Сжимать XML файл для уменьшения размера (GZIP)';
$_['help_feed_url']           = 'Используйте этот URL для подключения к Rozetka';
$_['help_api_key']            = 'API ключ для доступа к административным функциям фида';
$_['help_allowed_ips']        = 'IP адреса, разрешенные для API запросов (через запятую)';
$_['help_webhook_secret']     = 'Секретный ключ для проверки webhook запросов от Rozetka';

// Button
$_['button_save']             = 'Сохранить';
$_['button_cancel']           = 'Отмена';
$_['button_test']             = 'Тест генерации';
$_['button_preview']          = 'Предпросмотр';
$_['button_generate']         = 'Генерировать фид';
$_['button_clear_cache']      = 'Очистить кэш';
$_['button_refresh']          = 'Обновить';
$_['button_export']           = 'Экспорт настроек';
$_['button_import']           = 'Импорт настроек';
$_['button_history']          = 'История генераций';
$_['button_validate']         = 'Валидировать фид';
$_['button_download']         = 'Скачать фид';

// Tab
$_['tab_general']             = 'Основные настройки';
$_['tab_images']              = 'Изображения';
$_['tab_content']             = 'Контент';
$_['tab_filters']             = 'Фильтры';
$_['tab_advanced']            = 'Дополнительно';
$_['tab_api']                 = 'API настройки';
$_['tab_statistics']          = 'Статистика';
$_['tab_history']             = 'История';

// Column
$_['column_date']             = 'Дата';
$_['column_status']           = 'Статус';
$_['column_products']         = 'Товаров';
$_['column_offers']           = 'Предложений';
$_['column_time']             = 'Время';
$_['column_memory']           = 'Память';
$_['column_size']             = 'Размер';
$_['column_action']           = 'Действие';

// Statistics
$_['text_total_products_shop']    = 'Всего товаров в магазине';
$_['text_total_products_feed']    = 'Товаров в фиде';
$_['text_products_with_images']   = 'Товаров с изображениями';
$_['text_products_without_images'] = 'Товаров без изображений';
$_['text_products_in_stock']      = 'Товаров в наличии';
$_['text_products_out_of_stock']  = 'Товаров без наличия';
$_['text_excluded_categories']    = 'Исключено категорий';
$_['text_excluded_manufacturers'] = 'Исключено производителей';
$_['text_last_generation']        = 'Последняя генерация';
$_['text_avg_generation_time']    = 'Среднее время генерации';
$_['text_success_rate']           = 'Процент успешных генераций';

// Status
$_['text_status_started']     = 'Запущена';
$_['text_status_success']     = 'Успешно';
$_['text_status_error']       = 'Ошибка';
$_['text_status_disabled']    = 'Отключен';

// Messages
$_['text_feed_generated']     = 'Фид успешно сгенерирован';
$_['text_cache_cleared']      = 'Кэш успешно очищен';
$_['text_settings_exported']  = 'Настройки экспортированы';
$_['text_settings_imported']  = 'Настройки импортированы';
$_['text_test_completed']     = 'Тест завершен';
$_['text_preview_generated']  = 'Предпросмотр сгенерирован';
$_['text_validation_passed']  = 'Валидация прошла успешно';
$_['text_validation_failed']  = 'Валидация не пройдена';

// Warnings
$_['text_warning_memory']     = 'Прогнозируемая память превышает лимит';
$_['text_warning_time']       = 'Прогнозируемое время превышает лимит';
$_['text_warning_no_products'] = 'Нет товаров для выгрузки';
$_['text_warning_large_feed'] = 'Большой размер фида может замедлить работу';

// Validation
$_['text_validation_offers']    = 'Товарных предложений';
$_['text_validation_categories'] = 'Категорий';
$_['text_validation_currencies'] = 'Валют';
$_['text_validation_errors']    = 'Ошибки валидации';
$_['text_validation_warnings']  = 'Предупреждения';

// API
$_['text_api_url_feed']       = 'URL фида';
$_['text_api_url_info']       = 'Информация о фиде';
$_['text_api_url_status']     = 'Статус фида';
$_['text_api_url_validate']   = 'Валидация фида';
$_['text_api_url_regenerate'] = 'Регенерация фида';
$_['text_api_url_download']   = 'Скачивание фида';
$_['text_api_url_webhook']    = 'Webhook URL';

// Frequency
$_['text_freq_hourly']        = 'Каждый час';
$_['text_freq_daily']         = 'Ежедневно';
$_['text_freq_weekly']        = 'Еженедельно';
$_['text_freq_monthly']       = 'Ежемесячно';

// Error
$_['error_permission']        = 'У вас нет прав для изменения Rozetka feed!';
$_['error_image_width']       = 'Ширина изображения должна быть от 100 до 2000 пикселей!';
$_['error_image_height']      = 'Высота изображения должна быть от 100 до 2000 пикселей!';
$_['error_price_range']       = 'Минимальная цена не может быть больше максимальной!';
$_['error_description_length'] = 'Длина описания должна быть от 100 до 10000 символов!';
$_['error_generation_failed'] = 'Не удалось сгенерировать фид!';
$_['error_no_products']       = 'Нет товаров для выгрузки в фид!';
$_['error_invalid_xml']       = 'Сгенерированный XML невалидный!';
$_['error_file_write']        = 'Не удалось записать файл фида!';
$_['error_memory_limit']      = 'Недостаточно памяти для генерации фида!';
$_['error_time_limit']        = 'Превышено время выполнения!';
$_['error_api_key']           = 'Неверный API ключ!';
$_['error_access_denied']     = 'Доступ запрещен!';
$_['error_invalid_format']    = 'Неверный формат данных!';
$_['error_file_upload']       = 'Ошибка загрузки файла!';

// Success
$_['success_generation']      = 'Фид успешно сгенерирован за %s секунд. Обработано товаров: %s';
$_['success_cache_clear']     = 'Кэш успешно очищен. Удалено файлов: %s';
$_['success_settings_save']   = 'Настройки успешно сохранены';
$_['success_export']          = 'Настройки успешно экспортированы';
$_['success_import']          = 'Настройки успешно импортированы';

// Placeholders
$_['placeholder_shop_name']   = 'Введите название магазина';
$_['placeholder_company']     = 'Введите название компании';
$_['placeholder_api_key']     = 'Введите API ключ';
$_['placeholder_ips']         = '127.0.0.1, 192.168.1.1';
$_['placeholder_secret']      = 'Введите секретный ключ';

// Tooltips
$_['tooltip_test']            = 'Проверить работу генератора на небольшом количестве товаров';
$_['tooltip_preview']         = 'Просмотреть фрагмент XML фида';
$_['tooltip_generate']        = 'Сгенерировать полный XML фид';
$_['tooltip_validate']        = 'Проверить корректность сгенерированного XML';
$_['tooltip_download']        = 'Скачать XML фид как файл';
$_['tooltip_history']         = 'Посмотреть историю генераций фида';

// Info
$_['info_system_requirements'] = 'Системные требования';
$_['info_php_version']        = 'Версия PHP';
$_['info_memory_limit']       = 'Лимит памяти';
$_['info_execution_time']     = 'Время выполнения';
$_['info_feed_size']          = 'Размер фида';
$_['info_last_update']        = 'Последнее обновление';
$_['info_next_update']        = 'Следующее обновление';

// Legends
$_['legend_statistics']       = 'Статистика фида';
$_['legend_management']       = 'Управление фидом';
$_['legend_system_info']      = 'Информация о системе';
$_['legend_api_endpoints']    = 'API endpoints';
$_['legend_generation_history'] = 'История генераций';

// Modal titles
$_['modal_preview_title']     = 'Предпросмотр XML фида';
$_['modal_history_title']     = 'История генераций';
$_['modal_validation_title']  = 'Результаты валидации';
$_['modal_test_title']        = 'Результаты тестирования';
$_['modal_import_title']      = 'Импорт настроек';
$_['modal_api_title']         = 'API информация';

// Progress
$_['text_progress_loading']   = 'Загрузка данных...';
$_['text_progress_generating'] = 'Генерация фида...';
$_['text_progress_validating'] = 'Валидация XML...';
$_['text_progress_testing']   = 'Тестирование...';
$_['text_progress_clearing']  = 'Очистка кэша...';

// File operations
$_['text_file_not_found']     = 'Файл не найден';
$_['text_file_empty']         = 'Файл пуст';
$_['text_file_too_large']     = 'Файл слишком большой';
$_['text_file_invalid']       = 'Неверный формат файла';

// Time formats
$_['text_seconds']            = 'сек';
$_['text_minutes']            = 'мин';
$_['text_hours']              = 'ч';
$_['text_days']               = 'дн';
$_['text_never']              = 'Никогда';
$_['text_just_now']           = 'Только что';

// Actions
$_['text_action_view']        = 'Просмотр';
$_['text_action_download']    = 'Скачать';
$_['text_action_delete']      = 'Удалить';
$_['text_action_retry']       = 'Повторить';
$_['text_action_details']     = 'Подробности';

// Confirm dialogs
$_['text_confirm_regenerate'] = 'Вы уверены, что хотите перегенерировать фид?';
$_['text_confirm_clear_cache'] = 'Вы уверены, что хотите очистить кэш?';
$_['text_confirm_delete']     = 'Вы уверены, что хотите удалить этот элемент?';
$_['text_confirm_import']     = 'Импорт перезапишет текущие настройки. Продолжить?';

// Charts and graphs
$_['text_chart_generation_time'] = 'Время генерации';
$_['text_chart_memory_usage']    = 'Использование памяти';
$_['text_chart_feed_size']       = 'Размер фида';
$_['text_chart_success_rate']    = 'Успешность генераций';
$_['text_chart_products_count']  = 'Количество товаров';

// Instructions
$_['text_instruction_setup']     = 'Для настройки фида выполните следующие шаги:';
$_['text_instruction_step1']     = '1. Включите статус модуля';
$_['text_instruction_step2']     = '2. Настройте параметры изображений и контента';
$_['text_instruction_step3']     = '3. Установите фильтры товаров при необходимости';
$_['text_instruction_step4']     = '4. Проведите тестирование генерации';
$_['text_instruction_step5']     = '5. Передайте URL фида в Rozetka';

// Recommendations
$_['text_recommend_memory']      = 'Рекомендуется увеличить лимит памяти PHP до 512MB или выше';
$_['text_recommend_time']        = 'Рекомендуется увеличить max_execution_time до 300 секунд или выше';
$_['text_recommend_cron']        = 'Настройте cron задачу для автоматической регенерации фида';
$_['text_recommend_backup']      = 'Создайте резервную копию настроек перед импортом';

// Units
$_['text_unit_bytes']         = 'байт';
$_['text_unit_kb']            = 'КБ';
$_['text_unit_mb']            = 'МБ';
$_['text_unit_gb']            = 'ГБ';
$_['text_unit_pixels']        = 'пикс.';
$_['text_unit_percent']       = '%';

// Categories mapping (for future use)
$_['text_rozetka_categories'] = 'Категории Rozetka';
$_['text_shop_categories']    = 'Категории магазина';
$_['text_category_mapping']   = 'Сопоставление категорий';
$_['text_unmapped_categories'] = 'Несопоставленные категории';
$_['text_map_category']       = 'Сопоставить категорию';

// Attributes settings (for future use)
$_['text_attribute_mapping']  = 'Настройка атрибутов';
$_['text_required_attributes'] = 'Обязательные атрибуты';
$_['text_optional_attributes'] = 'Дополнительные атрибуты';
$_['text_attribute_priority']  = 'Приоритет атрибута';

// Notifications
$_['notification_feed_ready'] = 'XML фид готов для использования';
$_['notification_cache_cleared'] = 'Кэш фида очищен';
$_['notification_settings_saved'] = 'Настройки сохранены';
$_['notification_validation_complete'] = 'Валидация завершена';
$_['notification_test_complete'] = 'Тестирование завершено';

// Badges
$_['badge_enabled']           = 'Включен';
$_['badge_disabled']          = 'Отключен';
$_['badge_success']           = 'Успех';
$_['badge_error']             = 'Ошибка';
$_['badge_warning']           = 'Внимание';
$_['badge_info']              = 'Инфо';
$_['badge_new']               = 'Новый';
$_['badge_updated']           = 'Обновлен';

// Feed quality indicators
$_['text_quality_excellent']  = 'Отличное качество фида';
$_['text_quality_good']       = 'Хорошее качество фида';
$_['text_quality_fair']       = 'Удовлетворительное качество';
$_['text_quality_poor']       = 'Низкое качество фида';

// Performance indicators
$_['text_performance_fast']   = 'Быстрая генерация';
$_['text_performance_normal'] = 'Нормальная скорость';
$_['text_performance_slow']   = 'Медленная генерация';
$_['text_performance_critical'] = 'Критически медленно';

// Debug information
$_['text_debug_mode']         = 'Режим отладки';
$_['text_debug_info']         = 'Информация для отладки';
$_['text_debug_queries']      = 'SQL запросы';
$_['text_debug_memory']       = 'Использование памяти';
$_['text_debug_time']         = 'Время выполнения';

// Export/Import
$_['text_export_description'] = 'Экспорт сохранит все настройки модуля в JSON файл';
$_['text_import_description'] = 'Импорт загрузит настройки из JSON файла';
$_['text_export_filename']    = 'rozetka_settings';
$_['text_import_select_file'] = 'Выберите файл настроек';

// Security
$_['text_security_warning']   = 'Предупреждение безопасности';
$_['text_api_security']       = 'API настройки влияют на безопасность';
$_['text_webhook_security']   = 'Используйте надежный секретный ключ';
$_['text_ip_whitelist']       = 'Ограничьте доступ по IP адресам';
?>