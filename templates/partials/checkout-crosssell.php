<?php
// Partial: templates/partials/checkout-crosssell.php
// Expects $products (array of product arrays with keys: id, name, price_formatted, image, permalink)
if (empty($products) || !is_array($products)) {
    return;
}
?>
<div class="checkout-crosssell-wrapper">
    <h3 class="crosssell-title"><?php echo esc_html__('Może Cię zainteresować', 'universal-theme'); ?></h3>
    <div class="checkout-crosssell-grid">
        <?php foreach ($products as $p): ?>
            <?php
            $rating_count = isset($p['rating_count']) ? (int) $p['rating_count'] : 0;
            $average_rating = isset($p['average_rating']) ? (float) $p['average_rating'] : 0;
            $rating_display = $average_rating == 5.0
                ? '5/5'
                : number_format($average_rating, 1, '.', '') . '/5';
            $full_title = isset($p['name']) ? (string) $p['name'] : '';
            $product_title = isset($p['acf_name']) ? trim((string) $p['acf_name']) : '';
            $product_subtitle = isset($p['acf_description']) ? trim((string) $p['acf_description']) : '';

            // Fallback: jeśli ACF jest puste, wyciągnij title/subtitle z nazwy produktu.
            if ($product_title === '') {
                $product_title = $full_title;
                foreach (array(' - ', ' – ', '–', '-') as $separator) {
                    if (strpos($full_title, $separator) !== false) {
                        $title_parts = explode($separator, $full_title, 2);
                        $product_title = trim($title_parts[0]);
                        if ($product_subtitle === '' && isset($title_parts[1])) {
                            $product_subtitle = trim($title_parts[1]);
                        }
                        break;
                    }
                }
            }
            ?>
            <div class="product-card">
                <a href="<?php echo esc_url($p['permalink']); ?>" class="product-link">
                    <div class="product-image-wrapper">
                        <img
                            class="product-image-primary"
                            src="<?php echo esc_url($p['image']); ?>"
                            alt="<?php echo esc_attr($p['name']); ?>">
                        <?php if (!empty($p['secondary_image'])): ?>
                            <img
                                class="product-image-secondary"
                                src="<?php echo esc_url($p['secondary_image']); ?>"
                                alt="<?php echo esc_attr($p['name']); ?>">
                        <?php endif; ?>
                        <?php if (!empty($p['is_on_sale'])): ?>
                            <span class="sale-badge">Sale</span>
                        <?php endif; ?>
                    </div>
                </a>

                <a href="<?php echo esc_url($p['permalink']); ?>" class="product-link product-info-link">
                    <div class="product-info-row">
                        <div class="product-title-wrapper">
                            <h3 class="product-title">
                                <?php echo esc_html($product_title); ?>
                            </h3>
                            <?php if ($product_subtitle !== ''): ?>
                                <p class="slider-product-subtitle"><?php echo esc_html($product_subtitle); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="product-price">
                            <?php echo wp_kses_post($p['price_formatted']); ?>
                        </div>
                    </div>

                    <?php if ($rating_count > 0): ?>
                        <div class="stars">
                            <span class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= round($average_rating)): ?>
                                        <span class="star filled">★</span>
                                    <?php else: ?>
                                        <span class="star empty">★</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </span>
                            <span class="rating-number"><?php echo esc_html($rating_display); ?></span>
                            <span class="rating-count">(<?php echo esc_html((string) $rating_count); ?>)</span>
                        </div>
                    <?php endif; ?>
                </a>

                <div class="product-actions">
                    <?php if (!empty($p['has_variants'])): ?>
                        <a href="<?php echo esc_url($p['permalink']); ?>" class="button crosssell-add-btn" data-product-id="<?php echo esc_attr($p['id']); ?>">
                            <span class="btn-icon">→</span>
                            <span class="btn-text"><?php echo esc_html__('Wybierz wariant', 'universal-theme'); ?></span>
                        </a>
                    <?php else: ?>
                        <button class="button crosssell-add-btn" data-product-id="<?php echo esc_attr($p['id']); ?>" data-product-name="<?php echo esc_attr($p['name']); ?>" data-product-price="<?php echo esc_attr(strip_tags($p['price_formatted'])); ?>">
                            <span class="btn-icon">+</span>
                            <span class="btn-text"><?php echo esc_html__('Dodaj', 'universal-theme'); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>