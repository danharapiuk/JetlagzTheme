#!/bin/bash

# Skrypt do szybkiego tworzenia nowego motywu ze wzorca
# Użycie: ./create-new-theme.sh "nazwa-sklepu" "Nazwa Sklepu"

if [ $# -ne 2 ]; then
    echo "Użycie: $0 nazwa-sklepu 'Nazwa Sklepu'"
    echo "Przykład: $0 sklep-odziezowy 'Sklep Odzieżowy'"
    exit 1
fi

THEME_SLUG=$1
THEME_NAME=$2
SOURCE_DIR="."
TARGET_DIR="../${THEME_SLUG}-theme"

echo "Tworzenie nowego motywu: $THEME_NAME"
echo "Katalog docelowy: $TARGET_DIR"

# Kopiowanie plików
cp -r "$SOURCE_DIR" "$TARGET_DIR"

# Usunięcie niepotrzebnych plików
rm -f "$TARGET_DIR/.DS_Store"
rm -f "$TARGET_DIR/create-new-theme.sh"
rm -rf "$TARGET_DIR/.git"

# Aktualizacja style.css
sed -i '' "s/Universal Storefront Theme/${THEME_NAME} Theme/g" "$TARGET_DIR/style.css"
sed -i '' "s/Uniwersalny motyw potomny Storefront/Motyw dla ${THEME_NAME}/g" "$TARGET_DIR/style.css"
sed -i '' "s/universal-theme/${THEME_SLUG}/g" "$TARGET_DIR/style.css"

# Aktualizacja functions.php
sed -i '' "s/Universal Storefront Child Theme/${THEME_NAME} Theme/g" "$TARGET_DIR/functions.php"

# Aktualizacja pozostałych plików PHP
find "$TARGET_DIR" -name "*.php" -exec sed -i '' "s/universal-theme/${THEME_SLUG}/g" {} \;

# Aktualizacja plików CSS i JS
find "$TARGET_DIR" -name "*.css" -exec sed -i '' "s/universal-theme/${THEME_SLUG}/g" {} \;
find "$TARGET_DIR" -name "*.js" -exec sed -i '' "s/universal-theme/${THEME_SLUG}/g" {} \;

echo "✅ Motyw $THEME_NAME został utworzony w katalogu $TARGET_DIR"
echo ""
echo "Następne kroki:"
echo "1. Skopiuj katalog do /wp-content/themes/"
echo "2. Aktywuj motyw w WordPress"
echo "3. Dostosuj kolory w Customizer"
echo "4. Wgraj logo i favicon"
echo "5. Skonfiguruj WooCommerce"