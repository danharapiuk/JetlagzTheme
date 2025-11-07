# 🛒 Dodawanie Produktów Testowych

## 3 Sposoby Dodania Przykładowych Produktów

### 🚀 Sposób 1: Automatyczny Kreator (Zalecany)

1. **Skopiuj plik do WordPress:**
   ```bash
   cp add-sample-products.php /path/to/wordpress/
   ```

2. **Odwiedź w przeglądarce:**
   ```
   https://your-site.com/add-sample-products.php
   ```

3. **Kliknij "Dodaj produkty przykładowe"**

4. **Usuń plik po użyciu!** (ze względów bezpieczeństwa)

### 💾 Sposób 2: Import SQL

1. **Otwórz phpMyAdmin** lub inny klient MySQL

2. **Wykonaj skrypt SQL:**
   ```sql
   source sample-products.sql;
   ```

3. **Sprawdź prefiks tabel** (wp_ lub inny)

### ✋ Sposób 3: Ręczne Dodawanie

Idź do **Produkty → Dodaj nowy** w panelu WordPress i dodaj:

#### 📱 Słuchawki Bezprzewodowe Pro
- **Cena regularna:** 299.99 PLN
- **Cena promocyjna:** 249.99 PLN
- **SKU:** HEADPHONES-001
- **Stan magazynowy:** 25 szt.
- **Kategoria:** Elektronika

#### ⌚ Smartwatch Fitness Tracker
- **Cena:** 199.99 PLN
- **SKU:** SMARTWATCH-002
- **Stan magazynowy:** 15 szt.
- **Kategoria:** Elektronika

#### 🎒 Plecak Podróżny Urban
- **Cena regularna:** 149.99 PLN
- **Cena promocyjna:** 129.99 PLN
- **SKU:** BACKPACK-003
- **Stan magazynowy:** 30 szt.
- **Kategoria:** Akcesoria

#### ☕ Kawa Arabica Premium 1kg
- **Cena:** 79.99 PLN
- **SKU:** COFFEE-004
- **Stan magazynowy:** 50 szt.
- **Kategoria:** Żywność

#### 💡 Lampa Biurkowa LED Smart
- **Cena regularna:** 189.99 PLN
- **Cena promocyjna:** 159.99 PLN
- **SKU:** LAMP-005
- **Stan magazynowy:** 20 szt.
- **Kategoria:** Dom i ogród

## 🎯 Testowanie Funkcjonalności

Po dodaniu produktów możesz przetestować:

### ✅ One-Click Checkout
- Idź na stronę sklepu lub produktu
- Kliknij przycisk "Dodaj do koszyka i przejdź do płatności"
- Sprawdź czy przekierowanie działa

### ✅ Custom Checkout Layout
- Przejdź przez proces checkout
- Sprawdź 2-kolumnowy layout
- Sprawdź responsywność na mobile

### ✅ Cross-sell System
- Dodaj produkty do koszyka (łącznie ponad 100 PLN)
- Idź na checkout
- Sprawdź sekcję "Polecane produkty" pod podsumowaniem
- Sprawdź pasek postępu darmowej dostawy
- Przetestuj dodawanie produktów cross-sell

### ✅ Free Shipping Progress
- Dodaj produkty o wartości poniżej 100 PLN
- Sprawdź pasek postępu na checkout
- Dodaj więcej produktów i sprawdź aktualizację paska

## 📊 Podsumowanie Wartości

| Produkt | Cena regularna | Cena promocyjna | Wartość do free shipping |
|---------|----------------|-----------------|-------------------------|
| Słuchawki | 299.99 | **249.99** | ✅ Przekracza próg |
| Smartwatch | **199.99** | - | ✅ Przekracza próg |
| Plecak | 149.99 | **129.99** | ✅ Przekracza próg |
| Kawa | **79.99** | - | ⚠️ Potrzeba +20 PLN |
| Lampa | 189.99 | **159.99** | ✅ Przekracza próg |

### 💰 Kombinacje Testowe:
- **Kawa + Plecak** = 209.98 PLN → ✅ Darmowa dostawa
- **Kawa + dowolny inny** = 159.98+ PLN → ✅ Darmowa dostawa
- **Tylko Kawa** = 79.99 PLN → ⚠️ Brakuje 20.01 PLN

## 🧹 Usuwanie Produktów Testowych

### Przez Kreator:
1. Odwiedź `add-sample-products.php`
2. Kliknij "Usuń Produkty Testowe"

### Przez Panel WordPress:
1. Produkty → Wszystkie produkty
2. Zaznacz produkty z SKU: HEADPHONES-001, SMARTWATCH-002, etc.
3. Usuń masowo

### Przez SQL:
```sql
DELETE FROM wp_posts WHERE post_type = 'product' AND post_name LIKE '%sample%';
```

## ⚠️ Bezpieczeństwo

**WAŻNE:** Usuń pliki `add-sample-products.php` i `quick-products.php` po dodaniu produktów!

```bash
rm add-sample-products.php quick-products.php
```

## 🎨 Customizacja

Możesz edytować produkty w `add-sample-products.php` przed uruchomieniem:
- Zmień ceny
- Dodaj więcej produktów
- Dostosuj kategorie
- Zmień opisy

---

**Jetlagz Universal Theme v1.0.0**  
*System testowania produktów WooCommerce*