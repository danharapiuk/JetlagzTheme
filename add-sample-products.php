<?php

/**
 * Skrypt do dodawania przykładowych produktów WooCommerce
 * 
 * INSTRUKCJA UŻYCIA:
 * 1. Skopiuj ten plik do głównego katalogu WordPress (obok wp-config.php)
 * 2. Odwiedź: https://your-site.com/add-sample-products.php
 * 3. Kliknij "Dodaj produkty przykładowe"
 * 4. Usuń ten plik po użyciu ze względów bezpieczeństwa
 */

// Sprawdź czy WordPress jest załadowany
if (!defined('ABSPATH')) {
    // Załaduj WordPress
    require_once(dirname(__FILE__) . '/../../../wp-config.php');
}

// Sprawdź czy WooCommerce jest aktywny
if (!class_exists('WooCommerce')) {
    die('WooCommerce nie jest zainstalowany lub aktywny!');
}

// Sprawdź czy użytkownik ma uprawnienia administratora
if (!current_user_can('manage_woocommerce')) {
    wp_die('Brak uprawnień do zarządzania produktami!');
}

/**
 * Funkcja do tworzenia przykładowych produktów
 */
function create_sample_products()
{
    $products = array(
        array(
            'name' => 'Słuchawki Bezprzewodowe Pro',
            'description' => 'Wysokiej jakości słuchawki bezprzewodowe z aktywną redukcją hałasu. Idealne do pracy i rozrywki. Bateria wystarcza na 30 godzin odtwarzania.',
            'short_description' => 'Profesjonalne słuchawki bezprzewodowe z ANC',
            'regular_price' => 299.99,
            'sale_price' => 249.99,
            'sku' => 'HEADPHONES-001',
            'stock_quantity' => 25,
            'category' => 'Elektronika',
            'tags' => array('słuchawki', 'bezprzewodowe', 'audio', 'premium'),
            'weight' => 0.3,
            'dimensions' => array('length' => 20, 'width' => 18, 'height' => 8),
        ),
        array(
            'name' => 'Smartwatch Fitness Tracker',
            'description' => 'Inteligentny zegarek z monitorowaniem aktywności fizycznej, pomiarem tętna i GPS. Wodoodporny do 50m. Kompatybilny z iOS i Android.',
            'short_description' => 'Smartwatch z GPS i monitorem tętna',
            'regular_price' => 199.99,
            'sale_price' => null,
            'sku' => 'SMARTWATCH-002',
            'stock_quantity' => 15,
            'category' => 'Elektronika',
            'tags' => array('smartwatch', 'fitness', 'sport', 'zdrowie'),
            'weight' => 0.1,
            'dimensions' => array('length' => 5, 'width' => 4, 'height' => 1.2),
        ),
        array(
            'name' => 'Plecak Podróżny Urban',
            'description' => 'Stylowy plecak miejski wykonany z wodoodpornego materiału. Posiada kieszeń na laptop do 15", port USB i system organizacji.',
            'short_description' => 'Wodoodporny plecak z kieszenią na laptop',
            'regular_price' => 149.99,
            'sale_price' => 129.99,
            'sku' => 'BACKPACK-003',
            'stock_quantity' => 30,
            'category' => 'Akcesoria',
            'tags' => array('plecak', 'podróże', 'laptop', 'miejski'),
            'weight' => 0.8,
            'dimensions' => array('length' => 45, 'width' => 30, 'height' => 15),
        ),
        array(
            'name' => 'Kawa Arabica Premium 1kg',
            'description' => 'Pojedyncze pochodzenie ziaren arabica z Kolumbii. Palona na miejscu, o profilu smakowym z nutami czekolady i orzechów.',
            'short_description' => 'Świeżo palona kawa arabica z Kolumbii',
            'regular_price' => 79.99,
            'sale_price' => null,
            'sku' => 'COFFEE-004',
            'stock_quantity' => 50,
            'category' => 'Żywność',
            'tags' => array('kawa', 'arabica', 'premium', 'świeża'),
            'weight' => 1.0,
            'dimensions' => array('length' => 20, 'width' => 15, 'height' => 8),
        ),
        array(
            'name' => 'Lampa Biurkowa LED Smart',
            'description' => 'Inteligentna lampa biurkowa z regulacją temperatury barwowej i jasności. Sterowana aplikacją mobilną. Idealna do pracy i nauki.',
            'short_description' => 'Smart lampa z regulacją światła',
            'regular_price' => 189.99,
            'sale_price' => 159.99,
            'sku' => 'LAMP-005',
            'stock_quantity' => 20,
            'category' => 'Dom i ogród',
            'tags' => array('lampa', 'LED', 'smart', 'biuro'),
            'weight' => 1.2,
            'dimensions' => array('length' => 25, 'width' => 25, 'height' => 45),
        ),
    );

    $created_products = array();

    foreach ($products as $product_data) {
        // Tworzenie produktu
        $product = new WC_Product_Simple();

        // Podstawowe informacje
        $product->set_name($product_data['name']);
        $product->set_description($product_data['description']);
        $product->set_short_description($product_data['short_description']);
        $product->set_sku($product_data['sku']);

        // Ceny
        $product->set_regular_price($product_data['regular_price']);
        if ($product_data['sale_price']) {
            $product->set_sale_price($product_data['sale_price']);
        }

        // Inwentarz
        $product->set_stock_quantity($product_data['stock_quantity']);
        $product->set_manage_stock(true);
        $product->set_stock_status('instock');

        // Wysyłka
        $product->set_weight($product_data['weight']);
        $product->set_length($product_data['dimensions']['length']);
        $product->set_width($product_data['dimensions']['width']);
        $product->set_height($product_data['dimensions']['height']);

        // Status i katalog
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_featured(false);

        // Zapisz produkt
        $product_id = $product->save();

        if ($product_id) {
            // Dodaj do kategorii
            $category_term = get_term_by('name', $product_data['category'], 'product_cat');
            if (!$category_term) {
                // Utwórz kategorię jeśli nie istnieje
                $category_term = wp_insert_term($product_data['category'], 'product_cat');
                if (!is_wp_error($category_term)) {
                    wp_set_post_terms($product_id, array($category_term['term_id']), 'product_cat');
                }
            } else {
                wp_set_post_terms($product_id, array($category_term->term_id), 'product_cat');
            }

            // Dodaj tagi
            wp_set_post_terms($product_id, $product_data['tags'], 'product_tag');

            // Ustaw featured image (placeholder)
            set_placeholder_image($product_id, $product_data['name']);

            $created_products[] = array(
                'id' => $product_id,
                'name' => $product_data['name'],
                'price' => $product_data['sale_price'] ?: $product_data['regular_price']
            );
        }
    }

    return $created_products;
}

