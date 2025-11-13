<?php
// Partial: templates/partials/checkout-crosssell.php
// Expects $products (array of product arrays with keys: id, name, price_formatted, image, permalink)
if (empty($products) || !is_array($products)) {
    return;
}
?>
<div class="universal-crosssell-wrapper">
    <h3 class="crosssell-title"><?php echo esc_html__('You may also like', 'universal-theme'); ?></h3>
    <div class="checkout-crosssell-section">
        <div class="universal-crosssell-items">
            <?php foreach ($products as $p): ?>
                <div class="crosssell-product-item" data-product-id="<?php echo esc_attr($p['id']); ?>">
                    <a class="crosssell-thumb" href="<?php echo esc_url($p['permalink']); ?>">
                        <img src="<?php echo esc_url($p['image']); ?>" alt="<?php echo esc_attr($p['name']); ?>" />
                    </a>
                    <div class="crosssell-meta">
                        <a class="crosssell-name" href="<?php echo esc_url($p['permalink']); ?>"><?php echo esc_html($p['name']); ?></a>
                        <div class="crosssell-price"><?php echo wp_kses_post($p['price_formatted']); ?></div>
                        <?php if (!empty($p['has_variants'])): ?>
                            <a href="<?php echo esc_url($p['permalink']); ?>" class="button crosssell-add-btn" data-product-id="<?php echo esc_attr($p['id']); ?>">
                                <span class="btn-icon">→</span>
                                <span class="btn-text"><?php echo esc_html__('Choose variant', 'universal-theme'); ?></span>
                            </a>
                        <?php else: ?>
                            <button class="button crosssell-add-btn" data-product-id="<?php echo esc_attr($p['id']); ?>" data-product-name="<?php echo esc_attr($p['name']); ?>" data-product-price="<?php echo esc_attr(strip_tags($p['price_formatted'])); ?>">
                                <span class="btn-icon">+</span>
                                <span class="btn-text"><?php echo esc_html__('Add', 'universal-theme'); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>