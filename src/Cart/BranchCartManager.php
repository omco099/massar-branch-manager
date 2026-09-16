<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Cart;

/**
 * Keeps WooCommerce cart branch data consistent.
 */
final class BranchCartManager
{
    /**
     * Prevent duplicate synchronization
     * during the same request.
     */
    private bool $synchronizing = false;

    /**
     * Register hooks.
     */
    public function register(): void
    {
        add_action(
            'woocommerce_check_cart_items',
            [$this, 'synchronizeCart'],
            1
        );
    }

    /**
     * Synchronize cart branch data.
     *
     * A cart may contain products from one branch only.
     */
    public function synchronizeCart(): void
    {
        if ($this->synchronizing) {
            return;
        }

        if (
            is_admin()
            && ! wp_doing_ajax()
        ) {
            return;
        }

        if (! function_exists('WC')) {
            return;
        }

        $cart = WC()->cart;

        if ($cart === null || $cart->is_empty()) {
            return;
        }

        $this->synchronizing = true;

        try {

            $cartBranchId = null;

            foreach ($cart->get_cart() as $cartItemKey => $cartItem) {

                $itemBranchId = $this->getCartItemBranchId(
                    $cartItem
                );

                /*
                 * A cart item without a branch cannot
                 * participate in branch validation.
                 *
                 * CartValidator / ProductBranchManager
                 * should prevent this for new items.
                 */
                if ($itemBranchId === null) {
                    continue;
                }

                /*
                 * First valid branch becomes the cart branch.
                 */
                if ($cartBranchId === null) {
                    $cartBranchId = $itemBranchId;
                    continue;
                }

                /*
                 * Never allow different branches
                 * to remain in the same cart.
                 */
                if ($itemBranchId === $cartBranchId) {
                    continue;
                }

                $cart->remove_cart_item(
                    $cartItemKey
                );
            }

        } finally {

            $this->synchronizing = false;
        }
    }

    /**
     * Get the branch ID stored on a cart item.
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