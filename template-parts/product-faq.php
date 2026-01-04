<?php

/**
 * Template Part: Product FAQ
 * Displays FAQ accordion on single product page
 * Content is pulled from Options Page
 */

$faq_items = get_field('faq', 'option');

if (!$faq_items || !is_array($faq_items) || empty($faq_items)) {
    return;
}
?>

<div class="product-faq-section mt-12 md:mt-20 text-black">
    <h2 class="text-2xl font-semibold mb-6 text-center">Produktowe FAQ</h2>

    <div class="faq-accordion space-y-4 max-w-3xl mx-auto">
        <?php foreach ($faq_items as $index => $item): ?>
            <?php if (!empty($item['title'])): ?>
                <div class="faq-item border-b border-gray-200 overflow-hidden">
                    <button
                        class="faq-question w-full text-left px-6 py-4 flex justify-between items-center hover:bg-transparent"
                        data-faq-index="<?php echo $index; ?>"
                        aria-expanded="false"
                        aria-controls="faq-answer-<?php echo $index; ?>">
                        <span class="font-medium text-base pr-4"><?php echo esc_html($item['title']); ?></span>
                        <svg class="faq-icon w-5 h-5 flex-shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div
                        id="faq-answer-<?php echo $index; ?>"
                        class="faq-answer overflow-hidden transition-all duration-300 max-h-0"
                        aria-hidden="true">
                        <div class="px-6 py-4 text-sm font-light">
                            <?php if (!empty($item['description'])): ?>
                                <div class="prose prose-sm max-w-none">
                                    <?php echo wp_kses_post($item['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const faqButtons = document.querySelectorAll('.faq-question');

        faqButtons.forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-faq-index');
                const answer = document.getElementById('faq-answer-' + index);
                const icon = this.querySelector('.faq-icon');
                const isExpanded = this.getAttribute('aria-expanded') === 'true';

                // Close all other FAQs
                faqButtons.forEach(otherButton => {
                    if (otherButton !== button) {
                        const otherIndex = otherButton.getAttribute('data-faq-index');
                        const otherAnswer = document.getElementById('faq-answer-' + otherIndex);
                        const otherIcon = otherButton.querySelector('.faq-icon');

                        otherButton.setAttribute('aria-expanded', 'false');
                        otherAnswer.setAttribute('aria-hidden', 'true');
                        otherAnswer.style.maxHeight = '0';
                        otherIcon.style.transform = 'rotate(0deg)';
                    }
                });

                // Toggle current FAQ
                if (isExpanded) {
                    this.setAttribute('aria-expanded', 'false');
                    answer.setAttribute('aria-hidden', 'true');
                    answer.style.maxHeight = '0';
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    this.setAttribute('aria-expanded', 'true');
                    answer.setAttribute('aria-hidden', 'false');
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    });
</script>

<style>
    .product-faq-section .prose p {
        margin-bottom: 0.5rem;
    }

    .product-faq-section .prose p:last-child {
        margin-bottom: 0;
    }

    .product-faq-section .prose ul,
    .product-faq-section .prose ol {
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .product-faq-section .prose a {
        color: #2563eb;
        text-decoration: underline;
    }

    .product-faq-section .prose strong {
        font-weight: 600;
    }
</style>