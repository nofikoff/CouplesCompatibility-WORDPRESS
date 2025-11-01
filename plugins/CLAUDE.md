# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Назначение
WordPress плагин для расчета нумерологической совместимости через shortcode `[numerology_calculator]`

## Ключевые принципы архитектуры

### Разделение ответственности
- **WordPress плагин** - только UI, валидация форм, вызовы API
- **Backend (Laravel API)** - вся бизнес-логика, расчеты, платежи, генерация PDF
- **Никакой авторизации WordPress** - плагин работает для всех посетителей, нужен только email

### Платежи
- **Модель**: Однократная оплата за расчет (НЕТ подписок)
- **Без регистрации**: Только email + даты рождения
- **Платежные системы**:
  - Monobank (по умолчанию, только UAH)
  - Stripe (USD, EUR, UAH)
- Все настройки платежных шлюзов на бэкенде
- Backend возвращает `checkout_url`, frontend просто делает редирект на этот URL
- WordPress не знает про конкретные платежные системы
- Нет Payment Element, нет специфичного кода платежных систем на фронтенде

### Тарифы
- **Free**: $0 - 3 позиции анализа, базовый compatibility score
- **Standard**: $9.99 (999 центов) - 7 позиций, полная матрица совместимости, персональный анализ
- **Premium**: $19.99 (1999 центов) - 9 позиций, полный нумерологический анализ, прогнозы, премиум PDF

### Локализация
- Поддерживаемые языки: `en` (English), `ru` (Русский), `uk` (Українська)
- Локализация применяется к: описаниям чисел, сообщениям API, PDF отчетам, email письмам
- По умолчанию: `en`

## Основная архитектура

### Поток данных

**Бесплатный расчет:**
```
User → Form → AJAX (nc_calculate_free) → ApiCalculations::calculate_free()
→ Backend API /api/v1/calculate/free → Success message
```

**Платный расчет:**
```
User → Form → Select tier (standard/premium) → AJAX (nc_calculate_paid)
→ ApiCalculations::calculate_paid() → Backend API /api/v1/calculate/paid
  Request: { email, person1_date, person2_date, tier, locale }
→ Backend creates Payment & returns checkout_url
→ Frontend redirects: window.location = checkout_url
→ Payment на стороне backend (Monobank или Stripe)
→ Backend redirects back:
  - Success: ?payment_success=1&session_id={ID}&calculation_id={ID}
  - Cancel: ?payment_cancelled=1
→ Frontend polling: GET /api/v1/payments/{id}/status (каждые 3 сек, макс 10 раз)
→ PDF генерируется и отправляется на email
```

### Ключевые компоненты

**API Layer** (`api/`):
- `ApiClient` - HTTP клиент с retry логикой, отправляет `X-API-Key` в заголовках
- `ApiCalculations` - методы `calculate_free()` и `calculate_paid()`

**Public Layer** (`public/`):
- `AjaxHandler` - обработка AJAX запросов (`handle_free_calculation`, `handle_paid_calculation`)
- `Shortcodes` - регистрация shortcode `[numerology_calculator]`
- `form-calculator.php` - многошаговая форма с 6 шагами:
  - **Step 1**: Ввод данных (email, даты рождения, согласия)
  - **Step 2**: Выбор пакета (free/standard/premium)
  - **Step 3**: Обработка (показ спиннера)
  - **Step 4**: Pending - проверка статуса платежа (polling каждые 3 сек, макс 10 попыток)
  - **Step 5**: Success - успешное завершение
  - **Step 6**: Error - страница ошибки (красная иконка, кнопка "Try Again")
- `calculator.js` - логика формы, редирект на `checkout_url`, polling статуса платежа
- Обработка ошибок - вместо `alert()` показывается Step 6 с понятным сообщением

**Admin Layer** (`admin/`):
- Страница: Settings (только настройки плагина)
- Настройки: General, API Configuration, Localization, Advanced
- **НЕТ** Dashboard, Statistics, Calculations - все данные хранятся на бэкенде Laravel
- **НЕТ** настроек Pricing и Payment Gateway (управляется на бэкенде)
- **НЕТ** локальной базы данных - все данные хранятся на бэкенде Laravel

### Настройки плагина

**API Configuration:**
- `nc_api_url` - URL бэкенда
- `nc_api_key` - идентификатор клиента (отправляется в `X-API-Key`)

## UI/UX и обработка ошибок

### Обработка ошибок
- **Никаких `alert()` всплывающих окон** - все ошибки показываются через UI
- При ошибках API, таймаутах или недоступности сервера показывается **Step 6 (Error Page)**
- Страница ошибки включает:
  - Красную иконку ✕ с анимацией "shake"
  - Понятное сообщение об ошибке
  - Инструкцию связаться с поддержкой если проблема повторяется
  - Кнопку "Try Again" для сброса формы и возврата к Step 1

