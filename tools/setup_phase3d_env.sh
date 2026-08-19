#!/usr/bin/env bash
set -e

WP_PATH="/tmp/wordpress-test"

echo "=== Activating Plugins ==="
wp plugin activate woocommerce --path="$WP_PATH" --allow-root || true
wp plugin activate apexseo --path="$WP_PATH" --allow-root

echo "=== Setting Permalinks ==="
wp rewrite structure '/%postname%/' --path="$WP_PATH" --allow-root

echo "=== Populating Core Taxonomy and Posts ==="
# Categories
CAT1=$(wp term create category "Tech News" --porcelain --path="$WP_PATH" --allow-root || wp term get category "Tech News" --field=term_id --path="$WP_PATH" --allow-root)
CAT2=$(wp term create category "Tutorials" --porcelain --path="$WP_PATH" --allow-root || wp term get category "Tutorials" --field=term_id --path="$WP_PATH" --allow-root)
CAT3=$(wp term create category "Case Studies" --porcelain --path="$WP_PATH" --allow-root || wp term get category "Case Studies" --field=term_id --path="$WP_PATH" --allow-root)

# Tags
TAG1=$(wp term create post_tag "SEO" --porcelain --path="$WP_PATH" --allow-root || wp term get post_tag "SEO" --field=term_id --path="$WP_PATH" --allow-root)
TAG2=$(wp term create post_tag "WordPress" --porcelain --path="$WP_PATH" --allow-root || wp term get post_tag "WordPress" --field=term_id --path="$WP_PATH" --allow-root)
TAG3=$(wp term create post_tag "Performance" --porcelain --path="$WP_PATH" --allow-root || wp term get post_tag "Performance" --field=term_id --path="$WP_PATH" --allow-root)

# Posts
POST1=$(wp post create --post_type=post --post_title="Mastering Modern Technical SEO" --post_content="Comprehensive guide to technical SEO in 2026." --post_category="$CAT1" --tags_input="SEO,Performance" --post_status=publish --porcelain --path="$WP_PATH" --allow-root)
POST2=$(wp post create --post_type=post --post_title="Schema Markup Deep Dive" --post_content="How JSON-LD graph structures enhance rich snippets." --post_category="$CAT2" --tags_input="SEO,WordPress" --post_status=publish --porcelain --path="$WP_PATH" --allow-root)
POST3=$(wp post create --post_type=post --post_title="High Performance WordPress Tuning" --post_content="Optimizing database queries, caching, and server stack." --post_category="$CAT3" --tags_input="Performance,WordPress" --post_status=publish --porcelain --path="$WP_PATH" --allow-root)

# Pages
PAGE1=$(wp post create --post_type=page --post_title="About Apex SEO" --post_content="Enterprise SEO engine for modern WordPress." --post_status=publish --porcelain --path="$WP_PATH" --allow-root)
PAGE2=$(wp post create --post_type=page --post_title="Services & Solutions" --post_content="End-to-end SEO analytics and rank tracking." --post_status=publish --porcelain --path="$WP_PATH" --allow-root)
PAGE3=$(wp post create --post_type=page --post_title="Contact Us" --post_content="Get in touch with our engineering team." --post_status=publish --porcelain --path="$WP_PATH" --allow-root)

# Custom Post Type 'book'
mkdir -p "$WP_PATH/wp-content/mu-plugins"
cat << 'EOF' > "$WP_PATH/wp-content/mu-plugins/custom_cpt.php"
<?php
add_action('init', function() {
    register_post_type('book', [
        'public' => true,
        'label' => 'Books',
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
    ]);
});
EOF

# Create a sample CPT item
CPT1=$(wp post create --post_type=book --post_title="The Art of Enterprise SEO" --post_content="Architecting high scale websites." --post_status=publish --porcelain --path="$WP_PATH" --allow-root)

# Create WooCommerce Product
PROD1=$(wp post create --post_type=product --post_title="Apex SEO Enterprise License" --post_content="Complete enterprise SEO automation platform." --post_status=publish --porcelain --path="$WP_PATH" --allow-root)
wp post meta set "$PROD1" _price "299" --path="$WP_PATH" --allow-root
wp post meta set "$PROD1" _regular_price "299" --path="$WP_PATH" --allow-root
wp post meta set "$PROD1" _stock_status "instock" --path="$WP_PATH" --allow-root

echo "=== Environment Provisioning Complete ==="
