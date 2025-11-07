-- SQL Skrypt do dodawania przykładowych produktów WooCommerce
-- INSTRUKCJA: Wykonaj ten skrypt w phpMyAdmin lub przez klienta MySQL
-- UWAGA: Dostosuj prefiks tabel zgodnie z Twoją konfiguracją WordPress (domyślnie wp_)

-- Czyści poprzednie produkty testowe (opcjonalne)
-- DELETE FROM wp_posts WHERE post_type = 'product' AND post_name LIKE '%sample%';

-- 1. SŁUCHAWKI BEZPRZEWODOWE PRO
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, post_type, post_content_filtered, menu_order) 
VALUES (1, NOW(), UTC_TIMESTAMP(), 
'<p>Wysokiej jakości słuchawki bezprzewodowe z aktywną redukcją hałasu. Idealne do pracy i rozrywki. Bateria wystarcza na 30 godzin odtwarzania.</p>

<h3>Specyfikacja:</h3>
<ul>
<li>Aktywna redukcja hałasu (ANC)</li>
<li>Bluetooth 5.0</li>
<li>Czas pracy: do 30h</li>
<li>Szybkie ładowanie: 15 min = 3h pracy</li>
<li>Kompatybilność: iOS, Android, Windows</li>
</ul>', 
'Słuchawki Bezprzewodowe Pro', 
'Profesjonalne słuchawki bezprzewodowe z ANC', 
'publish', 'open', 'closed', 'sluchawki-bezprzewodowe-pro', 'product', '', 0);

SET @product1_id = LAST_INSERT_ID();

-- Meta dla produktu 1
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES 
(@product1_id, '_visibility', 'visible'),
(@product1_id, '_stock_status', 'instock'),
(@product1_id, 'total_sales', '0'),
(@product1_id, '_downloadable', 'no'),
(@product1_id, '_virtual', 'no'),
(@product1_id, '_regular_price', '299.99'),
(@product1_id, '_sale_price', '249.99'),
(@product1_id, '_price', '249.99'),
(@product1_id, '_featured', 'no'),
(@product1_id, '_weight', '0.3'),
(@product1_id, '_length', '20'),
(@product1_id, '_width', '18'),
(@product1_id, '_height', '8'),
(@product1_id, '_sku', 'HEADPHONES-001'),
(@product1_id, '_product_attributes', 'a:0:{}'),
(@product1_id, '_sale_price_dates_from', ''),
(@product1_id, '_sale_price_dates_to', ''),
(@product1_id, '_price', '249.99'),
(@product1_id, '_sold_individually', ''),
(@product1_id, '_manage_stock', 'yes'),
(@product1_id, '_stock', '25'),
(@product1_id, '_backorders', 'no'),
(@product1_id, '_low_stock_amount', ''),
(@product1_id, '_stock_status', 'instock');

-- 2. SMARTWATCH FITNESS TRACKER
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, post_type, post_content_filtered, menu_order) 
VALUES (1, NOW(), UTC_TIMESTAMP(), 
'<p>Inteligentny zegarek z monitorowaniem aktywności fizycznej, pomiarem tętna i GPS. Wodoodporny do 50m. Kompatybilny z iOS i Android.</p>

<h3>Funkcje:</h3>
<ul>
<li>Monitor tętna 24/7</li>
<li>GPS + GLONASS</li>
<li>Wodoodporność: 50M</li>
<li>Bateria: do 7 dni</li>
<li>Monitorowanie snu</li>
<li>20+ trybów sportowych</li>
</ul>', 
'Smartwatch Fitness Tracker', 
'Smartwatch z GPS i monitorem tętna', 
'publish', 'open', 'closed', 'smartwatch-fitness-tracker', 'product', '', 0);

