# **Project Specification: High-Performance "Markdown-First" WordPress Theme**

## **1. Executive Summary**

**Objective:** Develop a lean, high-performance WordPress theme with zero technical debt, utilizing strict static analysis and modern CI/CD practices equivalent to a production-grade Python environment.

**Core Philosophy:**

1. **Pythonic Rigor:** All code is strictly typed, linted, and statically analyzed.  
2. **Explicit > Implicit:** We reject the "Child Theme" inheritance model in favor of a standalone, scaffolded codebase to prevent "upstream bloat."  
3. **Data > DOM:** Content is treated as raw data (Markdown), avoiding the tight coupling of the Gutenberg Block Editor's HTML serialization.

## **2. Architecture & Stack Decisions**

### **2.1 The Theme Structure: Standalone Block Theme**

We will build a **Standalone Block Theme** (FSE) from the ground up, bypassing the *Twenty Twenty-Five* inheritance model.

* **Rationale:** Child themes in the Block Era inherit unwanted CSS variables, assets, and theme.json configurations that pollute the DOM. A standalone theme ensures only defined assets are loaded.  
* **Scaffolding Tool:** @wordpress/create-block.  
* **Configuration:** theme.json serves as the strict schema for the site, explicitly disabling Core bloat (e.g., default palettes, gradients, and duotone filters).

### **2.2 The Content Engine: "Headless-in-a-Theme"**

The theme will bypass the standard Gutenberg block storage for post content, opting for a runtime Markdown rendering pipeline.

* **Data Source:** post_meta (Key: _markdown_content).  
* **Parser:** erusev/parsedown (PHP-based, fast, compliant).  
* **Caching Layer:** WordPress Transients API (memoizing parsed HTML).  
* **Styling:** github-markdown-css (or custom typography CSS) applied to the parsed output container.

## **3. The Quality Assurance Stack (Tooling)**

This project enforces code quality using PHP equivalents to standard Python utilities.

| Domain | Python Tool | WordPress/PHP Equivalent | Configuration Notes |
| :---- | :---- | :---- | :---- |
| **Linting** | Ruff | **PHPCS** + **WPCS** | Enforces "WordPress Coding Standards" (Late Escaping, Naming Conventions). |
| **Refactoring** | Ruff / Black | **Rector** | Automated code upgrades and "Dead Code" removal. |
| **Type Safety** | Mypy | **PHPStan** | Level 5+. Must use szepeviktor/phpstan-wordpress to handle WP globals. |
| **Architecture** | Grimp | **Deptrac** | Enforces layer separation (e.g., *Templates* cannot query DB). |
| **Testing** | Pytest | **Pest PHP** | Functional testing syntax. |
| **Deps** | Poetry/Pip | **Composer** | Dependency management. composer install --no-dev for production. |

## **4. Implementation Specification**

### **4.1 Dependency Management (composer.json)**

The root of the theme will contain the build manifest.

{  
    "name": "christopherspenn/crispy-theme",  
    "description": "A high-performance Markdown-first WordPress theme.",  
    "type": "wordpress-theme",  
    "require": {  
        "php": ">=8.1",  
        "erusev/parsedown": "^1.7"  
    },  
    "require-dev": {  
        "squizlabs/php_codesniffer": "^3.7",  
        "wp-coding-standards/wpcs": "^3.0",  
        "phpstan/phpstan": "^1.10",  
        "szepeviktor/phpstan-wordpress": "^1.3",  
        "pestphp/pest": "^2.0",  
        "rector/rector": "^0.18"  
    },  
    "scripts": {  
        "lint": "phpcs --standard=WordPress src/",  
        "analyze": "phpstan analyse src/ --level=6",  
        "test": "pest",  
        "format": "phpcbf --standard=WordPress src/"  
    }  
}

### **4.2 The Markdown Rendering Engine (src/MarkdownEngine.php)**

This logic replaces the standard the_content loop. It implements the "read-through" cache pattern.

<?php

namespace MyThemeEngine;

use ErusevParsedown;

class MarkdownRenderer {  
      
    public function init(): void {  
        add_filter('the_content', [$this, 'render_content'], 10);  
        add_action('save_post', [$this, 'clear_cache'], 10);  
    }

    public function render_content(string $default_content): string {  
        if (!is_singular('post')) {  
            return $default_content;  
        }

        $post_id = get_the_ID();  
        $markdown = get_post_meta($post_id, '_markdown_content', true);

        // Fallback to standard content if Meta is empty  
        if (empty($markdown) || !is_string($markdown)) {  
            return $default_content;  
        }

        // Cache Key Strategy: ID + Hash of content ensures versions don't clash  
        $cache_key = 'md_render_' . $post_id . '_' . md5($markdown);  
        $cached_html = get_transient($cache_key);

        if (false !== $cached_html) {  
            return (string) $cached_html;  
        }

        // Parse and Cache  
        $parser = new Parsedown();  
        $parser->setSafeMode(true);  
        $html = '<div class="markdown-body">' . $parser->text($markdown) . '</div>';  
          
        set_transient($cache_key, $html, DAY_IN_SECONDS);

        return $html;  
    }

    public function clear_cache(int $post_id): void {  
        $markdown = get_post_meta($post_id, '_markdown_content', true);  
        if ($markdown) {  
            $cache_key = 'md_render_' . $post_id . '_' . md5($markdown);  
            delete_transient($cache_key);  
        }  
    }  
}

### **4.3 Content Injection Methods**

We need to support two ways of inputting content: via CLI (for automated publishing) and via a minimal WP Admin interface (replacing the Block Editor).

**A. WP-CLI Integration (Dev/CI Workflow)**

# Push content from local file to WordPress  
wp post update 123 --meta_input='{"_markdown_content": "'"$(cat my-post.md)"'"}'

B. Admin Interface (Optional Fallback)  
A simple Metabox that hides the Block Editor.  
// In src/Admin/Editor.php  
public function register_metabox(): void {  
    add_meta_box(  
        'markdown_editor',  
        'Markdown Source',  
        function ($post) {  
            $value = get_post_meta($post->ID, '_markdown_content', true);  
            echo '<textarea name="md_source" class="large-text code" rows="20">' . esc_textarea($value) . '</textarea>';  
        },  
        'post',  
        'normal',  
        'high'  
    );  
}

## **5. Development Roadmap**

### **Phase 1: Scaffolding & Tooling**

1. Run npx @wordpress/create-block theme my-lean-theme --variant theme.  
2. Initialize composer init.  
3. Install PHPStan, PHPCS, and Pest.  
4. Configure phpstan.neon and phpcs.xml to ignore the vendor directory.

### **Phase 2: Core Logic Implementation**

1. Create src/ directory.  
2. Implement MarkdownRenderer class.  
3. Implement Admin/Editor class to handle the metaboxes.  
4. Update functions.php to autoload classes via Composer and instantiate the engine.

### **Phase 3: Frontend & Design**

1. Strip theme.json of all default WP presets (colors, gradients).  
2. Add github-markdown-css or a minimal Typography CSS module to style.css.  
3. Ensure the template (templates/single.html) uses the standard Post Content block (which our filter will hijack).

### **Phase 4: CI/CD Pipeline (GitHub Actions)**

1. **On Pull Request:** Run composer lint (PHPCS) and composer analyze (PHPStan). Fail build on errors.  
2. **On Merge:** Run composer install --no-dev.  
3. **Artifact:** Zip the theme (excluding tests, src/dev-tools, and .git).