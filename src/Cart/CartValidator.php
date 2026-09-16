<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Cart;

use Alnaseeg\BranchManager\Branch\BranchResolver;
use Alnaseeg\BranchManager\Product\ProductRepository;

/**
 * Validates adding products to the cart
 * according to the selected branch.
 */
final class CartValidator
{
    public function __construct(
        private readonly BranchResolver $branchResolver,
        private readonly ProductRepository $productRepository
    ) {
    }

    /**
     * Register WooCommerce hooks.
     */
    public function register(): void
    {
        add_filter(
            'woocommerce_add_to_cart_validation',
            [$this, 'validateAddToCart'],
            10,
            5
        );
    }

    /**
     * Validate adding a product to the cart.
     *
     * @param array<string,mixed> $variations
     */
    public function validateAddToCart(
        bool $passed,
        int $productId,
        int $quantity,
        int $variationId = 0,
        array $variations = []
    ): bool {

        if (! $passed) {
            return false;
        }

        /*
         * Variable products use the parent product
         * for branch availability.
         *
         * WooCommerce remains responsible for
         * the selected variation.
         */
        $resolvedProductId = $variationId > 0
            ? (int) wp_get_post_parent_id($variationId)
            : $productId;

        if ($resolvedProductId <= 0) {
            $resolvedProductId = $productId;
        }

        /*
         * Resolve the branch for this add-to-cart
         * operation.
         *
         * A branch page already provides the branch
         * through BranchResolver.
         *
         * Shop / category / search will provide the
         * selected branch through ProductBranchManager.
         */
        $branchId = $this->resolveBranchId();

        /*
         * No branch means that the customer has not
         * selected a branch yet.
         */
        if ($branchId === null) {

            wc_add_notice(
                __(
                    'Please select a branch before adding this product to the cart.',
                    'massar-branch-manager'
                ),
                'error'
            );

            return false;
        }

        /*
         * The product must explicitly be enabled
         * for the selected branch.
         */
        $branchData = $this->productRepository->findBranch(
            $resolvedProductId,
            $branchId
        );

        if (
            $branchData === null
            || empty($branchData['is_enabled'])
        ) {

            wc_add_notice(
                __(
                    'This product is not available in the selected branch.',
                    'massar-branch-manager'
                ),
                'error'
            );

            return false;
        }

        /*
         * The cart may contain products from one
         * branch only.
         */
        if (! $this->cartAllowsBranch($branchId)) {

            wc_add_notice(
                __(
                    'You cannot add products from different branches to the same cart.',
                    'massar-branch-manager'
                ),
                'error'
            );

            return false;
        }

        return true;
    }

    /**
     * Resolve the branch used for the current
     * add-to-cart operation.
     */
    private function resolveBranchId(): ?int
    {
        /*
         * First use the branch resolved from the
         * current branch page.
         */
        $branch = $this->branchResolver->resolve();

        if ($branch !== null) {
            return $branch->id();
        }

        /*
         * When the customer is shopping outside
         * a branch page, ProductBranchManager will
         * provide the selected branch.
         */
        if (! isset($_POST['wcbm_branch_id'])) {
            return null;
        }

        $branchId = absint(
            wp_unslash($_POST['wcbm_branch_id'])
        );

        return $branchId > 0
            ? $branchId
            : null;
    }

    /**
     * Determine whether the current cart can accept
     * products from the selected branch.
     */
    private function cartAllowsBranch(
        int $branchId
    ): bool {

        if (
            ! function_exists('WC')
            || WC()->cart === null
            || WC()->cart->is_empty()
        ) {
            return true;
        }

        foreach (WC()->cart->get_cart() as $cartItem) {

            $cartBranchId = $this->getCartItemBranchId(
                $cartItem
            );

            if ($cartBranchId === null) {
                continue;
            }

            if ($cartBranchId !== $branchId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the branch ID stored in a cart item.
     *
     * @param array<string,mixed> $cartItem
     */
    private function getCartItemBranchId(
        array $cartItem
    ): ?int {

        if (! isset($cartItem['wcbm_branch_id'])) {
            return null;
        }

        $branchId = absint(
            $cartItem['wcbm_branch_id']
        );

        return $branchId > 0
            ? $branchId
            : null;
    }
}