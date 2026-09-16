<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Product;

use Alnaseeg\BranchManager\Branch\BranchRepository;
use WP_Post;

/**
 * Registers the Branch Data panel inside WooCommerce Product Data.
 */
final class ProductDataPanel
{
    /**
     * Register WordPress hooks.
     */
    public function register(): void
    {
        add_action(
            'woocommerce_product_data_panels',
            [$this, 'render']
        );
    }

    /**
     * Render the Branch Data panel.
     */
    public function render(): void
    {
        global $wpdb, $post;

        if (! $post instanceof WP_Post) {
            return;
        }

        $branchRepository = new BranchRepository($wpdb);

        $productRepository = new ProductRepository($wpdb);

        $productFields = new ProductFields();

        $branches = $branchRepository->all();

        $productData = $productRepository->findByProduct(
            (int) $post->ID
        );

        ?>

        <div
            id="wcbm_branch_data_panel"
            class="panel woocommerce_options_panel hidden"
        >

            <?php

            wp_nonce_field(
                'wcbm_save_product_branches',
                'wcbm_branch_nonce'
            );

            $productFields->render(
                $branches,
                $productData
            );

            ?>

        </div>

        <?php
    }
}