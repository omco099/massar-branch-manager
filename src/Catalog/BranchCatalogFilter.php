<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Catalog;

use WP_Query;

/**
 * Filters WooCommerce catalog pages
 * according to the current branch.
 */
final class BranchCatalogFilter
{
    public function __construct(
        private readonly BranchCatalogService $catalog
    ) {
    }

    /**
     * Register hooks.
     */
    public function register(): void
    {
        add_action(
            'pre_get_posts',
            [$this, 'filterMainQuery'],
            20
        );
    }

    /**
     * Filter the main frontend catalog query.
     */
    public function filterMainQuery(
        WP_Query $query
    ): void {

        /*
         * Never modify admin queries.
         */
        if (is_admin()) {
            return;
        }

        /*
         * Only the main frontend query.
         */
        if (! $query->is_main_query()) {
            return;
        }

        /*
         * Ignore single product pages.
         */
        if (is_product()) {
            return;
        }

        /*
         * Only WooCommerce catalog pages.
         */
        if (! $this->isCatalogQuery($query)) {
            return;
        }

        $this->applyFilter($query);
    }

    /**
     * Determine whether the current query
     * belongs to the WooCommerce catalog.
     */
    private function isCatalogQuery(
        WP_Query $query
    ): bool {

        if (
            $query->is_post_type_archive('product')
        ) {
            return true;
        }

        if (
            $query->is_tax([
                'product_cat',
                'product_tag',
            ])
        ) {
            return true;
        }

        if (! $query->is_search()) {
            return false;
        }

        $postType = $query->get('post_type');

        return $postType === 'product'
            || (
                is_array($postType)
                && in_array(
                    'product',
                    $postType,
                    true
                )
            );
    }

    /**
     * Restrict catalog products
     * to the current branch.
     */
    private function applyFilter(
        WP_Query $query
    ): void {

        if (! $this->catalog->hasBranch()) {
            return;
        }

        $productIds = $this->catalog->queryProductIds();

        $existingIds = $query->get('post__in');

        if (
            is_array($existingIds)
            && $existingIds !== []
        ) {

            $productIds = array_values(
                array_intersect(
                    array_map(
                        'intval',
                        $existingIds
                    ),
                    $productIds
                )
            );

            if ($productIds === []) {
                $productIds = [0];
            }
        }

        $query->set(
            'post__in',
            $productIds
        );
    }
}