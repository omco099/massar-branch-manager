<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Product;

use wpdb;

/**
 * Handles persistence for product branch data.
 */
final class ProductRepository
{
    /**
     * Product branch table.
     */
    private string $table;

    public function __construct(
        private readonly wpdb $database
    ) {
        $this->table = $database->prefix . 'wcbm_product_branch';
    }

    /**
     * Get all branch data for a product.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByProduct(int $productId): array
    {
        $rows = $this->database->get_results(
            $this->database->prepare(
                "
                SELECT
                    branch_id,
                    is_enabled
                FROM {$this->table}
                WHERE product_id = %d
                ORDER BY branch_id ASC
                ",
                $productId
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return [];
        }

        $branches = [];

        foreach ($rows as $row) {

            $branches[(int) $row['branch_id']] = [
                'is_enabled' => (bool) $row['is_enabled'],
            ];
        }

        return $branches;
    }

    /**
     * Get branch data for a product.
     *
     * @return array<string,mixed>|null
     */
    public function findBranch(
        int $productId,
        int $branchId
    ): ?array {

        $row = $this->database->get_row(
            $this->database->prepare(
                "
                SELECT
                    is_enabled
                FROM {$this->table}
                WHERE product_id = %d
                  AND branch_id = %d
                LIMIT 1
                ",
                $productId,
                $branchId
            ),
            ARRAY_A
        );

        if ($row === null) {
            return null;
        }

        return [
            'is_enabled' => (bool) $row['is_enabled'],
        ];
    }

    /**
     * Get enabled product IDs for a branch.
     *
     * @return int[]
     */
    public function findProductsByBranch(
        int $branchId
    ): array {

        $productIds = $this->database->get_col(
            $this->database->prepare(
                "
                SELECT product_id
                FROM {$this->table}
                WHERE branch_id = %d
                  AND is_enabled = 1
                ORDER BY product_id ASC
                ",
                $branchId
            )
        );

        if (empty($productIds)) {
            return [];
        }

        return array_map(
            'intval',
            $productIds
        );
    }

    /**
     * Persist branch availability for a product.
     *
     * The submitted branches are treated as the complete
     * enabled-state for the product.
     *
     * A branch included in the request is enabled.
     * A branch not included in the request is disabled.
     *
     * Existing rows are never deleted.
     *
     * @param array<int,array<string,mixed>> $branches
     */
    public function save(
        int $productId,
        array $branches
    ): void {

        /*
         * The MVP currently has three fixed branches.
         *
         * We use the existing database rows when available.
         * Missing rows are created without touching any
         * unrelated product data.
         */
        $existing = $this->database->get_col(
            $this->database->prepare(
                "
                SELECT branch_id
                FROM {$this->table}
                WHERE product_id = %d
                ",
                $productId
            )
        );

        $existingIds = array_map(
            'intval',
            $existing
        );

        /*
         * Normalize submitted branch IDs.
         *
         * Example:
         *
         * [
         *     1 => ['is_enabled' => 1],
         *     2 => ['is_enabled' => 1],
         * ]
         *
         * means:
         *
         * branch 1 = enabled
         * branch 2 = enabled
         * every other existing branch = disabled
         */
        $enabledBranchIds = [];

        foreach ($branches as $branchId => $branch) {

            $branchId = (int) $branchId;

            if ($branchId <= 0) {
                continue;
            }

            if (! empty($branch['is_enabled'])) {
                $enabledBranchIds[] = $branchId;
            }
        }

        $enabledBranchIds = array_values(
            array_unique($enabledBranchIds)
        );

        /*
         * Update every existing branch row.
         *
         * This is important because unchecked checkboxes
         * are not submitted by the browser.
         *
         * Therefore:
         *
         * existing + submitted  = 1
         * existing + not submitted = 0
         */
        foreach ($existingIds as $branchId) {

            $this->updateEnabledState(
                $productId,
                $branchId,
                in_array(
                    $branchId,
                    $enabledBranchIds,
                    true
                )
            );
        }

        /*
         * Create rows for newly introduced branches.
         *
         * Normally the installer already creates all three
         * branch rows, but this keeps the repository safe if
         * a product is missing a branch row.
         */
        foreach ($enabledBranchIds as $branchId) {

            if (in_array(
                $branchId,
                $existingIds,
                true
            )) {
                continue;
            }

            $this->insert(
                $productId,
                $branchId,
                true
            );
        }
    }

    /**
     * Update only the enabled state of an existing branch.
     */
    private function updateEnabledState(
        int $productId,
        int $branchId,
        bool $enabled
    ): void {

        $this->database->update(
            $this->table,
            [
                'is_enabled' => $enabled ? 1 : 0,
                'updated_at' => current_time('mysql'),
            ],
            [
                'product_id' => $productId,
                'branch_id'  => $branchId,
            ],
            [
                '%d',
                '%s',
            ],
            [
                '%d',
                '%d',
            ]
        );
    }

    /**
     * Insert a missing branch row.
     *
     * Only branch availability is initialized here.
     * Existing product branch data is never overwritten.
     */
    private function insert(
        int $productId,
        int $branchId,
        bool $enabled
    ): void {

        $this->database->insert(
            $this->table,
            [
                'product_id' => $productId,
                'branch_id'  => $branchId,
                'is_enabled' => $enabled ? 1 : 0,
                'updated_at' => current_time('mysql'),
            ],
            [
                '%d',
                '%d',
                '%d',
                '%s',
            ]
        );
    }
}