<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Product;

/**
 * Registers the Branch Data tab inside WooCommerce Product Data.
 */
final class ProductDataTab
{
    /**
     * Register WordPress hooks.
     */
    public function register(): void
    {
        add_filter(
            'woocommerce_product_data_tabs',
            [$this, 'addTab']
        );
    }

    /**
     * Add the Branch Data tab.
     *
     * @param array<string,mixed> $tabs
     *
     * @return array<string,mixed>
     */
    public function addTab(array $tabs): array
    {
        $tabs['wcbm_branch_data'] = [
            'label'    => __(
                'Branch Data',
                'massar-branch-manager'
            ),
            'target'   => 'wcbm_branch_data_panel',
            'class'    => [],
            'priority' => 80,
        ];

        return $tabs;
    }
}