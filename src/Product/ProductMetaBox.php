<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Product;

use WP_Post;

/**
 * Product Branch Pricing Meta Box.
 */
final class ProductMetaBox
{
    /**
     * Register WordPress hooks.
     */
    public function register(): void
    {
        add_action(
            'add_meta_boxes',
            [$this, 'addMetaBox']
        );
    }

    /**
     * Register the meta box.
     */
    public function addMetaBox(): void
    {
        add_meta_box(
            'wcbm-branch-pricing',
            __('Branch Pricing & Stock', 'massar-branch-manager'),
            [$this, 'render'],
            'product',
            'normal',
            'default'
        );
    }

    /**
     * Render the meta box.
     */
    public function render(WP_Post $post): void
    {
        wp_nonce_field(
            'wcbm_save_product_branches',
            'wcbm_branch_nonce'
        );

        $branches = [
            1 => 'مول عمان',
            2 => 'فرع العريمي',
            3 => 'فرع نزوى',
        ];

        ?>
        <div class="wcbm-product-branches">

            <?php foreach ($branches as $branchId => $branchName) : ?>

                <div class="postbox" style="margin-bottom:20px;padding:15px;">

                    <h3 style="margin-top:0;">
                        <?php echo esc_html($branchName); ?>
                    </h3>

                    <table class="form-table" role="presentation">

                        <tr>

                            <th scope="row">
                                <label>
                                    <?php esc_html_e('Regular Price', 'massar-branch-manager'); ?>
                                </label>
                            </th>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="regular-text"
                                    name="wcbm_branch[<?php echo esc_attr((string) $branchId); ?>][regular_price]"
                                    value=""
                                >
                            </td>

                        </tr>

                        <tr>

                            <th scope="row">
                                <label>
                                    <?php esc_html_e('Sale Price', 'massar-branch-manager'); ?>
                                </label>
                            </th>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="regular-text"
                                    name="wcbm_branch[<?php echo esc_attr((string) $branchId); ?>][sale_price]"
                                    value=""
                                >
                            </td>

                        </tr>

                        <tr>

                            <th scope="row">
                                <label>
                                    <?php esc_html_e('Stock Quantity', 'massar-branch-manager'); ?>
                                </label>
                            </th>

                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    class="small-text"
                                    name="wcbm_branch[<?php echo esc_attr((string) $branchId); ?>][stock_quantity]"
                                    value=""
                                >
                            </td>

                        </tr>

                        <tr>

                            <th scope="row">
                                <label>
                                    <?php esc_html_e('Enable Branch', 'massar-branch-manager'); ?>
                                </label>
                            </th>

                            <td>

                                <label>

                                    <input
                                        type="checkbox"
                                        name="wcbm_branch[<?php echo esc_attr((string) $branchId); ?>][enabled]"
                                        value="1"
                                    >

                                    <?php esc_html_e('Enabled', 'massar-branch-manager'); ?>

                                </label>

                            </td>

                        </tr>

                    </table>

                </div>

            <?php endforeach; ?>

        </div>

        <?php
    }
}