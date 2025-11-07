<?php

/**
 * QUICK PRODUCT CREATOR - Szybki kreator produktów
 * 
 * Prostszy sposób dodania produktów testowych
 * Skopiuj ten plik do głównego katalogu WordPress i uruchom w przeglądarce
 */

// Sprawdź czy to jest środowisko WordPress
$wp_config_path = '';
$possible_paths = [
    __DIR__ . '/../../../wp-config.php',
    __DIR__ . '/../../wp-config.php',
    __DIR__ . '/../wp-config.php',
    __DIR__ . '/wp-config.php'
];

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $wp_config_path = $path;
        break;
    }
}

if ($wp_config_path) {
    require_once($wp_config_path);
} else {
    // Fallback - pokaż instrukcje
    show_instructions();
    exit;
}

function show_instructions()
{
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Instrukcje Dodawania Produktów</title>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
            }

            .box {
                background: #f0f0f0;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }

            .warning {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
            }

            .info {
                background: #d1ecf1;
                border-left: 4px solid #17a2b8;
            }

            code {
                background: #e9ecef;
                padding: 2px 5px;
                border-radius: 3px;
            }
        </style>
    </head>

    <body>
        <h1>🛒 Dodawanie Produktów Testowych</h1>

        <div class="box warning">
            <h3>⚠️ Nie znaleziono WordPress</h3>
            <p>Plik nie może automatycznie znaleźć instalacji WordPress.</p>
        </div>

        <div class="box info">
            <h3>📋 Instrukcje Ręczne:</h3>

            <h4>Opcja 1: Panel administracyjny WordPress</h4>
            <ol>
                <li>Zaloguj się do panelu WordPress</li>
                <li>Idź do <strong>Produkty → Dodaj nowy</strong></li>
                <li>Dodaj następujące produkty ręcznie:</li>
            </ol>

            <h4>Produkty do dodania:</h4>
            <ul>
                <li><strong>Słuchawki Bezprzewodowe Pro</strong> - 299.99 PLN (promocja: 249.99 PLN)</li>
                <li><strong>Smartwatch Fitness Tracker</strong> - 199.99 PLN</li>
                <li><strong>Plecak Podróżny Urban</strong> - 149.99 PLN (promocja: 129.99 PLN)</li>
                <li><strong>Kawa Arabica Premium 1kg</strong> - 79.99 PLN</li>
                <li><strong>Lampa Biurkowa LED Smart</strong> - 189.99 PLN (promocja: 159.99 PLN)</li>
            </ul>

            <h4>Opcja 2: Użyj pliku SQL</h4>
            <ol>
                <li>Otwórz plik <code>sample-products.sql</code></li>
                <li>Wykonaj go w phpMyAdmin lub przez MySQL</li>
                <li>Upewnij się, że prefiks tabel jest poprawny (wp_ lub inny)</li>
            </ol>

            <h4>Opcja 3: WooCommerce Sample Data</h4>
            <ol>
                <li>Idź do <strong>WooCommerce → Status → Narzędzia</strong></li>
                <li>Znajdź "Utwórz dane przykładowe"</li>
                <li>Kliknij "Utwórz przykładowe produkty"</li>
            </ol>
        </div>

        <div class="box">
            <h3>🎯 Testowanie Funkcjonalności</h3>
            <p>Po dodaniu produktów będziesz mógł przetestować:</p>
            <ul>
                <li>✅ One-click checkout (Dodaj do koszyka i przejdź do płatności)</li>
                <li>✅ Custom checkout layout (2-kolumnowy layout)</li>
                <li>✅ Cross-sell products na checkout</li>
                <li>✅ Free shipping progress bar</li>
            </ul>
        </div>
    </body>

    </html>
<?php
}

// Sprawdź czy WooCommerce jest dostępny
if (!function_exists('wc_get_products')) {
    echo '<h1>WooCommerce nie jest zainstalowany!</h1>';
    echo '<p>Zainstaluj i aktywuj wtyczkę WooCommerce przed uruchomieniem tego skryptu.</p>';
    exit;
}

// Główna funkcja tworzenia produktów
function create_quick_products()
{
    $products_data = [
        [
            'name' => 'Słuchawki Bezprzewodowe Pro',
            'price' => 299.99,
            'sale_price' => 249.99,
            'sku' => 'HEADPHONES-001',
            'stock' => 25
        ],
        [
            'name' => 'Smartwatch Fitness Tracker',
            'price' => 199.99,
            'sale_price' => '',
            'sku' => 'SMARTWATCH-002',
            'stock' => 15
        ],
        [
            'name' => 'Plecak Podróżny Urban',
            'price' => 149.99,
            'sale_price' => 129.99,
            'sku' => 'BACKPACK-003',
            'stock' => 30
        ],
        [
            'name' => 'Kawa Arabica Premium 1kg',
            'price' => 79.99,
            'sale_price' => '',
            'sku' => 'COFFEE-004',
            'stock' => 50
        ],
        [
            'name' => 'Lampa Biurkowa LED Smart',
            'price' => 189.99,
            'sale_price' => 159.99,
            'sku' => 'LAMP-005',
            'stock' => 20
        ]
    ];

    $created = [];

    foreach ($products_data as $data) {
        $product = new WC_Product_Simple();
        $product->set_name($data['name']);
        $product->set_regular_price($data['price']);
        if (!empty($data['sale_price'])) {
            $product->set_sale_price($data['sale_price']);
        }
        $product->set_sku($data['sku']);
        $product->set_stock_quantity($data['stock']);
        $product->set_manage_stock(true);
        $product->set_stock_status('instock');
        $product->set_status('publish');

        $product_id = $product->save();
        if ($product_id) {
            $created[] = [
                'id' => $product_id,
                'name' => $data['name'],
                'price' => $data['sale_price'] ?: $data['price']
            ];
        }
    }

    return $created;
}

// Obsługa formularza
$action_result = '';
if (isset($_POST['create_products'])) {
    $created = create_quick_products();
    $action_result = 'Utworzono ' . count($created) . ' produktów!';
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Quick Product Creator</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .button {
            background: #007cba;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .button:hover {
            background: #005a87;
        }

        .product-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <h1>🚀 Quick Product Creator</h1>

    <?php if ($action_result): ?>
        <div class="success"><?php echo $action_result; ?></div>
    <?php endif; ?>

    <div class="product-list">
        <h3>Produkty do utworzenia:</h3>
        <ul>
            <li>Słuchawki Bezprzewodowe Pro (249.99 PLN)</li>
            <li>Smartwatch Fitness Tracker (199.99 PLN)</li>
            <li>Plecak Podróżny Urban (129.99 PLN)</li>
            <li>Kawa Arabica Premium 1kg (79.99 PLN)</li>
            <li>Lampa Biurkowa LED Smart (159.99 PLN)</li>
        </ul>
    </div>

    <form method="post">
        <button type="submit" name="create_products" class="button">
            ✨ Utwórz 5 Produktów Testowych
        </button>
    </form>

    <p><small>💡 Po utworzeniu produktów odwiedź sklep i przetestuj funkcjonalność one-click checkout!</small></p>
</body>

</html>