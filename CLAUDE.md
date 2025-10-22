# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**RealSatisfied Blocks** is a standalone WordPress plugin providing Gutenberg blocks for real estate testimonials and ratings from RealSatisfied RSS feeds. Built using WordPress Interactivity API with server-side rendering.

**Version:** 1.4.0
**WordPress:** 5.4+ (tested up to 6.7)
**PHP:** 7.4+ (follows WordPress coding standards)

## Core Architecture

### Plugin Structure
```
realsatisfied-blocks/
├── realsatisfied-blocks.php         # Main plugin file (Singleton pattern)
├── includes/                         # PHP classes
│   ├── class-company-rss-parser.php  # Company-wide RSS parser (7-day cache)
│   ├── class-office-rss-parser.php   # Office RSS parser (12-hour cache)
│   ├── class-agent-rss-parser.php    # Agent RSS parser (12-hour cache)
│   ├── class-cloudflare-testimonials-client.php # Cloudflare Worker API client
│   ├── class-rss-cache-manager.php   # Cache management
│   └── class-custom-fields.php       # ACF integration
├── blocks/                           # Gutenberg blocks (4 total)
│   ├── testimonial-marquee/         # Company-wide marquee block
│   ├── office-testimonials/         # Office testimonials with Interactivity API
│   ├── office-ratings/              # Office ratings display
│   └── agent-testimonials/          # Agent testimonials
├── cloudflare-worker/               # Optional Cloudflare edge deployment
└── assets/realsatisfied-blocks.css  # Consolidated styles
```

### Design Patterns

**Singleton Pattern**: All parser classes and main plugin class use `get_instance()` pattern
```php
public static function get_instance() {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

**Server-Side Rendering**: Blocks render PHP HTML, enhanced with WordPress Interactivity API for client-side interactions

**No Build Process**: Vanilla JavaScript using WordPress globals - no webpack, no transpiling

## Development Commands

### Linting and Code Standards
```bash
# Run WordPress coding standards check
composer run lint

# Auto-fix coding standards issues
composer run lint-fix

# PHP syntax check (always run before commits)
php -l realsatisfied-blocks.php
php -l includes/class-*.php
```

### Testing
```bash
# Check plugin syntax
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# WordPress plugin verification (if WP-CLI available)
wp plugin verify-checksums realsatisfied-blocks
```

### Deployment
```bash
# Deploy to production via GitHub Actions (pushes to main branch)
git push origin main

# Deploy via Cloudflare Worker (optional)
cd cloudflare-worker
npx wrangler deploy
```

## RSS Data Processing

### RealSatisfied RSS Feed Structure
- **Company Feed**: `https://rss.realsatisfied.com/rss/company/{company_id}` (aggregates all offices)
- **Office Feed**: `https://rss.realsatisfied.com/rss/office/{vanity_key}`
- **Agent Feed**: `https://rss.realsatisfied.com/rss/agent/{agent_id}`

### Custom XML Namespaces
```php
$namespaces = array(
    'realsatisfied' => 'https://rss.realsatisfied.com/ns/realsatisfied/',
    'atom' => 'http://www.w3.org/2005/Atom',
);
```

**Key Elements**:
- `realsatisfied:overall_satisfaction`
- `realsatisfied:recommendation_rating`
- `realsatisfied:performance_rating`
- `realsatisfied:responseCount`
- `realsatisfied:agent_name`
- `realsatisfied:client_type`
- `realsatisfied:rating`

### Caching Strategy
- **Company Feed**: 7 days (weekly cron refresh)
- **Office/Agent Feeds**: 12 hours
- **Cloudflare Worker**: 7 days at edge + 1 hour WordPress cache
- **Cache Keys**: MD5 hashes of feed URL/parameters
- **Storage**: WordPress transients (`set_transient()`, `get_transient()`)

### Character Encoding Pipeline
Critical for international names and special characters:
```php
// Applied in all parsers via clean_rss_text()
$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
// Plus manual replacements for common encoding artifacts (smart quotes, etc.)
```

