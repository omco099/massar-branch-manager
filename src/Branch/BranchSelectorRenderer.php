<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

final class BranchSelectorRenderer
{
    /**
     * Render the branch selector.
     *
     * @param Branch[] $branches
     */
    public function render(
        array $branches,
        ?int $currentBranchId = null
    ): string {
        if ($branches === []) {
            return '';
        }

        ob_start();
        ?>

        <form method="post" class="abm-branch-selector">

            <?php wp_nonce_field('abm_select_branch', 'abm_nonce'); ?>

            <select name="branch_id">

                <?php foreach ($branches as $branch) : ?>

                    <option
                        value="<?php echo esc_attr((string) $branch->id()); ?>"
                        <?php selected($currentBranchId, $branch->id()); ?>
                    >
                        <?php echo esc_html($branch->name()); ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button
                type="submit"
                name="abm_select_branch"
            >
                <?php esc_html_e('Select Branch', 'massar-branch-manager'); ?>
            </button>

        </form>

        <?php

        return (string) ob_get_clean();
    }
}