# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Назначение
WordPress плагин для расчета нумерологической совместимости через shortcode `[numerology_calculator]`

## Ключевые принципы архитектуры

### Разделение ответственности
- **WordPress плагин** - только UI, валидация форм, вызовы API, прием webhook'ов
- **Backend (Laravel API)** - вся бизнес-логика, расчеты, платежи, генерация PDF
- **Никакой авторизации WordPress** - плагин работает для всех посетителей, нужен только email

### Платежи
- Все настройки платежных шлюзов (Stripe, PayPal, etc) на бэкенде
- Backend возвращает `checkout_url`, frontend просто делает редирект на этот URL
- WordPress не знает про конкретные платежные системы, только принимает webhook'и
- Нет Payment Element, нет специфичного кода платежных систем на фронтенде

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
→ Backend returns checkout_url → Frontend redirects: window.location = checkout_url
→ Payment на стороне backend (любой gateway: Stripe, PayPal, etc)
→ Backend redirects back с ?payment_success=1
→ Backend sends webhook → ApiPayments::handle_webhook()
```

### Ключевые компоненты

**API Layer** (`api/`):
- `ApiClient` - HTTP клиент с retry логикой, отправляет `X-API-Key` в заголовках
- `ApiCalculations` - методы `calculate_free()` и `calculate_paid()`, генерирует `success_url`/`cancel_url`
- `ApiPayments` - обработка webhook'ов от бэкенда, проверка HMAC подписи

**Public Layer** (`public/`):
- `AjaxHandler` - обработка AJAX запросов (`handle_free_calculation`, `handle_paid_calculation`)
- `Shortcodes` - регистрация shortcode `[numerology_calculator]`
- `form-calculator.php` - 4-шаговая форма (даты → пакет → обработка → успех)
- `calculator.js` - логика формы, редирект на `checkout_url`

**Admin Layer** (`admin/`):
- Страницы: Dashboard, Settings, Statistics, Calculations, Users, Logs
- Настройки: General, API Configuration, Localization, Advanced
- **НЕТ** настроек Pricing и Payment Gateway (управляется на бэкенде)

**Database Layer** (`database/`):
- Таблицы: `nc_calculations`, `nc_transactions`, `nc_analytics`, `nc_consents`, `nc_api_usage`, `nc_error_logs`
- Поле `gateway_payment_id` (универсальное, не привязано к Stripe)

### Настройки плагина

**API Configuration:**
- `nc_api_url` - URL бэкенда
- `nc_api_key` - идентификатор клиента (отправляется в `X-API-Key`)
- `nc_webhook_secret` - секрет для проверки HMAC подписи входящих webhook'ов

**Webhook URL:** `/wp-json/numerology/v1/webhook/{gateway}`
- Примеры: `/webhook/stripe`, `/webhook/paypal`
- Проверка подписи: `hash_hmac('sha256', $payload, nc_webhook_secret)`

## Общие правила
- Комментарии на русском языке
- PSR-12 стандарт для PHP кода
- Не читай файлы из .gitignore
- Фокус только на `./plugins/numerology-compatibility`

## API бэкенда (вторая часть проекта, если надо, расширим) openapi: 3.0.3
info:
  title: 'Numerology API Documentation'
  description: ''
  version: 1.0.0
servers:
  -
    url: 'http://localhost:8088/'
tags:
  -
    name: Endpoints
    description: ''
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
                success_url:
                  type: string
                  description: 'Must be a valid URL.'
                  example: 'http://bailey.com/'
                  nullable: true
                cancel_url:
                  type: string
                  description: 'Must be a valid URL.'
                  example: 'http://rempel.com/sunt-nihil-accusantium-harum-mollitia'
                  nullable: true
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
  /api/v1/webhooks/stripe:
    post:
      summary: 'Обработать webhook от Stripe'
      operationId: WebhookStripe
      description: 'POST /api/v1/webhooks/stripe'
      parameters: []
      responses: {  }
      tags:
        - Endpoints
      security: []
  '/api/v1/webhooks/{gateway}':
    post:
      summary: 'Обработать webhook от других платежных систем (будущее расширение)'
      operationId: Webhook
      description: 'POST /api/v1/webhooks/{gateway}'
      parameters: []
      responses: {  }
      tags:
        - Endpoints
      security: []
    parameters:
      -
        in: path
        name: gateway
        description: ''
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


