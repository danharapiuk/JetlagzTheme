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
            <div class="product-card">
                <a href="<?php echo esc_url($p['permalink']); ?>" class="product-link">
                    <div class="product-image-wrapper">
                        <img
                            class="product-image-primary"
                            src="<?php echo esc_url($p['image']); ?>"
                            alt="<?php echo esc_attr($p['name']); ?>">
                    </div>

                    <div class="product-info-row">
                        <h3 class="product-title">
                            <?php
                            // Wyświetl tylko część nazwy do myślnika
                            $full_title = $p['name'];

                            // Szukaj " - " (myślnik ze spacjami)
                            if (strpos($full_title, ' - ') !== false) {
                                $title_parts = explode(' - ', $full_title, 2);
                                echo esc_html(trim($title_parts[0]));
                            }
                            // Szukaj " – " (długi myślnik ze spacjami)
                            elseif (strpos($full_title, ' – ') !== false) {
                                $title_parts = explode(' – ', $full_title, 2);
                                echo esc_html(trim($title_parts[0]));
                            }
                            // Szukaj samego "–" (długi myślnik)
                            elseif (strpos($full_title, '–') !== false) {
                                $title_parts = explode('–', $full_title, 2);
                                echo esc_html(trim($title_parts[0]));
                            }
                            // Szukaj samego "-" (krótki myślnik)
                            elseif (strpos($full_title, '-') !== false) {
                                $title_parts = explode('-', $full_title, 2);
                                echo esc_html(trim($title_parts[0]));
                            } else {
                                echo esc_html($full_title);
                            }
                            ?>
                        </h3>

                        <div class="product-price">
                            <?php echo wp_kses_post($p['price_formatted']); ?>
                        </div>
                    </div>
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