## WordPress Interactivity API Integration

### Block Pattern (Office/Agent Testimonials)
```javascript
// blocks/{block-name}/view.js
import { store, getContext } from '@wordpress/interactivity';

store('realsatisfied-office-testimonials', {
    actions: {
        nextPage: () => {
            const context = getContext();
            context.currentPage += 1;
        }
    }
});
```

### Server-Side Context
```php
// In render_block() method
wp_interactivity_state('realsatisfied-office-testimonials', array(
    'currentPage' => 1,
    'itemsPerPage' => $attributes['itemsPerPage'],
));
```

### Asset Loading
```php
// Conditional loading - only when block is present
if (has_block('realsatisfied-blocks/office-testimonials')) {
    wp_enqueue_script_module('realsatisfied-office-testimonials-view');
}
```

## Cloudflare Worker Architecture (Optional)

**Purpose**: Move RSS fetching off WordPress server to Cloudflare edge

### Configuration
Define in `wp-config.php`:
```php
define('RSOB_CLOUDFLARE_API_URL', 'https://api.c21masters.com/testimonials');
define('RSOB_USE_CLOUDFLARE', true);
```

### API Endpoints
- `GET /api/testimonials?company_id={id}` - Fetch testimonials (cached)
- `GET /api/refresh?company_id={id}` - Force refresh (requires API key)
- `GET /api/status` - Check cache status

### Worker Deployment
```bash
cd cloudflare-worker
npx wrangler deploy
```

See [cloudflare-worker/SETUP.md](cloudflare-worker/SETUP.md) for complete setup instructions.

## Block Development Guidelines

### PHP Block Registration
Each block has its own class in `blocks/{block-name}/{block-name}.php`:
```php
class RealSatisfied_Office_Testimonials_Block {
    private $block_name = 'realsatisfied-blocks/office-testimonials';

    public function register_block() {
        register_block_type($this->block_name, array(
            'render_callback' => array($this, 'render_block'),
            'editor_script' => 'realsatisfied-office-testimonials-editor',
            'attributes' => array(/* ... */),
        ));
    }

    public function render_block($attributes, $content, $block) {
        // Return HTML string with proper escaping
    }
}
```

### JavaScript Editor Integration
```javascript
// blocks/{block-name}/{block-name}-editor.js
// Vanilla JS with WordPress globals (no JSX)
wp.blocks.registerBlockType('realsatisfied-blocks/office-testimonials', {
    title: 'Office Testimonials',
    category: 'widgets',
    edit: function(props) {
        return wp.element.createElement(wp.serverSideRender, {
            block: 'realsatisfied-blocks/office-testimonials',
            attributes: props.attributes
        });
    },
    save: function() {
        return null; // Server-side rendered
    }
});
```

### Adding New Blocks
1. Create directory in `blocks/{block-name}/`
2. Create PHP class: `{block-name}.php` with `register_block()` method
3. Create editor script: `{block-name}-editor.js`
4. Create asset file: `{block-name}-editor.asset.php` with dependencies
5. Optional: Create `view.js` for Interactivity API
6. Require in main plugin file: `realsatisfied-blocks.php`
7. Register styles in consolidated CSS: `assets/realsatisfied-blocks.css`

## ACF Custom Fields Integration

### Office Posts
- **Custom Field**: `realsatisfied_feed` (stores vanity key like "CENTURY21-Masters-11")
- **Post Type**: `post_type_685d8ecad6bb5` (check actual post type in production)
- **Usage**: RSS parser constructs URL from vanity key

### Agent Posts
- **Custom Field**: `realsatified-agent-vanity` (stores agent ID)
- **Usage**: Agent RSS parser uses this to fetch agent-specific testimonials

### Fallback to Post Meta
If ACF not available, plugin uses native WordPress post meta via `get_post_meta()`

## Critical Development Rules

### Always Test Before Committing
```bash
# Run syntax check
php -l file.php

# Run coding standards
composer run lint-fix

# Test in local WordPress environment
# Verify no PHP errors in debug log
```