SET @product2_id = LAST_INSERT_ID();

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES 
(@product2_id, '_visibility', 'visible'),
(@product2_id, '_stock_status', 'instock'),
(@product2_id, 'total_sales', '0'),
(@product2_id, '_downloadable', 'no'),
(@product2_id, '_virtual', 'no'),
(@product2_id, '_regular_price', '199.99'),
(@product2_id, '_sale_price', ''),
(@product2_id, '_price', '199.99'),
(@product2_id, '_featured', 'no'),
(@product2_id, '_weight', '0.1'),
(@product2_id, '_length', '5'),
(@product2_id, '_width', '4'),
(@product2_id, '_height', '1.2'),
(@product2_id, '_sku', 'SMARTWATCH-002'),
(@product2_id, '_manage_stock', 'yes'),
(@product2_id, '_stock', '15'),
(@product2_id, '_stock_status', 'instock');

-- 3. PLECAK PODRÓŻNY URBAN
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, post_type, post_content_filtered, menu_order) 
VALUES (1, NOW(), UTC_TIMESTAMP(), 
'<p>Stylowy plecak miejski wykonany z wodoodpornego materiału. Posiada kieszeń na laptop do 15", port USB i system organizacji.</p>

<h3>Cechy:</h3>
<ul>
<li>Materiał wodoodporny</li>
<li>Kieszeń na laptop 15"</li>
<li>Port USB do ładowania</li>
<li>System organizacji</li>
<li>Ergonomiczne szelki</li>
<li>Pojemność: 25L</li>
</ul>', 
'Plecak Podróżny Urban', 
'Wodoodporny plecak z kieszenią na laptop', 
'publish', 'open', 'closed', 'plecak-podrozny-urban', 'product', '', 0);

SET @product3_id = LAST_INSERT_ID();

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES 
(@product3_id, '_visibility', 'visible'),
(@product3_id, '_stock_status', 'instock'),
(@product3_id, 'total_sales', '0'),
(@product3_id, '_downloadable', 'no'),
(@product3_id, '_virtual', 'no'),
(@product3_id, '_regular_price', '149.99'),
(@product3_id, '_sale_price', '129.99'),
(@product3_id, '_price', '129.99'),
(@product3_id, '_featured', 'no'),
(@product3_id, '_weight', '0.8'),
(@product3_id, '_length', '45'),
(@product3_id, '_width', '30'),
(@product3_id, '_height', '15'),
(@product3_id, '_sku', 'BACKPACK-003'),
(@product3_id, '_manage_stock', 'yes'),
(@product3_id, '_stock', '30'),
(@product3_id, '_stock_status', 'instock');

-- 4. KAWA ARABICA PREMIUM 1KG
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, post_type, post_content_filtered, menu_order) 
VALUES (1, NOW(), UTC_TIMESTAMP(), 
'<p>Pojedyncze pochodzenie ziaren arabica z Kolumbii. Palona na miejscu, o profilu smakowym z nutami czekolady i orzechów.</p>

<h3>Szczegóły:</h3>
<ul>
<li>100% Arabica</li>
<li>Pochodzenie: Kolumbia</li>
<li>Profil: Czekolada, orzechy</li>
<li>Palenie: Średnie</li>
<li>Świeżo palona</li>
<li>Waga: 1000g</li>
</ul>', 
'Kawa Arabica Premium 1kg', 
'Świeżo palona kawa arabica z Kolumbii', 
'publish', 'open', 'closed', 'kawa-arabica-premium-1kg', 'product', '', 0);

SET @product4_id = LAST_INSERT_ID();

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES 
(@product4_id, '_visibility', 'visible'),
(@product4_id, '_stock_status', 'instock'),
(@product4_id, 'total_sales', '0'),
(@product4_id, '_downloadable', 'no'),
(@product4_id, '_virtual', 'no'),
(@product4_id, '_regular_price', '79.99'),
(@product4_id, '_sale_price', ''),
(@product4_id, '_price', '79.99'),
(@product4_id, '_featured', 'no'),
(@product4_id, '_weight', '1.0'),
(@product4_id, '_length', '20'),
(@product4_id, '_width', '15'),
(@product4_id, '_height', '8'),
(@product4_id, '_sku', 'COFFEE-004'),
(@product4_id, '_manage_stock', 'yes'),
(@product4_id, '_stock', '50'),
(@product4_id, '_stock_status', 'instock');

