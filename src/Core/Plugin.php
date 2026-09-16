<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Core;

use wpdb;
use Alnaseeg\BranchManager\Admin\Menu;
use Alnaseeg\BranchManager\Product\ProductDataPanel;
use Alnaseeg\BranchManager\Product\ProductDataTab;
use Alnaseeg\BranchManager\Product\ProductSaver;

/**
 * Main plugin application.
 */
final class Plugin
{
    /**
     * Boot the plugin.
     */
    public function boot(): void
    {
        $this->registerModules();
        $this->registerHooks();
    }

    /**
     * Register plugin modules.
     */
    private function registerModules(): void
    {
        (new ProductDataTab())->register();

        (new ProductDataPanel())->register();

        (new ProductSaver())->register();
    }

    /**
     * Register WordPress hooks.
     */
    private function registerHooks(): void
    {
        add_action(
            'admin_menu',
            [
                new Menu(),
                'register',
            ]
        );

        /*
         * Register frontend assets.
         */
        add_action(
            'wp_enqueue_scripts',
            [$this, 'enqueueFrontendAssets']
        );

        global $wpdb;

        /** @var wpdb $wpdb */
        $services = new Services($wpdb);

        /*
         * Version 1:
         * Branch Selector is intentionally disabled.
         *
         * The current branch is resolved from the current
         * Elementor branch page using PageBranchResolver.
         */

        /*
         * Register branch products shortcode.
         */
        $services->branchProductsShortcode()->register();

        /*
         * Register product branch selector.
         */
        $services->productBranchManager()->register();

        /*
         * Register cart validation.
         */
        $services->cartValidator()->register();

        /*
         * Keep the cart synchronized with
         * the currently active branch.
         */
        $services->branchCartManager()->register();

        /*
         * Filter Shop, category archives and search
         * according to the currently active branch.
         */
        $services->branchCatalogFilter()->register();

        /*
         * Save branch information into the order.
         */
        $services->orderMetaManager()->register();
    }

    /**
     * Enqueue frontend slider assets.
     */
    public function enqueueFrontendAssets(): void
    {
        wp_enqueue_style(
            'abm-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            [],
            '11'
        );

        wp_enqueue_style(
            'abm-branch-products',
            MASSAR_BRANCH_MANAGER_PLUGIN_URL . 'assets/css/branch-products.css',
            [
                'abm-swiper',
            ],
            MASSAR_BRANCH_MANAGER_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'abm-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            [],
            '11',
            true
        );

        wp_enqueue_script(
            'abm-branch-products',
            MASSAR_BRANCH_MANAGER_PLUGIN_URL . 'assets/js/branch-products.js',
            [
                'abm-swiper',
            ],
            MASSAR_BRANCH_MANAGER_PLUGIN_VERSION,
            true
        );
    }
}