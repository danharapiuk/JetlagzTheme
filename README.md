# Universal Storefront Theme

Uniwersalny motyw potomny Storefront do szybkiego wdrażania w różnych sklepach WooCommerce.

## 🚀 Funkcje

### 🎯 Perfect for:
- **Mass deployment** z jednego repozytorium
- **Hardcoded approach** - wszystko w kodzie, bez panelu WP
- **Developer-friendly** - pełna kontrola nad kodem
- **Consistent branding** z łatwą zmianą przez config
- **Version control** - zmiany trackowane w Git
- **Responsywny design** - Działa na wszystkich urządzeniach
- **Optymalizacja WooCommerce** - Dedykowane style i funkcje dla sklepu
- **Szybkość ładowania** - Zoptymalizowane CSS i JS
- **SEO friendly** - Semantyczny HTML i meta tagi
- **Preloader** - Opcjonalny preloader ze spinnerem
- **Quick View** - Szybki podgląd produktów
- **Menu mobilne** - Responsywne menu z animacjami

## 📁 Struktura plików

```
themes/universal-theme/
├── style.css                  # Główny plik stylów
├── functions.php             # Główne funkcje motywu
├── screenshot.png            # Zrzut ekranu motywu
├── assets/
│   ├── css/
│   │   └── custom.css       # Dodatkowe style
│   └── js/
│       └── theme.js         # JavaScript motywu
├── inc/
│   ├── theme-config.php     # Konfiguracja motywu
│   ├── customizer.php       # Ustawienia customizer
│   ├── woocommerce-functions.php # Funkcje WooCommerce
│   └── theme-functions.php  # Ogólne funkcje
├── template-parts/          # Części szablonów
└── woocommerce/            # Nadpisania szablonów WooCommerce
```

## ⚙️ Wdrożenie w nowym sklepie

### 1. Zainstaluj motyw
```bash
git clone https://github.com/danharapiuk/woocommerce-starter.git
# Skopiuj do /wp-content/themes/ w swoim WordPressie
```

### 2. Aktywuj motyw w WordPress
**Wygląd > Motywy > Aktywuj "Universal Storefront Theme"**

### 3. Personalizacja przez kod
Edytuj kolory, czcionki i ustawienia w pliku:
**`inc/theme-config.php`**
```php
'colors' => array(
    'primary' => '#twój-kolor-główny',
    'secondary' => '#twój-kolor-drugorzędny', 
    'accent' => '#twój-kolor-akcji',
    // ...
),
```

### 4. Gotowe!
Jeden motyw bazowy + edycja konfiguracji = unikalny sklep

## 🎨 Dostosowywanie kolorów

### Przez konfigurację (zalecane)
Edytuj plik **`inc/theme-config.php`**:
```php
'colors' => array(
    'primary' => '#twój-kolor',
    'secondary' => '#twój-drugi-kolor',
    'accent' => '#twój-kolor-akcji',
),
```

### Przez CSS (dla zaawansowanych)
Edytuj zmienne CSS w `assets/css/custom.css`:
```css
:root {
    --theme-primary: #twoj-kolor;
    --theme-secondary: #twoj-drugi-kolor;
    --theme-accent: #twoj-kolor-akcji;
}
```

## 📱 Responsywność

Motyw jest w pełni responsywny z breakpointami:
- Desktop: 1200px+
- Tablet: 768px - 1199px
- Mobile: 480px - 767px
- Small Mobile: <480px

## 🛍️ Funkcje WooCommerce

### Dostępne opcje:
- Liczba produktów na stronę
- Liczba produktów w rzędzie
- Zoom galerii produktów
- Lightbox galerii
- Quick View produktów

### Customization w WooCommerce:
- Style listy produktów
- Animacje przycisków
- Hover effects na zdjęciach
- Optymalizacja koszyka

## 🚀 Optymalizacja

### Ładowanie zasobów:
- Google Fonts z preconnect
- Lazy loading obrazów
- Debounced scroll events
- Minifikacja CSS/JS (przez plugin)

### SEO:
- Meta tagi theme-color
- Semantyczny HTML
- Breadcrumbs
- Title tag support

## 🔧 Rozszerzanie motywu

### Dodawanie nowych funkcji:
1. Dodaj funkcję w odpowiednim pliku w `/inc/`
2. Załącz plik w `functions.php`
3. Dodaj style w `assets/css/custom.css`
4. Dodaj JS w `assets/js/theme.js`

### Hook'i WordPress:
Motyw używa standardowych hook'ów WordPress:
- `wp_enqueue_scripts`
- `after_setup_theme`
- `customize_register`
- `body_class`

## 📋 Checklist wdrożenia

### Przed kopiowaniem:
- [ ] Zmień informacje w `style.css`
- [ ] Dostosuj kolory w `theme-config.php`
- [ ] Dodaj screenshot.png
- [ ] Sprawdź czy wszystkie pliki są na miejscu

### Po instalacji:
- [ ] Aktywuj motyw
- [ ] Ustaw kolory w customizer
- [ ] Wgraj logo
- [ ] Skonfiguruj WooCommerce
- [ ] Przetestuj na różnych urządzeniach

## 🐛 Debugowanie

### Częste problemy:
1. **Kolory się nie zmieniają** - Sprawdź czy cache jest wyczyszczony
2. **JS nie działa** - Sprawdź czy jQuery jest załadowane
3. **Style nie działają** - Sprawdź kolejność ładowania CSS

### Logi:
Włącz debug w WordPress:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🔄 Aktualizacje

### Przed aktualizacją:
1. Zrób backup motywu
2. Sprawdź czy customization nie zostaną utracone
3. Przetestuj na staging

### Po aktualizacji:
1. Sprawdź czy wszystkie funkcje działają
2. Wyczyść cache
3. Przetestuj customizer

## 📞 Wsparcie

W przypadku problemów:
1. Sprawdź dokumentację WordPress/WooCommerce
2. Przeszukaj logi błędów
3. Sprawdź compatibility z pluginami

## 📄 Licencja

GPL v2 lub nowsza - zgodnie z licencją WordPress

---

**🎯 Blueprint Strategy:** Jeden motyw bazowy + WordPress Customizer = nieskończone możliwości personalizacji! 

**Pamiętaj:** Zawsze testuj zmiany na środowisku testowym przed wdrożeniem na produkcji!# woocommerce-starter
# JetlagzTheme