-- 5. LAMPA BIURKOWA LED SMART
INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_name, post_type, post_content_filtered, menu_order) 
VALUES (1, NOW(), UTC_TIMESTAMP(), 
'<p>Inteligentna lampa biurkowa z regulacją temperatury barwowej i jasności. Sterowana aplikacją mobilną. Idealna do pracy i nauki.</p>

<h3>Właściwości:</h3>
<ul>
<li>Regulacja jasności: 1-100%</li>
<li>Temperatura barwowa: 3000-6000K</li>
<li>Sterowanie aplikacją</li>
<li>Wbudowany timer</li>
<li>Energooszczędna LED</li>
<li>Żywotność: 50,000h</li>
</ul>', 
'Lampa Biurkowa LED Smart', 
'Smart lampa z regulacją światła', 
'publish', 'open', 'closed', 'lampa-biurkowa-led-smart', 'product', '', 0);

SET @product5_id = LAST_INSERT_ID();

INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES 
(@product5_id, '_visibility', 'visible'),
(@product5_id, '_stock_status', 'instock'),
(@product5_id, 'total_sales', '0'),
(@product5_id, '_downloadable', 'no'),
(@product5_id, '_virtual', 'no'),
(@product5_id, '_regular_price', '189.99'),
(@product5_id, '_sale_price', '159.99'),
(@product5_id, '_price', '159.99'),
(@product5_id, '_featured', 'no'),
(@product5_id, '_weight', '1.2'),
(@product5_id, '_length', '25'),
(@product5_id, '_width', '25'),
(@product5_id, '_height', '45'),
(@product5_id, '_sku', 'LAMP-005'),
(@product5_id, '_manage_stock', 'yes'),
(@product5_id, '_stock', '20'),
(@product5_id, '_stock_status', 'instock');

-- TWORZENIE KATEGORII PRODUKTÓW
INSERT INTO wp_terms (name, slug, term_group) VALUES 
('Elektronika', 'elektronika', 0),
('Akcesoria', 'akcesoria', 0),
('Żywność', 'zywnosc', 0),
('Dom i ogród', 'dom-i-ogrod', 0);

SET @cat_elektronika = LAST_INSERT_ID() - 3;
SET @cat_akcesoria = LAST_INSERT_ID() - 2;
SET @cat_zywnosc = LAST_INSERT_ID() - 1;
SET @cat_dom = LAST_INSERT_ID();

INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES 
(@cat_elektronika, 'product_cat', 'Urządzenia elektroniczne', 0, 2),
(@cat_akcesoria, 'product_cat', 'Akcesoria i dodatki', 0, 1),
(@cat_zywnosc, 'product_cat', 'Produkty spożywcze', 0, 1),
(@cat_dom, 'product_cat', 'Artykuły dla domu i ogrodu', 0, 1);

-- PRZYPISYWANIE PRODUKTÓW DO KATEGORII
INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order) VALUES 
(@product1_id, @cat_elektronika, 0),
(@product2_id, @cat_elektronika, 0),
(@product3_id, @cat_akcesoria, 0),
(@product4_id, @cat_zywnosc, 0),
(@product5_id, @cat_dom, 0);

-- DODAWANIE TAGÓW
INSERT INTO wp_terms (name, slug, term_group) VALUES 
('słuchawki', 'sluchawki', 0),
('bezprzewodowe', 'bezprzewodowe', 0),
('audio', 'audio', 0),
('premium', 'premium', 0),
('smartwatch', 'smartwatch', 0),
('fitness', 'fitness', 0),
('sport', 'sport', 0),
('plecak', 'plecak', 0),
('laptop', 'laptop', 0),
('kawa', 'kawa', 0),
('arabica', 'arabica', 0),
('lampa', 'lampa', 0),
('LED', 'led', 0),
('smart', 'smart', 0);

-- PODSUMOWANIE
SELECT 'PRODUKTY TESTOWE ZOSTAŁY DODANE POMYŚLNIE!' as status;
SELECT 
    p.ID, 
    p.post_title as 'Nazwa produktu',
    pm.meta_value as 'Cena',
    pm2.meta_value as 'SKU'
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_price'
LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_sku'
WHERE p.post_type = 'product' 
AND p.ID IN (@product1_id, @product2_id, @product3_id, @product4_id, @product5_id);