### Проверка статуса платежа (Payment Polling)
После успешного редиректа с платежной страницы (`?payment_success=1`):
1. Показывается Step 4 (Pending) со спиннером
2. Каждые 3 секунды делается запрос к API: `GET /api/v1/payments/{id}/status`
3. Максимум 10 попыток (30 секунд)
4. Варианты завершения:
   - **Успех**: `isPaid=true && pdfReady=true` → Step 5 (Success)
   - **Провал**: `status=failed` → Step 6 (Error)
   - **Таймаут (pending после 10 попыток)**: Step 5 с сообщением "PDF придет на email в течение 5-10 минут"
   - **API недоступен/ошибка (после 10 попыток)**: Step 6 (Error) с сообщением связаться с поддержкой

### Сброс формы
- Кнопка "Calculate Another" на Step 5 (Success)
- Кнопка "Try Again" на Step 6 (Error)
- Обе кнопки вызывают `resetCalculator()`:
  - Очищает все поля формы
  - Снимает галочки с чекбоксов
  - Удаляет ошибки валидации
  - Сбрасывает внутреннее состояние
  - Возвращает на Step 1
- **Важно**: виджет работает через шорткод, поэтому НЕ делается переход на другую страницу

### CSS стили и анимации
**Цветовая схема** (`:root` переменные):
- `--nc-primary: #6B46C1` - основной фиолетовый
- `--nc-secondary: #F59E0B` - вторичный оранжевый
- `--nc-success: #10B981` - зеленый для успеха
- `--nc-danger: #EF4444` - красный для ошибок

**Анимации**:
- `fadeIn` - плавное появление (для всех шагов)
- `scaleIn` - появление с масштабированием (для иконки успеха)
- `shake` - тряска влево-вправо (для иконки ошибки)
- `spin` - вращение (для спиннера)

**Ключевые CSS классы**:
- `.nc-success` / `.nc-success-icon` - страница успеха с зеленой иконкой ✓
- `.nc-error-page` / `.nc-error-icon` - страница ошибки с красной иконкой ✕
- `.nc-pending` - страница ожидания проверки платежа
- `.nc-btn-restart` - кнопка сброса формы (используется на Success и Error страницах)

## Форматы данных

### Даты рождения
- **Формат**: `YYYY-MM-DD` (ISO 8601)
- **Валидация**: должна быть в прошлом (до сегодняшнего дня)
- **Пример**: `"1990-05-15"`

### Email
- **Формат**: Стандартный email
- **Обязательное поле** для бесплатного и платного расчета

### Tier (тип пакета)
- `free` - бесплатный расчет (3 позиции)
- `standard` - стандартный пакет ($9.99, 7 позиций)
- `premium` - премиум пакет ($19.99, 9 позиций)

## Общие правила
- Комментарии на русском языке
- PSR-12 стандарт для PHP кода
- Не читай файлы из .gitignore
- Фокус только на `./plugins/numerology-compatibility`

## API бэкенда - основные endpoints

### Base URL
- **Production**: `https://api.your-domain.com/api/v1`
- **Development**: `http://localhost:8088/api/v1`

### 1. POST /v1/calculate/free - Бесплатный расчет
**Request**: `{ email, person1_date, person2_date, locale? }`
**Response**: `{ success, message, data: { calculation_id, type: "free", result, pdf_url } }`

### 2. POST /v1/calculate/paid - Платный расчет
**Request**: `{ email, person1_date, person2_date, tier, locale?, success_url?, cancel_url? }`
**Response**: `{ success, message, data: { calculation_id, payment_id, checkout_url, amount, currency, tier } }`
**Действие**: `window.location.href = checkout_url`

### 3. GET /v1/payments/{id}/status - Статус платежа
**Response**: `{ success, data: { payment_id, calculation_id, status, amount, currency, gateway, paid_at } }`
**Status**: `pending | succeeded | failed | cancelled`

### 4. GET /v1/calculations/{id}/pdf - Скачать PDF
**Response**: PDF файл или `425 Too Early` если еще генерируется

### 5. GET /v1/calculations/{id} - Информация о расчете
**Response**: Детали расчета с результатами

## URL параметры после оплаты
- **Success**: `?payment_success=1&session_id={ID}&calculation_id={ID}`
- **Cancel**: `?payment_cancelled=1`

## HTTP коды ошибок
- `200` - Успех
- `422` - Ошибка валидации (возвращает `{ success: false, errors: {...} }`)
- `404` - Не найдено
- `425` - PDF еще генерируется
- `500` - Ошибка сервера

