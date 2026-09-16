<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Product;

use Alnaseeg\BranchManager\Branch\Branch;

/**
 * Renders branch product fields.
 */
final class ProductFields
{
    /**
     * Render branch fields.
     *
     * @param Branch[] $branches
     * @param array<int,array<string,mixed>> $productData
     */
    public function render(
        array $branches,
        array $productData
    ): void {

        foreach ($branches as $branch) {

            $branchId = $branch->id();

            $isEnabled = ! empty(
                $productData[$branchId]['is_enabled']
            );

            ?>

            <details class="wcbm-branch-card" open>

                <summary style="padding:12px 16px;font-weight:600;cursor:pointer;">
                    <?php echo esc_html($branch->name()); ?>
                </summary>

                <div class="options_group">

                    <p class="form-field">

                        <label>
                            <?php esc_html_e(
                                'Available In This Branch',
                                'massar-branch-manager'
                            ); ?>
                        </label>

                        <!--
                         * Always submit a value for every branch.
                         * Unchecked = 0
                         * Checked   = 1
                         -->
                        <input
                            type="hidden"
                            name="wcbm_branch[<?php echo esc_attr((string) $branchId); ?>][is_enabled]"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            value="1"
                            name="wcbm_branch[<?php echo esc_attr((string) $branchId); ?>][is_enabled]"
                            <?php checked($isEnabled); ?>
                        >

                    </p>

                </div>

            </details>

            <?php
        }
    }
}