/**
 * Funkcja do ustawiania placeholder obrazka
 */
function set_placeholder_image($product_id, $product_name)
{
    // Tworzymy placeholder używając usługi placeholder.com
    $placeholder_url = 'https://via.placeholder.com/600x600/7f54b3/ffffff?text=' . urlencode($product_name);

    // W prawdziwym środowisku, lepiej byłoby pobrać i zapisać obraz
    // Tutaj tylko ustawiamy meta dla demonstracji
    update_post_meta($product_id, '_thumbnail_id', 0);
}

/**
 * Funkcja do usuwania wszystkich produktów testowych
 */
function remove_sample_products()
{
    $sample_skus = array('HEADPHONES-001', 'SMARTWATCH-002', 'BACKPACK-003', 'COFFEE-004', 'LAMP-005');
    $removed_count = 0;

    foreach ($sample_skus as $sku) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id) {
            wp_delete_post($product_id, true);
            $removed_count++;
        }
    }

    return $removed_count;
}

// Obsługa akcji
$message = '';
$products = array();

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && wp_verify_nonce($_POST['nonce'], 'sample_products')) {
        $products = create_sample_products();
        $message = 'Utworzono ' . count($products) . ' przykładowych produktów!';
    } elseif ($_POST['action'] === 'remove' && wp_verify_nonce($_POST['nonce'], 'sample_products')) {
        $removed_count = remove_sample_products();
        $message = 'Usunięto ' . $removed_count . ' produktów testowych.';
    }
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Produkty Przykładowe - WooCommerce</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            line-height: 1.6;
            color: #333;
        }

        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        h1 {
            color: #7f54b3;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }

        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }

        .button {
            background: #7f54b3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 1rem;
            margin-bottom: 1rem;
        }

        .button:hover {
            background: #6d47a1;
        }

        .button.danger {
            background: #dc3545;
        }

        .button.danger:hover {
            background: #c82333;
        }

        .products-list {
            margin-top: 2rem;
        }

        .product-item {
            background: #f8f9fa;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            border-left: 4px solid #7f54b3;
        }

        .product-name {
            font-weight: bold;
            color: #7f54b3;
        }

        .product-price {
            color: #28a745;
            font-weight: bold;
        }

        .instructions {
            background: #e9ecef;
            padding: 1.5rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }

        .instructions h3 {
            margin-top: 0;
            color: #495057;
        }

        .instructions ol {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🛒 Kreator Produktów Przykładowych</h1>

        <div class="instructions">
            <h3>📋 Instrukcja:</h3>
            <ol>
                <li>Kliknij "Dodaj produkty przykładowe" aby utworzyć 5 testowych produktów</li>
                <li>Produkty będą dostępne w panelu WooCommerce i na stronie sklepu</li>
                <li>Możesz testować funkcjonalność one-click checkout i cross-sell</li>
                <li>Użyj "Usuń produkty testowe" aby wyczyścić sklep</li>
                <li><strong>Usuń ten plik po zakończeniu testów!</strong></li>
            </ol>
        </div>

        <div class="warning">
            ⚠️ <strong>Bezpieczeństwo:</strong> Ten plik powinien być usunięty po dodaniu produktów ze względów bezpieczeństwa!
        </div>

        <?php if ($message): ?>
            <div class="success">
                ✅ <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('sample_products', 'nonce'); ?>

            <button type="submit" name="action" value="create" class="button">
                ➕ Dodaj Produkty Przykładowe
            </button>

            <button type="submit" name="action" value="remove" class="button danger"
                onclick="return confirm('Czy na pewno chcesz usunąć wszystkie produkty testowe?')">
                🗑️ Usuń Produkty Testowe
            </button>
        </form>

        <?php if (!empty($products)): ?>
            <div class="products-list">
                <h3>📦 Utworzone Produkty:</h3>
                <?php foreach ($products as $product): ?>
                    <div class="product-item">
                        <div class="product-name"><?php echo esc_html($product['name']); ?></div>
                        <div class="product-price"><?php echo number_format($product['price'], 2); ?> PLN</div>
                        <small>ID: <?php echo $product['id']; ?></small>
                    </div>
                <?php endforeach; ?>

                <p><strong>🎯 Co dalej?</strong></p>
                <ul>
                    <li>Idź do <a href="<?php echo admin_url('edit.php?post_type=product'); ?>">Produkty → Wszystkie produkty</a> w panelu administracyjnym</li>
                    <li>Sprawdź <a href="<?php echo wc_get_page_permalink('shop'); ?>">stronę sklepu</a></li>
                    <li>Przetestuj funkcjonalność one-click checkout</li>
                    <li>Sprawdź cross-sell na stronie checkout</li>
                </ul>
            </div>
        <?php endif; ?>

        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #dee2e6; text-align: center; color: #6c757d;">
            <p><strong>Jetlagz Universal Theme</strong> - System zarządzania produktami testowymi</p>
            <p>Wersja: 1.0.0</p>
        </div>
    </div>
</body>

</html>