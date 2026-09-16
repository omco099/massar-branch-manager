<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Shortcodes;

use Alnaseeg\BranchManager\Catalog\BranchCatalogService;
use WP_Query;

/**
 * Handles the branch products shortcode.
 */
final class BranchProductsShortcode
{
    /**
     * Create a new shortcode instance.
     */
    public function __construct(
        private readonly BranchCatalogService $catalog
    ) {
    }

    /**
     * Register the shortcode.
     */
    public function register(): void
    {
        add_shortcode(
            'branch_products',
            [$this, 'render']
        );
    }

    /**
     * Render the branch products slider.
     *
     * @param array<string,mixed> $attributes
     */
    public function render(
        array $attributes = [],
        ?string $content = null,
        string $tag = ''
    ): string {

        if (! function_exists('WC')) {
            return '';
        }

        if (! $this->catalog->hasBranch()) {
            return '';
        }

        $productIds = $this->catalog->queryProductIds();

        if ($productIds === [] || $productIds === [0]) {
            return '';
        }

        $query = new WP_Query([
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'post__in'            => $productIds,
            'posts_per_page'      => -1,
            'orderby'             => 'post__in',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ]);

        if (! $query->have_posts()) {
            return '';
        }

        ob_start();
        ?>

        <div
            class="abm-branch-products"
            data-branch-id="<?php echo esc_attr((string) $this->catalog->branchId()); ?>"
        >

            <div class="swiper abm-products-swiper">

                <div class="swiper-wrapper">

                    <?php while ($query->have_posts()) : $query->the_post(); ?>

                        <div class="swiper-slide">

                            <ul class="products columns-1">

                                <?php
                                wc_get_template_part(
                                    'content',
                                    'product'
                                );
                                ?>

                            </ul>

                        </div>

                    <?php endwhile; ?>

                </div>

            </div>

            <button
                type="button"
                class="swiper-button-prev"
                aria-label="<?php echo esc_attr__(
                    'Previous products',
                    'massar-branch-manager'
                ); ?>"
            ></button>

            <button
                type="button"
                class="swiper-button-next"
                aria-label="<?php echo esc_attr__(
                    'Next products',
                    'massar-branch-manager'
                ); ?>"
            ></button>

            <div class="swiper-pagination"></div>

        </div>

        <?php

        wp_reset_postdata();

        return (string) ob_get_clean();
    }
}