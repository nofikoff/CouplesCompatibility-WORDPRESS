<?php
/**
 * Точка входа в тему
 */

// 1. Подключаем автозагрузчик Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Fallback, если забыли сделать composer install
	wp_die('<h1>Ошибка: Composer не установлен</h1><p>Запустите <code>composer install</code> в папке темы.</p>');
}

// 2. Инициализируем Timber
Timber\Timber::init();

// 3. Указываем, где лежат Twig файлы
Timber\Timber::$dirname = ['views'];

// 4. Запускаем наш класс конфигурации (см. следующий файл)
new MyTheme\StarterSite();