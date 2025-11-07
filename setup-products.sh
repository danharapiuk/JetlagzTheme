#!/bin/bash

# 🚀 SETUP SCRIPT - Konfiguracja produktów testowych
# 
# Ten skrypt kopiuje pliki potrzebne do utworzenia produktów testowych
# i pokazuje instrukcje użycia

echo "🛒 Universal Theme - Setup produktów testowych"
echo "============================================="

# Sprawdź czy jesteś w katalogu motywu
if [ ! -f "style.css" ] || [ ! -f "functions.php" ]; then
    echo "❌ BŁĄD: Uruchom ten skrypt z katalogu motywu (Jetlagz-theme)"
    echo "📍 Obecny katalog: $(pwd)"
    echo "📁 Przejdź do: /wp-content/themes/Jetlagz-theme"
    exit 1
fi

# Znajdź katalog główny WordPress
WP_ROOT=""
if [ -f "../../../../../wp-config.php" ]; then
    WP_ROOT="../../../../../"
elif [ -f "../../../../wp-config.php" ]; then
    WP_ROOT="../../../../"
elif [ -f "../../../wp-config.php" ]; then
    WP_ROOT="../../../"
elif [ -f "../../wp-config.php" ]; then
    WP_ROOT="../../"
elif [ -f "../wp-config.php" ]; then
    WP_ROOT="../"
fi

if [ -z "$WP_ROOT" ]; then
    echo "❌ BŁĄD: Nie znaleziono wp-config.php"
    echo "📁 Skopiuj pliki ręcznie do głównego katalogu WordPress"
    echo ""
    echo "📋 INSTRUKCJE RĘCZNE:"
    echo "1. Skopiuj add-sample-products.php do głównego katalogu WordPress"
    echo "2. Odwiedź: https://your-site.com/add-sample-products.php"
    echo "3. Kliknij 'Dodaj produkty przykładowe'"
    echo "4. Usuń plik po użyciu!"
    exit 1
fi

echo "✅ Znaleziono WordPress w: $(realpath $WP_ROOT)"
echo ""

# Kopiuj główny plik kreatora
echo "📁 Kopiowanie add-sample-products.php..."
cp add-sample-products.php "${WP_ROOT}add-sample-products.php"

if [ $? -eq 0 ]; then
    echo "✅ Plik skopiowany pomyślnie!"
else
    echo "❌ Błąd kopiowania pliku"
    exit 1
fi

# Sprawdź adres strony
SITE_URL=""
if [ -f "${WP_ROOT}wp-config.php" ]; then
    # Spróbuj wyciągnąć URL z wp-config
    SITE_URL=$(grep -o "https\?://[^'\"]*" "${WP_ROOT}wp-config.php" | head -1)
fi

echo ""
echo "🎉 GOTOWE! Teraz możesz dodać produkty:"
echo ""
echo "🌐 KROK 1: Odwiedź w przeglądarce:"
if [ -n "$SITE_URL" ]; then
    echo "   $SITE_URL/add-sample-products.php"
else
    echo "   https://your-site.com/add-sample-products.php"
fi
echo ""
echo "🖱️  KROK 2: Kliknij 'Dodaj produkty przykładowe'"
echo ""
echo "🗑️  KROK 3: Usuń plik po użyciu:"
echo "   rm ${WP_ROOT}add-sample-products.php"
echo ""
echo "🎯 TESTOWANIE:"
echo "   • One-click checkout"
echo "   • Cross-sell na checkout"
echo "   • Free shipping progress (próg: 100 PLN)"
echo "   • Responsive layout"
echo ""
echo "📚 Więcej info: zobacz PRODUCTS-README.md"
echo ""

# Pokaż alternatywne metody
echo "🔧 ALTERNATYWNE METODY:"
echo ""
echo "📊 SQL Import:"
echo "   mysql -u username -p database < sample-products.sql"
echo ""
echo "⚡ Quick Creator:"
echo "   cp quick-products.php ${WP_ROOT}quick-products.php"
echo "   # Odwiedź: /quick-products.php"
echo ""
echo "✋ Panel WordPress:"
echo "   WooCommerce → Status → Narzędzia → Utwórz dane przykładowe"
echo ""

echo "⚠️  BEZPIECZEŃSTWO: Pamiętaj usunąć pliki PHP po dodaniu produktów!"
echo ""
echo "Happy testing! 🚀"