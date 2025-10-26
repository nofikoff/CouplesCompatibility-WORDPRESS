# 1. Установите зависимости
cd wp-content/plugins/numerology-compatibility/
composer install
npm install
npm run build

# 2. Активируйте плагин в WordPress

# 3. Настройте API подключение в админке

# 4. Добавьте shortcode на страницу:


// Калькулятор с авто-выбором пакета
[numerology_compatibility]

// Калькулятор с фиксированным пакетом
[numerology_compatibility package="free"]
[numerology_compatibility package="light"]

// Прайсинг
[numerology_pricing highlight="light"]

// GDPR инструменты
[numerology_gdpr]