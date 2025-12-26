# Numerology Compatibility Plugin

A WordPress plugin that provides numerology compatibility calculator via shortcodes. Integrates with a Laravel backend for calculations, payments, and PDF generation.

## Description

This plugin adds a numerology compatibility calculator that can be embedded anywhere on your WordPress site using shortcodes. It displays:

- **Interactive form** for entering birth dates
- **Package selection** (Free, Standard, Premium)
- **Payment integration** (Stripe, Monobank)
- **PDF report generation** and delivery
- **Backend communication** for all calculations

The plugin is UI-only — all business logic (calculations, payments, PDF generation) is handled by the Laravel backend API.

## Shortcodes

### Calculator Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[numerology_compatibility]` | Standard flow: dates → package selection → calculation |
| `[numerology_compatibility_v2]` | Reversed flow: package selection → dates → calculation |

**Attributes:**

```php
[numerology_compatibility
    package="auto"      // auto, free, standard, premium
    show_prices="yes"   // yes, no
    theme=""            // empty or "hero" for dark backgrounds
]
```

### Result Page Shortcode

```php
[numerology_result]
```

Place on a dedicated result page. Handles:
- `?code={secret_code}` - access by permanent link
- `?payment_success=1&payment_id={id}` - redirect after payment
- `?payment_cancelled=1` - cancelled payment

### Additional Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[numerology_pricing]` | Pricing table with package comparison |
| `[numerology_gdpr]` | GDPR tools (export/delete user data) |

## Pricing Tiers

| Tier | Price | Features |
|------|-------|----------|
| **Free** | $0 | 3 positions, basic compatibility score |
| **Standard** | $9.99 | 7 positions, full compatibility matrix, personal analysis |
| **Premium** | $19.99 | 9 positions, karmic connections, timeline predictions |

## Installation

### 1. Install Dependencies

```bash
cd plugins/numerology-compatibility
composer install
npm install  # if building assets
```

### 2. Activate Plugin

Go to WordPress Admin → Plugins → Activate "Numerology Compatibility"

### 3. Configure Settings

Navigate to **Numerology → Settings** in WordPress admin:

**General Tab:**
- Result Page URL - page with `[numerology_result]` shortcode
- Standard Price / Premium Price - display prices (actual prices on backend)

**API Configuration Tab:**
- API URL - Laravel backend URL (e.g., `https://api.example.com`)
- API Key - authentication key for backend requests

**Advanced Tab:**
- Delete on Uninstall - remove all settings when plugin is deleted

## API Endpoints

The plugin communicates with Laravel backend:

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/calculate/free` | Free calculation |
| `POST` | `/api/v1/calculate/paid` | Paid calculation (returns checkout_url) |
| `GET` | `/api/v1/payments/{id}/status` | Payment status polling |
| `POST` | `/api/v1/calculations/send-email` | Send PDF to email |
| `GET` | `/api/v1/calculations/{id}/pdf` | Download PDF |
| `GET` | `/api/v1/calculations/by-code/{code}` | Get calculation by secret code |

## User Flow

### Free Calculation

```
Enter dates → Click "Get Free Report"
→ Backend calculates → PDF generated
→ Show result + optional email form
```

### Paid Calculation

```
Enter dates → Select package → Click "Get Report"
→ Redirect to payment page (Stripe/Monobank)
→ Payment processed → Redirect back with payment_id
→ Poll payment status → Show result + PDF download
```

## Features

### Geo-based Language Detection

Automatically detects visitor's country via Cloudflare `CF-IPCountry` header and switches language:
- Ukraine (UA) → Ukrainian
- All other → English

### Payment Integration

- **Monobank** - UAH payments (Ukraine)
- **Stripe** - USD, EUR, UAH payments (International)

Payment configuration is handled on the Laravel backend. The plugin only receives `checkout_url` and redirects users.

### PDF Generation

- Generated asynchronously on backend
- Polling mechanism checks when ready
- Download link provided when complete
- Optional email delivery

### GDPR Compliance

The `[numerology_gdpr]` shortcode provides:
- **Export Data** - download all user calculations as JSON
- **Delete Data** - permanently remove all user data

### Localization

Supported languages:
- English (en) - default
- Russian (ru)
- Ukrainian (uk)

Uses WordPress `__()` and `_e()` functions. Translation files in `/languages/`.

## File Structure

```
numerology-compatibility/
├── numerology-compatibility.php   # Main plugin file
├── includes/
│   ├── class-plugin.php           # Core plugin class
│   ├── class-loader.php           # Hook/filter loader
│   ├── class-i18n.php             # Internationalization
│   ├── class-activator.php        # Activation logic
│   ├── class-deactivator.php      # Deactivation logic
│   └── class-geo-language.php     # Geo-based language switching
├── public/
│   ├── class-public.php           # Frontend assets
│   ├── class-shortcodes.php       # Shortcode registration
│   ├── class-ajax-handler.php     # AJAX endpoints
│   ├── views/
│   │   ├── form-calculator.php    # Calculator form template
│   │   └── view-result.php        # Result page template
│   └── assets/
│       ├── js/
│       │   ├── calculator.js      # Calculator logic
│       │   └── result.js          # Result page logic
│       └── css/
│           └── public.css         # Frontend styles
├── admin/
│   ├── class-admin.php            # Admin menu
│   ├── class-settings.php         # Settings page
│   └── views/
│       └── settings-page.php      # Settings template
├── api/
│   ├── class-api-client.php       # HTTP client with retry
│   └── class-api-calculations.php # API method wrappers
├── languages/                     # Translation files
├── tests/                         # PHPUnit tests
├── composer.json
└── package.json
```

## Requirements

- PHP 7.4+
- WordPress 5.8+
- Composer
- Laravel backend API

## Development

### Running Tests

```bash
composer install
./vendor/bin/phpunit
```

### Building Assets

```bash
npm install
npm run build
```

## License

GPL v2 or later