---

## Полная OpenAPI спецификация (reference)

paths:
  /api/v1/calculate/free:
    post:
      summary: 'Бесплатный расчет'
      operationId: ''
      description: 'POST /api/v1/calculate/free'
      parameters: []
      responses: {  }
      tags:
        - Endpoints
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                email:
                  type: string
                  description: 'Must be a valid email address.'
                  example: gbailey@example.net
                  nullable: false
                person1_date:
                  type: string
                  description: 'Must be a valid date in the format <code>Y-m-d</code>. Must be a date before <code>today</code>.'
                  example: '2021-11-19'
                  nullable: false
                person2_date:
                  type: string
                  description: 'Must be a valid date in the format <code>Y-m-d</code>. Must be a date before <code>today</code>.'
                  example: '2021-11-19'
                  nullable: false
                locale:
                  type: string
                  description: ''
                  example: ru
                  nullable: true
                  enum:
                    - en
                    - ru
              required:
                - email
                - person1_date
                - person2_date
      security: []
  /api/v1/calculate/paid:
    post:
      summary: 'Платный расчет - создание Checkout Session'
      operationId: CheckoutSession
      description: 'POST /api/v1/calculate/paid'
      parameters: []
      responses: {  }
      tags:
        - Endpoints
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                email:
                  type: string
                  description: 'Must be a valid email address.'
                  example: gbailey@example.net
                  nullable: false
                person1_date:
                  type: string
                  description: 'Must be a valid date in the format <code>Y-m-d</code>. Must be a date before <code>today</code>.'
                  example: '2021-11-19'
                  nullable: false
                person2_date:
                  type: string
                  description: 'Must be a valid date in the format <code>Y-m-d</code>. Must be a date before <code>today</code>.'
                  example: '2021-11-19'
                  nullable: false
                tier:
                  type: string
                  description: ''
                  example: premium
                  nullable: false
                  enum:
                    - standard
                    - premium
                locale:
                  type: string
                  description: ''
                  example: en
                  nullable: true
                  enum:
                    - en
                    - ru
              required:
                - email
                - person1_date
                - person2_date
                - tier
      security: []
  '/api/v1/calculations/{id}':
    get:
      summary: 'Получить информацию о расчете'
      operationId: ''
      description: 'GET /api/v1/calculations/{id}'
      parameters: []
      responses:
        500:
          description: ''
          content:
            application/json:
              schema:
                type: object
                example:
                  message: 'Server Error'
                properties:
                  message:
                    type: string
                    example: 'Server Error'
      tags:
        - Endpoints
      security: []
    parameters:
      -
        in: path
        name: id
        description: 'The ID of the calculation.'
        example: architecto
        required: true
        schema:
          type: string
  '/api/v1/calculations/{id}/pdf':
    get:
      summary: 'Скачать PDF отчет'
      operationId: PDF
      description: 'GET /api/v1/calculations/{id}/pdf'
      parameters: []
      responses:
        500:
          description: ''
          content:
            application/json:
              schema:
                type: object
                example:
                  message: 'Server Error'
                properties:
                  message:
                    type: string
                    example: 'Server Error'
      tags:
        - Endpoints
      security: []
    parameters:
      -
        in: path
        name: id
        description: 'The ID of the calculation.'
        example: architecto
        required: true
        schema:
          type: string
  /api/partner/calculate:
    post:
      summary: 'Партнерский расчет (требует API ключ)'
      operationId: API
      description: "POST /api/partner/calculate\nHeaders: X-API-Key: {api_key}"
      parameters: []
      responses: {  }
      tags:
        - Endpoints
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                person1_date:
                  type: string
                  description: 'Must be a valid date in the format <code>Y-m-d</code>. Must be a date before <code>today</code>.'
                  example: '2021-11-19'
                  nullable: false
                person2_date:
                  type: string
                  description: 'Must be a valid date in the format <code>Y-m-d</code>. Must be a date before <code>today</code>.'
                  example: '2021-11-19'
                  nullable: false
                locale:
                  type: string
                  description: ''
                  example: en
                  nullable: true
                  enum:
                    - en
                    - ru
              required:
                - person1_date
                - person2_date
      security: []
  /api/partner/usage:
    get:
      summary: 'Получить статистику использования API ключа'
      operationId: API
      description: "GET /api/partner/usage\nHeaders: X-API-Key: {api_key}"
      parameters: []
      responses:
        401:
          description: ''
          content:
            application/json:
              schema:
                type: object
                example:
                  success: false
                  message: 'API key required'
                properties:
                  success:
                    type: boolean
                    example: false
                  message:
                    type: string
                    example: 'API key required'
      tags:
        - Endpoints
      security: []


