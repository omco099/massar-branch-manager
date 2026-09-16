<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Admin\Pages;

use Alnaseeg\BranchManager\Branch\BranchRepository;
use wpdb;

/**
 * Renders the Branches admin page.
 */
final class BranchListPage
{
    /**
     * Render branches page.
     */
    public function render(): void
    {
        global $wpdb;

        /** @var wpdb $wpdb */
        $repository = new BranchRepository($wpdb);

        $branches = $repository->all();

        ?>
        <div class="wrap">

            <div style="margin: 20px 0 16px;">
                <img
                    src="<?php echo esc_url(MASSAR_BRANCH_MANAGER_PLUGIN_URL . 'assets/images/massar-logo.png'); ?>"
                    alt="Massar Branch Manager"
                    style="display:block;max-width:220px;height:auto;"
                >
            </div>


            <h1 class="wp-heading-inline">
                <?php esc_html_e(
                    'Branches',
                    'massar-branch-manager'
                ); ?>
            </h1>

            <hr class="wp-header-end">

            <p>
                <?php esc_html_e(
                    'These are the branches currently available in the store.',
                    'massar-branch-manager'
                ); ?>
            </p>

            <table class="widefat striped">

                <thead>

                <tr>

                    <th width="80">
                        <?php esc_html_e(
                            'ID',
                            'massar-branch-manager'
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Name',
                            'massar-branch-manager'
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Slug',
                            'massar-branch-manager'
                        ); ?>
                    </th>

                    <th width="120">
                        <?php esc_html_e(
                            'Status',
                            'massar-branch-manager'
                        ); ?>
                    </th>

                </tr>

                </thead>

                <tbody>

                <?php if ($branches === []) : ?>

                    <tr>

                        <td colspan="4">

                            <?php esc_html_e(
                                'No branches found.',
                                'massar-branch-manager'
                            ); ?>

                        </td>

                    </tr>

                <?php else : ?>

                    <?php foreach ($branches as $branch) : ?>

                        <tr>

                            <td>
                                <?php echo esc_html(
                                    (string) $branch->id()
                                ); ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo esc_html(
                                        $branch->name()
                                    ); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $branch->slug()
                                ); ?>
                            </td>

                            <td>

                                <?php if ($branch->status() === 'active') : ?>

                                    <span>
                                        <?php esc_html_e(
                                            'Active',
                                            'massar-branch-manager'
                                        ); ?>
                                    </span>

                                <?php else : ?>

                                    <span>
                                        <?php esc_html_e(
                                            'Inactive',
                                            'massar-branch-manager'
                                        ); ?>
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>
        <?php
    }
}