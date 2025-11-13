# Checkout Review Order Table - Nowy Layout

## Zmiany wprowadzone

### 1. Nowa struktura HTML (`inc/checkout-table-custom.php`)
- Ukryto domyślną tabelę WooCommerce checkout
- Stworzono custom layout z 3 kolumnami:
  - **Kolumna 1**: Miniaturka produktu (80x80px)
  - **Kolumna 2**: Nazwa produktu + Sterowniki ilości (-, liczba, +)
  - **Kolumna 3**: Cena jednostkowa + Cena całkowita (jeśli ilość > 1)

### 2. CSS (`style.css`)
Dodano style dla:
- `.universal-checkout-review-wrapper` - kontener
- `.universal-checkout-item` - grid layout produktu
- `.checkout-item-*` - style dla poszczególnych sekcji
- Responsywność: Desktop, Tablet (1024px), Mobile (767px)

### 3. JavaScript (`assets/js/checkout-quantity-classic.js`)
Zaktualizowano do nowego layoutu:
- Nowe selektory: `.checkout-item-quantity-controls .qty-btn`
- Pobiera `data-cart-key` zamiast `cart_item_key`
- Aktualizuje `data-qty` atrybut w `qty-display`
- Wyzwala `update_checkout` po zmianach

### 4. Include (`functions.php`)
Dodano:
```php
require_once THEME_DIR . '/inc/checkout-table-custom.php';
```

## Jak to działa

1. **Wyświetlanie**:
   - Hook: `woocommerce_checkout_before_order_review`
   - Iteruje po `WC()->cart->get_cart()`
   - Renderuje custom HTML z miniaturkami i sterownikami

2. **Interakcja**:
   - Kliknięcie +/- wysyła AJAX: `universal_update_cart_quantity`
   - Server aktualizuje koszyk
   - Checkout odświeża się za pośrednictwem `update_checkout`
   - Tabela zmienia się bez przeładowania strony

3. **Responsive**:
   - Desktop (>1024px): 3-kolumnowy grid
   - Tablet (768-1024px): Zmniejszone rozmiary
   - Mobile (<768px): Thumbnail po lewej, info poniżej

## Customizacja

### Zmiana szerokości kolumn
W CSS zmień `grid-template-columns`:
```css
.universal-checkout-item {
    grid-template-columns: 100px 1fr 180px; /* Zwiększ miniaturkę */
}
```

### Zmiana rozmiaru miniaturki
```css
.checkout-item-thumbnail {
    width: 100px;  /* zmień z 80px */
    height: 100px; /* zmień z 80px */
}
```

### Zmiana kolorów przycisków
```css
.checkout-item-quantity-controls .qty-btn {
    border-color: #your-color;
    background: #your-background;
}
```

## Debugowanie

Otwórz DevTools (F12) i sprawdź:
- Console: Powinny być logi z `🔄 plus/minus clicked`
- Network: AJAX request `universal_update_cart_quantity`
- Elements: struktura `.universal-checkout-item`
