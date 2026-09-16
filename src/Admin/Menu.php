<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Admin;

use Alnaseeg\BranchManager\Admin\Pages\BranchListPage;

/**
 * Registers the Branch Manager admin menu.
 */
final class Menu
{
    /**
     * Register plugin admin menu.
     */
    public function register(): void
    {
        add_menu_page(
            __('Branches', 'massar-branch-manager'),
            __('Branches', 'massar-branch-manager'),
            'manage_options',
            'wcbm-branches',
            [
                new BranchListPage(),
                'render',
            ],
            'dashicons-store',
            56
        );
    }
}