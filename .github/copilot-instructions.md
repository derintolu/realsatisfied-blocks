# RealSatisfied Blocks - AI Development Guide

## Project Architecture

This is a **standalone WordPress Gutenberg blocks plugin** for real estate testimonials and ratings from RealSatisfied RSS feeds. The plugin evolved from widget-based legacy code to modern WordPress Interactivity API.

### Core Components

- **Main Plugin**: `realsatisfied-blocks.php` - Singleton pattern, manages block registration and asset loading
- **RSS Parsers**: `includes/class-{company|office|agent}-rss-parser.php` - Singleton RSS processors with 12-hour caching
- **Blocks**: `blocks/{block-name}/{block-name}.php` - Server-side rendering classes with block registration
- **Frontend**: `blocks/{block-name}/view.js` - WordPress Interactivity API stores (ES6 modules)
- **Editor**: `blocks/{block-name}/{block-name}-editor.js` - Vanilla JS Gutenberg editor integration

## WordPress Integration Patterns

### Block Registration (PHP-based, not block.json)
```php
// Each block class handles its own registration
$block = new RealSatisfied_Office_Testimonials_Block();
$block->register_block();
```

### Asset Loading Strategy
- **Conditional Loading**: Scripts only load when blocks are present (`has_block()` checks)
- **Interactivity API**: Use `wp_enqueue_script_module()` for view.js files
- **Editor Scripts**: Standard `wp_register_script()` with dependency arrays

### RSS Data Flow
1. **Caching**: 12-hour WordPress transients (`set_transient()`)
2. **Namespaces**: Custom XML namespace handling for RealSatisfied data
3. **Encoding**: Comprehensive text cleaning for international characters
4. **Error Handling**: Graceful fallbacks for RSS failures

## Development Conventions

### File Structure
```
blocks/{block-name}/
├── {block-name}.php          # Server-side rendering class
├── {block-name}-editor.js    # Gutenberg editor integration
├── {block-name}-editor.asset.php # Dependencies file
└── view.js                   # WordPress Interactivity API store
```

### JavaScript Patterns
- **Editor**: Vanilla JS with WordPress globals (`wp.blocks.registerBlockType`)
- **Frontend**: ES6 modules importing `@wordpress/interactivity`
- **No Build Process**: Direct browser-compatible JavaScript

### PHP Architecture
- **Singleton Pattern**: All parser classes use `get_instance()`
- **Server-Side Rendering**: Blocks return HTML from `render_block()` method
- **WordPress Standards**: Proper escaping, nonces, and WordPress hooks

## Block-Specific Implementation

### Testimonial Marquee (Company-wide)
- **Data Source**: Company RSS feed aggregating all offices
- **Animation**: Dual-row infinite scroll with GPU acceleration
- **Performance**: Intersection Observer for viewport-based control

### Office/Agent Testimonials
- **Data Sources**: Office or agent-specific RSS feeds
- **Interactivity**: Pagination, filtering, sorting via Interactivity API
- **ACF Integration**: Custom field support for vanity keys

### Office Ratings
- **Simple Display**: Statistical ratings without complex interactions
- **ServerSideRender**: Live preview in editor

## Critical Technical Details

### Character Encoding Pipeline
```php
// Essential for international names and special characters
$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
```

### WordPress Interactivity API Store Pattern
```javascript
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

### RSS Parser Singleton
```php
class RealSatisfied_Office_RSS_Parser {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

## Integration Points

- **ACF Fields**: `realsatisfied_feed` custom field for vanity keys
- **Custom Post Types**: Office, agent, and company post types
- **WordPress Transients**: RSS caching with MD5 cache keys
- **WordPress Hooks**: `init` for registration, conditional asset enqueuing

## Development Workflow

1. **Always check existing RSS parsers** before adding new data sources
2. **Use ServerSideRender** for editor previews with live data
3. **Test character encoding** with international names in testimonials
4. **Verify caching behavior** - RSS feeds cached for 12 hours
5. **Conditional asset loading** - only enqueue scripts when blocks present

## Performance Considerations

- **12-hour RSS cache** prevents API abuse
- **Intersection Observer** pauses animations when not visible
- **Conditional script loading** reduces payload
- **WordPress Interactivity API** smaller than jQuery alternatives
