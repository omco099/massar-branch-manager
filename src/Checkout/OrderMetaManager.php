<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Checkout;

use Alnaseeg\BranchManager\Branch\BranchRepository;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Handles branch information for WooCommerce orders.
 */
final class OrderMetaManager
{
    public function __construct(
        private readonly BranchRepository $branchRepository
    ) {
    }

    /**
     * Register hooks.
     */
    public function register(): void
    {
        /*
         * Save branch information to the order itself.
         */
        add_action(
            'woocommerce_checkout_create_order',
            [$this, 'saveBranchMeta'],
            10,
            2
        );

        /*
         * Save branch information to each order item.
         *
         * WooCommerce automatically displays this item meta
         * underneath the product inside the order.
         */
        add_action(
            'woocommerce_checkout_create_order_line_item',
            [$this, 'saveOrderItemBranch'],
            10,
            4
        );
    }

    /**
     * Save the cart branch into the order metadata.
     *
     * The cart is restricted to one branch only.
     */
    public function saveBranchMeta(
        WC_Order $order,
        array $_data
    ): void {

        if (! function_exists('WC')) {
            return;
        }

        $cart = WC()->cart;

        if ($cart === null || $cart->is_empty()) {
            return;
        }

        $branchId = $this->getCartBranchId();

        if ($branchId === null) {
            return;
        }

        $branch = $this->branchRepository->findById(
            $branchId
        );

        if ($branch === null) {
            return;
        }

        /*
         * Save branch information to the order.
         */
        $order->update_meta_data(
            '_wcbm_branch_id',
            $branch->id()
        );

        $order->update_meta_data(
            '_wcbm_branch_name',
            $branch->name()
        );

        $order->update_meta_data(
            '_wcbm_branch_slug',
            $branch->slug()
        );
    }

    /**
     * Save branch information to the order item.
     *
     * This is what makes the branch appear directly
     * underneath the product in WooCommerce order details.
     *
     * @param WC_Order_Item_Product $item
     * @param string                $cartItemKey
     * @param array<string,mixed>   $values
     * @param WC_Order              $order
     */
    public function saveOrderItemBranch(
        WC_Order_Item_Product $item,
        string $cartItemKey,
        array $values,
        WC_Order $order
    ): void {

        if (! isset($values['wcbm_branch_id'])) {
            return;
        }

        $branchId = absint(
            $values['wcbm_branch_id']
        );

        if ($branchId <= 0) {
            return;
        }

        $branch = $this->branchRepository->findById(
            $branchId
        );

        if ($branch === null) {
            return;
        }

        /*
         * Do NOT use an underscore here.
         *
         * WooCommerce hides underscore-prefixed metadata
         * from the normal order item meta display.
         */
        $item->add_meta_data(
            __(
                'Branch',
                'massar-branch-manager'
            ),
            $branch->name(),
            true
        );
    }

    /**
     * Get the branch ID from the current cart.
     *
     * All cart products are guaranteed to belong
     * to the same branch by the cart validation logic.
     */
    private function getCartBranchId(): ?int
    {
        if (! function_exists('WC')) {
            return null;
        }

        $cart = WC()->cart;

        if ($cart === null || $cart->is_empty()) {
            return null;
        }

        foreach ($cart->get_cart() as $cartItem) {

            if (! isset($cartItem['wcbm_branch_id'])) {
                continue;
            }

            $branchId = absint(
                $cartItem['wcbm_branch_id']
            );

            if ($branchId <= 0) {
                continue;
            }

            return $branchId;
        }

        return null;
    }
}