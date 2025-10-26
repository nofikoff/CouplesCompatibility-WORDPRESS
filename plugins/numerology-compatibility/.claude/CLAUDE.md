# Инструкции для Claude Code

## Назначение
- Плагин к Wordpress для отображения через shortcode формы расчета по нумерологии "Compatibility"

## Задачи
- Получение нумерологических PDF отчетов на основе входных даты рождения
- Авторизация не нужна, только e-mail обязательное поле
- Бесплатный минималистичный расчет
- Платный средний и полный расчеты
- Примем платежей с возможностью подключить несколько разных платежных шлюзов, для начала Stripe
- Мультиязычность

## Общие правила
- Не читай файлы из .gitignore
- Пиши комментарии на русском языке
- Используй PSR-12 стандарт для PHP кода
- Всегда запускай тесты после изменений

## Структура проекта
- ./plugins/numerology-compatibility - код плагина тут, остальной код не анализируем

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


