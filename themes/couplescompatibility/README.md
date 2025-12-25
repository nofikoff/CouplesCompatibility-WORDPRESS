# CouplesCompatibility Theme

A Timber-based WordPress theme for [couplescompatibility.com](https://couplescompatibility.com).

## Why WordPress Instead of Next.js?

This project uses WordPress instead of a modern framework like Next.js for several key reasons:

1. **Non-technical User Management**: Website content needs to be managed by non-developers. WordPress provides a familiar admin interface for updating text, images, and pages without coding knowledge.

2. **Multilingual Support**: The site supports multiple languages (English, Russian, Ukrainian). WordPress with Polylang plugin allows content editors to manage translations without developer involvement.

3. **Blog & Articles**: The project requires a blog section. WordPress excels at content management - editors can publish articles, manage categories, and handle SEO without technical skills.

4. **ACF for Structured Content**: Advanced Custom Fields (ACF) allows defining custom fields that content editors can easily update through the WordPress admin panel.

5. **No Build Process for Content**: Content changes are instant. No need to rebuild/redeploy the site for text updates.

## Development Workflow

### Landing Page Creation

Instead of using visual builders like Elementor, this project uses a streamlined AI-assisted workflow:

1. **Generate with Bolt.new**: Create initial landing page design with AI
   - See example: [`examples/bolt-landing-page-template.html`](../../examples/bolt-landing-page-template.html)

2. **Convert to Twig**: Use prompt to transform the design:
   ```
   Rewrite this code to pure HTML + Twig templating format.
   Replace hardcoded text with {{ fields.variable }} placeholders
   and give me list of these placeholders with content for import
   to WordPress plugin Advanced Custom Fields JSON format.
   ```

3. **Setup WordPress**:
   - Install ACF plugin and import JSON field definitions
   - See example: [`examples/acf-landing-page-fields.json`](../../examples/acf-landing-page-fields.json)
   - Install Polylang for multilingual support
   - Create translations for each language

4. **Integrate with Timber**: The theme uses Timber 2.3 for Twig templating in WordPress

## Theme Structure

```
couplescompatibility/
├── views/
│   ├── layouts/
│   │   └── base.twig           # Base layout with header/footer
│   ├── pages/
│   │   ├── landing.twig        # Homepage
│   │   ├── page.twig           # Generic page template
│   │   ├── single.twig         # Single post template
│   │   ├── archive.twig        # Archive template
│   │   └── 404.twig            # 404 page
│   ├── blocks/
│   │   ├── hero.twig           # Hero section with form
│   │   ├── about.twig          # About/benefits section
│   │   ├── example.twig        # Example report section
│   │   ├── pricing.twig        # Pricing cards
│   │   ├── reviews.twig        # Customer reviews
│   │   └── faq.twig            # FAQ accordion
│   └── partials/
│       ├── header.twig         # Site header
│       └── footer.twig         # Site footer
├── src/
│   └── StarterSite.php         # Theme configuration class
├── functions.php               # Theme entry point
├── index.php                   # Main template file
├── page.php                    # Page template
├── single.php                  # Post template
├── 404.php                     # 404 template
├── archive.php                 # Archive template
├── style.css                   # Theme metadata
└── composer.json               # Timber dependency
```

## Requirements

- PHP 8.0+
- WordPress 6.0+
- Composer

## Installation

```bash
cd themes/couplescompatibility
composer install
```

## Timber/Twig Integration

The theme uses [Timber](https://timber.github.io/docs/v2/) for Twig templating:

```php
// index.php
$context = Timber\Timber::context();
$context['post'] = Timber\Timber::get_post();
$context['fields'] = get_fields(); // ACF fields
Timber\Timber::render('pages/landing.twig', $context);
```

```twig
{# views/pages/landing.twig #}
{% extends "layouts/base.twig" %}

{% block content %}
    {% include "blocks/hero.twig" %}
    {% include "blocks/about.twig" %}
{% endblock %}
```

## Localization

The theme supports multiple languages through Polylang:

- **English (en)** - default
- **Russian (ru)**
- **Ukrainian (uk)**

### How it works:

1. **Polylang** handles page/post translations
2. **ACF** fields are automatically translated per language
3. **Twig** templates access translated content via `{{ fields.field_name }}`

### Language Switcher

Available in header via `pll_the_languages()`:

```twig
{% for lang in languages %}
    <a href="{{ lang.url }}" class="{{ lang.current_lang ? 'active' : '' }}">
        {{ lang.name }}
    </a>
{% endfor %}
```

## Customization

### Adding New Blocks

1. Create new Twig file in `views/blocks/`
2. Include in page template: `{% include "blocks/new-block.twig" %}`
3. Define ACF fields for the block content

### Modifying Styles

The theme uses Tailwind CSS via CDN. Customize in `views/layouts/base.twig`:

```twig
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#6B46C1',
                }
            }
        }
    }
</script>
```

## License

GPL v2 or later
