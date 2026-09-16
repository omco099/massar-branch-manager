<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Product;

use Alnaseeg\BranchManager\Branch\BranchRepository;
use Alnaseeg\BranchManager\Branch\BranchResolver;

/**
 * Handles branch selection for product add-to-cart operations.
 */
final class ProductBranchManager
{
    public function __construct(
        private readonly BranchResolver $branchResolver,
        private readonly BranchRepository $branchRepository,
        private readonly ProductRepository $productRepository
    ) {
    }

    /**
     * Register WooCommerce hooks.
     */
    public function register(): void
    {
        /*
         * Render branch selector before the add-to-cart button.
         */
        add_action(
            'woocommerce_before_add_to_cart_button',
            [$this, 'renderSelector']
        );

        /*
         * Store the selected branch in the cart item.
         */
        add_filter(
            'woocommerce_add_cart_item_data',
            [$this, 'addCartItemBranch'],
            10,
            3
        );

        /*
         * Display the selected branch in the cart.
         */
        add_filter(
            'woocommerce_get_item_data',
            [$this, 'displayCartItemBranch'],
            10,
            2
        );
    }

    /**
     * Render the branch selector on the single product page.
     */
    public function renderSelector(): void
    {
        global $product;

        if (! $product instanceof \WC_Product) {
            return;
        }

        /*
         * A product opened from a branch page already
         * has a branch context.
         */
        if ($this->branchResolver->resolve() !== null) {
            return;
        }

        $productId = $product->is_type('variation')
            ? (int) $product->get_parent_id()
            : (int) $product->get_id();

        if ($productId <= 0) {
            return;
        }

        $branches = $this->availableBranches($productId);

        if ($branches === []) {
            return;
        }

        /*
         * If the product is available in exactly one branch,
         * select it automatically and do not display a selector.
         */
        if (count($branches) === 1) {
            $branchId = (int) array_key_first($branches);

            echo '<input type="hidden" name="wcbm_branch_id" value="' .
                esc_attr((string) $branchId) .
                '">';

            return;
        }

        $fieldId = 'wcbm-branch-selector';

        ?>
        <div class="wcbm-product-branch-selector">

            <label
                for="<?php echo esc_attr($fieldId); ?>"
            >
                <?php esc_html_e(
                    'Choose Branch',
                    'massar-branch-manager'
                ); ?>
            </label>

            <select
                id="<?php echo esc_attr($fieldId); ?>"
                name="wcbm_branch_id"
                required
            >

                <option value="">
                    <?php esc_html_e(
                        'Select a branch',
                        'massar-branch-manager'
                    ); ?>
                </option>

                <?php foreach ($branches as $branchId => $branchName) : ?>

                    <option
                        value="<?php echo esc_attr((string) $branchId); ?>"
                    >
                        <?php echo esc_html($branchName); ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>
        <?php
    }

    /**
     * Add the selected branch to the cart item.
     *
     * @param array<string,mixed> $cartItemData
     * @param int                 $productId
     * @param int                 $variationId
     *
     * @return array<string,mixed>
     */
    public function addCartItemBranch(
        array $cartItemData,
        int $productId,
        int $variationId
    ): array {

        $branchId = $this->resolveSelectedBranch(
            $productId,
            $variationId
        );

        if ($branchId === null) {
            return $cartItemData;
        }

        $cartItemData['wcbm_branch_id'] = $branchId;

        return $cartItemData;
    }

    /**
     * Display branch information in the cart.
     *
     * @param array<int,array<string,mixed>> $itemData
     * @param array<string,mixed>            $cartItem
     *
     * @return array<int,array<string,mixed>>
     */
    public function displayCartItemBranch(
        array $itemData,
        array $cartItem
    ): array {

        if (! isset($cartItem['wcbm_branch_id'])) {
            return $itemData;
        }

        $branchId = absint(
            $cartItem['wcbm_branch_id']
        );

        if ($branchId <= 0) {
            return $itemData;
        }

        $branch = $this->branchRepository->findById(
            $branchId
        );

        if ($branch === null) {
            return $itemData;
        }

        $itemData[] = [
            'key'   => __(
                'Branch',
                'massar-branch-manager'
            ),
            'value' => esc_html($branch->name()),
        ];

        return $itemData;
    }

    /**
     * Get branches where the product is enabled.
     *
     * @return array<int,string>
     */
    private function availableBranches(
        int $productId
    ): array {

        $data = $this->productRepository->findByProduct(
            $productId
        );

        if ($data === []) {
            return [];
        }

        $branches = [];

        foreach ($data as $branchId => $branchData) {

            if (empty($branchData['is_enabled'])) {
                continue;
            }

            $branch = $this->branchRepository->findById(
                (int) $branchId
            );

            if ($branch === null) {
                continue;
            }

            $branches[(int) $branchId] = $branch->name();
        }

        return $branches;
    }

    /**
     * Resolve the branch for the current add-to-cart request.
     */
    private function resolveSelectedBranch(
        int $productId,
        int $variationId
    ): ?int {

        /*
         * A branch page provides the branch automatically.
         */
        $branch = $this->branchResolver->resolve();

        if ($branch !== null) {
            return $branch->id();
        }

        /*
         * Otherwise the customer must select a branch.
         */
        if (! isset($_POST['wcbm_branch_id'])) {
            return null;
        }

        $branchId = absint(
            wp_unslash($_POST['wcbm_branch_id'])
        );

        if ($branchId <= 0) {
            return null;
        }

        /*
         * Validate that the selected branch actually
         * has this product enabled.
         */
        $resolvedProductId = $variationId > 0
            ? (int) wp_get_post_parent_id($variationId)
            : $productId;

        if ($resolvedProductId <= 0) {
            $resolvedProductId = $productId;
        }

        $branchData = $this->productRepository->findBranch(
            $resolvedProductId,
            $branchId
        );

        if (
            $branchData === null
            || empty($branchData['is_enabled'])
        ) {
            return null;
        }

        return $branchId;
    }
}