### WordPress Security Standards
- **Escape output**: `esc_html()`, `esc_url()`, `esc_attr()`
- **Sanitize input**: `sanitize_text_field()`, `wp_kses_post()`
- **Nonces**: Use for all AJAX/form actions
- **Capability checks**: `current_user_can('manage_options')`
- **Prepared statements**: For any custom DB queries (though plugin uses WP APIs)

### RSS Error Handling
All RSS parsers return empty arrays on failure, never WP_Error to frontend. Log errors:
```php
if (is_wp_error($feed)) {
    error_log('RSS Feed Error: ' . $feed->get_error_message());
    return array(); // Return empty, don't break frontend
}
```

### Performance Optimization
- **Conditional asset loading**: Only enqueue scripts when blocks present
- **Cache validation**: Always check transients before fetching RSS
- **Intersection Observer**: Pause animations when not visible (marquee block)
- **Limit testimonials**: Default max is 20-50 per block to prevent memory issues

## Common Tasks

### Clear RSS Cache
```php
// Programmatically
delete_transient('rsob_company_feed_' . md5($company_id));

// Or use admin interface
// Settings > RealSatisfied Blocks > Clear Cache
```

### Debug RSS Parsing
```php
// Enable WordPress debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check error logs at wp-content/debug.log
// RSS parsers log detailed error messages
```

### Update Cache Duration
```php
// In parser class constructor
private $cache_duration = 43200; // 12 hours in seconds
```

### Add New RSS Feed Source
1. Create parser class in `includes/class-{type}-rss-parser.php`
2. Extend singleton pattern with `get_instance()`
3. Use `clean_rss_text()` method for encoding
4. Set appropriate cache duration
5. Register XML namespaces
6. Return standardized array format matching existing parsers

## Deployment Process

### GitHub Actions Workflows
- **Test Workflow** (`.github/workflows/test.yml`): Runs on PRs to main/develop
- **Deploy Workflow** (`.github/workflows/deploy.yml`): Runs on push to main or version tags

### Required GitHub Secrets
See [GITHUB-SECRETS-SETUP.md](GITHUB-SECRETS-SETUP.md) for complete list:
- SSH deployment: `SSH_HOST`, `SSH_USERNAME`, `SSH_KEY`, `DEPLOY_PATH`
- Or FTP: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`

### Creating Release
```bash
# Update version in realsatisfied-blocks.php
# Update changelog in README.md
git commit -m "chore: bump version to 1.4.1"
git tag v1.4.1
git push origin main --tags
# GitHub Actions automatically deploys
```

## Integration with Blocksy Theme

Plugin works with Blocksy's dynamic content templates for office pages. Blocks can be added to:
- Single post templates
- Archive templates
- Post loops with dynamic data

Custom fields automatically populate from office/agent post meta.

## Known Issues and Solutions

### Issue: Encoding artifacts in names
**Solution**: All parsers use `clean_rss_text()` method with comprehensive encoding fixes

### Issue: RSS feeds timing out
**Solution**: Cloudflare Worker API reduces load and provides edge caching

### Issue: Too many API requests
**Solution**: Aggressive caching (7-day for company, 12-hour for office/agent)

### Issue: Blocks not appearing in editor
**Solution**: Check `register_block()` is called in main plugin `init` hook

### Issue: Interactivity API not working
**Solution**: Ensure WordPress 6.5+ and use `wp_enqueue_script_module()` not `wp_enqueue_script()`

## File Exclusions from Production

See `.gitignore`:
- `vendor/` (Composer dependencies - dev only)
- `node_modules/` (if any)
- `logs/` (error logs)
- `.env` files
- IDE config (`.vscode/`, `.idea/`)
- Development documentation (keep README.md)

## Support References

- [WordPress Interactivity API Docs](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [Cloudflare Workers Docs](https://developers.cloudflare.com/workers/)
- RealSatisfied RSS Feed Documentation (external - contact RealSatisfied support)
